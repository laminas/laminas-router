<?php

declare(strict_types=1);

namespace Laminas\Router\Http\Builder;

use Laminas\Router\Exception;
use Laminas\Router\Http\Chain;
use Laminas\Router\Http\HttpRouteInterface;
use Laminas\Router\RouteBuilderContainerInterface;
use Laminas\Router\RouteBuilderInterface;

use function is_array;

/**
 * @template TRoute of HttpRouteInterface
 * @implements RouteBuilderInterface<Chain<TRoute>>
 */
final readonly class ChainBuilder implements RouteBuilderInterface
{
    public function __construct(
        private RouteBuilderContainerInterface $container,
    ) {
    }

    /** @inheritDoc */
    public function build(array $options): Chain
    {
        if (! isset($options['routes']) || ! is_array($options['routes'])) {
            throw new Exception\InvalidArgumentException('Missing "routes" in options array');
        }

        /** @psalm-var array<non-empty-string, array|TRoute> $routes */
        $routes = $options['routes'];
        /** @psalm-var int|null $priority */
        $priority = $options['priority'] ?? null;
        /** @psalm-var array<string, string|int|float|null> $defaults */
        $defaults = $options['defaults'] ?? [];

        return new Chain(
            $this->container,
            $routes,
            $defaults,
            $priority,
        );
    }
}
