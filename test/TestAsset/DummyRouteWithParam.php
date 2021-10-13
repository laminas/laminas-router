<?php

declare(strict_types=1);

namespace LaminasTest\Router\TestAsset;

use Laminas\Router\RouteMatch;
use Laminas\Stdlib\RequestInterface;

/**
 * Dummy route.
 */
class DummyRouteWithParam extends DummyRoute
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
        return new RouteMatch(['foo' => 'bar']);
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
        if (isset($params['foo'])) {
            return $params['foo'];
        }

        return '';
    }
}
