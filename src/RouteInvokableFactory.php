<?php

declare(strict_types=1);

namespace Laminas\Router;

use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Psr\Container\ContainerInterface;

use function class_exists;
use function is_subclass_of;

/**
 * Specialized invokable/abstract factory for use with RoutePluginManager.
 *
 * Can be mapped directly to specific route plugin names, or used as an
 * abstract factory to map FQCN services to invokables.
 */
final readonly class RouteInvokableFactory implements AbstractFactoryInterface
{
    /**
     * Can we create a route instance with the given name?
     *
     * Only works for FQCN $requestedName values, for classes that implement RouteInterface.
     */
    public function canCreate(ContainerInterface $container, string $requestedName): bool
    {
        return class_exists($requestedName) && is_subclass_of($requestedName, RouteInterface::class);
    }

    /**
     * Create and return a RouteInterface instance.
     * If the specified $requestedName class does not exist or does not implement
     * RouteInterface, this method will raise an exception.
     * Otherwise, it uses the class' `factory()` method with the provided
     * $options to produce an instance.
     */
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null
    ): RouteInterface {
        $options ??= [];

        /** @psalm-var class-string<RouteInterface> $requestedName */
        return $requestedName::factory($options);
    }
}
