<?php

namespace SV\StandardLib\ControllerPlugin;

use XF\ControllerPlugin\AbstractPlugin;
use XF\Mvc\Reply\AbstractReply;
use function array_key_exists;

class RedirectPlugin extends AbstractPlugin
{
    public function assertIsPostRequest(string $route, string $tokenKey = 't'): void
    {
        if (\XF::$versionId >= 2040000)
        {
            return;
        }
        if (!$this->isPost())
        {
            throw $this->exception($this->actionPost($route));
        }

        $token = $this->filter('_xfToken', 'str');
        $this->request()->set($tokenKey, $token);
    }

    public function actionPost(string $route, ?string $templateName = null): AbstractReply
    {
        $templateName = $templateName ?? 'public:svStandardLib_redirect_interstitial';

        $params['route'] = $route;
        $params['autoSubmit'] = $params['autoSubmit'] ?? (\XF::session()->exists() && $this->request->getRobotName() === '');
        $params['redirect'] = $this->getDynamicRedirect();

        return $this->view('SV\StandardLib:Redirect', $templateName, $params);
    }
}