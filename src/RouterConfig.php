<?php

declare(strict_types=1);

namespace Laminas\Router;

use Laminas\Translator\TranslatorInterface;

/**
 * Normalized router configuration.
 *
 * @internal
 *
 * @psalm-immutable
 * @psalm-internal \Laminas\Router
 * @psalm-internal \LaminasTest\Router
 */
final readonly class RouterConfig
{
    /**
     * @param class-string<RouteStackInterface> $routerClass
     * @param array<string, class-string<RouteBuilderInterface>> $routeBuilders
     * @param class-string<TranslatorInterface> $translator
     */
    public function __construct(
        public string $routerClass,
        public array $routeBuilders,
        public string $translator,
    ) {
    }
}
