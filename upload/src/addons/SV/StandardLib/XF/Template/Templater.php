<?php

namespace SV\StandardLib\XF\Template;

use XF\Mvc\Entity\AbstractCollection;
use SV\StandardLib\Helper as StandardLibHelper;
use XF\Template\Templater as BaseTemplater;

/**
 * Extends \XF\Template\Templater
 */
class Templater extends XFCP_Templater
{
    public function addDefaultHandlers()
    {
        parent::addDefaultHandlers();

        $hasFromCallable = is_callable('\Closure::fromCallable');

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

        $this->addFunction('sv_array_reverse', 'fnSvArrayReverse');
        $this->addFunction('sv_relative_timestamp', 'fnSvRelativeTimestamp');
    }

    /**
     * @param Templater       $templater
     * @param int[]|string[]  $value
     * @param string          $escape
     * @param int|string      $toReplace
     * @param int|string|null $replaceWith
     * @return int[]|string[]
     * @noinspection PhpUnusedParameterInspection
     */
    public function fnSvReplaceValue($templater, $value, &$escape, $toReplace, $replaceWith)
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
    public function fnSvArrayReverse($templater, &$escape, $array, bool $preserveKeys = true)
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
     * @param \DateTime $nowDateTimeObj
     * @param \DateTime $otherDateTimeObj
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
        $templater, &$escape, $nowDateTimeObj, $otherDateTimeObj,
        int $maximumDateParts = 0, bool $countUp = false, string $class = '', string $triggerEvent = '',
        string $triggerEventOnSelector = ''
    )
    {
        $escape = false;

        /**
         * @param int|\DateTime $dateTimeObj
         *
         * @return \DateTime
         */
        $convertToDateTimeObjIfNeeded = function ($dateTimeObj)
        {
            if ($dateTimeObj instanceof \DateTime)
            {
                return $dateTimeObj;
            }

            return new \DateTime('@' . $dateTimeObj, new \DateTimeZone(\XF::visitor()->timezone));
        };

        $nowDateTimeObj = $convertToDateTimeObjIfNeeded($nowDateTimeObj);
        $nowTimestamp = $nowDateTimeObj->getTimestamp();

        $otherDateTimeObj = $convertToDateTimeObjIfNeeded($otherDateTimeObj);
        $otherTimestamp = $otherDateTimeObj->getTimestamp();

        $repo = StandardLibHelper::repo();
        $interval = $repo->momentJsCompatibleTimeDiff($nowTimestamp, $otherTimestamp);

        if (isset($interval['invert']) && (!$countUp && !$interval['invert'] || $countUp && $interval['invert']))
        {
            $dateArr = $repo->buildRelativeDateString($interval, 0);
            $timeStr = \trim(\implode(', ', $dateArr));
        }
        else
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