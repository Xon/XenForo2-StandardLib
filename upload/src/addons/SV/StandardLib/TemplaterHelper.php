<?php
/**
 * @noinspection PhpUnusedParameterInspection
 */

namespace SV\StandardLib;

use SV\StandardLib\Helper as StandardLibHelper;
use XF\Mvc\Entity\AbstractCollection;
use XF\Template\Templater as BaseTemplater;
use function is_string;

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

    protected function mangleCallable(callable $filter): callable
    {
        if (is_string($filter))
        {
            $filter = [$this, $filter];
        }

        if ($this->hasFromCallable)
        {
            /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
            $filter = \Closure::fromCallable($filter);
        }

        return $filter;
    }

    protected function addFilter(string $name, callable $filter, bool $replace = false)
    {
        if (!$replace && $this->hasFilter($name))
        {
            return;
        }

        $this->templater->addFilter($name, $this->mangleCallable($filter));
    }

    protected function addFunction(string $name, callable $filter, bool $replace = false)
    {
        if (!$replace && $this->hasFunction($name))
        {
            return;
        }

        $this->templater->addFunction($name, $this->mangleCallable($filter));
    }

    public function addDefaultHandlers()
    {
        $this->addFilter('replacevalue', 'filterReplaceValue');
        $this->addFilter('addvalue', 'filterAddValue');
        $this->addFunction('dynamicphrase', 'fnDynamicPhrase');
        $this->addFunction('sv_array_reverse', 'fnArrayReverse');
        $this->addFunction('sv_relative_timestamp', 'fnRelativeTimestamp');
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
        else if (!\is_array($value))
        {
            $error = "addValue should be called on an array or an AbstractCollection";
            if (\XF::$debugMode)
            {
                \trigger_error($error, E_USER_WARNING);
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
        else if (\is_array($array))
        {
            return \array_reverse($array, $preserveKeys);
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
                $timeStr = \trim(\implode(', ', $dateArr));
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

    protected function app(): \XF\App
    {
        return $this->app;
    }

    protected function em(): \XF\Mvc\Entity\Manager
    {
        return $this->app->em();
    }
}