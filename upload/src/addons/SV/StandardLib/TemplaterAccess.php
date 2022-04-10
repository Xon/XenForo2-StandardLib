<?php

namespace SV\StandardLib;

use XF\Template\Templater;

abstract class TemplaterAccess extends Templater
{
    public static function app(\XF\Template\Templater $templater): \XF\App
    {
        return $templater->app;
    }

    public static function filters(\XF\Template\Templater $templater): array
    {
        return $templater->filters;
    }

    public static function functions(Templater $templater): array
    {
        return $templater->functions;
    }
}