<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http\TestAsset;

use Laminas\Router\Http\RouteInterface;
use Laminas\Router\Http\RouteMatch;
use Laminas\Stdlib\RequestInterface;

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
     * @param  int $pathOffset
     * @return RouteMatch
     */
    public function match(RequestInterface $request, $pathOffset = null)
    {
        return new RouteMatch(['offset' => $pathOffset], -4);
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
     * @param  iterable $options
     * @return DummyRoute
     */
    public static function factory($options = [])
    {
        return new static();
    }

    /**
     * getAssembledParams(): defined by RouteInterface interface.
     *
     * @see    Route::getAssembledParams
     *
     * @return array
     */
    public function getAssembledParams()
    {
        return [];
    }
}
