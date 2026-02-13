<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use Laminas\Router\Http\Chain;
use Laminas\Router\Http\HttpRouteInterface;
use Laminas\Router\Http\RouteMatch;
use Laminas\Router\Http\Segment;
use Laminas\Router\RoutePluginManager;
use Laminas\ServiceManager\ServiceManager;
use LaminasTest\Router\FactoryTester;
use LaminasTest\Router\TestAsset\MockServerRequest;
use LaminasTest\Router\TestAsset\MockUri;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;

use function strlen;
use function strpos;

final class ChainTest extends TestCase
{
    public static function getRoute(): Chain
    {
        /** @var RoutePluginManager<HttpRouteInterface> $routePlugins */
        $routePlugins = new RoutePluginManager(new ServiceManager());

        return new Chain(
            [
                [
                    'type'    => Segment::class,
                    'options' => [
                        'route'    => '/:controller',
                        'defaults' => [
                            'controller' => 'foo',
                        ],
                    ],
                ],
                [
                    'type'    => Segment::class,
                    'options' => [
                        'route'    => '/:bar',
                        'defaults' => [
                            'bar' => 'bar',
                        ],
                    ],
                ],
            ],
            $routePlugins
        );
    }

    public static function getRouteWithOptionalParam(): Chain
    {
        /** @var RoutePluginManager<HttpRouteInterface> $routePlugins */
        $routePlugins = new RoutePluginManager(new ServiceManager());

        return new Chain(
            [
                [
                    'type'    => Segment::class,
                    'options' => [
                        'route'    => '/:controller',
                        'defaults' => [
                            'controller' => 'foo',
                        ],
                    ],
                ],
                [
                    'type'    => Segment::class,
                    'options' => [
                        'route'    => '[/:bar]',
                        'defaults' => [
                            'bar' => 'bar',
                        ],
                    ],
                ],
            ],
            $routePlugins
        );
    }

    /**
     * @psalm-return array<string, array{
     *     0: Chain,
     *     1: string,
     *     2: null|int,
     *     3: array<string, string>
     * }>
     */
    public static function routeProvider(): array
    {
        return [
            'simple-match'                  => [
                self::getRoute(),
                '/foo/bar',
                null,
                [
                    'controller' => 'foo',
                    'bar'        => 'bar',
                ],
            ],
            'offset-skips-beginning'        => [
                self::getRoute(),
                '/baz/foo/bar',
                4,
                [
                    'controller' => 'foo',
                    'bar'        => 'bar',
                ],
            ],
            'parameters-are-used-only-once' => [
                self::getRoute(),
                '/foo/baz',
                null,
                [
                    'controller' => 'foo',
                    'bar'        => 'baz',
                ],
            ],
            'optional-parameter'            => [
                self::getRouteWithOptionalParam(),
                '/foo/baz',
                null,
                [
                    'controller' => 'foo',
                    'bar'        => 'baz',
                ],
            ],
            'optional-parameter-empty'      => [
                self::getRouteWithOptionalParam(),
                '/foo',
                null,
                [
                    'controller' => 'foo',
                    'bar'        => 'bar',
                ],
            ],
        ];
    }

    /**
     * @throws ContainerExceptionInterface
     */
    #[DataProvider('routeProvider')]
    public function testMatching(Chain $route, string $path, ?int $offset, ?array $params = null): void
    {
        $request = new MockServerRequest(new MockUri('https://example.com' . $path));
        $match   = $route->match($request, $offset);

        if ($params === null) {
            $this->assertNull($match);
        } else {
            $this->assertInstanceOf(RouteMatch::class, $match);

            if ($offset === null) {
                $this->assertEquals(strlen($path), $match->getLength());
            }

            foreach ($params as $key => $value) {
                $this->assertEquals($value, $match->getParam($key));
            }
        }
    }

    #[DataProvider('routeProvider')]
    public function testAssembling(Chain $route, string $path, ?int $offset, ?array $params = null): void
    {
        if ($params === null) {
            // Data which will not match are not tested for assembling.
            return;
        }

        $result = $route->assemble($params);

        if ($offset !== null) {
            $this->assertEquals($offset, strpos($path, $result, $offset));
        } else {
            $this->assertEquals($path, $result);
        }
    }

    public function testFactory(): void
    {
        $tester = new FactoryTester($this);
        $tester->testFactory(
            Chain::class,
            [
                'routes'        => 'Missing "routes" option',
                'route_plugins' => 'Missing "route_plugins" option',
            ],
            [
                'routes'        => [],
                'route_plugins' => new RoutePluginManager(new ServiceManager()),
            ]
        );
    }
}
