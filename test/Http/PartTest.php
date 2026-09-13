<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use Laminas\Diactoros\Request;
use Laminas\Diactoros\Uri;
use Laminas\Router\Exception\InvalidArgumentException;
use Laminas\Router\Exception\RuntimeException;
use Laminas\Router\Http\HttpRouteMatch;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Part;
use Laminas\Router\Http\Segment;
use Laminas\Router\RouteBuilderContainerInterface;
use Laminas\Router\RouteMatchInterface;
use Laminas\Translator\TranslatorInterface;
use LaminasTest\Router\BuilderTester;
use LaminasTest\Router\RouteBuilderContainerTestHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

use function assert;
use function strlen;
use function strpos;

final class PartTest extends TestCase
{
    public static function getRouteBuilderContainer(
        TranslatorInterface|null $translator = null,
    ): RouteBuilderContainerInterface {
        $services = RouteBuilderContainerTestHelper::createServiceManager(translator: $translator);
        // @mago-ignore analysis:mixed-assignment
        $routeBuilderContainer = $services->get(RouteBuilderContainerInterface::class);
        assert($routeBuilderContainer instanceof RouteBuilderContainerInterface);

        return $routeBuilderContainer;
    }

    public static function getRoute(): Part
    {
        return new Part(
            self::getRouteBuilderContainer(),
            [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/foo',
                    'defaults' => [
                        'controller' => 'foo',
                    ],
                ],
            ],
            [],
            null,
            true,
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

    /**
     * @psalm-return array<string, array{
     *     0: Part,
     *     1: string,
     *     2: null|int,
     *     3: null|string,
     *     4: null|array<non-empty-string, non-empty-string>
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
        ];
    }

    /**
     * @param array<non-empty-string, non-empty-string>|null $params
     */
    #[DataProvider('routeProvider')]
    public function testMatching(
        Part $route,
        string $path,
        int|null $offset,
        ?string $routeName,
        ?array $params = null
    ): void {
        $request = new Request();
        $request = $request->withUri(new Uri('http://example.com' . $path));
        $match   = $route->match($request, $offset);

        if ($params === null) {
            $this->assertNull($match);
        } else {
            $this->assertInstanceOf(HttpRouteMatch::class, $match);

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
     * @param array<non-empty-string, non-empty-string>|null $params
     */
    #[DataProvider('routeProvider')]
    public function testAssembling(
        Part $route,
        string $path,
        int|null $offset,
        ?string $routeName,
        ?array $params = null
    ): void {
        if ($params === null) {
            // Data which will not match are not tested for assembling.
            $this->expectNotToPerformAssertions();
            return;
        }

        $result = $route->assemble($params, ['name' => $routeName]);

        if ($offset !== null) {
            $this->assertEquals($offset, strpos($path, $result->toString(), $offset));
        } else {
            $this->assertEquals($path, $result->toString());
        }
    }

    public function testAssembleNonTerminatedRoute(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Part route may not terminate');
        self::getRoute()->assemble([], ['name' => 'baz']);
    }

    public function testBaseRouteMayNotBePartRoute(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Base route may not be a part route');

        new Part(
            self::getRouteBuilderContainer(),
            self::getRoute(),
            [],
            null,
            true
        );
    }

    public function testNoMatchWithoutUriMethod(): void
    {
        $route   = self::getRoute();
        $request = new Request();

        $this->assertNull($route->match($request));
    }

    public function testGetAssembledParams(): void
    {
        $route = self::getRoute();
        $this->assertEquals(
            ['controller'],
            $route->assemble(['controller' => 'foo'], ['name' => 'baz/bat'])->assembledParams,
        );
    }

    public function testBuilder(): void
    {
        $tester = new BuilderTester();
        $tester->testBuilder(
            Part::class,
            [
                'route' => 'Missing "route" in options array',
            ],
            [
                'route' => new Literal('foo', '/foo'),
            ]
        );
    }

    #[Group('3711')]
    public function testPartRouteMarkedAsMayTerminateCanMatchWhenQueryStringPresent(): void
    {
        $options = [
            'type'          => Part::class,
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

        $route   = RouteBuilderContainerTestHelper::create()->build($options);
        $request = new Request();
        $uri     = new Uri('http://example.com/resource?foo=bar');
        $uri     = $uri->withQuery('foo');
        $request = $request->withUri($uri);

        $match = $route->match($request);
        $this->assertInstanceOf(RouteMatchInterface::class, $match);
        $this->assertEquals('resource', $match->getParam('action'));
    }

    private function getLocalePartRoute(TranslatorInterface $translator): Part
    {
        return new Part(
            self::getRouteBuilderContainer($translator),
            [
                'type'    => Segment::class,
                'options' => [
                    'route' => '/:locale',
                ],
            ],
            [],
            null,
            true,
            [
                'index' => [
                    'type'    => Segment::class,
                    'options' => [
                        'route' => '/{homepage}',
                    ],
                ],
            ],
        );
    }

    private function getTranslator(int $expectedCallCount): TranslatorInterface&MockObject
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->exactly($expectedCallCount))
            ->method('translate')
            ->willReturnCallback(function (
                string $message,
                string $textDomain = 'default',
                ?string $locale = null
            ): string {
                if ($message === 'homepage' && $textDomain === 'route' && $locale === 'de') {
                    return 'hauptseite';
                }

                if ($message === 'homepage' && $textDomain === 'route' && $locale === 'en') {
                    return 'homepage';
                }

                throw new UnexpectedValueException('Translation not found');
            });

        return $translator;
    }

    public function testMatchPropagatesLocaleFromParentParam(): void
    {
        $route   = $this->getLocalePartRoute($this->getTranslator(1));
        $request = (new Request())->withUri(new Uri('http://example.com/de/hauptseite'));

        $match = $route->match($request, null, ['text_domain' => 'route']);

        $this->assertInstanceOf(HttpRouteMatch::class, $match);
        $this->assertSame('index', $match->getMatchedRouteName());
    }

    public function testAssemblePropagatesLocaleFromParamsWhenLocaleOptionIsNotSet(): void
    {
        $translator = $this->getTranslator(1);
        $route      = $this->getLocalePartRoute($translator);

        $this->assertSame(
            '/de/hauptseite',
            $route->assemble(
                ['locale' => 'de'],
                ['name' => 'index', 'translator' => $translator, 'text_domain' => 'route']
            )->toString()
        );
    }

    public function testAssembleDoesNotPropagateLocaleWhenLocaleOptionIsSet(): void
    {
        $translator = $this->getTranslator(1);
        $route      = $this->getLocalePartRoute($translator);

        $this->assertSame(
            '/de/homepage',
            $route->assemble(
                ['locale' => 'de'],
                [
                    'name'        => 'index',
                    'locale'      => 'en',
                    'translator'  => $translator,
                    'text_domain' => 'route',
                ]
            )->toString()
        );
    }

    public function testMatchDoesNotPropagateLocaleWhenLocaleOptionIsSet(): void
    {
        $translator = $this->getTranslator(1);
        $route      = $this->getLocalePartRoute($translator);
        $request    = (new Request())->withUri(new Uri('http://example.com/de/hauptseite'));

        $match = $route->match($request, null, [
            'text_domain' => 'route',
            'locale'      => 'en',
        ]);

        $this->assertNull($match);
    }

    public function testAssembleStripsParentAssembledParams(): void
    {
        $route = new Part(
            self::getRouteBuilderContainer(),
            [
                'type'    => Segment::class,
                'options' => [
                    'route' => '/:foo',
                ],
            ],
            [],
            null,
            true,
            [
                'bar' => [
                    'type'    => Segment::class,
                    'options' => [
                        'route'    => '/:foo/baz',
                        'defaults' => ['foo' => '2'],
                    ],
                ],
            ],
        );

        $this->assertSame('/1/2/baz', $route->assemble(['foo' => '1'], ['name' => 'bar'])->toString());
    }

    public function testAssembleChildReturnsPathOnly(): void
    {
        $route = self::getRoute();

        $this->assertSame(
            '/foo/bar',
            $route->assemble(
                ['controller' => 'bar'],
                [
                    'name'            => 'bar',
                    'uri'             => new Uri('https://example.com'),
                    'force_canonical' => true,
                ]
            )->toString()
        );
    }
}
