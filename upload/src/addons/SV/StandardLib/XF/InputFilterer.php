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
                }
                else if (array_key_exists('ymd', $value)
                         && array_key_exists('hh', $value)
                         && array_key_exists('mm', $value)
                         && array_key_exists('ss', $value)
                         && array_key_exists('tz', $value))
                {
                    // old XF2.2 version, shim to new version
                    $dateParts = $value['ymd'];
                    $timeValue = $value['hh'] . ':' . $value['mm'] . ':' . $value['ss'];
                    $tz = $value['tz'];
                }
                else
                {
                    return 0;
                }

                if (!is_string($dateParts) || $dateParts === '' || !is_string($timeValue) || $timeValue === '')
                {
                    return 0;
                }

                $ymdParts = explode('-', $dateParts, 3);
                if (count($ymdParts) !== 3)
                {
                    return 0;
                }
                $timeParts = explode(':', $timeValue, 3);
                if (count($timeParts) !== 3)
                {
                    return 0;
                }

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
                    if (!is_numeric($int))
                    {
                        return $min;
                    }

                    $int = (int) $int;
                    if (is_int($min) && $int < $min)
                    {
                        $int = $min;
                    }
                    else if (is_int($max) && $int > $max)
                    {
                        $int = $max;
                    }

                    return $int;
                };

                // php is somewhat smart and will move the y-m-d around to be valid
                $ymdParts[0] = $intSanitizer($ymdParts[0], 1970, null);
                $ymdParts[1] = $intSanitizer($ymdParts[1], 1, 12);
                $ymdParts[2] = $intSanitizer($ymdParts[2], 1, 31);

                $timeSanitizer = function (string $key) use(&$timeParts, &$intSanitizer)
                {
                    $timeParts[$key] = $intSanitizer($timeParts[$key], 0, null);
                };
                $timeSanitizer('hh'); // hours
                $timeSanitizer('mm'); // minutes
                $timeSanitizer('ss'); // seconds

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
                $dateTimeObj->setDate($ymdParts[0], $ymdParts[1], $ymdParts[2]);
                $dateTimeObj->setTime($timeParts['hh'], $timeParts['mm'], $timeParts['ss']);

                return $dateTimeObj->getTimestamp();
        }

        return parent::cleanInternal($value, $type, $options);
    }
}