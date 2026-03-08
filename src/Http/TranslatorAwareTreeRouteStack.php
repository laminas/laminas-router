<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\Exception;
use Laminas\Router\ReturnOfAssemble;
use Laminas\Router\RouteMatch;
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
    public function assemble(array $params = [], array $options = []): ReturnOfAssemble
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
