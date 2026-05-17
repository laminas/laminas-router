<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use ArrayObject;
use Laminas\Router\AssembledUrl;
use Laminas\Router\Exception;
use Laminas\Router\RouteMatch;
use Laminas\Router\RoutePluginManager;
use Override;
use Psr\Http\Message\RequestInterface;

use function array_diff_key;
use function array_flip;
use function assert;
use function count;
use function is_array;
use function is_bool;
use function is_object;
use function is_string;
use function strlen;

/**
 * @template TRoute of HttpRouteInterface
 * @template-extends TreeRouteStack<TRoute>
 */
final class Part extends TreeRouteStack implements HttpRouteInterface
{
    /**
     * RouteInterface to match.
     *
     * @var TRoute
     */
    private readonly HttpRouteInterface $route;

    private readonly bool $mayTerminate;

    /** @var array<non-empty-string, array|TRoute> */
    private array $childRoutes;

    /**
     * Create a new part route.
     *
     * @param TRoute|array|string           $routes
     * @param ArrayObject<string, TRoute> $prototypes
     * @param array<non-empty-string, non-empty-string> $defaultParams
     * @param array<non-empty-string, array|TRoute> $childRoutes
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(
        RoutePluginManager $routePluginManager,
        ArrayObject $prototypes,
        HttpRouteInterface|array|string $routes = [],
        array $defaultParams = [],
        bool $mayTerminate = false,
        array $childRoutes = [],
    ) {
        $this->mayTerminate = $mayTerminate;
        $this->childRoutes  = $childRoutes;

        parent::__construct($routePluginManager, $prototypes, [], $defaultParams);

        if (! is_object($routes)) {
            $routes = $this->routeFromArray($routes);
        }

        if ($routes instanceof self) {
            throw new Exception\InvalidArgumentException('Base route may not be a part route');
        }

        $this->route = $routes;
    }

    /**
     * @inheritDoc
     * @throws Exception\InvalidArgumentException
     */
    #[Override]
    public static function factory(array $options = []): static
    {
        $route        = $options['route'] ?? null;
        $routePlugins = $options['route_plugins'] ?? null;
        /** @var ArrayObject<string, TRoute> $prototypes */
        $prototypes   = $options['prototypes'] ?? new ArrayObject();
        $mayTerminate = $options['may_terminate'] ?? false;
        /** @var array<non-empty-string, TRoute> $childRoutes */
        $childRoutes = $options['child_routes'] ?? [];
        /** @psalm-var array<non-empty-string, non-empty-string> $defaultParams */
        $defaultParams = $options['default_params'] ?? [];

        if (! $routePlugins instanceof RoutePluginManager) {
            throw new Exception\InvalidArgumentException('Missing "route_plugins" in options array');
        }

        if ($route === null) {
            throw new Exception\InvalidArgumentException('Missing "route" in options array');
        }

        assert(is_bool($mayTerminate));
        assert(is_array($defaultParams));
        assert(is_array($route) || is_string($route) || $route instanceof HttpRouteInterface);

        return new self(
            $routePlugins,
            $prototypes,
            $route,
            $defaultParams,
            $mayTerminate,
            $childRoutes,
        );
    }

    /** @inheritDoc */
    #[Override]
    public function match(
        RequestInterface $request,
        int|null $pathOffset = null,
        array $options = []
    ): RouteMatch|null {
        $pathOffset ??= 0;
        $match        = $this->route->match($request, $pathOffset, $options);

        assert($match instanceof HttpRouteMatch || $match === null);

        if ($match !== null) {
            if (count($this->childRoutes) !== 0) {
                $this->addRoutes($this->childRoutes);
                $this->childRoutes = [];
            }

            $nextOffset = $pathOffset + $match->getLength();

            $pathLength = strlen($request->getUri()->getPath());

            if ($this->mayTerminate && $nextOffset === $pathLength) {
                return $match;
            }

            if (isset($options['translator']) && ! isset($options['locale'])) {
                /** @var mixed $locale */
                $locale = $match->getParam('locale');
                if (is_string($locale)) {
                    $options['locale'] = $locale;
                }
            }

            foreach ($this->routes as $name => $route) {
                assert($name !== '');
                assert($route instanceof HttpRouteInterface);
                $subMatch = $route->match($request, $nextOffset, $options);
                if ($subMatch instanceof HttpRouteMatch) {
                    if (($match->getLength() + $subMatch->getLength() + $pathOffset) === $pathLength) {
                        return $match->merge($subMatch)->setMatchedRouteName((string) $name);
                    }
                }
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     * @throws Exception\RuntimeException
     */
    #[Override]
    public function assemble(array $params = [], array $options = []): AssembledUrl
    {
        if (count($this->childRoutes) !== 0) {
            $this->addRoutes($this->childRoutes);
            $this->childRoutes = [];
        }

        $options['has_child'] = isset($options['name']);

        if (isset($options['translator']) && ! isset($options['locale']) && isset($params['locale'])) {
            $options['locale'] = $params['locale'];
        }

        $uri    = $this->route->assemble($params, $options);
        $params = array_diff_key($params, array_flip($this->route->getAssembledParams()));

        if (! isset($options['name'])) {
            if (! $this->mayTerminate) {
                throw new Exception\RuntimeException('Part route may not terminate');
            }

            return $uri;
        }

        unset($options['has_child']);
        $options['only_return_path'] = true;

        return $uri->merge(parent::assemble($params, $options));
    }

    /** @inheritDoc */
    #[Override]
    public function getAssembledParams(): array
    {
        // Part routes may not occur as base route of other part routes, so we
        // don't have to return anything here.
        return [];
    }
}
