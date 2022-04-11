<?php
/**
 * @noinspection PhpUnusedParameterInspection
 */

namespace SV\StandardLib;

use SV\StandardLib\Helper as StandardLibHelper;
use XF\Mvc\Entity\AbstractCollection;
use XF\Template\Templater as BaseTemplater;
use function is_string, is_array, count, array_filter, array_diff, array_reverse, abs, assert, trigger_error, trim, implode;

class TemplaterHelper
{
    /** @var BaseTemplater */
    protected $templater;
    /** @var \XF\App */
    protected $app;
    /** @var bool */
    private $hasFromCallable;

    public static function templaterSetup(\XF\Container $container, BaseTemplater &$templater)
    {
        $class = self::class;
        $class = \XF::extendClass($class);
        $templateHelper = new $class($templater);
        $templateHelper->setup();
    }

    public function __construct(BaseTemplater $templater)
    {
        $this->hasFromCallable = \is_callable('\Closure::fromCallable');
        $this->app = TemplaterAccess::app($templater);
        $this->templater = $templater;
    }

    public function setup()
    {
        $this->addDefaultHandlers();
    }

    protected function hasFilter(string $filter): bool
    {
        $filters = TemplaterAccess::filters($this->templater);

        return isset($filters[$filter]);
    }

    protected function hasFunction(string $function): bool
    {
        $functions = TemplaterAccess::functions($this->templater);

        return isset($functions[$function]);
    }

    /**
     * @param callable|callable-string $filter
     * @return callable
     */
    protected function mangleCallable($filter): callable
    {
        if (is_string($filter))
        {
            $filter = [$this, $filter];
        }

        if ($this->hasFromCallable && !($filter instanceof \Closure))
        {
            /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
            $filter = \Closure::fromCallable($filter);
        }

        return $filter;
    }

    /**
     * @param string $name
     * @param callable|callable-string $filter
     * @param bool   $replace
     * @return void
     */
    protected function addFilter(string $name, $filter, bool $replace = false)
    {
        if (!$replace && $this->hasFilter($name))
        {
            return;
        }

        $this->templater->addFilter($name, $this->mangleCallable($filter));
    }

    /**
     * @param string $name
     * @param callable|callable-string $filter
     * @param bool   $replace
     * @return void
     */
    protected function addFunction(string $name, $filter, bool $replace = false)
    {
        if (!$replace && $this->hasFunction($name))
        {
            return;
        }

        $this->templater->addFunction($name, $this->mangleCallable($filter));
    }

    protected function getStyle(): \XF\Style
    {
        return $this->templater->getStyle() ?? $this->app()->style();
    }

    public function addDefaultHandlers()
    {
        $this->addFilter('replacevalue', 'filterReplaceValue');
        $this->addFilter('addvalue', 'filterAddValue');
        $this->addFunction('array_diff', 'fnArrayDiff');
        $this->addFunction('phrase_dynamic', 'fnPhraseDynamic');
        $this->addFunction('array_reverse', 'fnArrayReverse');
        $this->addFunction('sv_array_reverse', \XF::$debugMode ? 'fnArrayReverseOld' : 'fnArrayReverse');
        $this->addFunction('sv_relative_timestamp', 'fnRelativeTimestamp');
        $this->addFunction('parse_less_func', 'fnParseLessFunc');
        $this->addFunction('abs', 'fnAbs');
    }


    /**
     * @param BaseTemplater             $templater
     * @param array|AbstractCollection  $value
     * @param bool                      $escape
     * @param mixed                     $toAdd
     * @return array|AbstractCollection
     */
    public function filterAddValue(BaseTemplater $templater, $value, bool &$escape, $toAdd)
    {
        $wasCollection = false;
        if ($value === null)
        {
            $value = [];
        }
        else if ($value instanceof AbstractCollection)
        {
            $wasCollection = true;
            $value = $value->toArray();
        }
        else if (!is_array($value))
        {
            $error = "addValue should be called on an array or an AbstractCollection";
            if (\XF::$debugMode)
            {
                trigger_error($error, E_USER_WARNING);
            }
            \XF::logError($error);

            return $value;
        }

        $value[] = $toAdd;

        return $wasCollection ? $this->em()->getBasicCollection([$value]) : $value;
    }

    /**
     * @param BaseTemplater   $templater
     * @param int[]|string[]|\ArrayAccess  $value
     * @param bool            $escape
     * @param int|string      $toReplace
     * @param int|string|null $replaceWith
     * @return int[]|string[]
     */
    public function filterReplaceValue(BaseTemplater $templater, $value, bool &$escape, $toReplace, $replaceWith): array
    {
        foreach ($value as $key => $_val)
        {
            // deliberately using non-strict equality checks
            if ($_val == $toReplace)
            {
                if ($replaceWith === null)
                {
                    unset($value[$key]);
                }
                else
                {
                    $value[$key] = $replaceWith;
                }
            }
        }

        return $value;
    }

