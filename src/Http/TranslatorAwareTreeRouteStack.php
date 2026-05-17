<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use ArrayObject;
use Laminas\Router\AssembledUrl;
use Laminas\Router\Exception;
use Laminas\Router\Exception\RuntimeException;
use Laminas\Router\RouteMatch;
use Laminas\Router\RoutePluginManager;
use Laminas\Translator\TranslatorInterface;
use Override;
use Psr\Http\Message\RequestInterface;

/**
 * Translator aware tree route stack.
 *
 * @template TRoute of HttpRouteInterface
 * @template-extends TreeRouteStack<TRoute>
 */
final class TranslatorAwareTreeRouteStack extends TreeRouteStack
{
    /**
     * Translator used for translatable segments.
     */
    private ?TranslatorInterface $translator = null;

    /**
     * Whether the translator is enabled.
     */
    private bool $translatorEnabled = true;

    /**
     * Translator text domain to use.
     */
    private string $translatorTextDomain = 'default';

    /**
     * @param ArrayObject<string, TRoute> $prototypes
     * @param array<non-empty-string, array|TRoute> $routes
     * @param array<non-empty-string, non-empty-string> $defaultParams
     */
    public function __construct(
        private readonly RoutePluginManager $routePluginManager,
        /**
         * Prototype routes.
         *
         * We use an ArrayObject in this case so we can easily pass it down the tree
         * by reference.
         */
        ArrayObject $prototypes,
        array $routes = [],
        array $defaultParams = [],
    ) {
        parent::__construct($this->routePluginManager, $prototypes, $routes, $defaultParams);
    }

    /**
     * @inheritDoc
     * @throws Exception\InvalidArgumentException
     */
    #[Override]
    public static function factory(array $options = []): static
    {
        /** @psalm-var array<non-empty-string, array|TRoute>  $routes */
        $routes = $options['routes'] ?? [];
        /** @var ArrayObject<string, TRoute> $prototypes */
        $prototypes   = $options['prototypes'] ?? new ArrayObject();
        $routePlugins = $options['route_plugins'] ?? null;
        /** @psalm-var array<non-empty-string, non-empty-string> $defaultParams */
        $defaultParams = $options['default_params'] ?? [];

        if (! $routePlugins instanceof RoutePluginManager) {
            throw new RuntimeException('Missing "route_plugins" in options array');
        }

        return new static(
            $routePlugins,
            $prototypes,
            $routes,
            $defaultParams,
        );
    }

    /**
     * @inheritDoc
     * @param int|null $pathOffset
     */
    #[Override]
    public function match(RequestInterface $request, int|null $pathOffset = null, array $options = []): ?RouteMatch
    {
        if ($this->hasTranslator() && $this->isTranslatorEnabled() && ! isset($options['translator'])) {
            $options['translator'] = $this->getTranslator();
        }

        if (! isset($options['text_domain'])) {
            $options['text_domain'] = $this->getTranslatorTextDomain();
        }

        return parent::match($request, $pathOffset, $options);
    }

    /**
     * @inheritDoc
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    #[Override]
    public function assemble(array $params = [], array $options = []): AssembledUrl
    {
        if ($this->hasTranslator() && $this->isTranslatorEnabled() && ! isset($options['translator'])) {
            $options['translator'] = $this->getTranslator();
        }

        if (! isset($options['text_domain'])) {
            $options['text_domain'] = $this->getTranslatorTextDomain();
        }

        return parent::assemble($params, $options);
    }

    public function setTranslator(?TranslatorInterface $translator = null, ?string $textDomain = null): self
    {
        $this->translator = $translator;

        if ($textDomain !== null) {
            $this->setTranslatorTextDomain($textDomain);
        }

        return $this;
    }

    public function getTranslator(): ?TranslatorInterface
    {
        return $this->translator;
    }

    public function hasTranslator(): bool
    {
        return $this->translator !== null;
    }

    public function setTranslatorEnabled(bool $enabled = true): self
    {
        $this->translatorEnabled = $enabled;
        return $this;
    }

    public function isTranslatorEnabled(): bool
    {
        return $this->translatorEnabled;
    }

    public function setTranslatorTextDomain(string $textDomain = 'default'): self
    {
        $this->translatorTextDomain = $textDomain;

        return $this;
    }

    public function getTranslatorTextDomain(): string
    {
        return $this->translatorTextDomain;
    }
}
