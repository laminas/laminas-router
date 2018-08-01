<?php
/**
 * @link      https://github.com/zendframework/zend-router for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Router\Http;

use PHPUnit\Framework\TestCase;
use Zend\Http\Request;
use Zend\Router\Http\Hostname;
use Zend\Router\Http\Literal;
use Zend\Router\Http\Placeholder;
use Zend\Router\Http\RouteMatch;
use Zend\Router\Http\TreeRouteStack;
use Zend\Stdlib\ArrayUtils;
use ZendTest\Router\FactoryTester;

class PlaceholderTest extends TestCase
{
    private static $routeConfig = [
        'auth' => [
            'type' => Placeholder::class,
            'child_routes' => [
                'login' => [
                    'type' => Literal::class,
                    'options' => [
                        'route' => '/',
                        'defaults' => [
                            'controller' => 'AuthController',
                            'action' => 'login'
                        ],
                    ],
                ],
                'register' => [
                    'type' => Literal::class,
                    'options' => [
                        'route' => '/register',
                        'defaults' => [
                            'controller' => 'RegistrationController',
                            'action' => 'register'
                        ],
                    ],
                ],
            ],
        ],
    ];
    public function testMatch()
    {
        $route = new Placeholder([]);

        $request = new Request();
        $request->setUri('http://example.com/');
        $match = $route->match($request);

        $this->assertInstanceOf(RouteMatch::class, $match);
    }

    public function testAssembling()
    {
        $route = new Placeholder([]);
        $this->assertEquals('', $route->assemble());
    }

    public function testGetAssembledParams()
    {
        $route = new Placeholder([]);
        $this->assertEquals([], $route->getAssembledParams());
    }

    public function testFactory()
    {
        $tester = new FactoryTester($this);
        $tester->testFactory(Placeholder::class, [], []);
    }

    /**
     * @dataProvider placeholderProvider
     * @param array $additionalConfig
     * @param string $uri
     * @param string $expectedRouteName
     */
    public function testPlaceholderDefault($additionalConfig, $uri, $expectedRouteName)
    {
        $routeConfig = ArrayUtils::merge(self::$routeConfig, $additionalConfig);
        $router = TreeRouteStack::factory(['routes' => $routeConfig]);

        $request = new Request();
        $request->setUri($uri);
        $match = $router->match($request);

        $this->assertInstanceOf(RouteMatch::class, $match);
        $this->assertEquals($expectedRouteName, $match->getMatchedRouteName());
    }

    public function placeholderProvider()
    {
        $home = [
            'home' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/home',
                    'defaults' => [
                        'controller' => 'HomeController',
                        'action' => 'index'
                    ],
                ],
            ]
        ];

        $homeAtRootAuthMoved = [
            'home' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/',
                    'defaults' => [
                        'controller' => 'HomeController',
                        'action' => 'index'
                    ],
                ],
            ],
            'auth' => [
                'type' => Literal::class,
                'options' => ['route' => '/auth']
            ]
        ];

        $homeAtRootAuthOnSubDomain = [
            'home' => [
                'type' => Hostname::class,
                'options' => [
                    'route' => 'example.com',
                    'defaults' => [
                        'controller' => 'HomeController',
                        'action' => 'index'
                    ],
                ],
            ],
            'auth' => [
                'type' => Hostname::class,
                'options' => ['route' => 'auth.example.com']
            ]
        ];

        // @codingStandardsIgnoreStart
        return [
            'no-override-login'           => [$home,                      'http://example.com/',              'auth/login'],
            'no-override-register'        => [$home,                      'http://example.com/register',      'auth/register'],
            'no-override-home'            => [$home,                      'http://example.com/home',          'home'],
            'path-override-login'         => [$homeAtRootAuthMoved,       'http://example.com/auth/',         'auth/login'],
            'path-override-register'      => [$homeAtRootAuthMoved,       'http://example.com/auth/register', 'auth/register'],
            'path-override-home'          => [$homeAtRootAuthMoved,       'http://example.com',               'home'],
            'subdomain-override-login'    => [$homeAtRootAuthOnSubDomain, 'http://auth.example.com/',         'auth/login'],
            'subdomain-override-register' => [$homeAtRootAuthOnSubDomain, 'http://auth.example.com/register', 'auth/register'],
            'subdomina-override-home'     => [$homeAtRootAuthOnSubDomain, 'http://example.com/',              'home'],
        ];
        // @codingStandardsIgnoreEnd
    }
}
