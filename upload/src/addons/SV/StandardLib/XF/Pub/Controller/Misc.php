<?php

namespace SV\StandardLib\XF\Pub\Controller;

use SV\StandardLib\ControllerPlugin\RedirectPlugin;
use SV\StandardLib\Helper;
use XF\CookieConsent;
use XF\Mvc\Reply\AbstractReply;
use function in_array;

/**
 * @extends \XF\Pub\Controller\Misc
 */
class Misc extends XFCP_Misc
{
    public function actionStyle()
    {
        if ($this->request->exists('style_id'))
        {
            $redirectPlugin = $this->plugin(RedirectPlugin::class);
            $redirectPlugin->assertIsPostRequest($this->buildLink('misc/style',null, [
                'style_id' => $this->filter('style_id', 'uint'),
            ]));
        }

        return parent::actionStyle();
    }

    public function actionLanguage()
    {
        if ($this->request->exists('language_id'))
        {
            $redirectPlugin = $this->plugin(RedirectPlugin::class);
            $redirectPlugin->assertIsPostRequest($this->buildLink('misc/language', null, [
                'language_id' => $this->filter('language_id', 'uint'),
            ]));
        }

        return parent::actionLanguage();
    }

    public function actionCookies(): AbstractReply
    {
        if (\XF::$versionId < 2020000 || $this->app()->cookieConsent()->getMode() !== CookieConsent::MODE_ADVANCED)
        {
            return $this->notFound();
        }

        if ($this->filter('update', 'bool'))
        {
            $parms = [
                'update' => 1,
            ];

            if ($this->filter('accept', 'bool'))
            {
                $parms['accept'] = 1;
            }
            else if ($this->filter('reject', 'bool'))
            {
                $parms['reject'] = 1;
            }
            if ($this->filter('accept', 'bool'))
            {
                $parms['consent'] = $this->filter('consent', 'array-bool');
                if ($this->filter('add', 'bool'))
                {
                    $parms['add'] = 1;
                }
                else if ($this->filter('remove', 'bool'))
                {
                    $parms['remove'] = 1;
                }
            }

            $redirectPlugin = $this->plugin(RedirectPlugin::class);
            $redirectPlugin->assertIsPostRequest($this->buildLink('misc/cookies', null, $parms));
        }

        return parent::actionCookies();
    }

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
        }
        else
        {
            $visitor = \XF::visitor();
            $selectedStyleId = Helper::repo()->getSelectedStyleIdForUser($visitor);
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
                return $redirectPlugin->actionPost($this->buildLink('misc/style-variation', null, $params));
            }
        }

        return parent::actionStyleVariation();
    }
}