<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\Exception;
use Laminas\Router\Http\HttpRouteMatch;
use Laminas\Router\Http\RouteDefinition\RouteDefinition;
use Laminas\Router\Http\RouteDefinition\RouteDefinitionLiteral;
use Laminas\Router\Http\RouteDefinition\RouteDefinitionOption;
use Laminas\Router\Http\RouteDefinition\RouteDefinitionParameter;
use Laminas\Router\Http\RouteDefinition\RouteDefinitionPartInterface;
use Laminas\Router\ReturnOfAssemble;
use Override;
use Psr\Http\Message\RequestInterface;

use function array_merge;
use function is_string;
use function preg_match;
use function preg_quote;
use function sprintf;
use function strlen;

/**
 * Hostname route.
 *
 * Note: the following type is recursive, but Psalm doesn't understand array shape recursion (yet). For now, we only
 *       represented recursion of the 'optional' part type to 1 level, to ease analysis.
 */
final class Hostname implements HttpRouteInterface
{
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
     * @internal
     * @deprecated Since 3.9.0 This property will be removed or made private in version 4.0
     */
    public int|null $priority = null;

    /**
     * Create a new hostname route.
     *
     * @param array<non-empty-string, string> $constraints
     * @param array<string, string> $defaults
     */
    public function __construct(
        string $route,
        array $constraints = [],
        private readonly array $defaults = []
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
        /** @psalm-var array<string, string> $defaults */
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
            if (! preg_match('(\G(?P<literal>[a-z0-9-.]*)(?P<token>[:\[\]]|$))', $def, $matches, 0, $currentPos)) {
                throw new Exception\RuntimeException('Matched hostname literal contains a disallowed character');
            }

            $currentPos += strlen($matches[0]);

            if (isset($matches['literal']) && $matches['literal'] !== '') {
                $routeDefinition->addPart(new RouteDefinitionLiteral($matches['literal']));
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

                /** @psalm-var non-empty-string $matches['name'] */

                $routeDefinition->addPart(new RouteDefinitionParameter(
                    $matches['name'],
                    $matches['delimiters'] ?? null
                ));
                $currentPos += strlen($matches[0]);
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
     * @psalm-param  list<RouteDefinitionPartInterface> $parts
     * @param array<string, string> $constraints
     * @throws Exception\RuntimeException
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
                    $regex .= '(' . $groupName . '[^.]+)';
                } else {
                    $regex .= '(' . $groupName . '[^' . $part->delimiter . ']+)';
                }

                $this->paramMap['param' . $groupIndex++] = $part->name;
                continue;
            }
            if ($part instanceof RouteDefinitionOption) {
                $regex .= '(?:' . $this->buildRegex($part->part, $constraints, $groupIndex) . ')?';
            }
        }

        return $regex;
    }

    /**
     * Build host.
     *
     * @psalm-param  list<RouteDefinitionPartInterface>                 $parts
     * @param array<string, string|null|int|float> $mergedParams
     * @throws Exception\RuntimeException
     * @throws Exception\InvalidArgumentException
     */
    private function buildHost(array $parts, array $mergedParams, bool $isOptional): string
    {
        $host      = '';
        $skip      = true;
        $skippable = false;

        foreach ($parts as $part) {
            if ($part instanceof RouteDefinitionLiteral) {
                $host .= $part->literal;
                continue;
            }

            if ($part instanceof RouteDefinitionParameter) {
                $skippable = true;

                if (! isset($mergedParams[$part->name])) {
                    if (! $isOptional) {
                        throw new Exception\InvalidArgumentException(sprintf('Missing parameter "%s"', $part->name));
                    }

                    return '';
                } elseif (
                    ! $isOptional
                    || ! isset($this->defaults[$part->name])
                    || $this->defaults[$part->name] !== $mergedParams[$part->name]
                ) {
                    $skip = false;
                }

                $host .= (string) $mergedParams[$part->name];

                $this->assembledParams[] = $part->name;
                continue;
            }

            if ($part instanceof RouteDefinitionOption) {
                $skippable    = true;
                $optionalPart = $this->buildHost($part->part, $mergedParams, true);

                if ($optionalPart !== '') {
                    $host .= $optionalPart;
                    $skip  = false;
                }
            }
        }

        if ($isOptional && $skippable && $skip) {
            return '';
        }

        return $host;
    }

    /** @inheritDoc */
    #[Override]
    public function match(RequestInterface $request, int|null $pathOffset = null, array $options = []): ?HttpRouteMatch
    {
        $host   = $request->getUri()->getHost();
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

        return new HttpRouteMatch(array_merge($this->defaults, $params));
    }

    /** @inheritDoc */
    #[Override]
    public function assemble(array $params = [], array $options = []): ReturnOfAssemble
    {
        $this->assembledParams = [];

        $host = $this->buildHost(
            $this->parts->getParts(),
            array_merge($this->defaults, $params),
            false
        );

        return new ReturnOfAssemble(
            host: $host,
        );
    }

    /** @inheritDoc */
    #[Override]
    public function getAssembledParams(): array
    {
        return $this->assembledParams;
    }
}
