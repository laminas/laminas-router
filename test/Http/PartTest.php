<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use ArrayObject;
use Laminas\Http\Request;
use Laminas\Router\Exception\InvalidArgumentException;
use Laminas\Router\Exception\RuntimeException;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Part;
use Laminas\Router\Http\RouteMatch;
use Laminas\Router\Http\Segment;
use Laminas\Router\Http\Wildcard;
use Laminas\Router\RouteInvokableFactory;
use Laminas\Router\RoutePluginManager;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\Parameters;
use Laminas\Stdlib\Request as BaseRequest;
use LaminasTest\Router\FactoryTester;
use PHPUnit\Framework\TestCase;

use function strlen;
use function strpos;

class PartTest extends TestCase
{
    public static function getRoutePlugins(): RoutePluginManager
    {
        return new RoutePluginManager(new ServiceManager(), [
            'aliases'   => [
                'literal'  => Literal::class,
                'Literal'  => Literal::class,
                'part'     => Part::class,
                'Part'     => Part::class,
                'segment'  => Segment::class,
                'Segment'  => Segment::class,
                'wildcard' => Wildcard::class,
                'Wildcard' => Wildcard::class,
                'wildCard' => Wildcard::class,
                'WildCard' => Wildcard::class,
            ],
            'factories' => [
                Literal::class  => RouteInvokableFactory::class,
                Part::class     => RouteInvokableFactory::class,
                Segment::class  => RouteInvokableFactory::class,
                Wildcard::class => RouteInvokableFactory::class,

                // v2 normalized names
                'laminasmvcrouterhttpliteral'  => RouteInvokableFactory::class,
                'laminasmvcrouterhttppart'     => RouteInvokableFactory::class,
                'laminasmvcrouterhttpsegment'  => RouteInvokableFactory::class,
                'laminasmvcrouterhttpwildcard' => RouteInvokableFactory::class,
            ],
        ]);
    }

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
                            'child_routes'  => [
                                'wildcard' => [
                                    'type' => Wildcard::class,
                                ],
                            ],
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

    public static function getRouteAlternative(): Part
    {
        return new Part(
            [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/[:controller[/:action]]',
                    'defaults' => [
                        'controller' => 'fo-fo',
                        'action'     => 'index',
                    ],
                ],
            ],
            true,
            self::getRoutePlugins(),
            [
                'wildcard' => [
                    'type'    => Wildcard::class,
                    'options' => [
                        'key_value_delimiter' => '/',
                        'param_delimiter'     => '/',
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
            'parameters-are-used-only-once'                    => [
                self::getRoute(),
                '/foo/baz/wildcard/foo/bar',
                null,
                'baz/bat/wildcard',
                ['controller' => 'wildcard', 'foo' => 'bar'],
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
            'simple-match'                                     => [
                self::getRouteAlternative(),
                '/',
                null,
                null,
                [
                    'controller' => 'fo-fo',
                    'action'     => 'index',
                ],
            ],
            'match-wildcard'                                   => [
                self::getRouteAlternative(),
                '/fo-fo/index/param1/value1',
                null,
                'wildcard',
                [
                    'controller' => 'fo-fo',
                    'action'     => 'index',
                    'param1'     => 'value1',
                ],
            ],
            /*
            'match-query' => array(
                self::getRouteAlternative(),
                '/fo-fo/index?param1=value1',
                0,
                'query',
                array(
                    'controller' => 'fo-fo',
                    'action' => 'index'
                )
            )
            */
        ];
    }

    /**
     * @dataProvider routeProvider
     * @param        string   $path
     * @param        int|null $offset
     * @param        string   $routeName
     */
    public function testMatching(Part $route, $path, $offset, $routeName, ?array $params = null)
    {
        $request = new Request();
        $request->setUri('http://example.com' . $path);
        $match = $route->match($request, $offset);

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

    /**
     * @dataProvider routeProvider
     * @param        string   $path
     * @param        int|null $offset
     * @param        string   $routeName
     */
    public function testAssembling(Part $route, $path, $offset, $routeName, ?array $params = null)
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

    public function testAssembleNonTerminatedRoute()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Part route may not terminate');
        self::getRoute()->assemble([], ['name' => 'baz']);
    }

    public function testBaseRouteMayNotBePartRoute()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Base route may not be a part route');

        new Part(self::getRoute(), true, new RoutePluginManager(new ServiceManager()));
    }

    public function testNoMatchWithoutUriMethod()
    {
        $route   = self::getRoute();
        $request = new BaseRequest();

        $this->assertNull($route->match($request));
    }

    public function testGetAssembledParams()
    {
        $route = self::getRoute();
        $route->assemble(['controller' => 'foo'], ['name' => 'baz/bat']);

        $this->assertEquals([], $route->getAssembledParams());
    }

    public function testFactory()
    {
        $tester = new FactoryTester($this);
        $tester->testFactory(
            Part::class,
            [
                'route'         => 'Missing "route" in options array',
                'route_plugins' => 'Missing "route_plugins" in options array',
            ],
            [
                'route'         => new Literal('/foo'),
                'route_plugins' => self::getRoutePlugins(),
            ]
        );
    }

    /**
     * @group Laminas-105
     */
    public function testFactoryShouldAcceptTraversableChildRoutes()
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
                        'controller' => 'Admin\UserController',
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

    /**
     * @group 3711
     */
    public function testPartRouteMarkedAsMayTerminateCanMatchWhenQueryStringPresent()
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
        $request = new Request();
        $request->setUri('http://example.com/resource?foo=bar');
        $query = new Parameters(['foo' => 'bar']);
        $request->setQuery($query);
        $query = $request->getQuery();

        $match = $route->match($request);
        $this->assertInstanceOf(\Laminas\Router\RouteMatch::class, $match);
        $this->assertEquals('resource', $match->getParam('action'));
    }

    /**
     * @group 3711
     */
    public function testPartRouteMarkedAsMayTerminateButWithQueryRouteChildWillMatchChildRoute()
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
        ];

        $route   = Part::factory($options);
        $request = new Request();
        $request->setUri('http://example.com/resource?foo=bar');
        $query = new Parameters(['foo' => 'bar']);
        $request->setQuery($query);
        $query = $request->getQuery();

        /*
        $match = $route->match($request);
        $this->assertInstanceOf(\Laminas\Router\RouteMatch::class, $match);
        $this->assertEquals('string', $match->getParam('query'));
        */
    }
}
