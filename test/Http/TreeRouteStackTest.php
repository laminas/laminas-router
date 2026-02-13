<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use ArrayIterator;
use Laminas\Router\Exception\InvalidArgumentException;
use Laminas\Router\Exception\RuntimeException;
use Laminas\Router\Http\Hostname;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\RoutePluginManager;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Uri\Http as HttpUri;
use LaminasTest\Router\FactoryTester;
use LaminasTest\Router\TestAsset\DummyRoute;
use LaminasTest\Router\TestAsset\MockServerRequest;
use LaminasTest\Router\TestAsset\MockUri;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionClass;
use TypeError;

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

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAddRouteViaStringRequiresHttpSpecificRoute(): void
    {
        $plugins = new RoutePluginManager(new ServiceManager(), [
            'invokables' => [
                DummyRoute::class => DummyRoute::class,
            ],
        ]);
        $stack   = new TreeRouteStack($plugins);

        $this->expectException(TypeError::class);
        $stack->addRoute('foo', [
            'type' => DummyRoute::class,
        ]);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAddRouteAcceptsTraversable(): void
    {
        $stack = new TreeRouteStack($this->createRoutePluginManager());
        $stack->addRoute('foo', new ArrayIterator([
            'type' => TestAsset\DummyRoute::class,
        ]));
        $this->assertTrue($stack->hasRoute('foo'));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testBaseUrlLengthIsPassedAsOffset(): void
    {
        $stack = new TreeRouteStack($this->createRoutePluginManager());
        $stack->setBaseUrl('/foo');
        $stack->addRoute('foo', [
            'type' => TestAsset\DummyRoute::class,
        ]);

        $this->assertEquals(4, $stack->match(new MockServerRequest(new MockUri()))->getParam('offset'));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testNoOffsetIsPassedWithoutBaseUrl(): void
    {
        $stack = new TreeRouteStack($this->createRoutePluginManager());
        $stack->addRoute('foo', [
            'type' => TestAsset\DummyRoute::class,
        ]);

        $this->assertEquals(null, $stack->match(new MockServerRequest(new MockUri()))->getParam('offset'));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAssemble(): void
    {
        $stack = new TreeRouteStack();
        $stack->addRoute('foo', new TestAsset\DummyRoute());
        $this->assertEquals('', $stack->assemble([], ['name' => 'foo']));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAssembleCanonicalUriWithoutRequestUri(): void
    {
        $stack = new TreeRouteStack();
        $stack->addRoute('foo', new TestAsset\DummyRoute());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Request URI has not been set');
        $stack->assemble([], ['name' => 'foo', 'force_canonical' => true]);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAssembleCanonicalUriWithRequestUri(): void
    {
        $uri   = new HttpUri('https://example.com:8080/');
        $stack = new TreeRouteStack();
        $stack->setRequestUri($uri);

        $stack->addRoute('foo', new TestAsset\DummyRoute());
        $this->assertEquals(
            'https://example.com:8080/',
            $stack->assemble([], ['name' => 'foo', 'force_canonical' => true])
        );
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAssembleCanonicalUriWithGivenUri(): void
    {
        $uri   = new HttpUri('https://example.com:8080/');
        $stack = new TreeRouteStack();

        $stack->addRoute('foo', new TestAsset\DummyRoute());
        $this->assertEquals(
            'https://example.com:8080/',
            $stack->assemble([], ['name' => 'foo', 'uri' => $uri, 'force_canonical' => true])
        );
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAssembleCanonicalUriWithHostnameRoute(): void
    {
        $stack = new TreeRouteStack();
        $stack->addRoute('foo', new Hostname('example.com'));
        $uri = new HttpUri();
        $uri->setScheme('https');

        $this->assertEquals('https://example.com/', $stack->assemble([], ['name' => 'foo', 'uri' => $uri]));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAssembleCanonicalUriWithHostnameRouteWithoutScheme(): void
    {
        $stack = new TreeRouteStack();
        $stack->addRoute('foo', new Hostname('example.com'));
        $uri = new HttpUri();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Request URI has not been set');
        $stack->assemble([], ['name' => 'foo', 'uri' => $uri]);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAssembleCanonicalUriWithHostnameRouteAndRequestUriWithoutScheme(): void
    {
        $uri = new HttpUri();
        $uri->setScheme('https');
        $stack = new TreeRouteStack();
        $stack->setRequestUri($uri);
        $stack->addRoute('foo', new Hostname('example.com'));

        $this->assertEquals('https://example.com/', $stack->assemble([], ['name' => 'foo']));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAssembleWithQueryParams(): void
    {
        $stack = new TreeRouteStack();
        $stack->addRoute(
            'index',
            [
                'type'    => 'Literal',
                'options' => [
                    'route' => '/',
                ],
            ]
        );

        $this->assertEquals('/?foo=bar', $stack->assemble([], ['name' => 'index', 'query' => ['foo' => 'bar']]));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAssembleWithEncodedPath(): void
    {
        $stack = new TreeRouteStack();
        $stack->addRoute(
            'index',
            [
                'type'    => 'Literal',
                'options' => [
                    'route' => '/this%2Fthat',
                ],
            ]
        );

        $this->assertEquals('/this%2Fthat', $stack->assemble([], ['name' => 'index']));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAssembleWithEncodedPathAndQueryParams(): void
    {
        $stack = new TreeRouteStack();
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
            $stack->assemble([], ['name' => 'index', 'query' => ['foo' => 'bar'], 'normalize_path' => false])
        );
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAssembleWithScheme(): void
    {
        $uri = new HttpUri();
        $uri->setScheme('http');
        $uri->setHost('example.com');
        $stack = new TreeRouteStack();
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

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAssembleWithFragment(): void
    {
        $stack = new TreeRouteStack();
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
        $stack = new TreeRouteStack();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing "name" option');
        $stack->assemble();
    }

    public function testAssembleNonExistentRoute(): void
    {
        $stack = new TreeRouteStack();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Route with name "foo" not found');
        $stack->assemble([], ['name' => 'foo']);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAssembleNonExistentChildRoute(): void
    {
        $stack = new TreeRouteStack();
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

    /**
     * @throws ContainerExceptionInterface
     */
    public function testDefaultParamIsAddedToMatch(): void
    {
        $stack = new TreeRouteStack();
        $stack->setBaseUrl('/foo');
        $stack->addRoute('foo', new TestAsset\DummyRoute());
        $stack->setDefaultParam('foo', 'bar');

        $this->assertEquals('bar', $stack->match(new MockServerRequest(new MockUri()))->getParam('foo'));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testDefaultParamDoesNotOverrideParam(): void
    {
        $stack = new TreeRouteStack();
        $stack->setBaseUrl('/foo');
        $stack->addRoute('foo', new TestAsset\DummyRouteWithParam());
        $stack->setDefaultParam('foo', 'baz');

        $this->assertEquals('bar', $stack->match(new MockServerRequest(new MockUri()))->getParam('foo'));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testDefaultParamIsUsedForAssembling(): void
    {
        $stack = new TreeRouteStack();
        $stack->addRoute('foo', new TestAsset\DummyRouteWithParam());
        $stack->setDefaultParam('foo', 'bar');

        $this->assertEquals('bar', $stack->assemble([], ['name' => 'foo']));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testDefaultParamDoesNotOverrideParamForAssembling(): void
    {
        $stack = new TreeRouteStack();
        $stack->addRoute('foo', new TestAsset\DummyRouteWithParam());
        $stack->setDefaultParam('foo', 'baz');

        $this->assertEquals('bar', $stack->assemble(['foo' => 'bar'], ['name' => 'foo']));
    }

    public function testSetBaseUrl(): void
    {
        $stack = new TreeRouteStack();

        $this->assertEquals($stack, $stack->setBaseUrl('/foo/'));
        $this->assertEquals('/foo', $stack->getBaseUrl());
    }

    public function testSetRequestUri(): void
    {
        $uri   = new HttpUri();
        $stack = new TreeRouteStack();

        $this->assertEquals($stack, $stack->setRequestUri($uri));
        $this->assertEquals($uri, $stack->getRequestUri());
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testPriorityIsPassedToPartRoute(): void
    {
        $stack = new TreeRouteStack();
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
        $fooRoute          = $routes->get('foo');

        $this->assertInstanceOf(TreeRouteStack::class, $fooRoute);
        $this->assertEquals(1000, $fooRoute->getPriority());
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testPrototypeRoute(): void
    {
        $stack = new TreeRouteStack();
        $stack->addPrototype(
            'bar',
            ['type' => 'literal', 'options' => ['route' => '/bar']]
        );
        $stack->addRoute('foo', 'bar');
        $this->assertEquals('/bar', $stack->assemble([], ['name' => 'foo']));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testChainRouteAssembling(): void
    {
        $stack = new TreeRouteStack();
        $stack->addPrototype(
            'bar',
            ['type' => 'literal', 'options' => ['route' => '/bar']]
        );
        $stack->addRoute(
            'foo',
            [
                'type'         => 'literal',
                'options'      => [
                    'route' => '/foo',
                ],
                'chain_routes' => [
                    'bar',
                ],
            ]
        );
        $this->assertEquals('/foo/bar', $stack->assemble([], ['name' => 'foo']));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testChainRouteAssemblingWithChildrenAndSecureScheme(): void
    {
        $stack = new TreeRouteStack();

        $uri = new HttpUri();
        $uri->setHost('localhost');

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
        $this->assertEquals('https://localhost/foo/baz', $stack->assemble([], ['name' => 'foo/baz']));
    }

    public function testFactory(): void
    {
        $tester = new FactoryTester($this);
        $tester->testFactory(
            TreeRouteStack::class,
            [],
            []
        );
    }
}
