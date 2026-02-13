<?php

declare(strict_types=1);

namespace Laminas\Router\Container;

use Laminas\Router\RouteInterface;

/**
 * Route container for simple route storage.
 *
 * @extends AbstractRouteContainer<RouteInterface>
 */
final class SimpleRouteContainer extends AbstractRouteContainer implements SimpleRouteContainerInterface
{
    public function insert(string $key, RouteInterface $value, ?int $priority = null): void
    {
        $this->setRoute($key, $value, $priority);
    }

    public function get(string $key): ?RouteInterface
    {
        return $this->getRoute($key);
    }

    public function current(): RouteInterface
    {
        return $this->getCurrentRoute();
    }
}
