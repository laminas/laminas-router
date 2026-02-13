<?php

declare(strict_types=1);

namespace Laminas\Router\Container;

use Laminas\Router\Http\HttpRouteInterface;
use Laminas\Router\RouteInterface as BaseRouteInterface;

/**
 * Route container for HTTP route storage.
 *
 * @extends AbstractRouteContainer<HttpRouteInterface>
 */
final class HttpRouteContainer extends AbstractRouteContainer implements HttpRouteContainerInterface
{
    public function insert(string $key, BaseRouteInterface $value, ?int $priority = null): void
    {
        $this->setRoute($key, $value, $priority);
    }

    public function get(string $key): ?HttpRouteInterface
    {
        return $this->getRoute($key);
    }

    public function current(): HttpRouteInterface
    {
        return $this->getCurrentRoute();
    }
}
