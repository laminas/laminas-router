<?php

declare(strict_types=1);

namespace Laminas\Router\Http\Builder;

use Laminas\Router\Http\HttpRouteInterface;
use Laminas\Router\Http\Part;
use Laminas\Router\RouteBuilderInterface;

/**
 * @template TRoute of HttpRouteInterface
 * @implements RouteBuilderInterface<Part<TRoute>>
 */
final readonly class PartBuilder implements RouteBuilderInterface
{
    /** @inheritDoc */
    public function build(array $options): Part
    {
        return Part::factory($options);
    }
}
