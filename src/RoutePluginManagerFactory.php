<?php
/**
 * @link      http://github.com/zendframework/zend-router for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Router;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class RoutePluginManagerFactory implements FactoryInterface
{
    /**
     * Create and return a route plugin manager.
     *
     * @param  ContainerInterface $container
     * @param  string $name
     * @param  null|array $options
     * @return RoutePluginManager
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        $options = $options ?: [];
        return new RoutePluginManager($container, $options);
    }

    /**
     * Create and return RoutePluginManager instance.
     *
     * For use with zend-servicemanager v2; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return RoutePluginManager
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, RoutePluginManager::class);
    }
}
