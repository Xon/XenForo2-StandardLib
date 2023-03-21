<?php

namespace SV\StandardLib\XF\Mvc;

use SV\StandardLib\Repository\LinkBuilder;
use XF\Mvc\RouteBuiltLink;
use function is_string;

/**
 * Extends \XF\Mvc\Router
 */
class Router extends XFCP_Router
{
    public function __construct($linkFormatter = null, array $routes = [])
    {
        parent::__construct($linkFormatter, $routes);

        /** @var LinkBuilder $repo */
        $repo = \XF::app()->repository('SV\StandardLib:LinkBuilder');
        $repo->hookRouteBuilder($this);
    }

    /**
     * @param string $prefix
     * @param array  $route
     * @param string $action
     * @param mixed  $data
     * @param array  $parameters
     * @return \XF\Mvc\RouteBuiltLink|string|null
     */
    protected function buildRouteUrl($prefix, array $route, $action, $data = null, array &$parameters = [])
    {
        $buildCallbackList = $route['build_callback_list'] ?? null;
        if ($buildCallbackList !== null)
        {
            $suppressDefaultCallback = false;
            foreach ($buildCallbackList as $callable)
            {
                /** @var callable(string&,array&,string&,mixed&,array&,\XF\Mvc\Router, bool&): mixed $callable */
                $output = $callable($prefix, $route, $action, $data, $parameters, $this, $suppressDefaultCallback);
                if ($output === false)
                {
                    break;
                }
                elseif (is_string($output) || $output instanceof RouteBuiltLink)
                {
                    return $output;
                }
            }

            if ($suppressDefaultCallback)
            {
                $route['build_callback'] = null;
            }
        }

        return parent::buildRouteUrl($prefix, $route, $action, $data, $parameters);
    }
}