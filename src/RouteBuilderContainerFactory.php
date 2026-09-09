<?php

declare(strict_types=1);

namespace Laminas\Router;

use Laminas\Router\Exception\RuntimeException;
use Laminas\Router\RouteBuilderContainerInterface;
use Psr\Container\ContainerInterface;
use Traversable;

use function is_array;
use function iterator_to_array;

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
        $config = $container->has('config') ? $container->get('config') : [];

        if ($config instanceof Traversable) {
            $config = iterator_to_array($config);
        }

        if (! is_array($config)) {
            throw new RuntimeException('Config service must return an array or Traversable');
        }

        $builderMap = $config['router']['route_builders'] ?? RouteBuilderContainer::defaultBuilderMap();

        if (! is_array($builderMap)) {
            throw new RuntimeException(
                'Config key "router.route_builders" must be an array of type => builder service id'
            );
        }

        /** @var array<string, class-string<RouteBuilderInterface>> $typedBuilderMap */
        $typedBuilderMap = $builderMap;

        return new RouteBuilderContainer($container, $typedBuilderMap);
    }
}
