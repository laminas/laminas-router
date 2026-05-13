<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\Exception;
use Laminas\Router\Http\RouteDefinition\RouteDefinition;
use Laminas\Router\Http\RouteDefinition\RouteDefinitionLiteral;
use Laminas\Router\Http\RouteDefinition\RouteDefinitionOption;
use Laminas\Router\Http\RouteDefinition\RouteDefinitionParameter;
use Laminas\Router\Http\RouteDefinition\RouteDefinitionPartInterface;
use Laminas\Router\Http\RouteDefinition\RouteDefinitionTranslatedLiteral;
use Laminas\Router\ReturnOfAssemble;
use Laminas\Translator\TranslatorInterface as Translator;
use Override;
use Psr\Http\Message\RequestInterface;

use function array_key_exists;
use function array_merge;
use function count;
use function is_string;
use function preg_match;
use function preg_match_all;
use function preg_quote;
use function rawurldecode;
use function rawurlencode;
use function sprintf;
use function str_replace;
use function strlen;
use function strtr;

/**
 * Segment route.
 */
final class Segment implements HttpRouteInterface
{
    /**
     * Cache for the encode output.
     *
     * @var array<string, string>
     */
    private static array $cacheEncode = [];

    /**
     * Map of allowed special chars in path segments.
     *
     * http://tools.ietf.org/html/rfc3986#appendix-A
     * segement      = *pchar
     * pchar         = unreserved / pct-encoded / sub-delims / ":" / "@"
     * unreserved    = ALPHA / DIGIT / "-" / "." / "_" / "~"
     * sub-delims    = "!" / "$" / "&" / "'" / "(" / ")"
     *               / "*" / "+" / "," / ";" / "="
     *
     * @var array<string, string>
     */
    private static array $urlencodeCorrectionMap = [
        '%21' => "!", // sub-delims
        '%24' => "$", // sub-delims
        '%26' => "&", // sub-delims
        '%27' => "'", // sub-delims
        '%28' => "(", // sub-delims
        '%29' => ")", // sub-delims
        '%2A' => "*", // sub-delims
        '%2B' => "+", // sub-delims
        '%2C' => ",", // sub-delims
//      '%2D' => "-", // unreserved - not touched by rawurlencode
//      '%2E' => ".", // unreserved - not touched by rawurlencode
        '%3A' => ":", // pchar
        '%3B' => ";", // sub-delims
        '%3D' => "=", // sub-delims
        '%40' => "@", // pchar
//      '%5F' => "_", // unreserved - not touched by rawurlencode
//      '%7E' => "~", // unreserved - not touched by rawurlencode
    ];

    /**
     * Parts of the route.
     */
    private readonly RouteDefinition $parts;

    /**
     * Regex used for matching the route.
     */
    private readonly string $regex;

    /**
     * Map from regex groups to parameter names.
     *
     * @var array<string, string>
     */
    private array $paramMap = [];

    /**
     * List of assembled parameters.
     *
     * @var list<non-empty-string>
     */
    private array $assembledParams = [];

    /**
     * Translation keys used in the regex.
     *
     * @var list<string>
     */
    private array $translationKeys = [];

    /**
     * @internal
     * @deprecated Since 3.9.0 This property will be removed or made private in version 4.0
     */
    public int|null $priority = null;

    /**
     * Create a new regex route.
     *
     * @param array<string, string> $constraints
     * @param array<string, string> $defaults
     */
    public function __construct(
        string $route,
        array $constraints = [],
        private array $defaults = []
    ) {
        $this->parts = $this->parseRouteDefinition($route);
        $this->regex = $this->buildRegex($this->parts->getParts(), $constraints);
    }

