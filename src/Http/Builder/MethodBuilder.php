<?php

declare(strict_types=1);

namespace Laminas\Router\Http\Builder;

use Laminas\Router\Http\Method;
use Laminas\Router\RouteBuilderInterface;

/**
 * @implements RouteBuilderInterface<Method>
 */
final readonly class MethodBuilder implements RouteBuilderInterface
{
    /** @inheritDoc */
    public function build(array $options): Method
    {
        return Method::factory($options);
    }
}
