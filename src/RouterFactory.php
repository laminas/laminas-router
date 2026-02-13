<?php

declare(strict_types=1);

namespace Laminas\Router;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final readonly class RouterFactory implements FactoryInterface
{
    /**
     * Create and return the router
     * Delegates to the HttpRouter service.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @return RouteStackInterface
     */
    public function __invoke(ContainerInterface $container, string $requestedName, ?array $options = null): mixed
    {
        return $container->get('HttpRouter');
    }
}
