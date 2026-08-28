<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

/**
 * @internal
 *
 * @psalm-internal \Laminas\Router
 * @psalm-internal \LaminasTest\Router
 */
final readonly class ChainBuilderFactory
{
    public function __invoke(): ChainBuilder
    {
        return new ChainBuilder();
    }
}
