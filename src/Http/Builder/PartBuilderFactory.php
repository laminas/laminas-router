<?php

declare(strict_types=1);

namespace Laminas\Router\Http\Builder;

use Laminas\Router\RouteBuilderContainerInterface;
use Psr\Container\ContainerInterface;

use function assert;

/**
 * @internal
 *
 * @psalm-internal \Laminas\Router
 * @psalm-internal \LaminasTest\Router
 */
final readonly class PartBuilderFactory
{
    public function __invoke(ContainerInterface $container): PartBuilder
    {
        /** @var RouteBuilderContainerInterface $routeBuilder */
        $routeBuilder = $container->get(RouteBuilderContainerInterface::class);

        assert($routeBuilder instanceof RouteBuilderContainerInterface);

        return new PartBuilder($routeBuilder);
    }
}
