<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\AssembledUrl;
use Laminas\Router\Exception;
use Laminas\Router\Http\HttpRouteMatch;
use Laminas\Router\Http\RouteBuild\RouteAssemblyBuildResult;
use Laminas\Router\Http\RouteBuild\RouteRegexBuildResult;
use Laminas\Router\Http\RouteDefinition\RouteDefinition;
use Laminas\Router\Http\RouteDefinition\RouteDefinitionLiteral;
use Laminas\Router\Http\RouteDefinition\RouteDefinitionOption;
use Laminas\Router\Http\RouteDefinition\RouteDefinitionParameter;
use Laminas\Router\Http\RouteDefinition\RouteDefinitionPartInterface;
use Laminas\Router\RouteMatchInterface;
use Override;
use Psr\Http\Message\RequestInterface;

use function array_merge;
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
final readonly class Hostname implements HttpRouteInterface
{
    /**
     * Parts of the route.
     */
    private RouteDefinition $parts;
    private RouteRegexBuildResult $routeRegexBuildResult;

    /**
     * Create a new hostname route.
     *
     * @param array<non-empty-string, string> $constraints
     * @param array<string, string|int|float|null> $defaults
     */
    public function __construct(
        private string $name,
        string $route,
        array $constraints = [],
        private array $defaults = [],
        private int|null $priority = null,
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
            if (! preg_match('(\G(?P<literal>[a-z0-9-.]*)(?P<token>[:\[\]]|$))', $def, $matches, 0, $currentPos)) {
                throw new Exception\RuntimeException('Matched hostname literal contains a disallowed character');
            }

            $firstMatch = $matches[0] ?? '';

            $currentPos += strlen($firstMatch);

            if (isset($matches['literal']) && $matches['literal'] !== '') {
                $routeDefinition->addPart(new RouteDefinitionLiteral($matches['literal']));
            }

            if ($matches['token'] === ':') {
                if (
                    ! preg_match(
                        '(\G(?P<name>[^:.{\[\]]+)(?:{(?P<delimiters>[^}]+)})?:?)',
                        $def,
                        $nameAndDelimitersMatch,
                        0,
                        $currentPos
                    )
                ) {
                    throw new Exception\RuntimeException('Found empty parameter name');
                }

                $nameAndDelimitersMatch0          = $nameAndDelimitersMatch[0] ?? '';
                $nameAndDelimitersMatchDelimiters = $nameAndDelimitersMatch['delimiters'] ?? null;
                $nameAndDelimitersMatchName       = $nameAndDelimitersMatch['name'] ?? '';

                if ($nameAndDelimitersMatchName === '') {
                    throw new Exception\RuntimeException('Found empty parameter name');
                }

                $routeDefinition->addPart(new RouteDefinitionParameter(
                    $nameAndDelimitersMatchName,
                    $nameAndDelimitersMatchDelimiters
                ));
                $currentPos += strlen($nameAndDelimitersMatch0);
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
    private function buildRegex(array $parts, array $constraints, int $groupIndex = 1): RouteRegexBuildResult
    {
        $regex    = '';
        $paramMap = [];

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

                $paramMap['param' . $groupIndex] = $part->name;
                $groupIndex++;
                continue;
            }
            if ($part instanceof RouteDefinitionOption) {
                $child      = $this->buildRegex($part->part, $constraints, $groupIndex);
                $regex     .= '(?:' . $child->regex . ')?';
                $paramMap   = [...$paramMap, ...$child->paramMap];
                $groupIndex = $child->nextGroupIndex;
            }
        }

        return new RouteRegexBuildResult($regex, $paramMap, nextGroupIndex: $groupIndex);
    }

    /**
     * Build host.
     *
     * @psalm-param  list<RouteDefinitionPartInterface>                 $parts
     * @param array<string, string|null|int|float> $mergedParams
     * @throws Exception\RuntimeException
     * @throws Exception\InvalidArgumentException
     */
    private function buildHost(array $parts, array $mergedParams, bool $isOptional): RouteAssemblyBuildResult
    {
        $host            = '';
        $skip            = true;
        $skippable       = false;
        $assembledParams = [];

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

                    return new RouteAssemblyBuildResult(null, []);
                } elseif (
                    ! $isOptional
                    || ! isset($this->defaults[$part->name])
                    || $this->defaults[$part->name] !== $mergedParams[$part->name]
                ) {
                    $skip = false;
                }

                $host .= (string) $mergedParams[$part->name];

                $assembledParams[] = $part->name;
                continue;
            }

            if ($part instanceof RouteDefinitionOption) {
                $skippable = true;
                $child     = $this->buildHost($part->part, $mergedParams, true);

                if ($child->segment !== null) {
                    $host           .= $child->segment;
                    $assembledParams = [...$assembledParams, ...$child->assembledParams];
                    $skip            = false;
                }
            }
        }

        if ($isOptional && $skippable && $skip) {
            return new RouteAssemblyBuildResult(null, []);
        }

        return new RouteAssemblyBuildResult($host === '' ? null : $host, $assembledParams);
    }

    /** @inheritDoc */
    #[Override]
    public function match(
        RequestInterface $request,
        int|null $pathOffset = null,
        array $options = []
    ): ?RouteMatchInterface {
        $host   = $request->getUri()->getHost();
        $result = preg_match('(^' . $this->routeRegexBuildResult->regex . '$)', $host, $matches);

        if (! $result) {
            return null;
        }

        $params = [];

        foreach ($this->routeRegexBuildResult->paramMap as $index => $name) {
            if (isset($matches[$index]) && $matches[$index] !== '') {
                $params[$name] = $matches[$index];
            }
        }

        return new HttpRouteMatch(array_merge($this->defaults, $params), $this->name, 0);
    }

    /** @inheritDoc */
    #[Override]
    public function assemble(array $params = [], array $options = []): AssembledUrl
    {
        $result = $this->buildHost(
            $this->parts->getParts(),
            array_merge($this->defaults, $params),
            false,
        );

        return new AssembledUrl(
            assembledParams: $result->assembledParams,
            host: $result->segment,
        );
    }

    #[Override]
    public function getPriority(): ?int
    {
        return $this->priority;
    }
}
