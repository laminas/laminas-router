<?php

declare(strict_types=1);

namespace Laminas\Router;

use Http\Discovery\Psr17FactoryDiscovery;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\UriFactoryInterface;

final class UriFactory implements FactoryInterface
{
    /**
     * Create and return the router
     *
     * Delegates to the HttpRouter service.
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null
    ): UriFactoryInterface {
        return Psr17FactoryDiscovery::findUriFactory();
    }
}
