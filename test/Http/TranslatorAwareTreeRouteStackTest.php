<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use ArrayObject;
use Laminas\Diactoros\Request;
use Laminas\Diactoros\Uri;
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

    public function testTranslatorAwareInterfaceImplementation(): void
    {
        /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();
        $stack      = new TranslatorAwareTreeRouteStack(new RoutePluginManager(new ServiceManager()), $prototypes);

        // Defaults
        $this->assertNull($stack->getTranslator());
        $this->assertFalse($stack->hasTranslator());
        $this->assertEquals('default', $stack->getTranslatorTextDomain());
        $this->assertTrue($stack->isTranslatorEnabled());

        // Inject translator without text domain
        $translator = $this->createStub(TranslatorInterface::class);
        $stack->setTranslator($translator);
        $this->assertSame($translator, $stack->getTranslator());
        $this->assertEquals('default', $stack->getTranslatorTextDomain());
        $this->assertTrue($stack->hasTranslator());

        // Reset translator
        $stack->setTranslator(null);
        $this->assertNull($stack->getTranslator());
        $this->assertFalse($stack->hasTranslator());

        // Inject translator with text domain
        $stack->setTranslator($translator, 'alternative');
        $this->assertSame($translator, $stack->getTranslator());
        $this->assertEquals('alternative', $stack->getTranslatorTextDomain());

        // Set text domain
        $stack->setTranslatorTextDomain('default');
        $this->assertEquals('default', $stack->getTranslatorTextDomain());

        // Disable translator
        $stack->setTranslatorEnabled(false);
        $this->assertFalse($stack->isTranslatorEnabled());
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

        /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();
        $stack      = new TranslatorAwareTreeRouteStack(new RoutePluginManager(new ServiceManager()), $prototypes);
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
            );

                /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();
        $stack      = new TranslatorAwareTreeRouteStack(new RoutePluginManager(new ServiceManager()), $prototypes);
        $stack->addRoute('test', $route);

        $stack->assemble([], ['name' => 'test', 'translator' => $translator, 'uri' => $uri]);
    }

    public function testAssembleRouteWithParameterLocale(): void
    {
                /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();
        $stack      = new TranslatorAwareTreeRouteStack(new RoutePluginManager(new ServiceManager()), $prototypes);
        $stack->setTranslator($this->getTranslator(2), 'route');
        $stack->addRoute(
            'foo',
            $this->fooRoute
        );

        $this->assertEquals('/de/hauptseite', $stack->assemble(['locale' => 'de'], ['name' => 'foo/index']));
        $this->assertEquals('/en/homepage', $stack->assemble(['locale' => 'en'], ['name' => 'foo/index']));
    }

    public function testMatchRouteWithParameterLocale(): void
    {
                /** @var ArrayObject<string, HttpRouteInterface> $prototypes */
        $prototypes = new ArrayObject();
        $stack      = new TranslatorAwareTreeRouteStack(new RoutePluginManager(new ServiceManager()), $prototypes);
        $stack->setTranslator($this->getTranslator(1), 'route');
        $stack->addRoute(
            'foo',
            $this->fooRoute
        );

        $request = new Request();
        $request = $request->withUri(new Uri('http://example.com/de/hauptseite'));

        $match = $stack->match($request);
        $this->assertNotNull($match);
        $this->assertEquals('foo/index', $match->getMatchedRouteName());
    }
}
