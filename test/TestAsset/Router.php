<?php // phpcs:disable Squiz.Commenting.FunctionComment.InvalidNoReturn

declare(strict_types=1);

namespace LaminasTest\Router\TestAsset;

use Laminas\Router\RouteStackInterface;
use Laminas\Stdlib\RequestInterface as Request;
use Traversable;

class Router implements RouteStackInterface
{
    /**
     * Create a new route with given options.
     *
     * @param array|Traversable $options
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

    /**
     * Add a route to the stack.
     *
     * @param  string $name
     * @param  mixed  $route
     * @param  int    $priority
     * @return RouteStackInterface
     */
    public function addRoute($name, $route, $priority = null)
    {
    }

    /**
     * Add multiple routes to the stack.
     *
     * @param array|Traversable $routes
     * @return RouteStackInterface
     */
    public function addRoutes($routes)
    {
    }

    /**
     * Remove a route from the stack.
     *
     * @param  string $name
     * @return RouteStackInterface
     */
    public function removeRoute($name)
    {
    }

    /**
     * Remove all routes from the stack and set new ones.
     *
     * @param array|Traversable $routes
     * @return RouteStackInterface
     */
    public function setRoutes($routes)
    {
    }
}
