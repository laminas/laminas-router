<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\RouteBuilderInterface;
use Laminas\Router\RouteInterface;

/**
 * @implements RouteBuilderInterface<Placeholder>
 */
final readonly class PlaceholderBuilder implements RouteBuilderInterface
{
    public function build(array $options = []): RouteInterface
    {
        return Placeholder::factory($options);
    }
}
