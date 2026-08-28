<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\RouteBuilderInterface;
use Laminas\Router\RouteInterface;

/**
 * @implements RouteBuilderInterface<Hostname>
 */
final readonly class HostnameBuilder implements RouteBuilderInterface
{
    public function build(array $options = []): RouteInterface
    {
        return Hostname::factory($options);
    }
}
