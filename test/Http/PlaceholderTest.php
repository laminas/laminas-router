<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use Laminas\Router\Http\Hostname;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Placeholder;
use Laminas\Router\Http\RouteMatch;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\RouteInterface;
use Laminas\Router\RoutePluginManager;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayUtils;
use LaminasTest\Router\FactoryTester;
use LaminasTest\Router\TestAsset\MockServerRequest;
use LaminasTest\Router\TestAsset\MockUri;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;

final class PlaceholderTest extends TestCase
{
    private function createRoutePluginManager(): RoutePluginManager
    {
        return new RoutePluginManager(new ServiceManager(), [
            'invokables' => [
                Placeholder::class => Placeholder::class,
            ],
        ]);
    }

    /** @var array<string, array<string, mixed>> */
    private static array $routeConfig = [
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
    public function testMatch(): void
    {
        $route = new Placeholder([]);

        $request = new MockServerRequest(new MockUri('https://example.com/'));
        $match   = $route->match($request);

        $this->assertInstanceOf(RouteMatch::class, $match);
    }

    public function testAssembling(): void
    {
        $route = new Placeholder([]);
        $this->assertEquals('', $route->assemble());
    }

    public function testGetAssembledParams(): void
    {
        $route = new Placeholder([]);
        $this->assertEquals([], $route->getAssembledParams());
    }

    public function testFactory(): void
    {
        $tester = new FactoryTester($this);
        $tester->testFactory(Placeholder::class, [], []);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    #[DataProvider('placeholderProvider')]
    public function testPlaceholderDefault(array $additionalConfig, string $uri, string $expectedRouteName): void
    {
        /** @var iterable<string, RouteInterface|iterable> $routeConfig */
        $routeConfig = ArrayUtils::merge(self::$routeConfig, $additionalConfig);
        $router      = new TreeRouteStack($this->createRoutePluginManager());
        $router->addRoutes($routeConfig);

        $request = new MockServerRequest(new MockUri($uri));
        $match   = $router->match($request);

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
    public static function placeholderProvider(): array
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
