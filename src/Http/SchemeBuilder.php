<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\RouteBuilderInterface;

/**
 * @implements RouteBuilderInterface<Scheme>
 */
final readonly class SchemeBuilder implements RouteBuilderInterface
{
    /** @inheritDoc */
    public function build(array $options): Scheme
    {
        return Scheme::factory($options);
    }
}
