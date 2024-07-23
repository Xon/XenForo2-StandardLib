<?php
/**
 * @noinspection PhpMissingParamTypeInspection
 */

namespace SV\StandardLib\XF;

use DateTime;
use DateTimeZone;
use XF\Data\TimeZone as TimeZoneData;
use function array_key_exists;
use function count;
use function explode;
use function is_array;
use function is_int;
use function is_numeric;
use function is_string;

/**
 * @extends \XF\InputFilterer
 */
class InputFilterer extends XFCP_InputFilterer
{
    /**
     * @param mixed  $value
     * @param string $type
     * @param array  $options
     * @return mixed
     * @throws \Exception
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
                if (!is_array($value))
                {
                    return 0;
                }

                if (array_key_exists('date', $value) && array_key_exists('time', $value))
                {
                    $dateParts = $value['date'];
                    $timeValue = $value['time'];
                    $tz = $value['tz'];

                    $timeParts = explode(':', $timeValue, 3);
                    if (count($timeParts) !== 3)
                    {
                        return 0;
                    }
                    [$hh, $mm, $ss] = $timeParts;
                }
                else if (array_key_exists('ymd', $value)
                         && array_key_exists('hh', $value)
                         && array_key_exists('mm', $value)
                         && array_key_exists('ss', $value)
                         && array_key_exists('tz', $value))
                {
                    // old XF2.2 version, shim to new version
                    $dateParts = $value['ymd'];
                    $hh = $value['hh'];
                    $mm = $value['mm'];
                    $ss = $value['ss'];
                    $tz = $value['tz'];
                }
                else
                {
                    return 0;
                }

                if (!is_string($dateParts) || $dateParts === '')
                {
                    return 0;
                }

                $ymdParts = explode('-', $dateParts, 3);
                if (count($ymdParts) !== 3)
                {
                    return 0;
                }
                [$year, $month, $day] = $ymdParts;

                $intSanitizer = function ($int, ?int $min, ?int$max): int
                {
                    if (!is_numeric($int))
                    {
                        return $min;
                    }

                    $int = (int) $int;
                    if ($min !== null && $int < $min)
                    {
                        $int = $min;
                    }
                    else if ($max !== null && $int > $max)
                    {
                        $int = $max;
                    }

                    return $int;
                };

                $year = $intSanitizer($year, 1970, null);
                $month = $intSanitizer($month, 1, 12);
                $day = $intSanitizer($day, 1, 31);
                $hh = $intSanitizer($hh, 0, 24);
                $mm = $intSanitizer($mm, 0, 60);
                $ss = $intSanitizer($ss, 0, 60);

                if (is_string($tz))
                {
                    /** @var TimeZoneData $tzData */
                    $tzData = \XF::app()->data('XF:TimeZone');
                    if (!array_key_exists($tz, $tzData->getTimeZoneOptions()))
                    {
                        $tz = \XF::visitor()->timezone;
                    }
                }
                else
                {
                    $tz = \XF::visitor()->timezone;
                }

                $dateTimeObj = new DateTime();
                $dateTimeObj->setTimezone(new DateTimeZone($tz));
                $dateTimeObj->setDate($year, $month, $day);
                $dateTimeObj->setTime($hh, $mm, $ss);

                return $dateTimeObj->getTimestamp();
        }

        return parent::cleanInternal($value, $type, $options);
    }
}