<?php // phpcs:disable Squiz.Commenting.FunctionComment.InvalidNoReturn


declare(strict_types=1);

namespace LaminasTest\Router\TestAsset;

use Laminas\Router\RouteInterface;
use Laminas\Router\RouteMatch;
use Laminas\Router\RoutePriorityTrait;
use Laminas\Router\RouteStackInterface;
use Psr\Http\Message\ServerRequestInterface;

final class Router implements RouteStackInterface
{
    use RoutePriorityTrait;

    /**
     * Create a new route with given options.
     *
     * @return self
     */
    public static function factory(iterable $options = []): RouteInterface
    {
        return new Router();
    }

    /**
     * Match a given request.
     */
    public function match(ServerRequestInterface $request): ?RouteMatch
    {
    }

    /**
     * Assemble the route.
     */
    public function assemble(array $params = [], array $options = []): mixed
    {
    }

    /** @inheritDoc */
    public function addRoute($name, $route, $priority = null): RouteInterface
    {
        return $this;
    }

    /** @inheritDoc */
    public function addRoutes($routes): RouteStackInterface
    {
        return $this;
    }

    /** @inheritDoc */
    public function removeRoute($name): RouteStackInterface
    {
        return $this;
    }

    /** @inheritDoc */
    public function setRoutes($routes): RouteStackInterface
    {
        return $this;
    }
}
