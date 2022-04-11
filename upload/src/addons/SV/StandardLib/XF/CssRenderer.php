<?php

namespace SV\StandardLib\XF;

/**
 * Extends \XF\CssRenderer
 */
class CssRenderer extends XFCP_CssRenderer
{
    /**
     * When given a color value which may contain a mix of XF and Less functions test and return the parsed color.
     * If the provided Less is invalid, or no valid color found, returns null.
     *
     * @param string  $value
     * @param boolean $forceDebug
     * @return null|string
     * @noinspection PhpMissingParamTypeInspection
     */
    public function parseLessColorFuncValue(string $value, $forceDebug = false)
    {
        $parser = $this->getFreshLessParser();
        $parser->SetOption('compress', false);

        $value = '@someVar: ' . $value . '; #test { color: @someVar; }';
        $value = $this->prepareLessForRendering($value);

        try
        {
            $css = $parser->parse($value)->getCss();
        }
        catch (\Exception $e)
        {
            if (\XF::$debugMode)
            {
                // Note: this is intentionally triggering a warning rather than an exception. This will commonly
                // trigger in templates and we will still be able to render in that case.
                trigger_error("parse_less_func({$value}) render error:" . $e->getMessage(), E_USER_WARNING);
            }

            \XF::logException($e, false, "parse_less_func({$value}) render error:");

            return null;
        }

        \preg_match('/color:\s*(.*?)\s*;?\s*}$/si', $css, $matches);

        if ($forceDebug ||(\XF::options()->svAdvancedBbCodeLogLessFunc ?? false))
        {
            \XF::logError("parse_less_func({$value}), " . $css . ", matches:" . var_export($matches, true), true);
        }

        if (!$matches || !isset($matches[1]))
        {
            return null;
        }

        return $matches[1];
    }
}