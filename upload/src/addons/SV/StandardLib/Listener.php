<?php

namespace SV\StandardLib;

use XF\App as BaseApp;
use XF\Data\TimeZone as TimeZoneData;
use XF\Language;
use XF\Template\Templater;

class Listener
{

    /**
     * Allows the modification of various properties for template macros before they are rendered.
     *
     * Event hint: A string representing the template type, template name and macro name, e.g. public:template_name:macro_name.
     * 
     * @param Templater $templater  Templater object.
     * @param string    $type       Template type.
     * @param string    $template   Template name.
     * @param string    $name       Macro name.
     * @param array     $arguments  Array of arguments passed to this macro.
     * @param array     $globalVars Array of global vars available to this macro.
     */
    public static function templaterMacroPreRender(Templater $templater, string &$type, string &$template, string &$name, array &$arguments, array &$globalVars)
    {
        switch ("{$type}:{$template}:{$name}")
        {
            case 'public:svStandardLib_helper_macros:date_time_input':
                $padInt = function (int $value)
                {
                    return \str_pad($value, 2, '0', \STR_PAD_LEFT);
                };

                if (empty($arguments['hours']))
                {
                    $arguments['hours'] = [];

                    foreach (\range(0, 23, 1) AS $option)
                    {
                        $arguments['hours'][$option] = $padInt($option);
                    }
                }

                $fill0To59 = function (string $argument) use(&$arguments, $padInt)
                {
                    if (empty($arguments[$argument]))
                    {
                        $arguments[$argument] = [];

                        foreach (\range(0, 59, 1) AS $option)
                        {
                            $arguments[$argument][$option] = $padInt($option);
                        }
                    }
                };

                $fill0To59('minutes');
                $fill0To59('seconds');

                if (empty($arguments['timeZones']))
                {
                    $arguments['timeZones'] = static::getTimeZoneData()->getTimeZoneOptions();
                }

                $currentTimestampOrDateTimeArr = $arguments['timestampOrDateTimeArr'];
                $arguments['timestampOrDateTimeArr'] = [
                    'ymd' => null,
                    'hh' => null,
                    'mm' => null,
                    'ss' => null,
                    'tz' => null
                ];

                if (!\is_array($currentTimestampOrDateTimeArr) && \is_int($currentTimestampOrDateTimeArr))
                {
                    $language = static::language();
                    $arguments['timestampOrDateTimeArr'] = \array_merge($arguments['timestampOrDateTimeArr'], [
                        'ymd' => $language->date($currentTimestampOrDateTimeArr, 'picker'),
                        'hh' => $language->time($currentTimestampOrDateTimeArr, 'H'),
                        'mm' => (int) $language->time($currentTimestampOrDateTimeArr, 'i'),
                        'ss' => (int) $language->time($currentTimestampOrDateTimeArr, 's'),
                        'tz' => $language->getTimeZone()->getName()
                    ]);
                }

                break;
        }
    }

    protected static function language() : Language
    {
        return static::app()->language(\XF::visitor()->language_id);
    }

    protected static function app() : BaseApp
    {
        return \XF::app();
    }

    protected static function data(string $class)
    {
        return static::app()->data($class);
    }

    protected static function getTimeZoneData() : TimeZoneData
    {
        return static::data('XF:TimeZone');
    }
}