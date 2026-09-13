<?php

declare(strict_types=1);

namespace Laminas\Router;

use Laminas\Router\Builder\SimpleRouteStackBuilder;
use Laminas\Router\Exception\RuntimeException;
use Laminas\Router\Http\Builder\ChainBuilder;
use Laminas\Router\Http\Builder\HostnameBuilder;
use Laminas\Router\Http\Builder\LiteralBuilder;
use Laminas\Router\Http\Builder\MethodBuilder;
use Laminas\Router\Http\Builder\PartBuilder;
use Laminas\Router\Http\Builder\PlaceholderBuilder;
use Laminas\Router\Http\Builder\RegexBuilder;
use Laminas\Router\Http\Builder\SchemeBuilder;
use Laminas\Router\Http\Builder\SegmentBuilder;
use Laminas\Router\Http\Builder\TreeRouteStackBuilder;
use Laminas\Router\Http\Chain;
use Laminas\Router\Http\Hostname;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Method;
use Laminas\Router\Http\Part;
use Laminas\Router\Http\Placeholder;
use Laminas\Router\Http\Regex;
use Laminas\Router\Http\Scheme;
use Laminas\Router\Http\Segment;
use Laminas\Router\Http\TreeRouteStack;
use Psr\Container\ContainerInterface;

use function get_debug_type;
use function is_string;
use function sprintf;

/**
 * @psalm-import-type RouteSpec from RouteInterface
 */
final readonly class RouteBuilderContainer implements RouteBuilderContainerInterface
{
    /**
     * @param array<string, class-string<RouteBuilderInterface>> $builderMap type/alias => builder service id
     */
    public function __construct(
        private ContainerInterface $container,
        private array $builderMap = [],
    ) {
    }

    /**
     * @internal
     *
     * @return array<string, class-string<RouteBuilderInterface>>
     */
    public static function defaultBuilderMap(): array
    {
        return [
            'chain'                 => ChainBuilder::class,
            'Chain'                 => ChainBuilder::class,
            Chain::class            => ChainBuilder::class,
            'hostname'              => HostnameBuilder::class,
            'Hostname'              => HostnameBuilder::class,
            'hostName'              => HostnameBuilder::class,
            'HostName'              => HostnameBuilder::class,
            Hostname::class         => HostnameBuilder::class,
            'literal'               => LiteralBuilder::class,
            'Literal'               => LiteralBuilder::class,
            Literal::class          => LiteralBuilder::class,
            'method'                => MethodBuilder::class,
            'Method'                => MethodBuilder::class,
            Method::class           => MethodBuilder::class,
            'part'                  => PartBuilder::class,
            'Part'                  => PartBuilder::class,
            Part::class             => PartBuilder::class,
            'regex'                 => RegexBuilder::class,
            'Regex'                 => RegexBuilder::class,
            Regex::class            => RegexBuilder::class,
            'scheme'                => SchemeBuilder::class,
            'Scheme'                => SchemeBuilder::class,
            Scheme::class           => SchemeBuilder::class,
            'segment'               => SegmentBuilder::class,
            'Segment'               => SegmentBuilder::class,
            Segment::class          => SegmentBuilder::class,
            'placeholder'           => PlaceholderBuilder::class,
            'Placeholder'           => PlaceholderBuilder::class,
            Placeholder::class      => PlaceholderBuilder::class,
            SimpleRouteStack::class => SimpleRouteStackBuilder::class,
            TreeRouteStack::class   => TreeRouteStackBuilder::class,
        ];
    }

    /**
     * @param array<array-key, mixed> $options
     */
    public function build(array $options): RouteInterface
    {
        /** @var mixed $type */
        $type = $options['type'] ?? '';

        if (! is_string($type) || $type === '') {
            throw new RuntimeException('Route option "type" must be a non-empty string');
        }

        return $this->get($type)->build($options);
    }

    public function get(string $id): RouteBuilderInterface
    {
        $serviceId = $this->builderMap[$id] ?? $id;

        if (! $this->container->has($serviceId)) {
            throw new RuntimeException(sprintf(
                'Unable to resolve route builder for type "%s" (service "%s")',
                $id,
                $serviceId
            ));
        }

        /** @var mixed $builder */
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

    public function has(string $id): bool
    {
        $serviceId = $this->builderMap[$id] ?? $id;

        return $this->container->has($serviceId);
    }
}
