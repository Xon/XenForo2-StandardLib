<?php

namespace SV\StandardLib\XF\Pub\Controller;

use SV\StandardLib\ControllerPlugin\Filter as FilterPlugin;
use SV\StandardLib\Helper;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\AbstractReply;
use XF\Mvc\Reply\View;

/**
 * @extends \XF\Pub\Controller\Forum
 */
class Forum extends XFCP_Forum
{
    /**
     * @param ParameterBag $params
     * @return AbstractReply
     */
    public function actionForum(ParameterBag $params)
    {
        $reply = parent::actionForum($params);

        if ($reply instanceof View && ($forum = $reply->getParam('forum')))
        {
            $this->getSvFilterPlugin()->injectIntoReply($reply, 'forums', $forum);
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