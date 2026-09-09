<?php

declare(strict_types=1);

namespace Laminas\Router\Http\Builder;

use Laminas\Router\RouteBuilderContainerInterface;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @psalm-internal \Laminas\Router
 * @psalm-internal \LaminasTest\Router
 */
final readonly class TreeRouteStackBuilderFactory
{
    public function __invoke(ContainerInterface $container): TreeRouteStackBuilder
    {
        return new TreeRouteStackBuilder($container->get(RouteBuilderContainerInterface::class));
    }
}
