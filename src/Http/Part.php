<?php

declare(strict_types=1);

namespace Laminas\Router\Http;

use ArrayObject;
use Laminas\Router\Exception;
use Laminas\Router\RouteConfigTrait;
use Laminas\Router\RoutePluginManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;

use function array_diff_key;
use function array_flip;
use function is_array;
use function is_iterable;
use function strlen;

final class Part extends TreeRouteStack implements HttpRouteInterface
{
    use RouteConfigTrait;

    /**
     * Create a new part route.
     *
     * @param ?iterable<string, HttpRouteInterface|iterable> $childRoutes
     * @param ?ArrayObject<string, HttpRouteInterface> $prototypes
     * @throws ContainerExceptionInterface
     */
    public function __construct(
        protected iterable|HttpRouteInterface $route,
        private readonly bool $mayTerminate,
        RoutePluginManager $routePluginManager,
        protected ?array $childRoutes = null,
        ?ArrayObject $prototypes = null
    ) {
        parent::__construct($routePluginManager);

        if (! $route instanceof HttpRouteInterface) {
            $this->route = $this->routeFromSpec($route);
        }

        if ($this->route instanceof self) {
            throw new Exception\InvalidArgumentException('Base route may not be a part route');
        }

        if ($prototypes !== null) {
            $this->prototypes = $prototypes;
        }
    }

    /**
     * @inheritDoc
     * @throws Exception\InvalidArgumentException
     * @throws ContainerExceptionInterface
     */
    public static function factory(iterable $options = []): Part
    {
        $options = self::processRouteOptions(
            $options,
            ['route', 'route_plugins'],
            ['prototypes' => null, 'may_terminate' => false, 'child_routes' => null],
        );

        $childRoutes = null;
        if (is_iterable($options['child_routes'] ?? null)) {
            /** @var iterable<string, HttpRouteInterface|iterable> $childRoutes */
            $childRoutes = self::iteratorToArray($options['child_routes']);
        }

        // Ensure prototypes is ArrayObject or null
        $prototypes = $options['prototypes'];
        if (is_array($prototypes)) {
            $prototypes = new ArrayObject($prototypes);
        }

        return new Part(
            $options['route'],
            $options['may_terminate'],
            $options['route_plugins'],
            $childRoutes,
            $prototypes
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
            $pathOffset = 0;
        }

        $match = $this->route->match($request, $pathOffset, $options);

        if ($match !== null) {
            if ($this->childRoutes !== null) {
                $this->addRoutes($this->childRoutes);
                $this->childRoutes = null;
            }

            $nextOffset = $pathOffset + $match->getLength();

            $pathLength = strlen($request->getUri()->getPath());

            if ($this->mayTerminate && $nextOffset === $pathLength) {
                return $match;
            }

            if (
                isset($options['translator'])
                && ! isset($options['locale'])
                && null !== ($locale = $match->getParam('locale'))
            ) {
                $options['locale'] = $locale;
            }

            foreach ($this->routes as $name => $route) {
                if (($subMatch = $route->match($request, $nextOffset, $options)) instanceof RouteMatch) {
                    if ($match->getLength() + $subMatch->getLength() + $pathOffset === $pathLength) {
                        return $match->merge($subMatch)->setMatchedRouteName($name);
                    }
                }
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     * @throws Exception\RuntimeException
     * @throws ContainerExceptionInterface
     */
    public function assemble(array $params = [], array $options = []): mixed
    {
        if ($this->childRoutes !== null) {
            $this->addRoutes($this->childRoutes);
            $this->childRoutes = null;
        }

        $options['has_child'] = isset($options['name']);

        if (isset($options['translator']) && ! isset($options['locale']) && isset($params['locale'])) {
            $options['locale'] = $params['locale'];
        }

        $path   = $this->route->assemble($params, $options);
        $params = array_diff_key($params, array_flip($this->route->getAssembledParams()));

        if (! isset($options['name'])) {
            if (! $this->mayTerminate) {
                throw new Exception\RuntimeException('Part route may not terminate');
            } else {
                return $path;
            }
        }

        unset($options['has_child']);
        $options['only_return_path'] = true;

        return $path . parent::assemble($params, $options);
    }

    /** @inheritDoc */
    public function getAssembledParams(): array
    {
        // Part routes may not occur as base route of other part routes, so we
        // don't have to return anything here.
        return [];
    }
}
