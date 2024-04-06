<?php

namespace SV\StandardLib;

use XF\App;
use XF\Template\Templater;

abstract class TemplaterAccess extends Templater
{
    public static function app(Templater $templater): App
    {
        return $templater->app;
    }

    public static function filters(Templater $templater): array
    {
        return $templater->filters;
    }

    public static function functions(Templater $templater): array
    {
        return $templater->functions;
    }

    public static function uncacheTemplateData(Templater $templater, string $type, string $template)
    {
        $languageId = $templater->getLanguage()->getId();
        $styleId = $templater->getStyleId();
        $cacheKey = "{$languageId}-{$styleId}-{$type}-{$template}";

        unset($templater->templateCache[$cacheKey]);
    }

    public static function getDefaultParam(Templater $templater, string $name)
    {
        return $templater->defaultParams[$name] ?? null;
    }
}