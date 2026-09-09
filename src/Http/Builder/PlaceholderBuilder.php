<?php

declare(strict_types=1);

namespace Laminas\Router\Http\Builder;

use Laminas\Router\Http\Placeholder;
use Laminas\Router\RouteBuilderInterface;

/**
 * @implements RouteBuilderInterface<Placeholder>
 */
final readonly class PlaceholderBuilder implements RouteBuilderInterface
{
    /** @inheritDoc */
    public function build(array $options): Placeholder
    {
        return Placeholder::factory($options);
    }
}
