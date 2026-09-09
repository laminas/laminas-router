<?php

declare(strict_types=1);

namespace Laminas\Router;

use Psr\Container\ContainerInterface;

use function assert;

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
        $config = $container->get(RouterConfig::class);

        assert($config instanceof RouterConfig);

        return new RouteBuilderContainer($container, $config->routeBuilders);
    }
}
