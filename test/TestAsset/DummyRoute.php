<?php

declare(strict_types=1);

namespace LaminasTest\Router\TestAsset;

use Laminas\Router\RouteInterface;
use Laminas\Router\RouteMatch;
use Laminas\Router\RoutePriorityTrait;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Dummy route.
 */
class DummyRoute implements RouteInterface
{
    /**
     * @internal
     * @deprecated Since 3.9.0 This property will be removed or made private in version 4.0
     */
    public ?int $priority = null;

    /**
     * match(): defined by RouteInterface interface.
     *
     * @see    Route::match()
     */
    public function match(ServerRequestInterface $request): ?RouteMatch
    {
        return new RouteMatch([]);
    }

    /**
     * assemble(): defined by RouteInterface interface.
     *
     * @see    Route::assemble()
     */
    public function assemble(?array $params = null, ?array $options = null): string
    {
        return '';
    }

    /**
     * factory(): defined by RouteInterface interface
     *
     * @return DummyRoute
     */
    public static function factory(iterable $options = []): RouteInterface
    {
        return new static();
    }
}
