<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use Laminas\Diactoros\Request;
use Laminas\Diactoros\Uri;
use Laminas\Router\AssembledUrl;
use Laminas\Router\Exception\RuntimeException;
use Laminas\Router\Http\HttpRouteInterface;
use Laminas\Router\Http\TranslatorAwareTreeRouteStack;
use Laminas\Router\RoutePluginManager;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Translator\TranslatorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

final class TranslatorAwareTreeRouteStackTest extends TestCase
{
    private array $fooRoute = [
        'type'         => 'Segment',
        'options'      => [
            'route' => '/:locale',
        ],
        'child_routes' => [
            'index' => [
                'type'    => 'Segment',
                'options' => [
                    'route' => '/{homepage}',
                ],
            ],
        ],
    ];

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
                if ($message === 'homepage' && $textDomain === 'default' && $locale === null) {
                    return 'homepage';
                }

                if ($message === 'homepage' && $textDomain === 'route' && $locale === 'en') {
                    return 'homepage';
                }

                if ($message === 'homepage' && $textDomain === 'route' && $locale === 'de') {
                    return 'hauptseite';
                }

                if ($message === 'homepage' && $textDomain === 'default' && $locale === 'de-DE') {
                    return 'hauptseite';
                }

                throw new UnexpectedValueException('Translation not found');
            });

        return $translator;
    }

    public function testTranslatorIsPassedThroughMatchMethod(): void
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $request    = new Request();

        $route = $this->createMock(HttpRouteInterface::class);
        $route->expects($this->once())
              ->method('match')
            ->with(
                $this->equalTo($request),
                $this->isNull(),
                $this->equalTo(['translator' => $translator, 'text_domain' => 'default'])
            );

        /** @var TranslatorAwareTreeRouteStack<HttpRouteInterface> $stack */
        $stack = new TranslatorAwareTreeRouteStack(
            new RoutePluginManager(new ServiceManager()),
            translator: $translator
        );
        $stack->addRoute('test', $route);

        $stack->match($request, null, ['translator' => $translator]);
    }

    public function testTranslatorIsPassedThroughAssembleMethod(): void
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $uri        = new Uri();

        $route = $this->createMock(HttpRouteInterface::class);
        $route->expects($this->once())
              ->method('assemble')
            ->with(
                $this->equalTo([]),
                $this->equalTo(['translator' => $translator, 'text_domain' => 'default', 'uri' => $uri])
            )->willReturn(new AssembledUrl());

        /** @var TranslatorAwareTreeRouteStack<HttpRouteInterface> $stack */
        $stack = new TranslatorAwareTreeRouteStack(
            new RoutePluginManager(new ServiceManager()),
            translator: $translator
        );
        $stack->addRoute('test', $route);

        $stack->assemble([], ['name' => 'test', 'translator' => $translator, 'uri' => $uri]);
    }

    public function testAssembleRouteWithParameterLocale(): void
    {
        $translator = $this->getTranslator(2);
        $stack      = new TranslatorAwareTreeRouteStack(
            new RoutePluginManager(new ServiceManager()),
            translator: $translator,
            translatorTextDomain: 'route'
        );
        $stack->addRoute(
            'foo',
            $this->fooRoute
        );

        $this->assertEquals(
            '/de/hauptseite',
            $stack->assemble(['locale' => 'de'], ['name' => 'foo/index'])->toString()
        );
        $this->assertEquals(
            '/en/homepage',
            $stack->assemble(['locale' => 'en'], ['name' => 'foo/index'])->toString()
        );
    }

    public function testMatchRouteWithParameterLocale(): void
    {
        $translator = $this->getTranslator(1);
        $stack      = new TranslatorAwareTreeRouteStack(
            new RoutePluginManager(new ServiceManager()),
            translator: $translator,
            translatorTextDomain: 'route'
        );
        $stack->addRoute(
            'foo',
            $this->fooRoute
        );

        $request = new Request();
        $request = $request->withUri(new Uri('http://example.com/de/hauptseite'));

        $match = $stack->match($request);
        $this->assertNotNull($match);
        $this->assertSame($translator, $stack->getTranslator());
        $this->assertSame('route', $stack->getTranslatorTextDomain());
        $this->assertEquals('foo/index', $match->getMatchedRouteName());
    }

    public function testTranslatorIsRequired(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid "translator" option');

        self::assertInstanceOf(
            TranslatorAwareTreeRouteStack::class,
            TranslatorAwareTreeRouteStack::factory([
                'route_plugins' => new RoutePluginManager(new ServiceManager()),
            ])
        );
    }
}
