<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\Exception;
use Laminas\Router\RouteConfigTrait;
use Laminas\Router\RoutePriorityTrait;
use Psr\Http\Message\ServerRequestInterface;

use function array_merge;
use function count;
use function preg_match;
use function preg_quote;
use function sprintf;
use function strlen;

/**
 * Hostname route.
 * Note: the following type is recursive, but Psalm doesn't understand array shape recursion (yet). For now, we only
 *       represented recursion of the 'optional' part type to 1 level, to ease analysis.
 *
 * @psalm-type Parts = list<
 *     array{
 *      'literal',
 *      string,
 *      string|null
 *     }|array{
 *      'parameter',
 *      string
 *     }|array{
 *      'optional',
 *      list<array{'literal', string, string|null}|array{'parameter', string}|array{'optional', array}>
 *     }
 * >
 */
final class Hostname implements HttpRouteInterface
{
    use RouteConfigTrait;
    use RoutePriorityTrait;

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
     *
     * @var list<string>
     */
    private array $assembledParams = [];

    /**
     * Create a new hostname route.
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
    public static function factory(iterable $options = []): Hostname
    {
        $options = self::processRouteOptions(
            $options,
            ['route'],
            ['constraints' => [], 'defaults' => []],
        );

        return new static(
            $options['route'],
            $options['constraints'],
            $options['defaults']
        );
    }

    /**
     * Parse a route definition.
     *
     * @throws Exception\RuntimeException
     * @return Parts
     */
    protected function parseRouteDefinition(string $def): array
    {
        $currentPos = 0;
        $length     = strlen($def);
        $parts      = [];
        $levelParts = [&$parts];
        $level      = 0;

        while ($currentPos < $length) {
            if (! preg_match('(\G(?P<literal>[a-z0-9-.]*)(?P<token>[:\[\]]|$))', $def, $matches, 0, $currentPos)) {
                throw new Exception\RuntimeException('Matched hostname literal contains a disallowed character');
            }

            $currentPos += strlen($matches[0]);

            if (isset($matches['literal']) && $matches['literal'] !== '') {
                $levelParts[$level][] = ['literal', $matches['literal']];
            }

            if ($matches['token'] === ':') {
                if (
                    ! preg_match(
                        '(\G(?P<name>[^:.{\[\]]+)(?:{(?P<delimiters>[^}]+)})?:?)',
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
                        $regex .= '(' . $groupName . '[^.]+)';
                    } else {
                        $regex .= '(' . $groupName . '[^' . $part[2] . ']+)';
                    }

                    $this->paramMap['param' . $groupIndex++] = $part[1];
                    break;

                case 'optional':
                    $regex .= '(?:' . $this->buildRegex($part[1], $constraints, $groupIndex) . ')??';
                    break;
            }
        }

        return $regex;
    }

    /**
     * Build host.
     *
     * @param array<string, string> $mergedParams
     */
    protected function buildHost(array $parts, array $mergedParams, bool $isOptional): string
    {
        $host      = '';
        $skip      = true;
        $skippable = false;

        foreach ($parts as $part) {
            switch ($part[0]) {
                case 'literal':
                    $host .= $part[1];
                    break;

                case 'parameter':
                    $skippable = true;

                    if (! isset($mergedParams[$part[1]])) {
                        if (! $isOptional) {
                            throw new Exception\InvalidArgumentException(sprintf('Missing parameter "%s"', $part[1]));
                        }

                        return '';
                    } elseif (
                        ! $isOptional
                        || ! isset($this->defaults[$part[1]])
                        || $this->defaults[$part[1]] !== $mergedParams[$part[1]]
                    ) {
                        $skip = false;
                    }

                    $host .= $mergedParams[$part[1]];

                    $this->assembledParams[] = $part[1];
                    break;

                case 'optional':
                    $skippable    = true;
                    $optionalPart = $this->buildHost($part[1], $mergedParams, true);

                    if ($optionalPart !== '') {
                        $host .= $optionalPart;
                        $skip  = false;
                    }
                    break;
            }
        }

        if ($isOptional && $skippable && $skip) {
            return '';
        }

        return $host;
    }

    /** @inheritDoc */
    public function match(
        ServerRequestInterface $request,
        ?int $pathOffset = null,
        array $options = []
    ): ?RouteMatch {
        $host = $request->getUri()->getHost();

        $result = preg_match('(^' . $this->regex . '$)', $host, $matches);

        if (! $result) {
            return null;
        }

        $params = [];

        foreach ($this->paramMap as $index => $name) {
            if (isset($matches[$index]) && $matches[$index] !== '') {
                $params[$name] = $matches[$index];
            }
        }

        return new RouteMatch(array_merge($this->defaults, $params));
    }

    /** @inheritDoc */
    public function assemble(array $params = [], array $options = []): string
    {
        $this->assembledParams = [];

        if (isset($options['uri'])) {
            $host = $this->buildHost(
                $this->parts,
                array_merge($this->defaults, $params),
                false
            );

            $options['uri']->setHost($host);
        }

        // A hostname does not contribute to the path, thus nothing is returned.
        return '';
    }

    /**
     * @inheritDoc
     * @return list<string>
     */
    public function getAssembledParams(): array
    {
        return $this->assembledParams;
    }
}
