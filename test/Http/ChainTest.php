<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use Laminas\Diactoros\Request;
use Laminas\Diactoros\Uri;
use Laminas\Router\Http\Chain;
use Laminas\Router\Http\HttpRouteMatch;
use Laminas\Router\Http\Segment;
use LaminasTest\Router\BuilderTester;
use LaminasTest\Router\RouteBuilderContainerTestHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function strlen;
use function strpos;

final class ChainTest extends TestCase
{
    public static function getRoute(): Chain
    {
        $routePlugins = RouteBuilderContainerTestHelper::create();

        return new Chain(
            $routePlugins,
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
        $routePlugins = RouteBuilderContainerTestHelper::create();

        return new Chain(
            $routePlugins,
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
            $this->assertEquals($offset, strpos($path, $result->toString(), $offset));
        } else {
            $this->assertEquals($path, $result->toString());
        }
    }

    public function testMatchRejectsTrailingPathWhenNoOffset(): void
    {
        $request = (new Request())->withUri(new Uri('http://example.com/foo/bar/extra'));

        $this->assertNull(self::getRoute()->match($request));
    }

    public function testMatchWithZeroOffsetAllowsPartialPath(): void
    {
        $request = (new Request())->withUri(new Uri('http://example.com/foo/bar/extra'));

        $this->assertInstanceOf(HttpRouteMatch::class, self::getRoute()->match($request, 0));
    }

    public function testAssemblingOmitsOptionalTrailingSegmentWithoutParam(): void
    {
        $this->assertSame(
            '/foo',
            self::getRouteWithOptionalParam()->assemble(['controller' => 'foo'])->toString()
        );
    }

    public function testAssemblingPropagatesHasChildOptionToLastSegment(): void
    {
        $routePlugins = RouteBuilderContainerTestHelper::create();
        $route        = new Chain(
            $routePlugins,
            [
                [
                    'type'    => Segment::class,
                    'options' => [
                        'route'    => '/:controller',
                        'defaults' => ['controller' => 'foo'],
                    ],
                ],
                [
                    'type'    => Segment::class,
                    'options' => [
                        'route'    => '[/:bar]',
                        'defaults' => ['bar' => 'bar'],
                    ],
                ],
            ]
        );

        $this->assertSame('/foo/bar', $route->assemble([], ['has_child' => true])->toString());
    }

    public function testAssemblingStripsConsumedParamsBetweenSegments(): void
    {
        $routePlugins = RouteBuilderContainerTestHelper::create();
        $route        = new Chain(
            $routePlugins,
            [
                [
                    'type'    => Segment::class,
                    'options' => [
                        'route'    => '/:id',
                        'defaults' => ['id' => '1'],
                    ],
                ],
                [
                    'type'    => Segment::class,
                    'options' => [
                        'route'    => '/:id',
                        'defaults' => ['id' => '2'],
                    ],
                ],
            ]
        );

        $this->assertSame('/x/2', $route->assemble(['id' => 'x'])->toString());
    }

    public function testGetAssembledParams(): void
    {
        $route = self::getRoute();
        $this->assertSame(
            ['controller', 'bar'],
            $route->assemble(['controller' => 'foo', 'bar' => 'baz'])->assembledParams,
        );
    }

    public function testBuilder(): void
    {
        $tester = new BuilderTester();
        $tester->testBuilder(
            Chain::class,
            [
                'routes' => 'Missing "routes" in options array',
            ],
            [
                'routes' => [],
            ]
        );
    }
}
