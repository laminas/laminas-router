<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

/**
 * @internal
 *
 * @psalm-internal \Laminas\Router
 * @psalm-internal \LaminasTest\Router
 */
final readonly class PlaceholderBuilderFactory
{
    public function __invoke(): PlaceholderBuilder
    {
        return new PlaceholderBuilder();
    }
}
