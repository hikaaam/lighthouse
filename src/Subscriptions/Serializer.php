<?php

namespace Nuwave\Lighthouse\Subscriptions;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Nuwave\Lighthouse\Subscriptions\Contracts\ContextSerializer;
use Nuwave\Lighthouse\Support\Contracts\CreatesContext;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Serializer implements ContextSerializer
{
    /**
     * @var \Nuwave\Lighthouse\Support\Contracts\CreatesContext
     */
    protected $createsContext;

    public function __construct(CreatesContext $createsContext)
    {
        $this->createsContext = $createsContext;
    }

    /**
     * Serialize the context.
     */
    public function serialize(GraphQLContext $context): string
    {
        $request = $context->request();

        return serialize([
            'request' => [
                'query' => $request->query->all(),
                'request' => $request->request->all(),
                'attributes' => $request->attributes->all(),
                'cookies' => [],
                'files' => [],
                'server' => Arr::except($request->server->all(), ['HTTP_AUTHORIZATION']),
                'content' => $request->getContent(),
            ],
            'user' => serialize($context->user()),
        ]);
    }

    /**
     * Unserialize the context.
     */
    public function unserialize(string $context): GraphQLContext
    {
        [
            'request' => $rawRequest,
            'user' => $rawUser
        ] = unserialize($context);

        $request = new Request(
            $rawRequest['query'],
            $rawRequest['request'],
            $rawRequest['attributes'],
            $rawRequest['cookies'],
            $rawRequest['files'],
            $rawRequest['server'],
            $rawRequest['content']
        );

        $request->setUserResolver(
            function () use ($rawUser) {
                return unserialize($rawUser);
            }
        );

        return $this->createsContext->generate($request);
    }
}
