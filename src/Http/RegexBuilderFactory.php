<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

/**
 * @internal
 *
 * @psalm-internal \Laminas\Router
 * @psalm-internal \LaminasTest\Router
 */
final readonly class RegexBuilderFactory
{
    public function __invoke(): RegexBuilder
    {
        return new RegexBuilder();
    }
}
