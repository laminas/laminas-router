<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use ArrayObject;
use Laminas\Diactoros\Request;
use Laminas\Diactoros\Uri;
use Laminas\Router\Http\Chain;
use Laminas\Router\Http\HttpRouteInterface;
use Laminas\Router\Http\HttpRouteMatch;
use Laminas\Router\Http\Segment;
use Laminas\Router\RoutePluginManager;
use Laminas\ServiceManager\ServiceManager;
use LaminasTest\Router\FactoryTester;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function strlen;
use function strpos;

final class ChainTest extends TestCase
{
    public static function getRoute(): Chain
    {
        $routePlugins = new RoutePluginManager(new ServiceManager());
        /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();

        return new Chain(
            $routePlugins,
            $prototypes,
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
            ]
        );
    }

    public static function getRouteWithOptionalParam(): Chain
    {
        $routePlugins = new RoutePluginManager(new ServiceManager());
        /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();

        return new Chain(
            $routePlugins,
            $prototypes,
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
            ]
        );
    }

    /**
     * @psalm-return array<string, array{
     *     0: Chain,
     *     1: string,
     *     2: null|int,
     *     3: array<non-empty-string, non-empty-string>
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
     * @param array<non-empty-string, non-empty-string>|null $params
     */
    #[DataProvider('routeProvider')]
    public function testMatching(Chain $route, string $path, int|null $offset, ?array $params = null): void
    {
        $request = new Request();
        $request = $request->withUri(new Uri('http://example.com' . $path));
        $match   = $route->match($request, $offset);

        if ($params === null) {
            $this->assertNull($match);
        } else {
            $this->assertInstanceOf(HttpRouteMatch::class, $match);

            if ($offset === null) {
                $this->assertEquals(strlen($path), $match->getLength());
            }

            foreach ($params as $key => $value) {
                $this->assertEquals($value, $match->getParam($key));
            }
        }
    }

    /**
     * @param array<non-empty-string, non-empty-string>|null $params
     */
    #[DataProvider('routeProvider')]
    public function testAssembling(Chain $route, string $path, int|null $offset, ?array $params = null): void
    {
        if ($params === null) {
            // Data which will not match are not tested for assembling.
            return;
        }

        $result = $route->assemble($params);

        if ($offset !== null) {
            $this->assertEquals($offset, strpos($path, (string) $result, $offset));
        } else {
            $this->assertEquals($path, $result);
        }
    }

    public function testFactory(): void
    {
        $tester = new FactoryTester();
        $tester->testFactory(
            Chain::class,
            [
                'routes'        => 'Missing "routes" in options array',
                'route_plugins' => 'Missing "route_plugins" in options array',
            ],
            [
                'routes'        => [],
                'route_plugins' => new RoutePluginManager(new ServiceManager()),
            ]
        );
    }
}
