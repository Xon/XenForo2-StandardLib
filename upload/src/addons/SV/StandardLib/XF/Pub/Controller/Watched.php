<?php

namespace SV\StandardLib\XF\Pub\Controller;

use SV\StandardLib\ControllerPlugin\Filter as FilterPlugin;
use XF\Mvc\Reply\View;

/**
 * Extends \XF\Pub\Controller\Watched
 */
class Watched extends XFCP_Watched
{
    public function actionThreads()
    {
        $reply = parent::actionThreads();

        if ($reply instanceof View)
        {
            $this->getSvFilterPlugin()->injectIntoReply($reply, 'watched/threads');
        }

        return $reply;
    }

    /** @var FilterPlugin|null */
    protected $svFilterPlugin = null;

    protected function getSvFilterPlugin(): FilterPlugin
    {
        if ($this->svFilterPlugin === null)
        {
            $this->svFilterPlugin = $this->plugin('SV\StandardLib:Filter');
        }

        return $this->svFilterPlugin;
    }
}