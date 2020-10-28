<?php

namespace SV\StandardLib\XF;

use XF\App as BaseApp;
use XF\Data\TimeZone as TimeZoneData;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Repository;
use XF\Service\AbstractService;
use XF\Mvc\Entity\Manager as EntityManager;
use XF\Job\Manager as JobManager;

class InputFilterer extends XFCP_InputFilterer
{
    /**
     * @param mixed $value
     * @param string $type
     * @param array $options
     *
     * @return mixed
     */
    protected function cleanInternal($value, $type, array $options)
    {
        switch ($type)
        {
            case 'sv-date-time':
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
                    // daily reminder to wash your hands
                    $intSanitizer = function ($int, int $min, int $max)
                    {
                        if (!\is_int($int))
                        {
                            return $min;
                        }

                        $int = (int) $int;
                        if ($int < $min || $int > $max)
                        {
                            return $min;
                        }

                        return $int;
                    };

                    $ymdParts = null;
                    if (\is_string($value['ymd']))
                    {
                        $ymdParts = \explode('-', $value['ymd'], 3);
                        if (\count($ymdParts) === 3)
                        {
                            $ymdParts[0] = $intSanitizer($ymdParts[0], 1970, (int) \date('Y', \XF::$time));
                            $ymdParts[1] = $intSanitizer($ymdParts[1], 1, 12);

                            // @see https://www.php.net/manual/en/function.cal-days-in-month.php#38666
                            $ymdParts[2] = $ymdParts[1] === 2 ? ($ymdParts[0] % 4 ? 28 : ($ymdParts[0] % 100 ? 29 : ($ymdParts[0] % 400 ? 28 : 29))) : (($ymdParts[1] - 1) % 7 % 2 ? 30 : 31);
                        }
                    }

                    if ($ymdParts === null || \count($ymdParts) !== 3)
                    {
                        $ymdParts = [
                            \date('Y', \XF::$time),
                            \date('m', \XF::$time),
                            \date('d', \XF::$time)
                        ];
                    }

                    $timeSanitizer = function (string $key, int $min, int $max) use(&$value, &$intSanitizer)
                    {
                        $intSanitizer($value[$key], $min, $max);
                    };

                    $timeSanitizer('hh', 0, 23); // hours
                    $timeSanitizer('mm', 0, 59); // minutes
                    $timeSanitizer('ss', 0, 59); // seconds

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

                    $value = $dateTimeObj->getTimestamp();
                }
                else
                {
                    $value = 0;
                }

                return $value;
        }

        return parent::cleanInternal($value, $type, $options);
    }
}