    /**
     * @inheritDoc
     * @throws Exception\InvalidArgumentException
     */
    #[Override]
    public static function factory(array $options = []): static
    {
        $route = $options['route'] ?? null;
        /** @psalm-var array<non-empty-string, string> $constraints */
        $constraints = $options['constraints'] ?? [];
        /** @psalm-var array<non-empty-string, string> $defaults */
        $defaults = $options['defaults'] ?? [];

        if (! is_string($route)) {
            throw new Exception\InvalidArgumentException('Missing "route" in options array');
        }

        return new self($route, $constraints, $defaults);
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
                        $matches,
                        0,
                        $currentPos
                    )
                ) {
                    throw new Exception\RuntimeException('Found empty parameter name');
                }

                /** @psalm-var non-empty-string $matches['name'] */
                $routeDefinition->addPart(new RouteDefinitionParameter(
                    $matches['name'],
                    $matches['delimiters'] ?? null
                ));

                $currentPos += strlen($matches[0]);
            } elseif ($matches['token'] === '{') {
                if (! preg_match('(\G(?P<literal>[^}]+)\})', $def, $matches, 0, $currentPos)) {
                    throw new Exception\RuntimeException('Translated literal missing closing bracket');
                }

                $currentPos += strlen($matches[0]);

                $routeDefinition->addPart(new RouteDefinitionTranslatedLiteral($matches['literal']));
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
    private function buildRegex(array $parts, array $constraints, int &$groupIndex = 1): string
    {
        $regex = '';

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

                $this->paramMap['param' . $groupIndex++] = $part->name;
                continue;
            }
            if ($part instanceof RouteDefinitionOption) {
                $regex .= '(?:' . $this->buildRegex($part->part, $constraints, $groupIndex) . ')?';
                continue;
            }
            if ($part instanceof RouteDefinitionTranslatedLiteral) {
                $regex                  .= '#' . $part->literal . '#';
                $this->translationKeys[] = $part->literal;
            }
        }

        return $regex;
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
        array $options
    ): string {
        $translator = null;
        $textDomain = null;
        $locale     = null;

        if ($this->translationKeys) {
            /** @var mixed $translator */
            $translator = $options['translator'] ?? null;
            /** @psalm-var string $textDomain */
            $textDomain = $options['text_domain'] ?? 'default';
            /** @psalm-var string|null $locale */
            $locale = $options['locale'] ?? null;

            if (! $translator instanceof Translator) {
                throw new Exception\RuntimeException('No translator provided');
            }
        }

        $path      = '';
        $skip      = true;
        $skippable = false;

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

                    return '';
                } elseif (
                    ! $isOptional
                    || $hasChild
                    || ! isset($this->defaults[$part->name])
                    || $this->defaults[$part->name] !== $mergedParams[$part->name]
                ) {
                    $skip = false;
                }

                $path .= $this->encode((string) $mergedParams[$part->name]);

                $this->assembledParams[] = $part->name;
                continue;
            }
            if ($part instanceof RouteDefinitionOption) {
                $skippable    = true;
                $optionalPart = $this->buildPath($part->part, $mergedParams, true, $hasChild, $options);

                if ($optionalPart !== '') {
                    $path .= $optionalPart;
                    $skip  = false;
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
            return '';
        }

        return $path;
    }

    /**
     * @inheritDoc
     * @throws Exception\RuntimeException
     */
    #[Override]
    public function match(RequestInterface $request, int|null $pathOffset = null, array $options = []): ?HttpRouteMatch
    {
        $path  = $request->getUri()->getPath();
        $regex = $this->regex;

        if ($this->translationKeys) {
            $translator = $options['translator'] ?? null;
            /** @psalm-var string $textDomain */
            $textDomain = $options['text_domain'] ?? 'default';
            /** @psalm-var string|null $locale */
            $locale = $options['locale'] ?? null;

            if (! $translator instanceof Translator) {
                throw new Exception\RuntimeException('No translator provided');
            }

            foreach ($this->translationKeys as $key) {
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
        if (
            $pathOffset === null
            && $this->shouldUseDecodedMatchLengthForFullPath($path)
        ) {
            $matchedLength = strlen(rawurldecode($matches[0]));
        }
        $params = [];

        foreach ($this->paramMap as $index => $name) {
            if (isset($matches[$index]) && $matches[$index] !== '') {
                $params[$name] = $this->decode($matches[$index]);
            }
        }

        return new HttpRouteMatch(array_merge($this->defaults, $params), $matchedLength);
    }

    /** @inheritDoc */
    #[Override]
    public function assemble(array $params = [], array $options = []): ReturnOfAssemble
    {
        $this->assembledParams = [];

        return new ReturnOfAssemble(path :$this->buildPath(
            $this->parts->getParts(),
            array_merge($this->defaults, $params),
            false,
            array_key_exists('has_child', $options) && $options['has_child'] === true,
            $options
        ));
    }

    /** @inheritDoc */
    #[Override]
    public function getAssembledParams(): array
    {
        return $this->assembledParams;
    }

    /**
     * Encode a path segment.
     */
    private function encode(string $value): string
    {
        if (! isset(static::$cacheEncode[$value])) {
            static::$cacheEncode[$value] = rawurlencode($value);
            static::$cacheEncode[$value] = strtr(static::$cacheEncode[$value], static::$urlencodeCorrectionMap);
        }
        return static::$cacheEncode[$value];
    }

    /**
     * Decode a path segment.
     */
    private function decode(string $value): string
    {
        return rawurldecode($value);
    }

    /**
     * Diactoros normalizes some raw path characters into percent-encoding; match length should
     * reflect the logical (decoded) length in that case, but not when the client path intentionally
     * uses percent-encoding (e.g. {@code %20} for a space).
     */
    private function shouldUseDecodedMatchLengthForFullPath(string $requestPath): bool
    {
        if (! preg_match_all('/%[0-9A-Fa-f]{2}/', $requestPath, $matches)) {
            return false;
        }

        $codes = $matches[0];

        return ! (count($codes) === 1 && $codes[0] === '%20');
    }
}
