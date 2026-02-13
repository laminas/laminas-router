<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\I18n\Translator\Translator;
use Laminas\Router\Exception;
use Laminas\Router\RouteConfigTrait;
use Laminas\Router\RoutePriorityTrait;
use Laminas\Translator\TranslatorInterface;
use Psr\Http\Message\ServerRequestInterface;

use function array_merge;
use function count;
use function preg_match;
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
    use RouteConfigTrait;
    use RoutePriorityTrait;

    /**
     * Cache for the encode output.
     *
     * @var array<string, string>
     */
    protected static array $cacheEncode = [];

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
    protected static array $urlencodeCorrectionMap = [
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
    private readonly array $parts;

    /**
     * Regex used for matching the route.
     */
    private readonly string $regex;

    /**
     * Map from regex groups to parameter names.
     */
    private array $paramMap = [];

    /**
     * Default values.
     */
    private readonly array $defaults;

    /**
     * List of assembled parameters.
     */
    private array $assembledParams = [];

    /**
     * Translation keys used in the regex.
     */
    private array $translationKeys = [];

    /**
     * Create a new regex route.
     */
    public function __construct(string $route, array $constraints = [], array $defaults = [])
    {
        $this->defaults = $defaults;
        $this->parts    = $this->parseRouteDefinition($route);
        $this->regex    = $this->buildRegex($this->parts, $constraints);
    }

    /**
     * @inheritDoc
     * @throws Exception\InvalidArgumentException
     */
    public static function factory(iterable $options = []): Segment
    {
        $options = self::processRouteOptions(
            $options,
            ['route'],
            ['constraints' => [], 'defaults' => []],
        );

        return new Segment(
            $options['route'],
            $options['constraints'],
            $options['defaults']
        );
    }

    /**
     * Parse a route definition.
     *
     * @throws Exception\RuntimeException
     */
    protected function parseRouteDefinition(string $def): array
    {
        $currentPos = 0;
        $length     = strlen($def);
        $parts      = [];
        $levelParts = [&$parts];
        $level      = 0;

        while ($currentPos < $length) {
            preg_match('(\G(?P<literal>[^:{\[\]]*)(?P<token>[:{\[\]]|$))', $def, $matches, 0, $currentPos);

            $currentPos += strlen($matches[0]);

            if (isset($matches['literal']) && $matches['literal'] !== '') {
                $levelParts[$level][] = ['literal', $matches['literal']];
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

                $levelParts[$level][] = [
                    'parameter',
                    $matches['name'],
                    $matches['delimiters'] ?? null,
                ];

                $currentPos += strlen($matches[0]);
            } elseif ($matches['token'] === '{') {
                if (! preg_match('(\G(?P<literal>[^}]+)\})', $def, $matches, 0, $currentPos)) {
                    throw new Exception\RuntimeException('Translated literal missing closing bracket');
                }

                $currentPos += strlen($matches[0]);

                $levelParts[$level][] = ['translated-literal', $matches['literal']];
            } elseif ($matches['token'] === '[') {
                $levelParts[$level][]   = ['optional', []];
                $levelParts[$level + 1] = &$levelParts[$level][count($levelParts[$level]) - 1][1];

                $level++;
            } elseif ($matches['token'] === ']') {
                unset($levelParts[$level]);
                $level--;

                if ($level < 0) {
                    throw new Exception\RuntimeException('Found closing bracket without matching opening bracket');
                }
            } else {
                break;
            }
        }

        if ($level > 0) {
            throw new Exception\RuntimeException('Found unbalanced brackets');
        }

        return $parts;
    }

    /**
     * Build the matching regex from parsed parts.
     */
    protected function buildRegex(array $parts, array $constraints, int &$groupIndex = 1): string
    {
        $regex = '';

        foreach ($parts as $part) {
            switch ($part[0]) {
                case 'literal':
                    $regex .= preg_quote($part[1]);
                    break;

                case 'parameter':
                    $groupName = '?P<param' . $groupIndex . '>';

                    if (isset($constraints[$part[1]])) {
                        $regex .= '(' . $groupName . $constraints[$part[1]] . ')';
                    } elseif ($part[2] === null) {
                        $regex .= '(' . $groupName . '[^/]+)';
                    } else {
                        $regex .= '(' . $groupName . '[^' . $part[2] . ']+)';
                    }

                    $this->paramMap['param' . $groupIndex++] = $part[1];
                    break;

                case 'optional':
                    $regex .= '(?:' . $this->buildRegex($part[1], $constraints, $groupIndex) . ')?';
                    break;

                case 'translated-literal':
                    $regex                  .= '#' . $part[1] . '#';
                    $this->translationKeys[] = $part[1];
                    break;
            }
        }

        return $regex;
    }

    /**
     * Build a path.
     *
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    protected function buildPath(
        array $parts,
        array $mergedParams,
        bool $isOptional,
        bool $hasChild,
        array $options
    ): string {
        if ($this->translationKeys) {
            if (! isset($options['translator']) || ! $options['translator'] instanceof TranslatorInterface) {
                throw new Exception\RuntimeException('No translator provided');
            }

            $translator = $options['translator'];
            $textDomain = $options['text_domain'] ?? 'default';
            $locale     = $options['locale'] ?? null;
        }

        $path      = '';
        $skip      = true;
        $skippable = false;

        foreach ($parts as $part) {
            switch ($part[0]) {
                case 'literal':
                    $path .= $part[1];
                    break;

                case 'parameter':
                    $skippable = true;

                    if (! isset($mergedParams[$part[1]])) {
                        if (! $isOptional || $hasChild) {
                            throw new Exception\InvalidArgumentException(sprintf('Missing parameter "%s"', $part[1]));
                        }

                        return '';
                    } elseif (
                        ! $isOptional
                        || $hasChild
                        || ! isset($this->defaults[$part[1]])
                        || $this->defaults[$part[1]] !== $mergedParams[$part[1]]
                    ) {
                        $skip = false;
                    }

                    $path .= $this->encode((string) $mergedParams[$part[1]]);

                    $this->assembledParams[] = $part[1];
                    break;

                case 'optional':
                    $skippable    = true;
                    $optionalPart = $this->buildPath($part[1], $mergedParams, true, $hasChild, $options);

                    if ($optionalPart !== '') {
                        $path .= $optionalPart;
                        $skip  = false;
                    }
                    break;

                case 'translated-literal':
                    $path .= $translator->translate($part[1], $textDomain, $locale);
                    break;
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
    public function match(
        ServerRequestInterface $request,
        ?int $pathOffset = null,
        array $options = []
    ): ?RouteMatch {
        $path = $request->getUri()->getPath();

        $regex = $this->regex;

        if ($this->translationKeys) {
            if (! isset($options['translator']) || ! $options['translator'] instanceof Translator) {
                throw new Exception\RuntimeException('No translator provided');
            }

            $translator = $options['translator'];
            $textDomain = $options['text_domain'] ?? 'default';
            $locale     = $options['locale'] ?? null;

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
        $params        = [];

        foreach ($this->paramMap as $index => $name) {
            if (isset($matches[$index]) && $matches[$index] !== '') {
                $params[$name] = $this->decode($matches[$index]);
            }
        }

        return new RouteMatch(array_merge($this->defaults, $params), $matchedLength);
    }

    /** @inheritDoc */
    public function assemble(array $params = [], array $options = []): string
    {
        $this->assembledParams = [];

        return $this->buildPath(
            $this->parts,
            array_merge($this->defaults, $params),
            false,
            $options['has_child'] ?? false,
            $options
        );
    }

    /** @inheritDoc */
    public function getAssembledParams(): array
    {
        return $this->assembledParams;
    }

    /**
     * Encode a path segment.
     */
    protected function encode(string $value): string
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
    protected function decode(string $value): string
    {
        return rawurldecode($value);
    }
}
