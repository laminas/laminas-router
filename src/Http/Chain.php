<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use ArrayObject;
use Laminas\Router\Exception;
use Laminas\Router\Exception\RuntimeException;
use Laminas\Router\Http\HttpRouteMatch;
use Laminas\Router\ReturnOfAssemble;
use Laminas\Router\RoutePluginManager;
use Override;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriFactoryInterface;

use function array_diff_key;
use function array_flip;
use function array_key_last;
use function array_reverse;
use function assert;
use function is_array;
use function is_bool;
use function strlen;

/**
 * @template TRoute of HttpRouteInterface
 * @template-extends TreeRouteStack<TRoute>
 */
final class Chain extends TreeRouteStack implements HttpRouteInterface
{
    /**
     * Chain routes.
     *
     * @var array<array-key, array|TRoute>
     */
    private array|null $chainRoutes;

    /**
     * List of assembled parameters.
     *
     * @var list<non-empty-string>
     */
    private array $assembledParams = [];

    /**
     * Create a new part route.
     *
     * @param array<array-key, array|TRoute> $routes
     * @param ArrayObject<string, TRoute> $prototypes
     * @param array<non-empty-string, non-empty-string> $defaultParams
     */
    public function __construct(
        RoutePluginManager $routePlugins,
        ArrayObject $prototypes,
        array $routes,
        array $defaultParams,
        UriFactoryInterface $uriFactory,
    ) {
        $this->chainRoutes = array_reverse($routes);
        parent::__construct($routePlugins, $prototypes, $uriFactory, [], $defaultParams);
    }

    /**
     * @inheritDoc
     * @throws Exception\InvalidArgumentException
     */
    #[Override]
    public static function factory(array $options = []): static
    {
        $route = $options['routes'] ?? null;
        /** @var ArrayObject<string, TRoute> $prototypes */
        $prototypes   = $options['prototypes'] ?? new ArrayObject();
        $routePlugins = $options['route_plugins'] ?? null;
        $uriFactory   = $options['uri_factory'] ?? null;

        if ($route === null) {
            throw new Exception\InvalidArgumentException('Missing "routes" in options array');
        }

        if (! $routePlugins instanceof RoutePluginManager) {
            throw new Exception\InvalidArgumentException('Missing "route_plugins" in options array');
        }

        if (! $uriFactory instanceof UriFactoryInterface) {
            throw new RuntimeException('Missing "uri_factory" in options array');
        }

        assert(is_array($route));

        /** @psalm-var RoutePluginManager $routePlugins */
        /** @psalm-var array<non-empty-string, TRoute> $route */

        return new self(
            $routePlugins,
            $prototypes,
            $route,
            [],
            $uriFactory
        );
    }

    /** @inheritDoc */
    #[Override]
    public function match(RequestInterface $request, int|null $pathOffset = null, array $options = []): ?HttpRouteMatch
    {
        $mustTerminate = $pathOffset === null;
        $pathOffset  ??= 0;

        if ($this->chainRoutes !== null) {
            $this->addRoutes($this->chainRoutes);
            $this->chainRoutes = null;
        }

        $match      = new HttpRouteMatch([]);
        $pathLength = strlen($request->getUri()->getPath());

        foreach ($this->routes as $route) {
            assert($route instanceof HttpRouteInterface);
            $subMatch = $route->match($request, $pathOffset, $options);

            if ($subMatch === null) {
                return null;
            }

            assert($subMatch instanceof HttpRouteMatch);

            $match->merge($subMatch);
            $pathOffset += $subMatch->getLength();
        }

        if ($mustTerminate && $pathOffset !== $pathLength) {
            return null;
        }

        return $match;
    }

    /** @inheritDoc */
    #[Override]
    public function assemble(array $params = [], array $options = []): ReturnOfAssemble
    {
        // On commence avec un résultat vide
        $finalResult = new ReturnOfAssemble();
        if ($this->chainRoutes !== null) {
            $this->addRoutes($this->chainRoutes);
            $this->chainRoutes = null;
        }

        $this->assembledParams = [];

        $routes       = [...$this->routes];
        $lastRouteKey = array_key_last($routes);

        foreach ($routes as $key => $route) {
            assert($route instanceof HttpRouteInterface);
            $chainOptions = $options;
            $hasChild     = isset($options['has_child']) && is_bool($options['has_child']) && $options['has_child'];

            $chainOptions['has_child'] = $hasChild || $key !== $lastRouteKey;

            $returnOfAssemble = $route->assemble($params, $chainOptions);
            $finalResult      = $finalResult->merge($returnOfAssemble);

            $params = array_diff_key($params, array_flip($route->getAssembledParams()));

            $this->assembledParams = [
                ...$this->assembledParams,
                ...$route->getAssembledParams(),
            ];
        }

        return $finalResult;
    }

    /** @inheritDoc */
    #[Override]
    public function getAssembledParams(): array
    {
        return $this->assembledParams;
    }
}
