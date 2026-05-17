<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\AssembledUrl;
use Laminas\Router\Exception;
use Laminas\Router\Exception\InvalidArgumentException;
use Laminas\Router\Http\HttpRouteMatch;
use Override;
use Psr\Http\Message\RequestInterface;

use function array_merge;
use function assert;
use function is_array;
use function is_string;
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
     * Create a new regex route.
     *
     * @param non-empty-string $regex
     * @param non-empty-string $spec
     * @param array<non-empty-string, string|int> $defaults
     */
    public function __construct(
        /**
         * Regex to match.
         */
        private readonly string $regex,
        /**
         * Specification for URL assembly.
         *
         * Parameters accepting substitutions should be denoted as "%key%"
         */
        private readonly string $spec,
        /**
         * Default values.
         *
         * @var array<non-empty-string, string|int>
         */
        private readonly array $defaults = []
    ) {
    }

    /**
     * @inheritDoc
     * @throws InvalidArgumentException
     */
    #[Override]
    public static function factory(array $options = []): static
    {
        $regex    = $options['regex'] ?? null;
        $spec     = $options['spec'] ?? null;
        $defaults = $options['defaults'] ?? [];

        if (! is_string($regex) || $regex === '') {
            throw new Exception\InvalidArgumentException('Missing "regex" in options array');
        }
        if (! is_string($spec) || $spec === '') {
            throw new Exception\InvalidArgumentException('Missing "spec" in options array');
        }
        assert(is_array($defaults));

        /** @psalm-var array<non-empty-string, non-empty-string> $defaults */

        return new self($regex, $spec, $defaults);
    }

    /** @inheritDoc */
    #[Override]
    public function match(RequestInterface $request, int|null $pathOffset = null, array $options = []): ?HttpRouteMatch
    {
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
        $cleanMatches  = [];

        foreach ($matches as $key => $value) {
            if (is_string($key) && $value !== '') {
                unset($matches[$key]);
                $cleanMatches[$key] = rawurldecode($value);
            }
        }

        return new HttpRouteMatch(array_merge($this->defaults, $cleanMatches), $matchedLength);
    }

    /** @inheritDoc */
    #[Override]
    public function assemble(array $params = [], array $options = []): AssembledUrl
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

        return new AssembledUrl(path:$url);
    }

    /** @inheritDoc */
    #[Override]
    public function getAssembledParams(): array
    {
        return $this->assembledParams;
    }
}
