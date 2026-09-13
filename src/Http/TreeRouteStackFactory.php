<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\RouteBuilderContainerInterface;
use Psr\Container\ContainerInterface;

use function assert;

/**
 * @internal
 *
 * @psalm-internal LaminasTest\Router
 */
final readonly class TreeRouteStackFactory
{
    public function __invoke(ContainerInterface $container): TreeRouteStack
    {
        /** @var RouteBuilderContainerInterface $routeBuilderContainer */
        $routeBuilderContainer = $container->get(RouteBuilderContainerInterface::class);

        assert($routeBuilderContainer instanceof RouteBuilderContainerInterface);

        return new TreeRouteStack($routeBuilderContainer);
    }
}
