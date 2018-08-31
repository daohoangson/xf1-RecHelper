<?php

class RecHelper_Listener
{
    public static function api_setup_routes(array &$routes)
    {
        bdApi_Route_PrefixApi::addRoute($routes, 'rec-helper', 'RecHelper_Route_ApiPrefix_RecHelper');
    }

    public static function file_health_check(XenForo_ControllerAdmin_Abstract $controller, array &$hashes)
    {
        $hashes += RecHelper_FileSums::getHashes();
    }
}
