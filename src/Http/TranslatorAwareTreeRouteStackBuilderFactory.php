<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

/**
 * @internal
 *
 * @psalm-internal \Laminas\Router
 * @psalm-internal \LaminasTest\Router
 */
final readonly class TranslatorAwareTreeRouteStackBuilderFactory
{
    public function __invoke(): TranslatorAwareTreeRouteStackBuilder
    {
        return new TranslatorAwareTreeRouteStackBuilder();
    }
}
