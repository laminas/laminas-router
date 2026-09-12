<?php

declare(strict_types=1);

namespace Laminas\Router;

use Psr\Container\ContainerInterface;

use function assert;

/**
 * @internal
 *
 * @psalm-internal LaminasTest\Router
 */
final readonly class SimpleRouteStackFactory
{
    public function __invoke(ContainerInterface $container): SimpleRouteStack
    {
        $routeBuilderContainer = $container->get(RouteBuilderContainerInterface::class);

        assert($routeBuilderContainer instanceof RouteBuilderContainerInterface);

        return new SimpleRouteStack($routeBuilderContainer);
    }
}
