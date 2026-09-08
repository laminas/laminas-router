<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\RouteBuilderInterface;

/**
 * @implements RouteBuilderInterface<Segment>
 */
final readonly class SegmentBuilder implements RouteBuilderInterface
{
    /** @inheritDoc */
    public function build(array $options): Segment
    {
        return Segment::factory($options);
    }
}
