<?php

declare(strict_types=1);

namespace Laminas\Router\Http\Builder;

use Laminas\Router\RouterConfig;
use Laminas\Translator\TranslatorInterface;
use Psr\Container\ContainerInterface;

use function assert;

/**
 * @internal
 *
 * @psalm-internal \Laminas\Router
 * @psalm-internal \LaminasTest\Router
 */
final readonly class SegmentBuilderFactory
{
    public function __invoke(ContainerInterface $container): SegmentBuilder
    {
        $config = $container->get(RouterConfig::class);

        assert($config instanceof RouterConfig);

        $translator = $container->has($config->translator) ? $container->get($config->translator) : null;
        assert($translator instanceof TranslatorInterface || $translator === null);

        return new SegmentBuilder($translator);
    }
}
