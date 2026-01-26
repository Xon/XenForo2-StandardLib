<?php

namespace SV\StandardLib\XF\Pub\Controller;

use SV\StandardLib\ControllerPlugin\Filter as FilterPlugin;
use SV\StandardLib\Helper;
use XF\Mvc\Reply\View;

/**
 * @extends \XF\Pub\Controller\Watched
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
            $this->svFilterPlugin = Helper::plugin($this, FilterPlugin::class);
        }

        return $this->svFilterPlugin;
    }
}