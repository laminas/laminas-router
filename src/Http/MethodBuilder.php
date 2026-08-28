<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\RouteBuilderInterface;
use Laminas\Router\RouteInterface;

/**
 * @implements RouteBuilderInterface<Method>
 */
final readonly class MethodBuilder implements RouteBuilderInterface
{
    public function build(array $options = []): RouteInterface
    {
        return Method::factory($options);
    }
}
