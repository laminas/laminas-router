<?php

declare(strict_types=1);

namespace Laminas\Router;

use Laminas\Router\Exception\RuntimeException;
use Laminas\Router\Http\Chain;
use Laminas\Router\Http\ChainBuilder;
use Laminas\Router\Http\Hostname;
use Laminas\Router\Http\HostnameBuilder;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\LiteralBuilder;
use Laminas\Router\Http\Method;
use Laminas\Router\Http\MethodBuilder;
use Laminas\Router\Http\Part;
use Laminas\Router\Http\PartBuilder;
use Laminas\Router\Http\Placeholder;
use Laminas\Router\Http\PlaceholderBuilder;
use Laminas\Router\Http\Regex;
use Laminas\Router\Http\RegexBuilder;
use Laminas\Router\Http\Scheme;
use Laminas\Router\Http\SchemeBuilder;
use Laminas\Router\Http\Segment;
use Laminas\Router\Http\SegmentBuilder;
use Laminas\Router\Http\TranslatorAwareTreeRouteStack;
use Laminas\Router\Http\TranslatorAwareTreeRouteStackBuilder;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\Http\TreeRouteStackBuilder;
use Laminas\Router\Http\Wildcard;
use Laminas\Router\Http\WildcardBuilder;
use Psr\Container\ContainerInterface;

use function get_debug_type;
use function sprintf;

/**
 * Container for resolving route builders by type or alias.
 *
 * Builders are resolved lazily from the container to avoid a construction cycle
 * between composite builders and this container.
 */
final class RouteBuilderContainer implements ContainerInterface
{
    /**
     * @param array<string, string> $builderMap type/alias => builder service id
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly array $builderMap = [],
    ) {
    }

    /**
     * @return array<string, class-string>
     */
    public static function defaultBuilderMap(): array
    {
        return [
            'chain'                              => ChainBuilder::class,
            'Chain'                              => ChainBuilder::class,
            Chain::class                         => ChainBuilder::class,
            'hostname'                           => HostnameBuilder::class,
            'Hostname'                           => HostnameBuilder::class,
            'hostName'                           => HostnameBuilder::class,
            'HostName'                           => HostnameBuilder::class,
            Hostname::class                      => HostnameBuilder::class,
            'literal'                            => LiteralBuilder::class,
            'Literal'                            => LiteralBuilder::class,
            Literal::class                       => LiteralBuilder::class,
            'method'                             => MethodBuilder::class,
            'Method'                             => MethodBuilder::class,
            Method::class                        => MethodBuilder::class,
            'part'                               => PartBuilder::class,
            'Part'                               => PartBuilder::class,
            Part::class                          => PartBuilder::class,
            'regex'                              => RegexBuilder::class,
            'Regex'                              => RegexBuilder::class,
            Regex::class                         => RegexBuilder::class,
            'scheme'                             => SchemeBuilder::class,
            'Scheme'                             => SchemeBuilder::class,
            Scheme::class                        => SchemeBuilder::class,
            'segment'                            => SegmentBuilder::class,
            'Segment'                            => SegmentBuilder::class,
            Segment::class                       => SegmentBuilder::class,
            'placeholder'                        => PlaceholderBuilder::class,
            'Placeholder'                        => PlaceholderBuilder::class,
            Placeholder::class                   => PlaceholderBuilder::class,
            SimpleRouteStack::class              => SimpleRouteStackBuilder::class,
            TreeRouteStack::class                => TreeRouteStackBuilder::class,
            TranslatorAwareTreeRouteStack::class => TranslatorAwareTreeRouteStackBuilder::class,
            'wildcard'                           => WildcardBuilder::class,
            'Wildcard'                           => WildcardBuilder::class,
            Wildcard::class                      => WildcardBuilder::class,
        ];
    }

    /**
     * @param array<string, mixed> $options
     */
    public function build(string $type, array $options = []): RouteInterface
    {
        return $this->get($type)->build($options);
    }

    public function get(string $type): RouteBuilderInterface
    {
        $serviceId = $this->builderMap[$type] ?? $type;

        if (! $this->container->has($serviceId)) {
            throw new RuntimeException(sprintf(
                'Unable to resolve route builder for type "%s" (service "%s")',
                $type,
                $serviceId
            ));
        }

        $builder = $this->container->get($serviceId);

        if (! $builder instanceof RouteBuilderInterface) {
            throw new RuntimeException(sprintf(
                'Route builder service "%s" must implement %s; got %s',
                $serviceId,
                RouteBuilderInterface::class,
                get_debug_type($builder)
            ));
        }

        return $builder;
    }

    public function has(string $type): bool
    {
        $serviceId = $this->builderMap[$type] ?? $type;

        return $this->container->has($serviceId);
    }
}
