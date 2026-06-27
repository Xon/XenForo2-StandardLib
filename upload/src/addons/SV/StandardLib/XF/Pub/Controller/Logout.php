<?php

namespace SV\StandardLib\XF\Pub\Controller;

use SV\StandardLib\ControllerPlugin\RedirectPlugin;

/**
 * @extends \XF\Pub\Controller\Logout
 */
class Logout extends XFCP_Logout
{
    /** @noinspection PhpMissingReturnTypeInspection */
    public function actionIndex()
    {
        $redirectPlugin = $this->plugin(RedirectPlugin::class);
        $redirectPlugin->assertIsPostRequest($this->buildLink('logout'));

        return parent::actionIndex();
    }
}