<?php

namespace SV\StandardLib\XF\Template;



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
}