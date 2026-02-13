<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\Exception;
use Laminas\Translator\TranslatorInterface as Translator;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Translator aware tree route stack.
 */
final class TranslatorAwareTreeRouteStack extends TreeRouteStack
{
    /**
     * Translator used for translatable segments.
     */
    protected ?Translator $translator = null;

    /**
     * Whether the translator is enabled.
     */
    protected bool $translatorEnabled = true;

    /**
     * Translator text domain to use.
     */
    protected string $translatorTextDomain = 'default';

    /** @inheritDoc */
    public function match(
        ServerRequestInterface $request,
        ?int $pathOffset = null,
        array $options = []
    ): ?RouteMatch {
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
    public function assemble(array $params = [], array $options = []): mixed
    {
        if ($this->hasTranslator() && $this->isTranslatorEnabled() && ! isset($options['translator'])) {
            $options['translator'] = $this->getTranslator();
        }

        if (! isset($options['text_domain'])) {
            $options['text_domain'] = $this->getTranslatorTextDomain();
        }

        return parent::assemble($params, $options);
    }

    /**
     * setTranslator(): defined by TranslatorAwareInterface.
     *
     * @see    TranslatorAwareInterface::setTranslator()
     */
    public function setTranslator(
        ?Translator $translator = null,
        ?string $textDomain = null
    ): TranslatorAwareTreeRouteStack {
        $this->translator = $translator;

        if ($textDomain !== null) {
            $this->setTranslatorTextDomain($textDomain);
        }

        return $this;
    }

    /**
     * getTranslator(): defined by TranslatorAwareInterface.
     */
    public function getTranslator(): ?Translator
    {
        return $this->translator;
    }

    /**
     * hasTranslator(): defined by TranslatorAwareInterface.
     */
    public function hasTranslator(): bool
    {
        return $this->translator !== null;
    }

    /**
     * setTranslatorEnabled(): defined by TranslatorAwareInterface.
     */
    public function setTranslatorEnabled(bool $enabled = true): TranslatorAwareTreeRouteStack
    {
        $this->translatorEnabled = $enabled;

        return $this;
    }

    /**
     * isTranslatorEnabled(): defined by TranslatorAwareInterface.
     */
    public function isTranslatorEnabled(): bool
    {
        return $this->translatorEnabled;
    }

    /**
     * setTranslatorTextDomain(): defined by TranslatorAwareInterface.
     */
    public function setTranslatorTextDomain(string $textDomain = 'default'): TranslatorAwareTreeRouteStack
    {
        $this->translatorTextDomain = $textDomain;

        return $this;
    }

    /**
     * getTranslatorTextDomain(): defined by TranslatorAwareInterface.
     */
    public function getTranslatorTextDomain(): string
    {
        return $this->translatorTextDomain;
    }
}
