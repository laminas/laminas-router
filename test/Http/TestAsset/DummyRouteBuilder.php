<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http\TestAsset;

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
        $name = $options['name'] ?? null;
        if (! is_string($name)) {
            throw new InvalidArgumentException('Missing "name" in options array');
        }

        return new DummyRoute($name);
    }
}
