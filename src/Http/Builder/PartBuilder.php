<?php

declare(strict_types=1);

namespace Laminas\Router\Http\Builder;

use Laminas\Router\Exception;
use Laminas\Router\Http\HttpRouteInterface;
use Laminas\Router\Http\Part;
use Laminas\Router\RouteBuilderContainerInterface;
use Laminas\Router\RouteBuilderInterface;

use function assert;
use function is_array;
use function is_bool;
use function is_int;

/**
 * @template TRoute of HttpRouteInterface
 * @implements RouteBuilderInterface<Part<TRoute>>
 */
final readonly class PartBuilder implements RouteBuilderInterface
{
    public function __construct(
        private RouteBuilderContainerInterface $container,
    ) {
    }

    /** @inheritDoc */
    public function build(array $options): Part
    {
        $routes       = $options['route'] ?? null;
        $mayTerminate = $options['may_terminate'] ?? false;
        /** @var array<non-empty-string, TRoute> $childRoutes */
        $childRoutes = $options['child_routes'] ?? [];
        /** @psalm-var array<string, string|int|float|null> $defaults */
        $defaults = $options['defaults'] ?? [];

        if ($routes === null) {
            throw new Exception\InvalidArgumentException('Missing "route" in options array');
        }

        assert(is_bool($mayTerminate));
        assert(is_array($routes) || $routes instanceof HttpRouteInterface);

        /** @psalm-var int|null $priority */
        $priority = $options['priority'] ?? null;

        /** @psalm-var TRoute|array $routes */

        return new Part(
            $this->container,
            $routes,
            $defaults,
            is_int($priority) ? $priority : null,
            $mayTerminate,
            $childRoutes,
        );
    }
}
