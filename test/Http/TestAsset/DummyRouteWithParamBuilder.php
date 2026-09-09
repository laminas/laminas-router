<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http\TestAsset;

use Laminas\Router\Exception\InvalidArgumentException;
use Laminas\Router\RouteBuilderInterface;

use function is_string;

/**
 * @implements RouteBuilderInterface<DummyRouteWithParam>
 */
final readonly class DummyRouteWithParamBuilder implements RouteBuilderInterface
{
    public function build(array $options): DummyRouteWithParam
    {
        $name = $options['name'] ?? null;
        if (! is_string($name)) {
            throw new InvalidArgumentException('Missing "name" in options array');
        }

        return new DummyRouteWithParam($name);
    }
}
