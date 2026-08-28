<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

/**
 * @internal
 *
 * @psalm-internal \Laminas\Router
 * @psalm-internal \LaminasTest\Router
 */
final readonly class WildcardBuilderFactory
{
    public function __invoke(): WildcardBuilder
    {
        return new WildcardBuilder();
    }
}
