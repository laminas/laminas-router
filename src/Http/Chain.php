<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use ArrayObject;
use Laminas\Router\Exception;
use Laminas\Router\RouteConfigTrait;
use Laminas\Router\RoutePluginManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Traversable;

use function array_diff_key;
use function array_flip;
use function array_reverse;
use function count;
use function strlen;

final class Chain extends TreeRouteStack implements HttpRouteInterface
{
    use RouteConfigTrait;

    /**
     * Chain routes.
     *
     * @var iterable<string, HttpRouteInterface>|null
     */
    protected ?array $chainRoutes = null;

    /**
     * List of assembled parameters.
     */
    protected array $assembledParams = [];

    /**
     * Create a new part route.
     *
     * @param ArrayObject<string, HttpRouteInterface>|null $prototypes
     */
    public function __construct(array $routes, RoutePluginManager $routePlugins, ?ArrayObject $prototypes = null)
    {
        parent::__construct($routePlugins);

        /** @psalm-var array<string, HttpRouteInterface> $routes */
        $this->chainRoutes = array_reverse($routes);
        if ($prototypes !== null) {
            $this->prototypes = $prototypes;
        }
    }

    /**
     * @inheritDoc
     * @throws Exception\InvalidArgumentException
     */
    public static function factory(iterable $options = []): Chain
    {
        $options = self::processRouteOptions(
            $options,
            ['routes', 'route_plugins'],
            ['prototypes' => null],
        );

        if ($options['routes'] instanceof Traversable) {
            $options['routes'] = self::iteratorToArray($options['routes']);
        }

        return new Chain(
            $options['routes'],
            $options['route_plugins'],
            $options['prototypes']
        );
    }

    /**
     * @inheritDoc
     * @throws ContainerExceptionInterface
     */
    public function match(
        ServerRequestInterface $request,
        ?int $pathOffset = null,
        array $options = []
    ): ?RouteMatch {
        if ($pathOffset === null) {
            $mustTerminate = true;
            $pathOffset    = 0;
        } else {
            $mustTerminate = false;
        }

        if ($this->chainRoutes !== null) {
            $this->addRoutes($this->chainRoutes);
            $this->chainRoutes = null;
        }

        $match      = new RouteMatch([]);
        $pathLength = strlen($request->getUri()->getPath());

        foreach ($this->routes as $route) {
            $subMatch = $route->match($request, $pathOffset, $options);
            if ($subMatch === null) {
                return null;
            }

            $match->merge($subMatch);
            $pathOffset += $subMatch->getLength();
        }

        if ($mustTerminate && $pathOffset !== $pathLength) {
            return null;
        }

        return $match;
    }

    /**
     * @inheritDoc
     * @throws ContainerExceptionInterface
     */
    public function assemble(array $params = [], array $options = []): string
    {
        if ($this->chainRoutes !== null) {
            $this->addRoutes($this->chainRoutes);
            $this->chainRoutes = null;
        }

        $this->assembledParams = [];

        $count = count($this->routes);
        $index = 0;
        $path  = '';

        foreach ($this->routes as $route) {
            $index++;
            $chainOptions              = $options;
            $chainOptions['has_child'] = ($options['has_child'] ?? false) === true || $index !== $count;

            $path  .= $route->assemble($params, $chainOptions);
            $params = array_diff_key($params, array_flip($route->getAssembledParams()));

            $this->assembledParams += $route->getAssembledParams();
        }

        return $path;
    }

    /** @inheritDoc */
    public function getAssembledParams(): array
    {
        return $this->assembledParams;
    }
}
