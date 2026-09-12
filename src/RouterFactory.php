<?php

declare(strict_types=1);

namespace Laminas\Router;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

use function assert;

/**
 * @internal
 *
 * @psalm-internal \Laminas\Router
 * @psalm-internal \LaminasTest\Router
 */
final readonly class RouterFactory implements FactoryInterface
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
    ): RouteStackInterface {
        $router = $container->get(RouteStackInterface::class);

        assert($router instanceof RouteStackInterface);

        return $router;
    }
}
