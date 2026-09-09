<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\AssembledUrl;
use Laminas\Router\Exception;
use Laminas\Router\Http\RouteBuild\RouteAssemblyBuildResult;
use Laminas\Router\Http\RouteBuild\RouteRegexBuildResult;
use Laminas\Router\Http\RouteBuild\SegmentPathEncoder;
use Laminas\Router\Http\RouteDefinition\RouteDefinition;
use Laminas\Router\Http\RouteDefinition\RouteDefinitionLiteral;
use Laminas\Router\Http\RouteDefinition\RouteDefinitionOption;
use Laminas\Router\Http\RouteDefinition\RouteDefinitionParameter;
use Laminas\Router\Http\RouteDefinition\RouteDefinitionPartInterface;
use Laminas\Router\Http\RouteDefinition\RouteDefinitionTranslatedLiteral;
use Laminas\Router\RouteMatchInterface;
use Laminas\Translator\TranslatorInterface as Translator;
use Override;
use Psr\Http\Message\RequestInterface;
use Laminas\Translator\TranslatorInterface;

use function array_key_exists;
use function array_merge;
use function count;
use function preg_match;
use function preg_quote;
use function sprintf;
use function str_replace;
use function strlen;

/**
 * Segment route.
 */
final readonly class Segment implements HttpRouteInterface
{
    /**
     * Parts of the route.
     */
    private RouteDefinition $parts;
    private RouteRegexBuildResult $routeRegexBuildResult;

    /**
     * Create a new regex route.
     *
     * @param array<string, string> $constraints
     * @param array<string, string|int|float|null> $defaults
     */
    public function __construct(
        private string $name,
        string $route,
        array $constraints = [],
        private array $defaults = [],
        private int|null $priority = null,
        private ?Translator $translator = null,
    ) {
        $this->parts                 = $this->parseRouteDefinition($route);
        $this->routeRegexBuildResult = $this->buildRegex($this->parts->getParts(), $constraints);
    }

    /**
     * Parse a route definition.
     *
     * @throws Exception\RuntimeException
     */
    private function parseRouteDefinition(string $def): RouteDefinition
    {
        $currentPos      = 0;
        $length          = strlen($def);
        $routeDefinition = new RouteDefinition();

        while ($currentPos < $length) {
            preg_match('(\G(?P<literal>[^:{\[\]]*)(?P<token>[:{\[\]]|$))', $def, $matches, 0, $currentPos);

            $currentPos += strlen($matches[0]);

            if (isset($matches['literal']) && $matches['literal'] !== '') {
                $routeDefinition->addPart(new RouteDefinitionLiteral($matches['literal']));
            }

            if ($matches['token'] === ':') {
                if (
                    ! preg_match(
                        '(\G(?P<name>[^:/{\[\]]+)(?:{(?P<delimiters>[^}]+)})?:?)',
                        $def,
                        $nameAndDelimitersMatch,
                        0,
                        $currentPos
                    )
                ) {
                    throw new Exception\RuntimeException('Found empty parameter name');
                }

                /** @psalm-var non-empty-string $nameAndDelimitersMatch['name'] */
                $routeDefinition->addPart(new RouteDefinitionParameter(
                    $nameAndDelimitersMatch['name'],
                    $nameAndDelimitersMatch['delimiters'] ?? null
                ));

                $currentPos += strlen($nameAndDelimitersMatch[0]);
            } elseif ($matches['token'] === '{') {
                if (! preg_match('(\G(?P<literal>[^}]+)\})', $def, $literalMatch, 0, $currentPos)) {
                    throw new Exception\RuntimeException('Translated literal missing closing bracket');
                }

                $currentPos += strlen($literalMatch[0]);

                $routeDefinition->addPart(new RouteDefinitionTranslatedLiteral($literalMatch['literal']));
            } elseif ($matches['token'] === '[') {
                $routeDefinition->assertStartOptional();
            } elseif ($matches['token'] === ']') {
                $routeDefinition->endOptional();
            } else {
                break;
            }
        }

        return $routeDefinition;
    }

    /**
     * Build the matching regex from parsed parts.
     *
     * @param list<RouteDefinitionPartInterface> $parts
     * @param array<string, string> $constraints
     */
    private function buildRegex(array $parts, array $constraints, int $groupIndex = 1): RouteRegexBuildResult
    {
        $regex           = '';
        $paramMap        = [];
        $translationKeys = [];

        foreach ($parts as $part) {
            if ($part instanceof RouteDefinitionLiteral) {
                $regex .= preg_quote($part->literal);
                continue;
            }
            if ($part instanceof RouteDefinitionParameter) {
                $groupName = '?P<param' . $groupIndex . '>';

                if (isset($constraints[$part->name])) {
                    $regex .= '(' . $groupName . $constraints[$part->name] . ')';
                } elseif ($part->delimiter === null) {
                    $regex .= '(' . $groupName . '[^/]+)';
                } else {
                    $regex .= '(' . $groupName . '[^' . $part->delimiter . ']+)';
                }

                $paramMap['param' . $groupIndex] = $part->name;
                $groupIndex++;
                continue;
            }
            if ($part instanceof RouteDefinitionOption) {
                $child           = $this->buildRegex($part->part, $constraints, $groupIndex);
                $regex          .= '(?:' . $child->regex . ')?';
                $paramMap        = [...$paramMap, ...$child->paramMap];
                $translationKeys = [...$translationKeys, ...$child->translationKeys];
                $groupIndex      = $child->nextGroupIndex;
                continue;
            }
            if ($part instanceof RouteDefinitionTranslatedLiteral) {
                $regex            .= '#' . $part->literal . '#';
                $translationKeys[] = $part->literal;
            }
        }

        return new RouteRegexBuildResult($regex, $paramMap, $translationKeys, $groupIndex);
    }

    /**
     * Build a path.
     *
     * @param list<RouteDefinitionPartInterface> $parts
     * @param array<string, string|null|int|float> $mergedParams
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    private function buildPath(
        array $parts,
        array $mergedParams,
        bool $isOptional,
        bool $hasChild,
        array $options,
    ): RouteAssemblyBuildResult {
        $translator = $this->translator;
        $textDomain = null;
        $locale     = null;

        if (count($this->routeRegexBuildResult->translationKeys) > 0) {
            /** @psalm-var string $textDomain */
            $textDomain = $options['text_domain'] ?? 'default';
            /** @psalm-var string|null $locale */
            $locale = $options['locale'] ?? null;

            if (! $translator instanceof Translator) {
                throw new Exception\RuntimeException('No translator provided');
            }
        }

        $path            = '';
        $skip            = true;
        $skippable       = false;
        $assembledParams = [];

        foreach ($parts as $part) {
            if ($part instanceof RouteDefinitionLiteral) {
                $path .= $part->literal;
                continue;
            }
            if ($part instanceof RouteDefinitionParameter) {
                $skippable = true;

                if (! isset($mergedParams[$part->name])) {
                    if (! $isOptional || $hasChild) {
                        throw new Exception\InvalidArgumentException(sprintf('Missing parameter "%s"', $part->name));
                    }

                    return new RouteAssemblyBuildResult(null);
                } elseif (
                    ! $isOptional
                    || $hasChild
                    || ! isset($this->defaults[$part->name])
                    || $this->defaults[$part->name] !== $mergedParams[$part->name]
                ) {
                    $skip = false;
                }

                $path .= SegmentPathEncoder::encode((string) $mergedParams[$part->name]);

                $assembledParams[] = $part->name;
                continue;
            }
            if ($part instanceof RouteDefinitionOption) {
                $skippable = true;
                $child     = $this->buildPath(
                    $part->part,
                    $mergedParams,
                    true,
                    $hasChild,
                    $options,
                );

                if ($child->segment !== null) {
                    $path           .= $child->segment;
                    $assembledParams = [...$assembledParams, ...$child->assembledParams];
                    $skip            = false;
                }
                continue;
            }
            if ($part instanceof RouteDefinitionTranslatedLiteral) {
                if ($translator === null || $textDomain === null) {
                    throw new Exception\RuntimeException('No translator provided');
                }
                $path .= $translator->translate($part->literal, $textDomain, $locale);
            }
        }

        if ($isOptional && $skippable && $skip) {
            return new RouteAssemblyBuildResult(null);
        }

        return new RouteAssemblyBuildResult($path === '' ? null : $path, $assembledParams);
    }

    /**
     * @inheritDoc
     * @throws Exception\RuntimeException
     */
    #[Override]
    public function match(
        RequestInterface $request,
        int|null $pathOffset = null,
        array $options = []
    ): ?RouteMatchInterface {
        $path  = $request->getUri()->getPath();
        $regex = $this->routeRegexBuildResult->regex;

        if (count($this->routeRegexBuildResult->translationKeys) > 0) {
            $translator = $this->translator;
            /** @psalm-var string $textDomain */
            $textDomain = $options['text_domain'] ?? 'default';
            /** @psalm-var string|null $locale */
            $locale = $options['locale'] ?? null;

            if (! $translator instanceof Translator) {
                throw new Exception\RuntimeException('No translator provided');
            }

            foreach ($this->routeRegexBuildResult->translationKeys as $key) {
                $regex = str_replace('#' . $key . '#', $translator->translate($key, $textDomain, $locale), $regex);
            }
        }

        if ($pathOffset !== null) {
            $result = preg_match('(\G' . $regex . ')', $path, $matches, 0, $pathOffset);
        } else {
            $result = preg_match('(^' . $regex . '$)', $path, $matches);
        }

        if (! $result) {
            return null;
        }

        $matchedLength = strlen($matches[0]);
        $params        = [];

        foreach ($this->routeRegexBuildResult->paramMap as $index => $name) {
            if (isset($matches[$index]) && $matches[$index] !== '') {
                $params[$name] = SegmentPathEncoder::decode($matches[$index]);
            }
        }

        return new HttpRouteMatch(array_merge($this->defaults, $params), $this->name, $matchedLength);
    }

    /** @inheritDoc */
    #[Override]
    public function assemble(array $params = [], array $options = []): AssembledUrl
    {
        $result = $this->buildPath(
            $this->parts->getParts(),
            array_merge($this->defaults, $params),
            false,
            array_key_exists('has_child', $options) && $options['has_child'] === true,
            $options,
        );

        return new AssembledUrl(
            path: $result->segment ?? '',
            assembledParams: $result->assembledParams,
        );
    }

    #[Override]
    public function getPriority(): ?int
    {
        return $this->priority;
    }
}
