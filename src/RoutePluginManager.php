<?php

declare(strict_types=1);

namespace Laminas\Router;

use Laminas\Router\Http\Chain;
use Laminas\Router\Http\Hostname;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Method;
use Laminas\Router\Http\Part;
use Laminas\Router\Http\Placeholder;
use Laminas\Router\Http\Regex;
use Laminas\Router\Http\Scheme;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\Exception\InvalidServiceException;
use Laminas\ServiceManager\ServiceManager;
use Psr\Container\ContainerInterface;

use function array_merge;
use function array_replace_recursive;
use function get_debug_type;
use function sprintf;

/**
 * Plugin manager implementation for routes
 *
 * Enforces that routes retrieved are instances of RouteInterface. It overrides
 * configure() to map invokables to the component-specific
 * RouteInvokableFactory.
 *
 * The manager is marked to not share by default, in order to allow multiple
 * route instances of the same type.
 *
 * @deprecated since 3.20.0
 *
 * @see ServiceManager for expected configuration shape
 *
 * @psalm-type InstanceType = RouteInterface
 * @extends AbstractPluginManager<InstanceType>
 * @psalm-import-type FactoriesConfiguration from ServiceManager
 * @psalm-import-type ServiceManagerConfiguration from ServiceManager
 */
final class RoutePluginManager extends AbstractPluginManager
{
    /**
     * @psalm-var ServiceManagerConfiguration
     */
    private const CONFIG = [
        'aliases'   => [
            'chain'       => Chain::class,
            'Chain'       => Chain::class,
            'hostname'    => Hostname::class,
            'Hostname'    => Hostname::class,
            'hostName'    => Hostname::class,
            'HostName'    => Hostname::class,
            'literal'     => Literal::class,
            'Literal'     => Literal::class,
            'method'      => Method::class,
            'Method'      => Method::class,
            'part'        => Part::class,
            'Part'        => Part::class,
            'regex'       => Regex::class,
            'Regex'       => Regex::class,
            'scheme'      => Scheme::class,
            'Scheme'      => Scheme::class,
            'segment'     => Segment::class,
            'Segment'     => Segment::class,
            'Placeholder' => Placeholder::class,
            'placeholder' => Placeholder::class,
        ],
        'factories' => [
            Chain::class       => RouteInvokableFactory::class,
            Hostname::class    => RouteInvokableFactory::class,
            Literal::class     => RouteInvokableFactory::class,
            Method::class      => RouteInvokableFactory::class,
            Part::class        => RouteInvokableFactory::class,
            Regex::class       => RouteInvokableFactory::class,
            Scheme::class      => RouteInvokableFactory::class,
            Segment::class     => RouteInvokableFactory::class,
            Placeholder::class => RouteInvokableFactory::class,
        ],
    ];
    /**
     * Only RouteInterface instances are valid
     *
     * @var class-string
     */
    protected string $instanceOf = RouteInterface::class;

    /**
     * Do not share instances.
     */
    protected bool $sharedByDefault = false;

    /**
     * Constructor
     *
     * Ensure that the instance is seeded with the RouteInvokableFactory as an
     * abstract factory.
     *
     * @param ServiceManagerConfiguration $config
     */
    public function __construct(ContainerInterface $container, array $config = [])
    {
        /** @psalm-var ServiceManagerConfiguration $config Psalm cannot infer this after merge */
        $config = array_replace_recursive(self::CONFIG, $config);
        parent::__construct($container, $config);
    }

    /**
     * Validate a route plugin. (v2)
     *
     * @throws InvalidServiceException
     * @psalm-assert InstanceType $instance
     */
    public function validate(mixed $instance): void
    {
        if (! $instance instanceof $this->instanceOf) {
            throw new InvalidServiceException(sprintf(
                'Plugin of type %s is invalid; must implement %s',
                get_debug_type($instance),
                RouteInterface::class
            ));
        }
    }

    /**
     * Pre-process configuration. (v3)
     *
     * Checks for invokables, and, if found, maps them to the
     * component-specific RouteInvokableFactory; removes the invokables entry
     * before passing to the parent.
     *
     * @param ServiceManagerConfiguration $config
     */
    public function configure(array $config): static
    {
        if (isset($config['invokables']) && ! empty($config['invokables'])) {
            $aliases   = $this->createAliasesForInvokables($config['invokables']);
            $factories = $this->createFactoriesForInvokables($config['invokables']);

            if (! empty($aliases)) {
                $config['aliases'] = isset($config['aliases'])
                    ? array_merge($config['aliases'], $aliases)
                    : $aliases;
            }

            if (isset($config['factories'])) {
                /** @psalm-var FactoriesConfiguration $existingFactories */
                $existingFactories   = $config['factories'];
                $config['factories'] = array_merge($existingFactories, $factories);
            } else {
                $config['factories'] = $factories;
            }

            unset($config['invokables']);
        }

        /** @psalm-var ServiceManagerConfiguration $config */
        parent::configure($config);

        return $this;
    }

     /**
      * Create aliases for invokable classes.
      *
      * If an invokable service name does not match the class it maps to, this
      * creates an alias to the class (which will later be mapped as an
      * invokable factory).
      *
      * @param array<string, class-string> $invokables
      * @return array<string, class-string>
      */
    protected function createAliasesForInvokables(array $invokables): array
    {
        $aliases = [];
        foreach ($invokables as $name => $class) {
            if ($name === $class) {
                continue;
            }
            $aliases[$name] = $class;
        }
        return $aliases;
    }

    /**
     * Create invokable factories for invokable classes.
     *
     * If an invokable service name does not match the class it maps to, this
     * creates an invokable factory entry for the class name; otherwise, it
     * creates an invokable factory for the entry name.
     *
     * @param array<string, class-string> $invokables
     * @return array<class-string, class-string>
     */
    protected function createFactoriesForInvokables(array $invokables): array
    {
        $factories = [];
        foreach ($invokables as $name => $class) {
            if ($name === $class) {
                $factories[$name] = RouteInvokableFactory::class;
                continue;
            }

            $factories[$class] = RouteInvokableFactory::class;
        }
        return $factories;
    }
}
