<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\RouteBuilderInterface;

/**
 * @implements RouteBuilderInterface<Hostname>
 */
final readonly class HostnameBuilder implements RouteBuilderInterface
{
    /** @inheritDoc */
    public function build(array $options): Hostname
    {
        return Hostname::factory($options);
    }
}
