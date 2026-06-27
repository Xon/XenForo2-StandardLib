<?php

namespace SV\StandardLib\XF\Admin\Controller;

use SV\StandardLib\ControllerPlugin\RedirectPlugin;
use XF\Mvc\Reply\AbstractReply;
use XF\Repository\StyleRepository;
use function count;
use function in_array;

/**
 * @extends \XF\Admin\Controller\AccountController
 */
class AccountController extends XFCP_AccountController
{
    public function actionStyleVariation(): AbstractReply
    {
        if (\XF::$versionId < 2030000)
        {
            return $this->notFound();
        }

        if ($this->isPost())
        {
            $token = $this->filter('_xfToken', 'str');
            $this->request()->set('t', $token);

            $url = $this->buildLink('');
            $this->request()->set('_xfRedirect', $url);
        }
        else
        {
            $visitor = \XF::visitor();
            $styleRepo = \XF::repository(StyleRepository::class);
            $selectedStyleId = $styleRepo->getSelectedStyleIdForUser($visitor);
            $style = \XF::app()->style($selectedStyleId);
            if (!$visitor->canChangeStyleVariation($style, $error))
            {
                return $this->noPermission($error);
            }

            $params = [];
            if ($this->request->exists('variation'))
            {
                $params['variation'] = $this->filter('variation', 'str');
                if (!in_array($params['variation'], $style->getVariations()))
                {
                    $params['reset'] = 1;
                }
            }
            else if ($this->filter('reset', 'bool'))
            {
                $params['reset'] = 1;
            }

            if (count($params) !== 0)
            {
                $redirectPlugin = $this->plugin(RedirectPlugin::class);
                return $redirectPlugin->actionPost($this->buildLink('account/style-variation', null, $params));
            }
        }

        return parent::actionStyleVariation();
    }
}