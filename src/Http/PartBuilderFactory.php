<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

/**
 * @internal
 *
 * @psalm-internal \Laminas\Router
 * @psalm-internal \LaminasTest\Router
 */
final readonly class PartBuilderFactory
{
    public function __invoke(): PartBuilder
    {
        return new PartBuilder();
    }
}
