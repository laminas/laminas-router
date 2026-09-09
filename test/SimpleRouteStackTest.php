<?php

declare(strict_types=1);

namespace LaminasTest\Router;

use Laminas\Diactoros\Request;
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
use Laminas\Router\RouteBuilderContainerInterface;
use Laminas\Router\RouteInterface;
use Laminas\Router\RouteMatchInterface;
use Laminas\Router\SimpleRouteStack;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SimpleRouteStackTest extends TestCase
{
    private function createRouteBuilderContainer(): RouteBuilderContainerInterface
    {
        return RouteBuilderContainerTestHelper::create();
    }

    public function testAddRoutesAsArray(): void
    {
        /** @var SimpleRouteStack<RouteInterface> $stack */
        $stack = new SimpleRouteStack($this->createRouteBuilderContainer());
        $stack->addRoutes([
            'foo' => new TestAsset\DummyRoute('foo'),
        ]);

        $this->assertInstanceOf(RouteMatchInterface::class, $stack->match(new Request()));
    }

    public function testSetRoutesAsArray(): void
    {
        /** @var SimpleRouteStack<RouteInterface> $stack */
        $stack = new SimpleRouteStack($this->createRouteBuilderContainer());
        $stack->setRoutes([
            'foo' => new TestAsset\DummyRoute('foo'),
        ]);

        $this->assertInstanceOf(RouteMatchInterface::class, $stack->match(new Request()));

        $stack->setRoutes([]);

        $this->assertNull($stack->match(new Request()));
    }

    public function testRemoveRouteAsArray(): void
    {
        /** @var SimpleRouteStack<RouteInterface> $stack */
        $stack = new SimpleRouteStack($this->createRouteBuilderContainer());
        $stack->addRoutes([
            'foo' => new TestAsset\DummyRoute('foo'),
        ]);

        $stack->removeRoute('foo');
        $this->assertNull($stack->match(new Request()));
    }

    public function testAddRouteAsArrayWithoutOptions(): void
    {
        $stack = new SimpleRouteStack($this->createRouteBuilderContainer());
        $stack->addRoute('foo', [
            'type' => TestAsset\DummyRoute::class,
        ]);

        $this->assertInstanceOf(RouteMatchInterface::class, $stack->match(new Request()));
    }

    public function testAddRouteAsArrayWithOptions(): void
    {
        $stack = new SimpleRouteStack($this->createRouteBuilderContainer());
        $stack->addRoute('foo', [
            'type'    => TestAsset\DummyRoute::class,
            'options' => [],
        ]);

        $this->assertInstanceOf(RouteMatchInterface::class, $stack->match(new Request()));
    }

    public function testAddRouteAsArrayWithoutType(): void
    {
        $stack = new SimpleRouteStack($this->createRouteBuilderContainer());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "type" option');
        $stack->addRoute('foo', []);
    }

    public function testAddDuplicateRouteThrowsException(): void
    {
        /** @var SimpleRouteStack<RouteInterface> $stack */
        $stack = new SimpleRouteStack($this->createRouteBuilderContainer());

        $stack->addRoute('foo', new TestAsset\DummyRoute('foo'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Route with name "foo" already exists');
        $stack->addRoute('foo', new TestAsset\DummyRoute('foo'));
    }

    public function testAddRouteAsArrayWithPriority(): void
    {
        $stack = new SimpleRouteStack($this->createRouteBuilderContainer());

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
        $stack = new SimpleRouteStack($this->createRouteBuilderContainer());

        $route = new TestAsset\DummyRouteWithParam('baz', 2);
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
        $stack = new SimpleRouteStack($this->createRouteBuilderContainer());
        $stack->addRoute('foo', new TestAsset\DummyRoute('foo'));
        $this->assertEquals('', $stack->assemble([], ['name' => 'foo'])->toString());
    }

    public function testAssembleWithoutNameOption(): void
    {
        $stack = new SimpleRouteStack($this->createRouteBuilderContainer());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "name" option');
        $stack->assemble([], []);
    }

    public function testAssembleNonExistentRoute(): void
    {
        $stack = new SimpleRouteStack($this->createRouteBuilderContainer());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Route with name "foo" not found');
        $stack->assemble([], ['name' => 'foo']);
    }

    public function testDefaultParamIsNotAddedToMatchWhenRouteIsAnObject(): void
    {
        /** @var SimpleRouteStack<RouteInterface> $stack */
        $stack = new SimpleRouteStack($this->createRouteBuilderContainer(), defaultParams: ['foo' => 'bar']);
        $stack->addRoute('foo', new TestAsset\DummyRoute('foo'));

        $match = $stack->match(new Request());
        self::assertNotNull($match);

        $this->assertNull($match->getParam('foo'));
    }

    public function testDefaultParamIsAddedToMatchWhenRouteIsAnArray(): void
    {
        $stack = new SimpleRouteStack($this->createRouteBuilderContainer(), defaultParams: ['foo' => 'bar']);
        $stack->addRoute('foo', [
            'type' => TestAsset\DummyRoute::class,
        ]);

        $match = $stack->match(new Request());
        self::assertNotNull($match);

        $this->assertEquals('bar', $match->getParam('foo'));
    }

    public function testDefaultParamDoesNotOverrideParam(): void
    {
        /** @var SimpleRouteStack<RouteInterface> $stack */
        $stack = new SimpleRouteStack($this->createRouteBuilderContainer(), defaultParams: ['foo' => 'bar']);
        $stack->addRoute('foo', new TestAsset\DummyRouteWithParam('foo'));

        $match = $stack->match(new Request());

        self::assertNotNull($match);
        $this->assertEquals('bar', $match->getParam('foo'));
    }

    public function testDefaultParamIsUsedForAssembling(): void
    {
        /** @var SimpleRouteStack<RouteInterface> $stack */
        $stack = new SimpleRouteStack($this->createRouteBuilderContainer(), defaultParams: ['foo' => 'bar']);
        $stack->addRoute('foo', new TestAsset\DummyRouteWithParam('foo'));

        $this->assertEquals('bar', $stack->assemble([], ['name' => 'foo'])->toString());
    }

    public function testDefaultParamDoesNotOverrideParamForAssembling(): void
    {
        /** @var SimpleRouteStack<RouteInterface> $stack */
        $stack = new SimpleRouteStack($this->createRouteBuilderContainer(), defaultParams: ['foo' => 'bar']);
        $stack->addRoute('foo', new TestAsset\DummyRouteWithParam('foo'));

        $this->assertEquals('bar', $stack->assemble(['foo' => 'bar'], ['name' => 'foo'])->toString());
    }

    public function testBuilder(): void
    {
        $tester = new BuilderTester();
        $tester->testBuilder(
            SimpleRouteStack::class,
            [],
            [
                'routes'         => [],
                'default_params' => [],
            ]
        );
    }

    public function testGetRoutes(): void
    {
        $stack = new SimpleRouteStack($this->createRouteBuilderContainer());
        $this->assertInstanceOf(PriorityList::class, $stack->getRoutes());
    }

    public function testGetRouteByName(): void
    {
        /** @var SimpleRouteStack<RouteInterface> $stack */
        $stack = new SimpleRouteStack($this->createRouteBuilderContainer());
        $route = new TestAsset\DummyRoute('foo');
        $stack->addRoute('foo', $route);

        $this->assertEquals($route, $stack->getRoute('foo'));
    }

    public function testHasRoute(): void
    {
        /** @var SimpleRouteStack<RouteInterface> $stack */
        $stack = new SimpleRouteStack($this->createRouteBuilderContainer());
        $this->assertFalse($stack->hasRoute('foo'));

        $stack->addRoute('foo', new TestAsset\DummyRoute('foo'));
        $this->assertTrue($stack->hasRoute('foo'));
    }

    /** @return array<class-string, array{0: array, 1: int}> */
    public static function routeTypeProvider(): array
    {
        return [
            Chain::class       => [
                [
                    'type'     => Chain::class,
                    'priority' => 1,
                    'options'  => [
                        'routes' => [],
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
        $router = new SimpleRouteStack($this->createRouteBuilderContainer());
        $router->addRoute('name', $routeSpec);

        $route = $router->getRoute('name');
        self::assertNotNull($route);
        self::assertEquals($expectedPriority, $route->getPriority());
    }
}
