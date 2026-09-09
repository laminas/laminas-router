<?php

declare(strict_types=1);

namespace LaminasTest\Router\Http;

use Laminas\Router\Http\HttpRouterFactory;
use Laminas\Router\Http\TranslatorAwareTreeRouteStack;
use Laminas\Translator\TranslatorInterface;
use LaminasTest\Router\RouteBuilderContainerTestHelper;
use LaminasTest\Router\RouterFactoryTest as TestCase;

final class HttpRouterFactoryTest extends TestCase
{
    public function setUp(): void
    {
        $this->factory = new HttpRouterFactory();
    }

    public function testFactoryCanCreateTranslatorAwareRouter(): void
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $services   = RouteBuilderContainerTestHelper::createServiceManager(
            routerConfig: [
                'router_class' => TranslatorAwareTreeRouteStack::class,
                'translator'   => TranslatorInterface::class,
            ],
            services: [
                TranslatorInterface::class => $translator,
            ],
        );

        $router = $this->factory->__invoke($services, 'router');

        $this->assertInstanceOf(TranslatorAwareTreeRouteStack::class, $router);
        $this->assertSame($translator, $router->getTranslator());
    }
}
