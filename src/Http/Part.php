<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use Laminas\Router\AssembledUrl;
use Laminas\Router\Exception;
use Laminas\Router\RouteBuilderContainerInterface;
use Laminas\Router\RouteMatchInterface;
use Override;
use Psr\Http\Message\RequestInterface;

use function array_diff_key;
use function array_flip;
use function assert;
use function is_array;
use function is_string;
use function strlen;

/**
 * @template TRoute of HttpRouteInterface
 * @template-extends TreeRouteStack<TRoute>
 */
final readonly class Part extends TreeRouteStack implements HttpRouteInterface
{
    /**
     * RouteInterface to match.
     *
     * @var TRoute
     */
    private HttpRouteInterface $route;

    /**
     * Create a new part route.
     *
     * @param TRoute|array           $routes
     * @param array<non-empty-string, array|TRoute> $childRoutes
     * @param array<string, string|int|float|null> $defaultParams
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(
        RouteBuilderContainerInterface $routeBuilderContainer,
        HttpRouteInterface|array $routes = [],
        array $defaultParams = [],
        ?int $priority = null,
        /**
         * Whether the route may terminate.
         */
        private bool $mayTerminate = false,
        array $childRoutes = [],
    ) {
        parent::__construct($routeBuilderContainer, defaultParams: $defaultParams, priority: $priority);

        if (is_array($routes)) {
            $routes = $this->routeFromArray('', $routes);
        }

        if ($routes instanceof self) {
            throw new Exception\InvalidArgumentException('Base route may not be a part route');
        }

        $this->route = $routes;

        if ($childRoutes !== []) {
            $this->addRoutes($childRoutes);
        }
    }

    /** @inheritDoc */
    #[Override]
    public function match(
        RequestInterface $request,
        int|null $pathOffset = null,
        array $options = []
    ): RouteMatchInterface|null {
        $pathOffset ??= 0;
        $match        = $this->route->match($request, $pathOffset, $options);

        assert($match instanceof HttpRouteMatch || $match === null);

        if ($match !== null) {
            $nextOffset = $pathOffset + $match->getLength();
            $pathLength = strlen($request->getUri()->getPath());

            if ($this->mayTerminate && $nextOffset === $pathLength) {
                return $match;
            }

            if ( ! isset($options['locale'])) {
                /** @var mixed $locale */
                $locale = $match->getParam('locale');
                if (is_string($locale)) {
                    $options['locale'] = $locale;
                }
            }

            foreach ($this->routes->getAsArray() as $route) {
                $subMatch = $route->match($request, $nextOffset, $options);
                if ($subMatch instanceof RouteMatchInterface) {
                    if (($match->getLength() + $subMatch->getLength() + $pathOffset) === $pathLength) {
                        return $match->merge($subMatch);
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
        $options['has_child'] = isset($options['name']);

        if (isset($options['translator']) && ! isset($options['locale']) && isset($params['locale'])) {
            $options['locale'] = $params['locale'];
        }

        $uri    = $this->route->assemble($params, $options);
        $params = array_diff_key($params, array_flip($uri->assembledParams));

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
}
