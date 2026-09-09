<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use Laminas\Diactoros\Request;
use Laminas\Diactoros\Uri;
use Laminas\Router\Exception\InvalidArgumentException;
use Laminas\Router\Exception\RuntimeException;
use Laminas\Router\Http\Hostname;
use Laminas\Router\Http\HttpRouteInterface;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\PriorityList;
use Laminas\Router\RouteBuilderContainerInterface;
use LaminasTest\Router\BuilderTester;
use LaminasTest\Router\RouteBuilderContainerTestHelper;
use LaminasTest\Router\TestAsset\DummyRoute;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class TreeRouteStackTest extends TestCase
{
    private function createRouteBuilderContainer(): RouteBuilderContainerInterface
    {
        return RouteBuilderContainerTestHelper::create();
    }

    public function testAddRouteRequiresHttpSpecificRoute(): void
    {
        $stack = new TreeRouteStack($this->createRouteBuilderContainer());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only HttpRouteInterface instances or array specifications are allowed.');
        /** @psalm-suppress InvalidArgument we're explicitly testing runtime type validation here */
        $stack->addRoute('foo', new DummyRoute('foo'));
    }

    public function testAddRouteViaStringRequiresHttpSpecificRoute(): void
    {
        $stack = new TreeRouteStack($this->createRouteBuilderContainer());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Given route does not implement HTTP route interface');
        $stack->addRoute('foo', [
            'type' => DummyRoute::class,
        ]);
    }

    public function testNoMatchWithoutUriMethod(): void
    {
        $stack   = new TreeRouteStack($this->createRouteBuilderContainer());
        $request = new Request();

        $this->assertNull($stack->match($request));
    }

    public function testNoOffsetIsPassed(): void
    {
        $stack = new TreeRouteStack($this->createRouteBuilderContainer());
        $stack->addRoute('foo', [
            'type' => TestAsset\DummyRoute::class,
        ]);

        $match = $stack->match(new Request());

        self::assertNotNull($match);
        $this->assertEquals(null, $match->getParam('offset'));
    }

    public function testAssemble(): void
    {
        /** @var TreeRouteStack<HttpRouteInterface> $stack */
        $stack = new TreeRouteStack($this->createRouteBuilderContainer());
        $stack->addRoute('foo', new TestAsset\DummyRoute('foo'));
        $result = $stack->assemble([], ['name' => 'foo']);

        $this->assertFalse($result->forceCanonical);
        $this->assertEquals('', $result->toString());
    }

    public function testAssembleCanonicalUriWithoutRequestUri(): void
    {
        /** @var TreeRouteStack<HttpRouteInterface> $stack */
        $stack = new TreeRouteStack($this->createRouteBuilderContainer());
        $stack->addRoute('foo', new TestAsset\DummyRoute('foo'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Request URI has not been set');
        $stack->assemble([], ['name' => 'foo', 'force_canonical' => true]);
    }

    public function testAssembleCanonicalUriWithRequestUri(): void
    {
        $uri = new Uri('http://example.com:8080/');
        /** @var TreeRouteStack<HttpRouteInterface> $stack */
        $stack = new TreeRouteStack($this->createRouteBuilderContainer());

        $stack->addRoute('foo', new TestAsset\DummyRoute('foo'));
        $result = $stack->assemble([], ['name' => 'foo', 'force_canonical' => true, 'uri' => $uri]);

        $this->assertSame(8080, $result->port);
        $this->assertTrue($result->forceCanonical);
        $this->assertEquals('http://example.com:8080/', $result->toString());
    }

    public function testAssembleCanonicalUriWithGivenUri(): void
    {
        $uri = new Uri('http://example.com:8080/');
        /** @var TreeRouteStack<HttpRouteInterface> $stack */
        $stack = new TreeRouteStack($this->createRouteBuilderContainer());

        $stack->addRoute('foo', new TestAsset\DummyRoute('foo'));
        $this->assertEquals(
            'http://example.com:8080/',
            $stack->assemble([], ['name' => 'foo', 'uri' => $uri, 'force_canonical' => true])->toString()
        );
    }

    public function testAssembleCanonicalUriWithHostnameRoute(): void
    {
        /** @var TreeRouteStack<HttpRouteInterface> $stack */
        $stack = new TreeRouteStack($this->createRouteBuilderContainer());
        $stack->addRoute('foo', new Hostname('foo', 'example.com'));
        $uri = new Uri();
        $uri = $uri->withScheme('http');

        $this->assertEquals(
            'http://example.com/',
            $stack->assemble([], ['name' => 'foo', 'uri' => $uri])->toString()
        );
    }

    public function testAssembleCanonicalUriWithHostnameRouteWithoutScheme(): void
    {
        /** @var TreeRouteStack<HttpRouteInterface> $stack */
        $stack = new TreeRouteStack($this->createRouteBuilderContainer());
        $stack->addRoute('foo', new Hostname('foo', 'example.com'));
        $uri = new Uri();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Request URI has not been set');
        $stack->assemble([], ['name' => 'foo', 'uri' => $uri]);
    }

    public function testAssembleCanonicalUriWithHostnameRouteAndRequestUriWithoutScheme(): void
    {
        $uri = new Uri();
        $uri = $uri->withScheme('http');
        /** @var TreeRouteStack<HttpRouteInterface> $stack */
        $stack = new TreeRouteStack($this->createRouteBuilderContainer());
        $stack->addRoute('foo', new Hostname('foo', 'example.com'));

        $this->assertEquals('http://example.com/', $stack->assemble([], ['name' => 'foo', 'uri' => $uri])->toString());
    }

    public function testAssembleWithQueryParams(): void
    {
        $stack = new TreeRouteStack($this->createRouteBuilderContainer());
        $stack->addRoute(
            'index',
            [
                'type'    => 'Literal',
                'options' => [
                    'route' => '/',
                ],
            ]
        );

        $this->assertEquals(
            '/?foo=bar',
            $stack->assemble([], ['name' => 'index', 'query' => ['foo' => 'bar']])->toString()
        );
    }

    public function testAssembleWithEncodedPath(): void
    {
        $stack = new TreeRouteStack($this->createRouteBuilderContainer());
        $stack->addRoute(
            'index',
            [
                'type'    => 'Literal',
                'options' => [
                    'route' => '/this%2Fthat',
                ],
            ]
        );

        $this->assertEquals('/this%2Fthat', $stack->assemble([], ['name' => 'index'])->toString());
    }

    public function testAssembleWithEncodedPathAndQueryParams(): void
    {
        $stack = new TreeRouteStack($this->createRouteBuilderContainer());
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
            $stack->assemble(
                [],
                ['name' => 'index', 'query' => ['foo' => 'bar'], 'normalize_path' => false]
            )->toString()
        );
    }

    public function testAssembleWithScheme(): void
    {
        $uri   = new Uri();
        $uri   = $uri->withScheme('http');
        $uri   = $uri->withHost('example.com');
        $stack = new TreeRouteStack($this->createRouteBuilderContainer());

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
        $this->assertEquals(
            'https://example.com/',
            $stack->assemble([], ['name' => 'secure/index', 'uri' => $uri])->toString()
        );
    }

    public function testAssembleWithFragment(): void
    {
        $stack = new TreeRouteStack($this->createRouteBuilderContainer());
        $stack->addRoute(
            'index',
            [
                'type'    => 'Literal',
                'options' => [
                    'route' => '/',
                ],
            ]
        );

        $this->assertEquals('/#foobar', $stack->assemble([], ['name' => 'index', 'fragment' => 'foobar'])->toString());
    }

    public function testAssembleWithoutNameOption(): void
    {
        $stack = new TreeRouteStack($this->createRouteBuilderContainer());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "name" option');
        $stack->assemble();
    }

    public function testAssembleNonExistentRoute(): void
    {
        $stack = new TreeRouteStack($this->createRouteBuilderContainer());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Route with name "foo" not found');
        $stack->assemble([], ['name' => 'foo']);
    }

    public function testAssembleNonExistentChildRoute(): void
    {
        $stack = new TreeRouteStack($this->createRouteBuilderContainer());
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
        /** @var TreeRouteStack<HttpRouteInterface> $stack */
        $stack = new TreeRouteStack($this->createRouteBuilderContainer(), defaultParams: ['foo' => 'bar']);
        $stack->addRoute('foo', new TestAsset\DummyRouteWithParam('foo'));

        $this->assertEquals('bar', $stack->assemble([], ['name' => 'foo'])->toString());
    }

    public function testDefaultParamDoesNotOverrideParamForAssembling(): void
    {
        /** @var TreeRouteStack<HttpRouteInterface> $stack */
        $stack = new TreeRouteStack($this->createRouteBuilderContainer(), defaultParams: ['foo' => 'baz']);
        $stack->addRoute('foo', new TestAsset\DummyRouteWithParam('foo'));

        $this->assertEquals('bar', $stack->assemble(['foo' => 'bar'], ['name' => 'foo'])->toString());
    }

    public function testPriorityIsPassedToPartRoute(): void
    {
        $stack = new TreeRouteStack($this->createRouteBuilderContainer());
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

        $this->assertEquals(1000, $foo->getPriority());
    }

    public function testChainRouteAssemblingWithChildrenAndSecureScheme(): void
    {
        $stack = new TreeRouteStack($this->createRouteBuilderContainer());

        $uri = new Uri();
        $uri = $uri->withHost('localhost');

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

        $this->assertEquals(
            'https://localhost/foo/baz',
            $stack->assemble([], ['name' => 'foo/baz', 'uri' => $uri])->toString()
        );
    }

    public function testBuilder(): void
    {
        $tester = new BuilderTester();
        $tester->testBuilder(
            TreeRouteStack::class,
            [],
            []
        );
    }
}
