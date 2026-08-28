<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\RouteBuilderInterface;
use Laminas\Router\RouteInterface;

/**
 * @implements RouteBuilderInterface<Segment>
 */
final readonly class SegmentBuilder implements RouteBuilderInterface
{
    public function build(array $options = []): RouteInterface
    {
        return Segment::factory($options);
    }
}
