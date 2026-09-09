<?php

declare(strict_types=1);

namespace Laminas\Router\Builder;

use Laminas\Router\RouteBuilderContainerInterface;
use Psr\Container\ContainerInterface;

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
        return new SimpleRouteStackBuilder($container->get(RouteBuilderContainerInterface::class));
    }
}
