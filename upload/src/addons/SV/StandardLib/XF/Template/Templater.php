<?php
/**
 * @noinspection PhpMissingReturnTypeInspection
 * @noinspection PhpMissingParamTypeInspection
 * @noinspection PhpUnusedParameterInspection
 */

namespace SV\StandardLib\XF\Template;

use XF\Mvc\Entity\AbstractCollection;
use SV\StandardLib\Helper as StandardLibHelper;
use XF\Mvc\Entity\ArrayCollection;
use XF\Template\Templater as BaseTemplater;

/**
 * Extends \XF\Template\Templater
 */
class Templater extends XFCP_Templater
{
    public function addDefaultHandlers()
    {
        parent::addDefaultHandlers();

        $hasFromCallable = \is_callable('\Closure::fromCallable');

        if (empty($this->filters['replacevalue']))
        {
            $callable = [$this, 'fnSvReplaceValue'];
            if ($hasFromCallable)
            {
                /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
                $callable = \Closure::fromCallable($callable);
            }
            $this->addFilter('replacevalue', $callable);
        }

        if (empty($this->filters['addvalue']))
        {
            $callable = [$this, 'fnSvAddValue'];
            if ($hasFromCallable)
            {
                /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
                $callable = \Closure::fromCallable($callable);
            }
            $this->addFilter('addvalue', $callable);
        }

        $this->addFunction('sv_array_reverse', 'fnSvArrayReverse');
        $this->addFunction('sv_relative_timestamp', 'fnSvRelativeTimestamp');
    }


    /**
     * @param BaseTemplater             $templater
     * @param array|AbstractCollection  $value
     * @param bool                      $escape
     * @param mixed                     $toAdd
     * @return array|AbstractCollection
     * @noinspection PhpUnusedParameterInspection
     */
    public function fnSvAddValue(BaseTemplater $templater, $value, bool &$escape, $toAdd)
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

        return $wasCollection ? new ArrayCollection([$value]) : $value;
    }

    /**
     * @param BaseTemplater   $templater
     * @param int[]|string[]  $value
     * @param bool            $escape
     * @param int|string      $toReplace
     * @param int|string|null $replaceWith
     * @return int[]|string[]
     */
    public function fnSvReplaceValue(BaseTemplater $templater, $value, bool &$escape, $toReplace, $replaceWith)
    {
        foreach ($value as $key => $_val)
        {
            if ($_val === $toReplace)
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

    /**
     * @param Templater $templater
     * @param bool $escape
     * @param AbstractCollection|array $array
     * @param bool $preserveKeys
     *
     * @return array|AbstractCollection
     */
    public function fnSvArrayReverse(BaseTemplater $templater, bool &$escape, $array, bool $preserveKeys = true)
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
     * @return string
     *
     * @throws \Exception
     */
    public function fnSvRelativeTimestamp(
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

        return $this->renderMacro('public:svStandardLib_helper_macros', 'relative_timestamp', [
            'class' => $class,
            'countUp' => $countUp,
            'triggerEvent' => $triggerEvent,
            'triggerEventOnSelector' => $triggerEventOnSelector,
            'timeStr' => $timeStr,
            'otherTimestamp' => $otherTimestamp,
            'maximumDateParts' => $maximumDateParts
        ]);
    }
}