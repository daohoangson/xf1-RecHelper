<?php

class RecHelper_Route_ApiPrefix_RecHelper implements XenForo_Route_Interface
{
    public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
    {
        return $router->getRouteMatch('RecHelper_ControllerApi_RecHelper', $routePath);
    }
}
