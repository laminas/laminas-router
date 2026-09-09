<?php

declare(strict_types=1);

namespace Laminas\Router\Http\Builder;

use Laminas\Router\Exception\RuntimeException;
use Laminas\Router\Http\HttpRouteInterface;
use Laminas\Router\Http\TranslatorAwareTreeRouteStack;
use Laminas\Router\RouteBuilderContainerInterface;
use Laminas\Router\RouteBuilderInterface;
use Laminas\Translator\TranslatorInterface;

use function is_string;

/**
 * @template TRoute of HttpRouteInterface
 * @implements RouteBuilderInterface<TranslatorAwareTreeRouteStack<TRoute>>
 */
final readonly class TranslatorAwareTreeRouteStackBuilder implements RouteBuilderInterface
{
    public function __construct(
        private RouteBuilderContainerInterface $container,
    ) {
    }

    /** @inheritDoc */
    public function build(array $options): TranslatorAwareTreeRouteStack
    {
        /** @psalm-var array<non-empty-string|array-key, array|TRoute> $routes */
        $routes = $options['routes'] ?? [];
        /** @psalm-var array<string, string|int|float|null> $defaultParams */
        $defaultParams        = $options['default_params'] ?? [];
        $translatorTextDomain = $options['translator_text_domain'] ?? 'default';
        /** @psalm-var int|null $priority */
        $priority = $options['priority'] ?? null;

        if (! is_string($translatorTextDomain)) {
            throw new RuntimeException('Invalid "translator_text_domain" option');
        }

        return new TranslatorAwareTreeRouteStack(
            $this->container,
            $translator,
            $routes,
            $defaultParams,
            $priority,
            $translatorTextDomain,
        );
    }
}
