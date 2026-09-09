<?php

declare(strict_types=1);

namespace Laminas\Router;

use Laminas\Router\RouteBuilderInterface;
use Laminas\Router\RouteInterface;
use Psr\Container\ContainerInterface;

/**
 * Container for resolving route builders by type or alias.
 *
 * Builders are resolved lazily from the container to avoid a construction cycle
 * between composite builders and this container.
 *
 * @psalm-import-type RouteSpec from RouteInterface
 */
interface RouteBuilderContainerInterface extends ContainerInterface
{
    /**
     * @psalm-param RouteSpec $options
     */
    public function build(array $options): RouteInterface;

    /**
     * @template T of RouteBuilderInterface
     * @param string|class-string<T> $id
     * @return ($id is class-string<T> ? T : RouteBuilderInterface)
     */
    public function get(string $id): RouteBuilderInterface;

    public function has(string $id): bool;
}
