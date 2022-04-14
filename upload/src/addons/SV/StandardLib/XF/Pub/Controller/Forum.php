<?php

namespace SV\StandardLib\XF\Pub\Controller;

use SV\StandardLib\ControllerPlugin\Filter as FilterPlugin;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\View;

/**
 * Extends \XF\Pub\Controller\Forum
 */
class Forum extends XFCP_Forum
{
    /**
     * @param ParameterBag $params
     *
     * @return \XF\Mvc\Reply\AbstractReply
     */
    public function actionForum(ParameterBag $params)
    {
        $reply = parent::actionForum($params);

        if ($reply instanceof View && ($forum  = $reply->getParam('forum')))
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
            $this->svFilterPlugin = $this->plugin('SV\StandardLib:Filter');
        }

        return $this->svFilterPlugin;
    }
}