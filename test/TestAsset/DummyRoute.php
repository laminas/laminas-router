<?php

declare(strict_types=1);

namespace LaminasTest\Router\TestAsset;

use Laminas\Router\RouteInterface;
use Laminas\Router\RouteMatch;
use Laminas\Stdlib\RequestInterface;
use Traversable;

/**
 * Dummy route.
 */
class DummyRoute implements RouteInterface
{
    /**
     * match(): defined by RouteInterface interface.
     *
     * @see    Route::match()
     *
     * @return RouteMatch
     */
    public function match(RequestInterface $request)
    {
        return new RouteMatch([]);
    }

    /**
     * assemble(): defined by RouteInterface interface.
     *
     * @see    Route::assemble()
     *
     * @param  array $params
     * @param  array $options
     * @return mixed
     */
    public function assemble(?array $params = null, ?array $options = null)
    {
        return '';
    }

    /**
     * factory(): defined by RouteInterface interface
     *
     * @param array|Traversable $options
     * @return DummyRoute
     */
    public static function factory($options = [])
    {
        return new static();
    }
}
