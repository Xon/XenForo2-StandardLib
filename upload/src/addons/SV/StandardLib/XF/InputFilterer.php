<?php
/**
 * @noinspection PhpMissingParamTypeInspection
 */

namespace SV\StandardLib\XF;

use XF\Data\TimeZone as TimeZoneData;

class InputFilterer extends XFCP_InputFilterer
{
    /**
     * @param mixed $value
     * @param string $type
     * @param array $options
     * @return mixed
     */
    protected function cleanInternal($value, $type, array $options)
    {
        if ($value === '' && isset($options['empty-str-to-null']))
        {
            return null;
        }

        /** @noinspection PhpSwitchStatementWitSingleBranchInspection */
        switch ($type)
        {
            case 'sv-datetime':
                if (
                    \is_array($value) && (
                        \array_key_exists('ymd', $value)
                        && \array_key_exists('hh', $value)
                        && \array_key_exists('mm', $value)
                        && \array_key_exists('ss', $value)
                        && \array_key_exists('tz', $value)
                    )
                )
                {
                    /**
                     * Daily reminder to wash your hands
                     *
                     * @param mixed    $int
                     * @param int|null $min
                     * @param int|null $max
                     * @return int
                     */
                    $intSanitizer = function ($int, $min, $max)
                    {
                        if (!\is_numeric($int))
                        {
                            return $min;
                        }

                        $int = (int) $int;
                        if (\is_int($min) && $int < $min)
                        {
                            $int = $min;
                        }
                        else if (\is_int($max) && $int > $max)
                        {
                            $int = $max;
                        }

                        return $int;
                    };

                    $ymdParts = null;
                    if (\is_string($value['ymd']) && $value['ymd'] !== '')
                    {
                        $ymdParts = \explode('-', $value['ymd'], 3);
                        if (\count($ymdParts) === 3)
                        {
                            // php is somewhat smart and will move the y-m-d around to be valid
                            $ymdParts[0] = $intSanitizer($ymdParts[0], 1970, null);
                            $ymdParts[1] = $intSanitizer($ymdParts[1], 1, 12);
                            $ymdParts[2] = $intSanitizer($ymdParts[2], 1, 31);
                        }
                    }

                    if ($ymdParts === null || \count($ymdParts) !== 3)
                    {
                        return 0;
                    }

                    $timeSanitizer = function (string $key) use(&$value, &$intSanitizer)
                    {
                        $value[$key] = $intSanitizer($value[$key], 0, null);
                    };

                    $timeSanitizer('hh'); // hours
                    $timeSanitizer('mm'); // minutes
                    $timeSanitizer('ss'); // seconds

                    if (\is_string($value['tz']))
                    {
                        /** @var TimeZoneData $tzData */
                        $tzData = \XF::app()->data('XF:TimeZone');
                        if (!\array_key_exists($value['tz'], $tzData->getTimeZoneOptions()))
                        {
                            $value['tz'] = \XF::visitor()->timezone;
                        }
                    }
                    else
                    {
                        $value['tz'] = \XF::visitor()->timezone;
                    }

                    $dateTimeObj = new \DateTime();
                    $dateTimeObj->setTimezone(new \DateTimeZone($value['tz']));
                    $dateTimeObj->setDate($ymdParts[0], $ymdParts[1], $ymdParts[2]);
                    $dateTimeObj->setTime($value['hh'], $value['mm'], $value['ss']);

                    return $dateTimeObj->getTimestamp();
                }

                return 0;
        }

        return parent::cleanInternal($value, $type, $options);
    }
}