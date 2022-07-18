<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use Laminas\Http\Request;
use Laminas\Router\Http\Hostname;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Placeholder;
use Laminas\Router\Http\RouteMatch;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Stdlib\ArrayUtils;
use LaminasTest\Router\FactoryTester;
use PHPUnit\Framework\TestCase;

class PlaceholderTest extends TestCase
{
    /** @var array<string, array<string, mixed>> */
    private static $routeConfig = [
        'auth' => [
            'type'         => Placeholder::class,
            'child_routes' => [
                'login'    => [
                    'type'    => Literal::class,
                    'options' => [
                        'route'    => '/',
                        'defaults' => [
                            'controller' => 'AuthController',
                            'action'     => 'login',
                        ],
                    ],
                ],
                'register' => [
                    'type'    => Literal::class,
                    'options' => [
                        'route'    => '/register',
                        'defaults' => [
                            'controller' => 'RegistrationController',
                            'action'     => 'register',
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
        $router      = TreeRouteStack::factory(['routes' => $routeConfig]);

        $request = new Request();
        $request->setUri($uri);
        $match = $router->match($request);

        $this->assertInstanceOf(RouteMatch::class, $match);
        $this->assertEquals($expectedRouteName, $match->getMatchedRouteName());
    }

    /**
     * @psalm-return array<string, array{
     *     0: array<string, array<string, mixed>>,
     *     1: string,
     *     2: string
     * }>
     */
    public function placeholderProvider(): array
    {
        $home = [
            'home' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/home',
                    'defaults' => [
                        'controller' => 'HomeController',
                        'action'     => 'index',
                    ],
                ],
            ],
        ];

        $homeAtRootAuthMoved = [
            'home' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => 'HomeController',
                        'action'     => 'index',
                    ],
                ],
            ],
            'auth' => [
                'type'    => Literal::class,
                'options' => ['route' => '/auth'],
            ],
        ];

        $homeAtRootAuthOnSubDomain = [
            'home' => [
                'type'    => Hostname::class,
                'options' => [
                    'route'    => 'example.com',
                    'defaults' => [
                        'controller' => 'HomeController',
                        'action'     => 'index',
                    ],
                ],
            ],
            'auth' => [
                'type'    => Hostname::class,
                'options' => ['route' => 'auth.example.com'],
            ],
        ];

        // phpcs:disable Generic.Files.LineLength.TooLong
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
        // phpcs:enable Generic.Files.LineLength.TooLong
    }
}
