<?php

declare(strict_types=1);

namespace Laminas\Router\Builder;

use Laminas\Router\RouteBuilderContainerInterface;
use Laminas\Router\RouteBuilderInterface;
use Laminas\Router\RouteInterface;
use Laminas\Router\SimpleRouteStack;

/**
 * @template TRoute of RouteInterface
 * @implements RouteBuilderInterface<SimpleRouteStack<TRoute>>
 */
final readonly class SimpleRouteStackBuilder implements RouteBuilderInterface
{
    public function __construct(
        private RouteBuilderContainerInterface $container,
    ) {
    }

    /** @inheritDoc */
    public function build(array $options): SimpleRouteStack
    {
        /** @psalm-var array<non-empty-string|array-key, array|TRoute> $routes */
        $routes = $options['routes'] ?? [];
        /** @psalm-var array<string, string|int|float|null> $defaultParams */
        $defaultParams = $options['default_params'] ?? [];

        return new SimpleRouteStack(
            $this->container,
            $routes,
            $defaultParams,
        );
    }
}
