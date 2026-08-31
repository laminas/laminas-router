<?php

declare(strict_types=1);

namespace Laminas\Router;

use Laminas\Router\Exception\RuntimeException;
use Psr\Container\ContainerInterface;

use function is_array;

/**
 * @internal
 *
 * @psalm-internal \Laminas\Router
 * @psalm-internal \LaminasTest\Router
 */
final readonly class RouteBuilderContainerFactory
{
    public function __invoke(ContainerInterface $container): RouteBuilderContainer
    {
        $config = $container->has('config') ? $container->get('config') : [];
        if (! is_array($config)) {
            throw new RuntimeException('Config service must return an array');
        }

        $builderMap = $config['router']['route_builders'] ?? RouteBuilderContainer::defaultBuilderMap();

        if (! is_array($builderMap)) {
            throw new RuntimeException(
                'Config key "router.route_builders" must be an array of type => builder service id'
            );
        }

        /** @var array<string, string> $typedBuilderMap */
        $typedBuilderMap = $builderMap;

        return new RouteBuilderContainer($container, $typedBuilderMap);
    }
}
