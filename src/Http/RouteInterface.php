<?php
/**
 * @link      http://github.com/zendframework/zend-router for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Router\Http;

use Zend\Router\RouteInterface as BaseRoute;

/**
 * Tree specific route interface.
 */
interface RouteInterface extends BaseRoute
{
    /**
     * Get a list of parameters used while assembling.
     *
     * @return array
     */
    public function getAssembledParams();
}
