<?php

declare(strict_types=1);

namespace Laminas\Router;

use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @psalm-internal \Laminas\Router
 * @psalm-internal \LaminasTest\Router
 */
final readonly class RouteBuilderContainerFactory
{
    public function __invoke(ContainerInterface $container): RouteBuilderContainerInterface
    {
        /** @var RouterConfig $config */
        $config = $container->get(RouterConfig::class);

        return new RouteBuilderContainer($container, $config->routeBuilders);
    }
}
