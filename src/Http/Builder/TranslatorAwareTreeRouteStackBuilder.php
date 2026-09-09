<?php

declare(strict_types=1);

namespace Laminas\Router\Http\Builder;

use Laminas\Router\Http\HttpRouteInterface;
use Laminas\Router\Http\TranslatorAwareTreeRouteStack;
use Laminas\Router\RouteBuilderInterface;

/**
 * @template TRoute of HttpRouteInterface
 * @implements RouteBuilderInterface<TranslatorAwareTreeRouteStack<TRoute>>
 */
final readonly class TranslatorAwareTreeRouteStackBuilder implements RouteBuilderInterface
{
    /** @inheritDoc */
    public function build(array $options): TranslatorAwareTreeRouteStack
    {
        return TranslatorAwareTreeRouteStack::factory($options);
    }
}
