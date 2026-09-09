<?php

declare(strict_types=1);

namespace Laminas\Router\Http\Builder;

use Laminas\Router\Http\Hostname;
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
