<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

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
