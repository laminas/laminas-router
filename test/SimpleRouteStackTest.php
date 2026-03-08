<?php

declare(strict_types=1);

namespace LaminasTest\Router;

use Laminas\Diactoros\Request;
use Laminas\Diactoros\UriFactory;
use Laminas\Router\Exception\InvalidArgumentException;
use Laminas\Router\Exception\RuntimeException;
use Laminas\Router\Http\Chain;
use Laminas\Router\Http\Hostname;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Method;
use Laminas\Router\Http\Placeholder;
use Laminas\Router\Http\Regex;
use Laminas\Router\Http\Scheme;
use Laminas\Router\Http\Segment;
use Laminas\Router\PriorityList;
use Laminas\Router\RouteInterface;
use Laminas\Router\RouteMatch;
use Laminas\Router\RoutePluginManager;
use Laminas\Router\SimpleRouteStack;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SimpleRouteStackTest extends TestCase
{
    private function createRoutePluginManager(): RoutePluginManager
    {
        return new RoutePluginManager(new ServiceManager(), [
            'invokables' => [
                TestAsset\DummyRoute::class          => TestAsset\DummyRoute::class,
                TestAsset\DummyRouteWithParam::class => TestAsset\DummyRouteWithParam::class,
            ],
        ]);
    }

    public function testAddRoutesAsArray(): void
    {
        /** @var SimpleRouteStack<RouteInterface> $stack */
        $stack = new SimpleRouteStack(new RoutePluginManager(new ServiceManager()), new UriFactory());
        $stack->addRoutes([
            'foo' => new TestAsset\DummyRoute(),
        ]);

        $this->assertInstanceOf(RouteMatch::class, $stack->match(new Request()));
    }

    public function testSetRoutesAsArray(): void
    {
        /** @var SimpleRouteStack<RouteInterface> $stack */
        $stack = new SimpleRouteStack(new RoutePluginManager(new ServiceManager()), new UriFactory());
        $stack->setRoutes([
            'foo' => new TestAsset\DummyRoute(),
        ]);

        $this->assertInstanceOf(RouteMatch::class, $stack->match(new Request()));

        $stack->setRoutes([]);

        $this->assertNull($stack->match(new Request()));
    }

    public function testRemoveRouteAsArray(): void
    {
        /** @var SimpleRouteStack<RouteInterface> $stack */
        $stack = new SimpleRouteStack(new RoutePluginManager(new ServiceManager()), new UriFactory());
        $stack->addRoutes([
            'foo' => new TestAsset\DummyRoute(),
        ]);

        $stack->removeRoute('foo');
        $this->assertNull($stack->match(new Request()));
    }

    public function testAddRouteAsArrayWithoutOptions(): void
    {
        $stack = new SimpleRouteStack($this->createRoutePluginManager(), new UriFactory());
        $stack->addRoute('foo', [
            'type' => TestAsset\DummyRoute::class,
        ]);

        $this->assertInstanceOf(RouteMatch::class, $stack->match(new Request()));
    }

    public function testAddRouteAsArrayWithOptions(): void
    {
        $stack = new SimpleRouteStack($this->createRoutePluginManager(), new UriFactory());
        $stack->addRoute('foo', [
            'type'    => TestAsset\DummyRoute::class,
            'options' => [],
        ]);

        $this->assertInstanceOf(RouteMatch::class, $stack->match(new Request()));
    }

    public function testAddRouteAsArrayWithoutType(): void
    {
        $stack = new SimpleRouteStack(new RoutePluginManager(new ServiceManager()), new UriFactory());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "type" option');
        $stack->addRoute('foo', []);
    }

    public function testAddRouteAsArrayWithPriority(): void
    {
        $stack = new SimpleRouteStack($this->createRoutePluginManager(), new UriFactory());

        $stack->addRoute('foo', [
            'type'     => TestAsset\DummyRouteWithParam::class,
            'priority' => 2,
        ]);
        $stack->addRoute('bar', [
            'type'     => TestAsset\DummyRoute::class,
            'priority' => 1,
        ]);

        $match = $stack->match(new Request());

        self::assertNotNull($match);
        $this->assertEquals('bar', $match->getParam('foo'));
    }

    public function testAddRouteWithPriority(): void
    {
        /** @var SimpleRouteStack<RouteInterface> $stack */
        $stack = new SimpleRouteStack($this->createRoutePluginManager(), new UriFactory());

        $route           = new TestAsset\DummyRouteWithParam();
        $route->priority = 2;
        $stack->addRoute('baz', $route);

        $stack->addRoute('foo', [
            'type'     => TestAsset\DummyRoute::class,
            'priority' => 1,
        ]);

        $match = $stack->match(new Request());

        self::assertNotNull($match);
        $this->assertEquals('bar', $match->getParam('foo'));
    }

    public function testAssemble(): void
    {
        /** @var SimpleRouteStack<RouteInterface> $stack */
        $stack = new SimpleRouteStack(new RoutePluginManager(new ServiceManager()), new UriFactory());
        $stack->addRoute('foo', new TestAsset\DummyRoute());
        $this->assertEquals('', $stack->assemble([], ['name' => 'foo']));
    }

    public function testAssembleWithoutNameOption(): void
    {
        $stack = new SimpleRouteStack(new RoutePluginManager(new ServiceManager()), new UriFactory());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "name" option');
        $stack->assemble([], []);
    }

    public function testAssembleNonExistentRoute(): void
    {
        $stack = new SimpleRouteStack(new RoutePluginManager(new ServiceManager()), new UriFactory());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Route with name "foo" not found');
        $stack->assemble([], ['name' => 'foo']);
    }

    public function testDefaultParamIsAddedToMatch(): void
    {
        /** @var SimpleRouteStack<RouteInterface> $stack */
        $stack = new SimpleRouteStack(new RoutePluginManager(new ServiceManager()), new UriFactory());
        $stack->addRoute('foo', new TestAsset\DummyRoute());
        $stack->setDefaultParam('foo', 'bar');

        $match = $stack->match(new Request());
        self::assertNotNull($match);

        $this->assertEquals('bar', $match->getParam('foo'));
    }

    public function testDefaultParamDoesNotOverrideParam(): void
    {
        /** @var SimpleRouteStack<RouteInterface> $stack */
        $stack = new SimpleRouteStack(new RoutePluginManager(new ServiceManager()), new UriFactory());
        $stack->addRoute('foo', new TestAsset\DummyRouteWithParam());
        $stack->setDefaultParam('foo', 'baz');

        $match = $stack->match(new Request());

        self::assertNotNull($match);
        $this->assertEquals('bar', $match->getParam('foo'));
    }

    public function testDefaultParamIsUsedForAssembling(): void
    {
        /** @var SimpleRouteStack<RouteInterface> $stack */
        $stack = new SimpleRouteStack(new RoutePluginManager(new ServiceManager()), new UriFactory());
        $stack->addRoute('foo', new TestAsset\DummyRouteWithParam());
        $stack->setDefaultParam('foo', 'bar');

        $this->assertEquals('bar', (string) $stack->assemble([], ['name' => 'foo']));
    }

    public function testDefaultParamDoesNotOverrideParamForAssembling(): void
    {
        /** @var SimpleRouteStack<RouteInterface> $stack */
        $stack = new SimpleRouteStack(new RoutePluginManager(new ServiceManager()), new UriFactory());
        $stack->addRoute('foo', new TestAsset\DummyRouteWithParam());
        $stack->setDefaultParam('foo', 'baz');

        $this->assertEquals('bar', (string) $stack->assemble(['foo' => 'bar'], ['name' => 'foo']));
    }

    public function testFactory(): void
    {
        $tester = new FactoryTester();
        $tester->testFactory(
            SimpleRouteStack::class,
            [],
            [
                'route_plugins'  => new RoutePluginManager(new ServiceManager()),
                'routes'         => [],
                'default_params' => [],
            ]
        );
    }

    public function testGetRoutes(): void
    {
        $stack = new SimpleRouteStack(new RoutePluginManager(new ServiceManager()), new UriFactory());
        $this->assertInstanceOf(PriorityList::class, $stack->getRoutes());
    }

    public function testGetRouteByName(): void
    {
        /** @var SimpleRouteStack<RouteInterface> $stack */
        $stack = new SimpleRouteStack(new RoutePluginManager(new ServiceManager()), new UriFactory());
        $route = new TestAsset\DummyRoute();
        $stack->addRoute('foo', $route);

        $this->assertEquals($route, $stack->getRoute('foo'));
    }

    public function testHasRoute(): void
    {
        /** @var SimpleRouteStack<RouteInterface> $stack */
        $stack = new SimpleRouteStack(new RoutePluginManager(new ServiceManager()), new UriFactory());
        $this->assertFalse($stack->hasRoute('foo'));

        $stack->addRoute('foo', new TestAsset\DummyRoute());
        $this->assertTrue($stack->hasRoute('foo'));
    }

    /** @return array<class-string, array{0: array, 1: int}> */
    public static function routeTypeProvider(): array
    {
        $routePlugins = new RoutePluginManager(new ServiceManager());
        return [
            Chain::class       => [
                [
                    'type'     => Chain::class,
                    'priority' => 1,
                    'options'  => [
                        'routes'        => [],
                        'route_plugins' => $routePlugins,
                    ],
                ],
                1,
            ],
            Hostname::class    => [
                [
                    'type'     => Hostname::class,
                    'options'  => [
                        'route'    => 'www.example.com',
                        'defaults' => [
                            'controller' => 'SomeController',
                            'action'     => 'index',
                        ],
                    ],
                    'priority' => 5,
                ],
                5,
            ],
            Literal::class     => [
                [
                    'type'     => Literal::class,
                    'options'  => [
                        'route'    => '/blah',
                        'defaults' => [
                            'controller' => 'SomeController',
                            'action'     => 'index',
                        ],
                    ],
                    'priority' => 10,
                ],
                10,
            ],
            Method::class      => [
                [
                    'type'     => Method::class,
                    'options'  => [
                        'route' => '/duck',
                        'verb'  => 'QUACK',
                    ],
                    'priority' => 20,
                ],
                20,
            ],
            Placeholder::class => [
                [
                    'type'     => Placeholder::class,
                    'options'  => [],
                    'priority' => 30,
                ],
                30,
            ],
            Regex::class       => [
                [
                    'type'     => Regex::class,
                    'options'  => [
                        'regex' => '/(?<foo>[^/]+)',
                        'spec'  => '/%foo%',
                    ],
                    'priority' => 40,
                ],
                40,
            ],
            Scheme::class      => [
                [
                    'type'     => Scheme::class,
                    'options'  => [
                        'scheme' => 'carrots',
                    ],
                    'priority' => 50,
                ],
                50,
            ],
            Segment::class     => [
                [
                    'type'     => Segment::class,
                    'options'  => [
                        'route' => '/mushrooms',
                    ],
                    'priority' => 60,
                ],
                60,
            ],
        ];
    }

    #[DataProvider('routeTypeProvider')]
    public function testSimpleRouteStackSetsPriorityForAllKnownRouteTypes(array $routeSpec, int $expectedPriority): void
    {
        /** @var SimpleRouteStack<RouteInterface> $router */
        $router = new SimpleRouteStack(new RoutePluginManager(new ServiceManager()), new UriFactory());
        $router->addRoute('name', $routeSpec);

        $route = $router->getRoute('name');
        self::assertNotNull($route);
        self::assertEquals($expectedPriority, $route->priority);
    }
}
