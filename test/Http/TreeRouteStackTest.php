<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use ArrayObject;
use Laminas\Diactoros\Request;
use Laminas\Diactoros\Uri;
use Laminas\Router\Exception\InvalidArgumentException;
use Laminas\Router\Exception\RuntimeException;
use Laminas\Router\Http\Hostname;
use Laminas\Router\Http\HttpRouteInterface;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\PriorityList;
use Laminas\Router\RoutePluginManager;
use Laminas\ServiceManager\ServiceManager;
use LaminasTest\Router\FactoryTester;
use LaminasTest\Router\TestAsset\DummyRoute;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class TreeRouteStackTest extends TestCase
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

    public function testAddRouteRequiresHttpSpecificRoute(): void
    {
        /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();
        $stack      = new TreeRouteStack(new RoutePluginManager(new ServiceManager()), $prototypes);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only HttpRouteInterface instances or array/string specifications are allowed.');
        /** @psalm-suppress InvalidArgument we're explicitly testing runtime type validation here */
        $stack->addRoute('foo', new DummyRoute());
    }

    public function testAddRouteViaStringRequiresHttpSpecificRoute(): void
    {
        $plugins = new RoutePluginManager(new ServiceManager(), [
            'invokables' => [
                DummyRoute::class => DummyRoute::class,
            ],
        ]);
        /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();
        $stack      = new TreeRouteStack($plugins, $prototypes);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Given route does not implement HTTP route interface');
        $stack->addRoute('foo', [
            'type' => DummyRoute::class,
        ]);
    }

    public function testNoMatchWithoutUriMethod(): void
    {
        /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();
        $stack      = new TreeRouteStack($this->createRoutePluginManager(), $prototypes);
        $request    = new Request();

        $this->assertNull($stack->match($request));
    }

    public function testNoOffsetIsPassed(): void
    {
        /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();
        $stack      = new TreeRouteStack($this->createRoutePluginManager(), $prototypes);
        $stack->addRoute('foo', [
            'type' => TestAsset\DummyRoute::class,
        ]);

        $match = $stack->match(new Request());

        self::assertNotNull($match);
        $this->assertEquals(null, $match->getParam('offset'));
    }

    public function testAssemble(): void
    {
        /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();
        $stack      = new TreeRouteStack($this->createRoutePluginManager(), $prototypes);
        $stack->addRoute('foo', new TestAsset\DummyRoute());
        $this->assertEquals('', $stack->assemble([], ['name' => 'foo']));
    }

    public function testAssembleCanonicalUriWithoutRequestUri(): void
    {
        /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();
        $stack      = new TreeRouteStack($this->createRoutePluginManager(), $prototypes);
        $stack->addRoute('foo', new TestAsset\DummyRoute());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Request URI has not been set');
        $stack->assemble([], ['name' => 'foo', 'force_canonical' => true]);
    }

    public function testAssembleCanonicalUriWithRequestUri(): void
    {
        $uri = new Uri('http://example.com:8080/');
        /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();
        $stack      = new TreeRouteStack(new RoutePluginManager(new ServiceManager()), $prototypes);
        $stack->setRequestUri($uri);

        $stack->addRoute('foo', new TestAsset\DummyRoute());
        $this->assertEquals(
            'http://example.com:8080/',
            (string) $stack->assemble([], ['name' => 'foo', 'force_canonical' => true])
        );
    }

    public function testAssembleCanonicalUriWithGivenUri(): void
    {
        $uri = new Uri('http://example.com:8080/');
        /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();
        $stack      = new TreeRouteStack(new RoutePluginManager(new ServiceManager()), $prototypes);

        $stack->addRoute('foo', new TestAsset\DummyRoute());
        $this->assertEquals(
            'http://example.com:8080/',
            (string) $stack->assemble([], ['name' => 'foo', 'uri' => $uri, 'force_canonical' => true])
        );
    }

    public function testAssembleCanonicalUriWithHostnameRoute(): void
    {
        /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();
        $stack      = new TreeRouteStack(new RoutePluginManager(new ServiceManager()), $prototypes);
        $stack->addRoute('foo', new Hostname('example.com'));
        $uri = new Uri();
        $uri = $uri->withScheme('http');

        $this->assertEquals('http://example.com/', (string) $stack->assemble([], ['name' => 'foo', 'uri' => $uri]));
    }

    public function testAssembleCanonicalUriWithHostnameRouteWithoutScheme(): void
    {
        /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();
        $stack      = new TreeRouteStack(new RoutePluginManager(new ServiceManager()), $prototypes);
        $stack->addRoute('foo', new Hostname('example.com'));
        $uri = new Uri();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Request URI has not been set');
        $stack->assemble([], ['name' => 'foo', 'uri' => $uri]);
    }

    public function testAssembleCanonicalUriWithHostnameRouteAndRequestUriWithoutScheme(): void
    {
        $uri = new Uri();
        $uri = $uri->withScheme('http');
        /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();
        $stack      = new TreeRouteStack(new RoutePluginManager(new ServiceManager()), $prototypes);
        $stack->setRequestUri($uri);
        $stack->addRoute('foo', new Hostname('example.com'));

        $this->assertEquals('http://example.com/', (string) $stack->assemble([], ['name' => 'foo']));
    }

    public function testAssembleWithQueryParams(): void
    {
        /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();
        $stack      = new TreeRouteStack(new RoutePluginManager(new ServiceManager()), $prototypes);
        $stack->addRoute(
            'index',
            [
                'type'    => 'Literal',
                'options' => [
                    'route' => '/',
                ],
            ]
        );

        $this->assertEquals('/?foo=bar', (string) $stack->assemble([], ['name' => 'index', 'query' => ['foo' => 'bar']]));
    }

    public function testAssembleWithEncodedPath(): void
    {
        /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();
        $stack      = new TreeRouteStack(new RoutePluginManager(new ServiceManager()), $prototypes);
        $stack->addRoute(
            'index',
            [
                'type'    => 'Literal',
                'options' => [
                    'route' => '/this%2Fthat',
                ],
            ]
        );

        $this->assertEquals('/this%2Fthat', (string) $stack->assemble([], ['name' => 'index']));
    }

    public function testAssembleWithEncodedPathAndQueryParams(): void
    {
        /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();
        $stack      = new TreeRouteStack(new RoutePluginManager(new ServiceManager()), $prototypes);
        $stack->addRoute(
            'index',
            [
                'type'    => 'Literal',
                'options' => [
                    'route' => '/this%2Fthat',
                ],
            ]
        );

        $this->assertEquals(
            '/this%2Fthat?foo=bar',
            (string) $stack->assemble([], ['name' => 'index', 'query' => ['foo' => 'bar'], 'normalize_path' => false])
        );
    }

    public function testAssembleWithScheme(): void
    {
        $uri = new Uri();
        $uri = $uri->withScheme('http');
        $uri = $uri->withHost('example.com');
        /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();
        $stack      = new TreeRouteStack(new RoutePluginManager(new ServiceManager()), $prototypes);
        $stack->setRequestUri($uri);
        $stack->addRoute(
            'secure',
            [
                'type'         => 'Scheme',
                'options'      => [
                    'scheme' => 'https',
                ],
                'child_routes' => [
                    'index' => [
                        'type'    => 'Literal',
                        'options' => [
                            'route' => '/',
                        ],
                    ],
                ],
            ]
        );
        $this->assertEquals('https://example.com/', $stack->assemble([], ['name' => 'secure/index']));
    }

    public function testAssembleWithFragment(): void
    {
        /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();
        $stack      = new TreeRouteStack(new RoutePluginManager(new ServiceManager()), $prototypes);
        $stack->addRoute(
            'index',
            [
                'type'    => 'Literal',
                'options' => [
                    'route' => '/',
                ],
            ]
        );

        $this->assertEquals('/#foobar', $stack->assemble([], ['name' => 'index', 'fragment' => 'foobar']));
    }

    public function testAssembleWithoutNameOption(): void
    {
        /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();
        $stack      = new TreeRouteStack(new RoutePluginManager(new ServiceManager()), $prototypes);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "name" option');
        $stack->assemble();
    }

    public function testAssembleNonExistentRoute(): void
    {
        /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();
        $stack      = new TreeRouteStack(new RoutePluginManager(new ServiceManager()), $prototypes);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Route with name "foo" not found');
        $stack->assemble([], ['name' => 'foo']);
    }

    public function testAssembleNonExistentChildRoute(): void
    {
        /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();
        $stack      = new TreeRouteStack(new RoutePluginManager(new ServiceManager()), $prototypes);
        $stack->addRoute(
            'index',
            [
                'type'    => 'Literal',
                'options' => [
                    'route' => '/',
                ],
            ]
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Route with name "index" does not have child routes');
        $stack->assemble([], ['name' => 'index/foo']);
    }

    public function testDefaultParamIsUsedForAssembling(): void
    {
        /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();
        $stack      = new TreeRouteStack(new RoutePluginManager(new ServiceManager()), $prototypes);
        $stack->addRoute('foo', new TestAsset\DummyRouteWithParam());
        $stack->setDefaultParam('foo', 'bar');

        $this->assertEquals('bar', (string) $stack->assemble([], ['name' => 'foo']));
    }

    public function testDefaultParamDoesNotOverrideParamForAssembling(): void
    {
        /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();
        $stack      = new TreeRouteStack(new RoutePluginManager(new ServiceManager()), $prototypes);
        $stack->addRoute('foo', new TestAsset\DummyRouteWithParam());
        $stack->setDefaultParam('foo', 'baz');

        $this->assertEquals('bar', (string) $stack->assemble(['foo' => 'bar'], ['name' => 'foo']));
    }

    public function testSetRequestUri(): void
    {
        $uri = new Uri();
        /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();
        $stack      = new TreeRouteStack(new RoutePluginManager(new ServiceManager()), $prototypes);

        $stack->setRequestUri($uri);
        $this->assertEquals($uri, $stack->getRequestUri());
    }

    public function testPriorityIsPassedToPartRoute(): void
    {
        /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();
        $stack      = new TreeRouteStack(new RoutePluginManager(new ServiceManager()), $prototypes);
        $stack->addRoutes([
            'foo' => [
                'type'          => 'Literal',
                'priority'      => 1000,
                'options'       => [
                    'route'    => '/foo',
                    'defaults' => [
                        'controller' => 'foo',
                    ],
                ],
                'may_terminate' => true,
                'child_routes'  => [
                    'bar' => [
                        'type'    => 'Literal',
                        'options' => [
                            'route'    => '/bar',
                            'defaults' => [
                                'controller' => 'foo',
                                'action'     => 'bar',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $reflectedClass    = new ReflectionClass($stack);
        $reflectedProperty = $reflectedClass->getProperty('routes');
        $routes            = $reflectedProperty->getValue($stack);

        self::assertInstanceOf(PriorityList::class, $routes);

        $foo = $routes->get('foo');

        self::assertNotNull($foo);

        $this->assertEquals(1000, $foo->priority);
    }

    public function testChainRouteAssemblingWithChildrenAndSecureScheme(): void
    {
        /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();
        $stack      = new TreeRouteStack(new RoutePluginManager(new ServiceManager()), $prototypes);

        $uri = new Uri();
        $uri = $uri->withHost('localhost');

        $stack->setRequestUri($uri);
        $stack->addRoute(
            'foo',
            [
                'type'         => 'literal',
                'options'      => [
                    'route' => '/foo',
                ],
                'chain_routes' => [
                    ['type' => 'scheme', 'options' => ['scheme' => 'https']],
                ],
                'child_routes' => [
                    'baz' => [
                        'type'    => 'literal',
                        'options' => [
                            'route' => '/baz',
                        ],
                    ],
                ],
            ]
        );

        $this->assertEquals('https://localhost/foo/baz', (string) $stack->assemble([], ['name' => 'foo/baz']));
    }

    public function testFactory(): void
    {
        $tester = new FactoryTester();
        $tester->testFactory(
            TreeRouteStack::class,
            [],
            []
        );
    }
}
