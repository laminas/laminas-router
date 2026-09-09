<?php

declare(strict_types=1);

namespace Laminas\Router\Builder;

use Laminas\Router\RouteBuilderContainerInterface;
use Psr\Container\ContainerInterface;

use function assert;

/**
 * @internal
 *
 * @psalm-internal \Laminas\Router
 * @psalm-internal \LaminasTest\Router
 */
final readonly class SimpleRouteStackBuilderFactory
{
    public function __invoke(ContainerInterface $container): SimpleRouteStackBuilder
    {
        $routeBuilder = $container->get(RouteBuilderContainerInterface::class);

        assert($routeBuilder instanceof RouteBuilderContainerInterface);

        return new SimpleRouteStackBuilder($routeBuilder);
    }
}
