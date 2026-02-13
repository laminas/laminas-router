<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use ArrayObject;
use Laminas\Router\Exception\InvalidArgumentException;
use Laminas\Router\Exception\RuntimeException;
use Laminas\Router\Http\HttpRouteInterface;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Part;
use Laminas\Router\Http\RouteMatch;
use Laminas\Router\Http\Segment;
use Laminas\Router\RouteInvokableFactory;
use Laminas\Router\RoutePluginManager;
use Laminas\ServiceManager\ServiceManager;
use LaminasTest\Router\FactoryTester;
use LaminasTest\Router\TestAsset\MockServerRequest;
use LaminasTest\Router\TestAsset\MockUri;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;

use function strlen;
use function strpos;

final class PartTest extends TestCase
{
    /** @return RoutePluginManager<HttpRouteInterface> */
    public static function getRoutePlugins(): RoutePluginManager
    {
        return new RoutePluginManager(new ServiceManager(), [
            'aliases'   => [
                'literal' => Literal::class,
                'Literal' => Literal::class,
                'part'    => Part::class,
                'Part'    => Part::class,
                'segment' => Segment::class,
                'Segment' => Segment::class,
            ],
            'factories' => [
                Literal::class => RouteInvokableFactory::class,
                Part::class    => RouteInvokableFactory::class,
                Segment::class => RouteInvokableFactory::class,
            ],
        ]);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public static function getRoute(): Part
    {
        return new Part(
            [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/foo',
                    'defaults' => [
                        'controller' => 'foo',
                    ],
                ],
            ],
            true,
            self::getRoutePlugins(),
            [
                'bar' => [
                    'type'    => Literal::class,
                    'options' => [
                        'route'    => '/bar',
                        'defaults' => [
                            'controller' => 'bar',
                        ],
                    ],
                ],
                'baz' => [
                    'type'         => Literal::class,
                    'options'      => [
                        'route' => '/baz',
                    ],
                    'child_routes' => [
                        'bat' => [
                            'type'          => Segment::class,
                            'options'       => [
                                'route' => '/:controller',
                            ],
                            'may_terminate' => true,
                        ],
                    ],
                ],
                'bat' => [
                    'type'          => Segment::class,
                    'options'       => [
                        'route'    => '/bat[/:foo]',
                        'defaults' => [
                            'foo' => 'bar',
                        ],
                    ],
                    'may_terminate' => true,
                    'child_routes'  => [
                        'literal'  => [
                            'type'    => Literal::class,
                            'options' => [
                                'route' => '/bar',
                            ],
                        ],
                        'optional' => [
                            'type'    => Segment::class,
                            'options' => [
                                'route' => '/bat[/:bar]',
                            ],
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * @psalm-return array<string, array{
     *     0: Part,
     *     1: string,
     *     2: null|int,
     *     3: null|string,
     *     4: null|array<string, string>
     * }>
     */
    public static function routeProvider(): array
    {
        return [
            'simple-match'                                     => [
                self::getRoute(),
                '/foo',
                null,
                null,
                ['controller' => 'foo'],
            ],
            'offset-skips-beginning'                           => [
                self::getRoute(),
                '/bar/foo',
                4,
                null,
                ['controller' => 'foo'],
            ],
            'simple-child-match'                               => [
                self::getRoute(),
                '/foo/bar',
                null,
                'bar',
                ['controller' => 'bar'],
            ],
            'offset-does-not-enable-partial-matching'          => [
                self::getRoute(),
                '/foo/foo',
                null,
                null,
                null,
            ],
            'offset-does-not-enable-partial-matching-in-child' => [
                self::getRoute(),
                '/foo/bar/baz',
                null,
                null,
                null,
            ],
            'non-terminating-part-does-not-match'              => [
                self::getRoute(),
                '/foo/baz',
                null,
                null,
                null,
            ],
            'child-of-non-terminating-part-does-match'         => [
                self::getRoute(),
                '/foo/baz/bat',
                null,
                'baz/bat',
                ['controller' => 'bat'],
            ],
            'optional-parameters-are-dropped-without-child'    => [
                self::getRoute(),
                '/foo/bat',
                null,
                'bat',
                ['foo' => 'bar'],
            ],
            'optional-parameters-are-not-dropped-with-child'   => [
                self::getRoute(),
                '/foo/bat/bar/bar',
                null,
                'bat/literal',
                ['foo' => 'bar'],
            ],
            'optional-parameters-not-required-in-last-part'    => [
                self::getRoute(),
                '/foo/bat/bar/bat',
                null,
                'bat/optional',
                ['foo' => 'bar'],
            ],
        ];
    }

    #[DataProvider('routeProvider')]
    public function testMatching(
        Part $route,
        string $path,
        ?int $offset,
        ?string $routeName,
        ?array $params = null
    ): void {
        $request = new MockServerRequest(new MockUri('http://example.com' . $path));
        $match   = $route->match($request, $offset);

        if ($params === null) {
            $this->assertNull($match);
        } else {
            $this->assertInstanceOf(RouteMatch::class, $match);

            if ($offset === null) {
                $this->assertEquals(strlen($path), $match->getLength());
            }

            $this->assertEquals($routeName, $match->getMatchedRouteName());

            foreach ($params as $key => $value) {
                $this->assertEquals($value, $match->getParam($key));
            }
        }
    }

    #[DataProvider('routeProvider')]
    public function testAssembling(Part $route, string $path, ?int $offset, ?string $routeName, ?array $params = null)
    {
        if ($params === null) {
            // Data which will not match are not tested for assembling.
            $this->expectNotToPerformAssertions();

            return;
        }

        $result = $route->assemble($params, ['name' => $routeName]);

        if ($offset !== null) {
            $this->assertEquals($offset, strpos($path, (string) $result, $offset));
        } else {
            $this->assertEquals($path, $result);
        }
    }

    public function testAssembleNonTerminatedRoute(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Part route may not terminate');
        self::getRoute()->assemble([], ['name' => 'baz']);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testBaseRouteMayNotBePartRoute(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Base route may not be a part route');

        new Part(self::getRoute(), true, new RoutePluginManager(new ServiceManager()));
    }

    public function testGetAssembledParams(): void
    {
        $route = self::getRoute();
        $route->assemble(['controller' => 'foo'], ['name' => 'baz/bat']);

        $this->assertEquals([], $route->getAssembledParams());
    }

    public function testFactory(): void
    {
        $tester = new FactoryTester($this);
        $tester->testFactory(
            Part::class,
            [
                'route'         => 'Missing "route" option',
                'route_plugins' => 'Missing "route_plugins" option',
            ],
            [
                'route'         => new Literal('/foo'),
                'route_plugins' => self::getRoutePlugins(),
            ]
        );
    }

    #[Group('Laminas-105')]
    public function testFactoryShouldAcceptTraversableChildRoutes(): void
    {
        $children = new ArrayObject([
            'create' => [
                'type'    => 'Literal',
                'options' => [
                    'route'    => 'create',
                    'defaults' => [
                        'controller' => 'user-admin',
                        'action'     => 'edit',
                    ],
                ],
            ],
        ]);
        $options  = [
            'route'         => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/admin/users',
                    'defaults' => [
                        'controller' => 'Admin\\UserController',
                        'action'     => 'index',
                    ],
                ],
            ],
            'route_plugins' => self::getRoutePlugins(),
            'may_terminate' => true,
            'child_routes'  => $children,
        ];

        $route = Part::factory($options);
        $this->assertInstanceOf(Part::class, $route);
    }

    #[Group('3711')]
    public function testPartRouteMarkedAsMayTerminateCanMatchWhenQueryStringPresent(): void
    {
        $options = [
            'route'         => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/resource',
                    'defaults' => [
                        'controller' => 'ResourceController',
                        'action'     => 'resource',
                    ],
                ],
            ],
            'route_plugins' => self::getRoutePlugins(),
            'may_terminate' => true,
            'child_routes'  => [
                'child' => [
                    'type'    => Literal::class,
                    'options' => [
                        'route'    => '/child',
                        'defaults' => [
                            'action' => 'child',
                        ],
                    ],
                ],
            ],
        ];

        $route   = Part::factory($options);
        $request = new MockServerRequest(new MockUri('http://example.com/resource?foo=bar'));

        $match = $route->match($request);
        $this->assertInstanceOf(\Laminas\Router\RouteMatch::class, $match);
        $this->assertEquals('resource', $match->getParam('action'));
    }
}
