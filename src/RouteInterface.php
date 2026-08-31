<?php

declare(strict_types=1);

namespace Laminas\Router;

use Laminas\Stdlib\RequestInterface;

/**
 * RouteInterface interface.
 */
interface RouteInterface
{
    /**
     * Priority used for route stacks.
     *
     * @var int
     * public $priority;
     */

    /**
     * Create a new route with given options.
     *
     * @deprecated since 3.20.0, use RouteBuilderContainer::build() instead
     *
     * @param iterable $options
     * @return RouteInterface
     */
    public static function factory($options = []);

    /**
     * Match a given request.
     *
     * @return RouteMatch|null
     */
    public function match(RequestInterface $request);

    /**
     * Assemble the route.
     *
     * @return mixed
     */
    public function assemble(array $params = [], array $options = []);
}
