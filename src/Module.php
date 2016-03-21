<?php
/**
 * @link      http://github.com/zendframework/zend-router for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Router;

/**
 * Register with a zend-mvc application.
 */
class Module
{
    /**
     * Provide default router configuration.
     *
     * @return array
     */
    public function getConfig()
    {
        $provider = new ConfigProvider();
        return [
            'service_manager' => $provider->getDependencyConfig(),
            'route_manager' => $provider->getRouteManagerConfig(),
            'router' => ['routes' => []],
        ];
    }
}
