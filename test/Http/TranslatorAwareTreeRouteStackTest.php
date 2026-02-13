<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use Laminas\I18n\Translator\Translator;
use Laminas\I18n\Translator\TranslatorAwareInterface;
use Laminas\Router\Http\HttpRouteInterface;
use Laminas\Router\Http\TranslatorAwareTreeRouteStack;
use Laminas\Translator\TranslatorInterface;
use Laminas\Uri\Http as HttpUri;
use LaminasTest\Router\TestAsset\MockServerRequest;
use LaminasTest\Router\TestAsset\MockUri;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;

use function class_exists;

final class TranslatorAwareTreeRouteStackTest extends TestCase
{
    protected string $testFilesDir;

    protected TranslatorInterface $translator;

    protected array $fooRoute;

    public function setUp(): void
    {
        if (! class_exists(TranslatorAwareInterface::class)) {
            $this->markTestSkipped('laminas-i18n is not installed');
        }

        $this->testFilesDir = __DIR__ . '/_files';

        $this->translator = new Translator();
        $this->translator->addTranslationFile('phpArray', $this->testFilesDir . '/tokens.en.php', 'route', 'en');
        $this->translator->addTranslationFile('phpArray', $this->testFilesDir . '/tokens.de.php', 'route', 'de');

        $this->fooRoute = [
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
    }

    public function testTranslatorAwareInterfaceImplementation(): void
    {
        $stack = new TranslatorAwareTreeRouteStack();
        $this->assertInstanceOf(TranslatorAwareInterface::class, $stack);

        // Defaults
        $this->assertNull($stack->getTranslator());
        $this->assertFalse($stack->hasTranslator());
        $this->assertEquals('default', $stack->getTranslatorTextDomain());
        $this->assertTrue($stack->isTranslatorEnabled());

        // Inject translator without text domain
        $translator = new Translator();
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

    /**
     * @throws ContainerExceptionInterface
     */
    public function testTranslatorIsPassedThroughMatchMethod(): void
    {
        $translator = new Translator();
        $request    = new MockServerRequest(new MockUri('http://example.com/'));

        $route = $this->createMock(HttpRouteInterface::class);
        $route->expects($this->once())
              ->method('match')
            ->with(
                $this->equalTo($request),
                $this->isNull(),
                $this->equalTo(['translator' => $translator, 'text_domain' => 'default'])
            );

        $stack = new TranslatorAwareTreeRouteStack();
        $stack->addRoute('test', $route);

        $stack->match($request, null, ['translator' => $translator]);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testTranslatorIsPassedThroughAssembleMethod(): void
    {
        $translator = new Translator();
        $uri        = new HttpUri();

        $route = $this->createMock(HttpRouteInterface::class);
        $route->expects($this->once())
              ->method('assemble')
            ->with(
                $this->equalTo([]),
                $this->equalTo(['translator' => $translator, 'text_domain' => 'default', 'uri' => $uri])
            );

        $stack = new TranslatorAwareTreeRouteStack();
        $stack->addRoute('test', $route);

        $stack->assemble([], ['name' => 'test', 'translator' => $translator, 'uri' => $uri]);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testAssembleRouteWithParameterLocale(): void
    {
        $stack = new TranslatorAwareTreeRouteStack();
        $stack->setTranslator($this->translator, 'route');
        $stack->addRoute(
            'foo',
            $this->fooRoute
        );

        $this->assertEquals('/de/hauptseite', $stack->assemble(['locale' => 'de'], ['name' => 'foo/index']));
        $this->assertEquals('/en/homepage', $stack->assemble(['locale' => 'en'], ['name' => 'foo/index']));
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testMatchRouteWithParameterLocale(): void
    {
        $stack = new TranslatorAwareTreeRouteStack();
        $stack->setTranslator($this->translator, 'route');
        $stack->addRoute(
            'foo',
            $this->fooRoute
        );

        $request = new MockServerRequest(new MockUri('https://example.com/de/hauptseite'));

        $match = $stack->match($request);
        $this->assertNotNull($match);
        $this->assertEquals('foo/index', $match->getMatchedRouteName());
    }
}
