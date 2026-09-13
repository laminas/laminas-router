<?php

declare(strict_types=1);

namespace LaminasTest\Router\TestAsset;

use Laminas\Router\Exception\InvalidArgumentException;
use Laminas\Router\RouteBuilderInterface;

use function is_string;

/**
 * @implements RouteBuilderInterface<DummyRoute>
 */
final readonly class DummyRouteBuilder implements RouteBuilderInterface
{
    public function build(array $options): DummyRoute
    {
        /** @var mixed $name */
        $name = $options['name'] ?? null;
        if (! is_string($name)) {
            throw new InvalidArgumentException('Missing "name" in options array');
        }

        /** @psalm-var int|null $priority */
        $priority = $options['priority'] ?? null;
        /** @psalm-var array<string, string|int|float|null> $defaults */
        $defaults = $options['defaults'] ?? [];

        return new DummyRoute($name, $priority, $defaults);
    }
}
