<?php

namespace SV\StandardLib\Repository;

use XF\Mvc\Entity\Repository;
use XF\Mvc\Router;
use function is_array;
use function method_exists;

class LinkBuilder extends Repository
{
    /**
     * @param Router $router
     * @return void
     */
    public function hookRouteBuilder(\XF\Mvc\Router $router)
    {
        $app = $this->app();
        $classType = $app->container('app.classType');
        $app->fire('patch_route_build_callback', [$this, $router], $classType);
    }

    /**
     * @param Router   $router
     * @param string   $routeLabel
     * @param callable $callable Should have the signature: callable(string &$prefix,array &$route,string &$action,&$data,array &$params,\XF\Mvc\Router $router):\XF\Mvc\RouteBuiltLink|string|false|null
     * @return void
     */
    public function injectLinkBuilderCallback(\XF\Mvc\Router $router, string $routeLabel, callable $callable)
    {
        $routes = $router->getRoutes();

        $routeSection = $routes[$routeLabel] ?? null;
        if ($routeSection === null)
        {
            return;
        }

        if (is_array($callable) && method_exists(\Closure::class, 'fromCallable'))
        {
            /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
            $callable = \Closure::fromCallable($callable);
        }

        foreach ($routeSection as $subSection => $route)
        {
            $route['build_callback_list'][] = $callable;
            $router->addRoute($routeLabel, $subSection, $route);
        }
    }

    /**
     * @param Router   $router
     * @param string   $routeLabel
     * @param callable $callable Should have the signature: callable(string &$prefix,array &$route,string &$action,&$data,array &$params,\XF\Mvc\Router $router):\XF\Mvc\RouteBuiltLink|string|false|null
     * @return void
     */
    public function injectLinkBuilderCallbackForSubsection(\XF\Mvc\Router $router, string $routeLabel, string $subSection, callable $callable)
    {
        $routes = $router->getRoutes();

        $route = $routes[$routeLabel][$subSection] ?? null;
        if ($route === null)
        {
            return;
        }

        if (is_array($callable) && method_exists(\Closure::class, 'fromCallable'))
        {
            /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
            $callable = \Closure::fromCallable($callable);
        }

        $route['build_callback_list'][] = $callable;
        $router->addRoute($routeLabel, $subSection, $route);
    }
}