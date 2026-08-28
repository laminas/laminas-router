<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\RouteBuilderInterface;
use Laminas\Router\RouteInterface;

/**
 * @implements RouteBuilderInterface<Scheme>
 */
final readonly class SchemeBuilder implements RouteBuilderInterface
{
    public function build(array $options = []): RouteInterface
    {
        return Scheme::factory($options);
    }
}
