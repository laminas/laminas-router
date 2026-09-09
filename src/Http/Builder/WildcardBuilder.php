<?php

declare(strict_types=1);

namespace Laminas\Router\Http\Builder;

use Laminas\Router\Http\HttpRouteInterface;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\Http\Wildcard;
use Laminas\Router\RouteBuilderInterface;

/**
 * @deprecated since version 2.3.
 *  Misuse of this route type can lead to potential security issues.
 *  Use the `Segment` route type instead.
 *
 * @template TRoute of HttpRouteInterface
 * @implements RouteBuilderInterface<TreeRouteStack<TRoute>>
 */
final readonly class WildcardBuilder implements RouteBuilderInterface
{
    /** @inheritDoc */
    public function build(array $options): Wildcard
    {
        return Wildcard::factory($options);
    }
}
