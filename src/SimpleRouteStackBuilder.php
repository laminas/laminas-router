<?php

declare(strict_types=1);

namespace Laminas\Router;

/**
 * @template TRoute of RouteInterface
 * @implements RouteBuilderInterface<SimpleRouteStack<TRoute>>
 */
final readonly class SimpleRouteStackBuilder implements RouteBuilderInterface
{
    public function build(array $options = []): RouteInterface
    {
        return SimpleRouteStack::factory($options);
    }
}
