<?php

namespace SV\StandardLib\ControllerPlugin;

use XF\ControllerPlugin\AbstractPlugin;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Reply\View;

/**
 * Class Filter
 *
 * @package SV\StandardLib\ControllerPlugin
 */
class Filter extends AbstractPlugin
{
    /**
     * @param View $reply
     * @param string $route
     * @param Entity|null $container
     * @return void
     */
    public function injectIntoReply(View $reply, string $route, Entity $container = null)
    {
        $reply->setParam('filterRoute', $route);
        $reply->setParam('filterContainer', $container);
    }
}
