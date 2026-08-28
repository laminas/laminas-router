<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\RouteBuilderInterface;
use Laminas\Router\RouteInterface;

/**
 * @template TRoute of HttpRouteInterface
 * @implements RouteBuilderInterface<Part<TRoute>>
 */
final readonly class PartBuilder implements RouteBuilderInterface
{
    public function build(array $options = []): RouteInterface
    {
        return Part::factory($options);
    }
}
