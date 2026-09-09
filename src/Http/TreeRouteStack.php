<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\AssembledUrl;
use Laminas\Router\Exception;
use Laminas\Router\Exception\RuntimeException;
use Laminas\Router\RouteBuilderContainerInterface;
use Laminas\Router\RouteInterface;
use Laminas\Router\RouteMatchInterface;
use Laminas\Router\SimpleRouteStack;
use Override;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

use function array_key_exists;
use function array_merge;
use function assert;
use function explode;
use function is_array;
use function is_string;
use function sprintf;
use function strlen;

/**
 * Tree search implementation.
 *
 * @template TRoute of HttpRouteInterface
 * @template-extends SimpleRouteStack<TRoute>
 */
readonly class TreeRouteStack extends SimpleRouteStack
{
    /**
     * @param array<non-empty-string|array-key, array|TRoute> $routes
     * @param array<string, string|int|float|null> $defaultParams
     */
    public function __construct(
        RouteBuilderContainerInterface $routeBuilderContainer,
        array $routes = [],
        array $defaultParams = [],
        protected int|null $priority = null,
    ) {
        parent::__construct($routeBuilderContainer, $routes, $defaultParams);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function addRoute(string $name, array|RouteInterface $route, ?int $priority = null): void
    {
        if ($route instanceof RouteInterface && ! $route instanceof HttpRouteInterface) {
            throw new Exception\InvalidArgumentException(
                'Only HttpRouteInterface instances or array specifications are allowed.'
            );
        }
        if (is_array($route)) {
            $route = $this->routeFromArray($name, $route);
        }

        assert($route instanceof HttpRouteInterface);

        parent::addRoute($name, $route, $priority);
    }

    /**
     * @inheritDoc
     * @param  array $specs
     * @return TRoute
     * @throws Exception\InvalidArgumentException When route definition is not an array nor traversable.
     * @throws Exception\InvalidArgumentException When chain routes are not an array nor traversable.
     * @throws Exception\RuntimeException         When a generated routes does not implement the HTTP route interface.
     */
    #[Override]
    final protected function routeFromArray(string $name, array $specs): RouteInterface
    {
        $route = $this->buildChainRoute($name, $specs);

        if (! $route instanceof HttpRouteInterface) {
            throw new Exception\RuntimeException('Given route does not implement HTTP route interface');
        }

        if (isset($specs['child_routes'])) {
            /** @psalm-var array<string, string|int|float|null> $defaults */
            $defaults = $specs['defaults'] ?? [];
            $route    = $this->routeBuilderContainer->build([
                'type'          => Part::class,
                'name'          => $name,
                'route'         => $route,
                'may_terminate' => isset($specs['may_terminate']) && $specs['may_terminate'] === true,
                'child_routes'  => $specs['child_routes'],
                'priority'      => $route->getPriority(),
                'defaults'      => array_merge($defaults, $this->defaultParams),
            ]);
        }

        /** @psalm-var TRoute $route */
        return $route;
    }

    /**
     * @inheritDoc
     * @param int|null $pathOffset
     */
    #[Override]
    public function match(
        RequestInterface $request,
        int|null $pathOffset = null,
        array $options = []
    ): ?RouteMatchInterface {
        $baseUrlLength = $pathOffset;
        $pathLength    = null;

        if ($baseUrlLength !== null) {
            $pathLength = strlen($request->getUri()->getPath()) - $baseUrlLength;
        }

        foreach ($this->routes->getAsArray() as $route) {
            $match = $route->match($request, $baseUrlLength, $options);
            if ($match instanceof HttpRouteMatch && ($pathLength === null || $match->getLength() === $pathLength)) {
                return $match; //->setDefaults($this->defaultParams);
            }
        }

        return null;
    }

    /**
     * @return array{name : non-empty-string, child : string|null}
     */
    private function getRouteName(array $options): array
    {
        $name = $options['name'] ?? '';
        if (! is_string($name) || $name === '') {
            throw new Exception\InvalidArgumentException('Missing "name" option');
        }

        $names = explode('/', $name, 2);

        if ($names[0] === '') {
            throw new Exception\RuntimeException('Invalid route name');
        }

        return [
            'name'  => $names[0],
            'child' => $names[1] ?? null,
        ];
    }

    /**
     * @inheritDoc
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    #[Override]
    public function assemble(array $params = [], array $options = []): AssembledUrl
    {
        $names = $this->getRouteName($options);
        $route = $this->routes->get($names['name']);

        if (! $route) {
            throw new Exception\RuntimeException(sprintf('Route with name "%s" not found', $names['name']));
        }

        if ($names['child'] !== null) {
            if (! $route instanceof TreeRouteStack) {
                throw new Exception\RuntimeException(sprintf(
                    'Route with name "%s" does not have child routes',
                    $names['name']
                ));
            }
            $options['name'] = $names['child'];
        } else {
            unset($options['name']);
        }

        $assembledUrl = $route->assemble(array_merge($this->defaultParams, $params), $options);

        if (isset($options['only_return_path']) && $options['only_return_path'] === true) {
            return $assembledUrl;
        }

        $forceCanonical = isset($options['force_canonical']) && $options['force_canonical'] === true;
        $fallbackUri    = isset($options['uri']) && $options['uri'] instanceof UriInterface ? $options['uri'] : null;

        if ($forceCanonical && $fallbackUri === null) {
            throw new RuntimeException('Request URI has not been set');
        }

        $normalizeNonEmpty = static fn(?string $value): ?string => $value === '' ? null : $value;
        $resolvedPort      = $assembledUrl->port ?? $fallbackUri?->getPort();
        $resolvedScheme    = $assembledUrl->scheme ?? $normalizeNonEmpty($fallbackUri?->getScheme());
        $resolvedHost      = $assembledUrl->host ?? $normalizeNonEmpty($fallbackUri?->getHost());

        if ($resolvedHost !== null && $resolvedScheme === null) {
            throw new RuntimeException('Request URI has not been set');
        }

        $query    = $assembledUrl->query;
        $fragment = $assembledUrl->fragment;

        if (array_key_exists('query', $options) && is_array($options['query'])) {
            /** @var array<string, scalar> $query */
            $query = $options['query'];
        }

        if (array_key_exists('fragment', $options) && is_string($options['fragment'])) {
            $fragment = $options['fragment'];
        }

        return new AssembledUrl(
            $assembledUrl->path,
            $query,
            $assembledUrl->assembledParams,
            $resolvedHost,
            $resolvedScheme,
            $fragment,
            $forceCanonical || $assembledUrl->host !== null || $assembledUrl->scheme !== null,
            $resolvedPort,
        );
    }

    #[Override]
    public function getPriority(): ?int
    {
        return $this->priority;
    }

    private function buildChainRoute(string $name, array $specs): RouteInterface
    {
        if (! isset($specs['chain_routes'])) {
            return parent::routeFromArray($name, $specs);
        }

        if (! is_array($specs['chain_routes'])) {
            throw new Exception\InvalidArgumentException('Chain routes must be an array');
        }

        $chainRoutes = array_merge([$specs], $specs['chain_routes']);
        if (isset($chainRoutes[0]['chain_routes'])) {
            unset($chainRoutes[0]['chain_routes']);
        }

        if (isset($specs['child_routes']) && isset($chainRoutes[0]['child_routes'])) {
            unset($chainRoutes[0]['child_routes']);
        }

        return $this->routeBuilderContainer->build([
            'type'     => Chain::class,
            'routes'   => $chainRoutes,
            'priority' => $specs['priority'] ?? null,
        ]);
    }
}
