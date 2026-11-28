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
        if (is_callable([$this, 'assertNotEmbeddedImageRequest']))
        {
            $this->assertNotEmbeddedImageRequest();
        }
        $redirectPlugin = $this->plugin(RedirectPlugin::class);
        $redirectPlugin->assertIsPostRequest($this->buildLink('logout'), 't', null);

        return parent::actionIndex();
    }
}