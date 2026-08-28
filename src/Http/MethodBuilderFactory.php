<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

/**
 * @internal
 *
 * @psalm-internal \Laminas\Router
 * @psalm-internal \LaminasTest\Router
 */
final readonly class MethodBuilderFactory
{
    public function __invoke(): MethodBuilder
    {
        return new MethodBuilder();
    }
}
