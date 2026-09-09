<?php

declare(strict_types=1);

namespace Laminas\Router\Http\Builder;

use Laminas\Router\Http\Segment;
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
