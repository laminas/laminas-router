<?php

declare(strict_types=1);

namespace Laminas\Router;

use Laminas\Router\Container\RouteContainerInterface;
use Laminas\Router\Container\SimpleRouteContainer;
use Laminas\Router\Container\SimpleRouteContainerInterface;
use Laminas\ServiceManager\ServiceManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;

use function array_merge;
use function is_array;
use function is_iterable;
use function is_string;
use function sprintf;

/**
 * Simple route stack implementation.
 *
 * @extends AbstractRouteStack<SimpleRouteContainerInterface>
 */
class SimpleRouteStack extends AbstractRouteStack
{
    /** @var SimpleRouteContainerInterface */
    protected RouteContainerInterface $routes;

    public function __construct(
        ?RoutePluginManager $routePluginManager = null,
        ?SimpleRouteContainerInterface $routes = null
    ) {
        $this->routePluginManager = $routePluginManager ?? new RoutePluginManager(new ServiceManager());
        $this->routes             = $routes ?? new SimpleRouteContainer();
        $this->init();
    }

    /**
     * @inheritDoc
     * @throws Exception\InvalidArgumentException
     * @throws ContainerExceptionInterface
     */
    public static function factory(iterable $options = []): RouteStackInterface
    {
        if (! is_array($options)) {
            $options = self::iteratorToArray($options);
        }

        $routePluginManager = $options['route_plugins'] ?? null;
        if ($routePluginManager !== null && ! $routePluginManager instanceof RoutePluginManager) {
            throw new Exception\InvalidArgumentException('route_plugins must be an instance of RoutePluginManager');
        }

        $instance = new static($routePluginManager);

        if (is_iterable($options['routes'] ?? null)) {
            $instance->addRoutes($options['routes']);
        }

        if (is_array($options['default_params'] ?? null)) {
            $defaultParams = (array) $options['default_params'];
            $instance->setDefaultParams($defaultParams);
        }

        return $instance;
    }

    /** @inheritDoc */
    public function match(ServerRequestInterface $request): ?RouteMatch
    {
        foreach ($this->routes as $name => $route) {
            if (($match = $route->match($request)) instanceof RouteMatch) {
                $match->setMatchedRouteName($name);

                foreach ($this->defaultParams as $paramName => $value) {
                    if ($match->getParam($paramName) === null) {
                        $match->setParam($paramName, $value);
                    }
                }

                return $match;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    public function assemble(array $params = [], array $options = []): mixed
    {
        $name = $options['name'] ?? null;
        if (! is_string($name)) {
            throw new Exception\InvalidArgumentException('Missing "name" option');
        }

        $route = $this->routes->get($name);

        if (! $route) {
            throw new Exception\RuntimeException(sprintf('Route with name "%s" not found', $name));
        }

        unset($options['name']);

        return $route->assemble(array_merge($this->defaultParams, $params), $options);
    }
}
