<?php // phpcs:disable Squiz.Commenting.FunctionComment.InvalidNoReturn


declare(strict_types=1);

namespace LaminasTest\Router\TestAsset;

use Laminas\Router\RouteInterface;
use Laminas\Router\RouteStackInterface;
use Laminas\Stdlib\RequestInterface as Request;

/**
 * @template TRoute of RouteInterface
 * @template-implements RouteStackInterface<TRoute>
 */
class Router implements RouteStackInterface
{
    /**
     * Create a new route with given options.
     *
     * @param iterable $options
     * @return self
     */
    public static function factory($options = [])
    {
        return new static();
    }

    /**
     * Match a given request.
     *
     * @return RouteMatch|null
     */
    public function match(Request $request)
    {
    }

    /**
     * Assemble the route.
     *
     * @param  array $params
     * @param  array $options
     * @return mixed
     */
    public function assemble(array $params = [], array $options = [])
    {
    }

    /** @inheritDoc */
    public function addRoute($name, $route, $priority = null)
    {
        return $this;
    }

    /** @inheritDoc */
    public function addRoutes($routes)
    {
        return $this;
    }

    /** @inheritDoc */
    public function removeRoute($name)
    {
        return $this;
    }

    /** @inheritDoc */
    public function setRoutes($routes)
    {
        return $this;
    }
}
