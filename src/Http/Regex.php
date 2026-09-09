<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\AssembledUrl;
use Laminas\Router\Http\HttpRouteMatch;
use Laminas\Router\RouteMatchInterface;
use Override;
use Psr\Http\Message\RequestInterface;

use function array_merge;
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
final readonly class Regex implements HttpRouteInterface
{
    /**
     * Create a new regex route.
     *
     * @param non-empty-string $regex
     * @param non-empty-string $spec
     * @param array<string, string|int|float|null> $defaults
     */
    public function __construct(
        private string $name,
        /**
         * Regex to match.
         */
        private string $regex,
        /**
         * Specification for URL assembly.
         *
         * Parameters accepting substitutions should be denoted as "%key%"
         */
        private string $spec,
        /**
         * Default values.
         */
        private array $defaults = [],
        private int|null $priority = null,
    ) {
    }

    /** @inheritDoc */
    #[Override]
    public function match(
        RequestInterface $request,
        int|null $pathOffset = null,
        array $options = []
    ): ?RouteMatchInterface {
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

        return new HttpRouteMatch(array_merge($this->defaults, $cleanMatches), $this->name, $matchedLength);
    }

    /** @inheritDoc */
    #[Override]
    public function assemble(array $params = [], array $options = []): AssembledUrl
    {
        $url             = $this->spec;
        $mergedParams    = array_merge($this->defaults, $params);
        $assembledParams = [];

        foreach ($mergedParams as $key => $value) {
            $spec = '%' . $key . '%';

            if (str_contains($url, $spec)) {
                $url = str_replace($spec, rawurlencode((string) $value), $url);

                $assembledParams[] = $key;
            }
        }

        return new AssembledUrl(path: $url, assembledParams: $assembledParams);
    }

    #[Override]
    public function getPriority(): ?int
    {
        return $this->priority;
    }
}
