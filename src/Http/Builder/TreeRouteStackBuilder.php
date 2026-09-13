<?php

declare(strict_types=1);

namespace Laminas\Router\Http\Builder;

use Laminas\Router\Http\HttpRouteInterface;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\RouteBuilderContainerInterface;
use Laminas\Router\RouteBuilderInterface;

/**
 * @template TRoute of HttpRouteInterface
 * @implements RouteBuilderInterface<TreeRouteStack<TRoute>>
 */
final readonly class TreeRouteStackBuilder implements RouteBuilderInterface
{
    public function __construct(
        private RouteBuilderContainerInterface $container,
    ) {
    }

    /** @inheritDoc */
    public function build(array $options): TreeRouteStack
    {
        /** @psalm-var array<non-empty-string|array-key, array|TRoute> $routes */
        $routes = $options['routes'] ?? [];
        /** @psalm-var array<string, string|int|float|null> $defaultParams */
        $defaultParams = $options['default_params'] ?? [];
        /** @psalm-var int|null $priority */
        $priority = $options['priority'] ?? null;

        return new TreeRouteStack(
            $this->container,
            $routes,
            $defaultParams,
            $priority,
        );
    }
}
