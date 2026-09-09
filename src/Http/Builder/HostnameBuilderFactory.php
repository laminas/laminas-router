<?php

declare(strict_types=1);

namespace Laminas\Router\Http\Builder;

/**
 * @internal
 *
 * @psalm-internal \Laminas\Router
 * @psalm-internal \LaminasTest\Router
 */
final readonly class HostnameBuilderFactory
{
    public function __invoke(): HostnameBuilder
    {
        return new HostnameBuilder();
    }
}