    public function fnDynamicPhrase(BaseTemplater $templater, bool &$escape, string $value): \XF\Phrase
    {
        $escape = false;

        return \XF::phrase($value);
    }

    /**
     * XF2.1 only, as XF2.2 implements this
     *
     * @param BaseTemplater $templater
     * @param bool          $escape
     * @param string        $phraseName
     * @param array         $params
     * @return string|\XF\PreEscaped
     */
    public function fnPhraseDynamic(BaseTemplater $templater, bool &$escape, string $phraseName, array $params = [])
    {
        $phrase = $this->templater->getLanguage()->phrase($phraseName, $params);

        return $phrase->render();
    }

    /**
     * @param BaseTemplater $templater
     * @param bool          $escape
     * @param array $array1
     * @param array ...$arrays
     * @return array
     */
    public function fnArrayDiff(BaseTemplater $templater, bool &$escape, array $array1 = null, ...$arrays): array
    {
        $array1 = $array1 ?? [];
        $arrays = array_filter($arrays);

        if (count($arrays) === 0)
        {
            return $array1;
        }

        return array_diff($array1, ...$arrays);
    }

    /**
     * @param BaseTemplater $templater
     * @param bool $escape
     * @param AbstractCollection|array $array
     * @param bool $preserveKeys
     *
     * @return array|AbstractCollection
     * @deprecated
     */
    public function fnArrayReverseOld(BaseTemplater $templater, bool &$escape, $array, bool $preserveKeys = true)
    {
        $error = "sv_array_reverse is deprecated use array_reverse instead";
        if (\XF::$debugMode)
        {
            trigger_error($error, E_USER_WARNING);
        }

        return $this->fnArrayReverse($templater, $escape, $array, $preserveKeys);
    }

    /**
     * @param BaseTemplater $templater
     * @param bool $escape
     * @param AbstractCollection|array $array
     * @param bool $preserveKeys
     *
     * @return array|AbstractCollection
     */
    public function fnArrayReverse(BaseTemplater $templater, bool &$escape, $array, bool $preserveKeys = true)
    {
        if ($array instanceof AbstractCollection)
        {
            return $array->reverse($preserveKeys);
        }
        else if (is_array($array))
        {
            return array_reverse($array, $preserveKeys);
        }

        return $array;
    }

    /**
     * @param BaseTemplater $templater
     * @param bool $escape
     * @param int $nowTimestamp
     * @param int $otherTimestamp
     * @param int $maximumDateParts
     * @param bool $countUp
     * @param string $class
     * @param string $triggerEvent
     * @param string $triggerEventOnSelector
     *
     * @return string|\XF\PreEscaped
     *
     * @throws \Exception
     */
    public function fnRelativeTimestamp(
        BaseTemplater $templater, bool &$escape, int $nowTimestamp, int $otherTimestamp,
        int $maximumDateParts = 0, bool $countUp = false, string $class = '', string $triggerEvent = '',
        string $triggerEventOnSelector = ''
    )
    {
        $escape = false;

        $timeStr = '';
        $repo = StandardLibHelper::repo();
        $interval = $repo->momentJsCompatibleTimeDiff($nowTimestamp, $otherTimestamp);

        if (isset($interval['invert']) && (!$countUp && !$interval['invert'] || $countUp && $interval['invert']))
        {
            $dateArr = $repo->buildRelativeDateString($interval, $maximumDateParts);
            if ($dateArr)
            {
                $timeStr = trim(implode(', ', $dateArr));
            }
        }
        if (!$timeStr)
        {
            $timeStr = \XF::language()->dateTime($otherTimestamp);
        }

        return $this->templater->renderMacro('public:svStandardLib_helper_macros', 'relative_timestamp', [
            'class' => $class,
            'countUp' => $countUp,
            'triggerEvent' => $triggerEvent,
            'triggerEventOnSelector' => $triggerEventOnSelector,
            'timeStr' => $timeStr,
            'otherTimestamp' => $otherTimestamp,
            'maximumDateParts' => $maximumDateParts
        ]);
    }

    /**
     * @param BaseTemplater    $templater
     * @param bool             $escape
     * @param string|float|int $value
     * @return string
     */
    public function fnAbs(BaseTemplater $templater, bool &$escape, $value): string
    {
        return (string)abs($value ?? 0);
    }

    public function fnParseLessFunc(BaseTemplater $templater, bool &$escape, string $value, bool $forceDebug = false): string
    {
        $rendererClass = $this->app->extendClass('XF\CssRenderer');

        $renderer = new $rendererClass($this->app, $this);
        assert($renderer instanceof \SV\StandardLib\XF\CssRenderer);
        $renderer->setStyle($this->getStyle());

        return $renderer->parseLessColorFuncValue($value, $forceDebug) ?? '';
    }

    protected function app(): \XF\App
    {
        return $this->app;
    }

    protected function em(): \XF\Mvc\Entity\Manager
    {
        return $this->app->em();
    }
}