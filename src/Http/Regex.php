<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\Exception\InvalidArgumentException;
use Laminas\Router\RouteConfigTrait;
use Laminas\Router\RoutePriorityTrait;
use Psr\Http\Message\ServerRequestInterface;

use function array_merge;
use function is_int;
use function is_numeric;
use function preg_match;
use function rawurldecode;
use function rawurlencode;
use function str_contains;
use function str_replace;
use function strlen;

/**
 * Regex route.
 */
final class Regex implements HttpRouteInterface
{
    use RouteConfigTrait;
    use RoutePriorityTrait;

    /**
     * List of assembled parameters.
     */
    private array $assembledParams = [];

    /**
     * Create a new regex route.
     *
     * @param string $regex Regex to match
     * @param string $spec Specification for URL assembly. Parameters with substitutions should be denoted as "%key%"
     */
    public function __construct(
        private readonly string $regex,
        private readonly string $spec,
        private readonly array $defaults = []
    ) {
    }

    /**
     * @inheritDoc
     * @throws InvalidArgumentException
     */
    public static function factory(iterable $options = []): Regex
    {
        $options = self::processRouteOptions(
            $options,
            ['regex', 'spec'],
            ['defaults' => []],
        );

        return new Regex(
            $options['regex'],
            $options['spec'],
            $options['defaults']
        );
    }

    /** @inheritDoc */
    public function match(
        ServerRequestInterface $request,
        ?int $pathOffset = null,
        array $options = []
    ): ?RouteMatch {
        $path = $request->getUri()->getPath();

        if ($pathOffset !== null) {
            $result = preg_match('(\G' . $this->regex . ')', $path, $matches, 0, $pathOffset);
        } else {
            $result = preg_match('(^' . $this->regex . '$)', $path, $matches);
        }

        if (! $result) {
            return null;
        }

        $matchedLength = strlen($matches[0]);

        foreach ($matches as $key => $value) {
            if (is_numeric($key) || is_int($key) || $value === '') {
                unset($matches[$key]);
            } else {
                $matches[$key] = rawurldecode($value);
            }
        }

        return new RouteMatch(array_merge($this->defaults, $matches), $matchedLength);
    }

    /** @inheritDoc */
    public function assemble(array $params = [], array $options = []): string|array
    {
        $url                   = $this->spec;
        $mergedParams          = array_merge($this->defaults, $params);
        $this->assembledParams = [];

        foreach ($mergedParams as $key => $value) {
            $spec = '%' . $key . '%';

            if (str_contains($url, $spec)) {
                $url = str_replace($spec, rawurlencode((string) $value), $url);

                $this->assembledParams[] = $key;
            }
        }

        return $url;
    }

    /** @inheritDoc */
    public function getAssembledParams(): array
    {
        return $this->assembledParams;
    }
}
