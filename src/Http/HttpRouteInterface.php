<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\RouteInterface;
use Laminas\Router\RouteMatchInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Tree specific route interface.
 */
interface HttpRouteInterface extends RouteInterface
{
    /**
     * @param array<array-key, mixed> $options
     */
    public function match(
        RequestInterface $request,
        int|null $pathOffset = null,
        array $options = []
    ): RouteMatchInterface|null;
}
