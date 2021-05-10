<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use Laminas\Http\Request;
use Laminas\Router\Http\Chain;
use Laminas\Router\Http\RouteMatch;
use Laminas\Router\Http\Segment;
use Laminas\Router\Http\Wildcard;
use Laminas\Router\RoutePluginManager;
use Laminas\ServiceManager\ServiceManager;
use LaminasTest\Router\FactoryTester;
use PHPUnit\Framework\TestCase;

class ChainTest extends TestCase
{
    public static function getRoute()
    {
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
                [
                    'type' => Wildcard::class,
                ],
            ],
            $routePlugins
        );
    }

    public static function getRouteWithOptionalParam()
    {
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

    public static function routeProvider()
    {
        return [
            'simple-match' => [
                self::getRoute(),
                '/foo/bar',
                null,
                [
                    'controller' => 'foo',
                    'bar'        => 'bar',
                ],
            ],
            'offset-skips-beginning' => [
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
                    'bar' => 'baz',
                ],
            ],
            'optional-parameter' => [
                self::getRouteWithOptionalParam(),
                '/foo/baz',
                null,
                [
                    'controller' => 'foo',
                    'bar' => 'baz',
                ],
            ],
            'optional-parameter-empty' => [
                self::getRouteWithOptionalParam(),
                '/foo',
                null,
                [
                    'controller' => 'foo',
                    'bar' => 'bar',
                ],
            ],
        ];
    }

    /**
     * @dataProvider routeProvider
     * @param        Chain   $route
     * @param        string  $path
     * @param        int     $offset
     * @param        array   $params
     */
    public function testMatching(Chain $route, $path, $offset, array $params = null)
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

            foreach ($params as $key => $value) {
                $this->assertEquals($value, $match->getParam($key));
            }
        }
    }

    /**
     * @dataProvider routeProvider
     * @param        Chain   $route
     * @param        string  $path
     * @param        int     $offset
     * @param        array   $params
     */
    public function testAssembling(Chain $route, $path, $offset, array $params = null)
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

    public function testFactory()
    {
        $tester = new FactoryTester($this);
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
