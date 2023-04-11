<?php

namespace SV\StandardLib\Repository;

use XF\Container;
use XF\Mvc\Entity\Repository;
use function in_array;
use function is_numeric;
use function is_string;
use function preg_replace;
use function str_replace;
use function strpos;
use function version_compare;

class Helper extends Repository
{
    /**
     * @param string          $addonId
     * @param string|int|null $targetVersion
     * @param string          $operator
     * @return bool
     */
    public function hasDesiredAddOnVersion(string $addonId, $targetVersion, string $operator = '>='): bool
    {
        // compatibility with \XF::isAddOnActive
        if (!in_array($operator, ['>', '>=', '<', '<=', '='], true))
        {
            $operator = '=';
        }

        if ($targetVersion === null || $targetVersion === '*')
        {
            $addOns = \XF::app()->container('addon.cache');
            return isset($addOns[$addonId]);
        }

        if (is_string($targetVersion) && strpos($targetVersion, '.') === false && is_numeric($targetVersion))
        {
            $targetVersion = (int)$targetVersion;
        }

        if (is_string($targetVersion))
        {
            $addOnEntity = \XF::em()->findCached('XF:AddOn', $addonId);
            if ($addOnEntity instanceof \XF\Entity\AddOn)
            {
                // unlike \XF::isAddOnActive, the add-on must not be in a processing state
                $installedVersionId = $addOnEntity->is_processing ? null : $addOnEntity->version_string;
            }
            else
            {
                $addons = $this->getAddonVersions();
                $installedVersionId = $addons[$addonId] ?? null;
                if ($installedVersionId === null)
                {
                    return false;
                }
            }

            if ($targetVersion === $installedVersionId && in_array($operator, ['=', '<=', '<='], true))
            {
                return true;
            }
            $targetVersion = $this->sanitizeVersionString($targetVersion);
            $installedVersionId = $this->sanitizeVersionString($installedVersionId);

            return version_compare($installedVersionId, $targetVersion, $operator);
        }

        if (\XF::$versionId < 2020000)
        {
            return $this->isAddOnActiveForXF21($addonId, $targetVersion, $operator);
        }

        return \XF::isAddOnActive($addonId, $targetVersion, $operator);
    }

    /**
     * XF2.1 support
     *
     * @param string $addOnId
     * @param int|null    $versionId
     * @param string $operator
     * @return bool|mixed
     */
    protected function isAddOnActiveForXF21(string $addOnId, int $versionId = null, string $operator = '>=')
    {
        $addOns = \XF::app()->container('addon.cache');
        if (!isset($addOns[$addOnId]))
        {
            return false;
        }

        $activeVersionId = $addOns[$addOnId];
        if ($versionId === null)
        {
            return $activeVersionId;
        }

        switch ($operator)
        {
            case '>':
                return ($activeVersionId > $versionId);

            case '>=':
                return ($activeVersionId >= $versionId);

            case '<':
                return ($activeVersionId < $versionId);

            case '<=':
                return ($activeVersionId <= $versionId);
        }

        return $activeVersionId;
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function getAddonVersions(): array
    {
        /** @var callable(Container, string): array $func */
        $func = $this->app()->fromRegistry('addon.versionCache', function (Container $c, string $key) {
            return $this->rebuildAddOnVersionCache();
        });

        return $func($this->app()->container(), 'addon.versionCache');
    }

    public function rebuildAddOnVersionCache(): array
    {
        // unlike \XF::isAddOnActive, the add-on must not be in a processing state
        $data = $this->db()->fetchPairs('
            SELECT addon_id, version_string
            FROM xf_addon
            WHERE `active` = 1 AND is_processing = 0
        ');
        $this->app()->registry()->set('addon.versionCache', $data);
        $this->markAsCriticalAddon();
        return $data;
    }

    protected function markAsCriticalAddon()//: void
    {
        if ($this->hasDesiredAddOnVersion('SV/InstallerAppHelper', null))
        {
            /** @noinspection PhpUndefinedClassInspection */
            /** @noinspection PhpUndefinedNamespaceInspection */
            \SV\InstallerAppHelper\InstallAppBootstrap::markAddonCritical('SV/StandardLib');
        }
    }

    public function resetAddOnVersionCache()//: void
    {
        $this->app()->registry()->delete('addon.versionCache');
    }

    /** @noinspection PhpUnnecessaryLocalVariableInspection */
    protected function sanitizeVersionString(string $version): string
    {
        $version = preg_replace('/\s+/u', ' ', mb_strtolower($version));
        $version = trim($version);
        if ($version === '')
        {
            return $version;
        }
        $version = preg_replace('/^(?:v|version)\s*/u', '', $version);
        $version = str_replace('patch level', 'pl', $version);
        $version = str_replace('release candidate', 'rc', $version);

        return $version;
    }

    /**
     * @param \DateInterval|array $interval
     * @param int                 $maximumDateParts
     * @param string              $phraseContext
     * @return array
     */
    public function buildRelativeDateString($interval, int $maximumDateParts = 0, string $phraseContext = 'raw'): array
    {
        if ($interval instanceof \DateInterval)
        {
            $interval = [
                'y' => $interval->y,
                'm' => $interval->m,
                'd' => $interval->d,
                'h' => $interval->h,
                'i' => $interval->i,
                's' => $interval->s,
                'invert' => $interval->invert,
            ];
        }

        $formatMaps = [
            'y' => 'year',
            'm' => 'month',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        ];

        $dateArr = [];
        foreach ($formatMaps AS $format => $phrase)
        {
            if ($maximumDateParts && \count($dateArr) >= $maximumDateParts)
            {
                break;
            }

            $value = $interval[$format];
            if ($value === 1)
            {
                $dateArr[] = \XF::phrase('time.' . $phrase, [
                    'count' => $value
                ])->render($phraseContext);
            }
            else if ($value > 1)
            {
                $dateArr[] = \XF::phrase('time.' . $phrase . 's', [
                    'count' => $value
                ])->render($phraseContext);
            }
            else if ($maximumDateParts > 0 && \count($dateArr) > 0)
            {
                break;
            }
        }

        return $dateArr;
    }

    /**
     * Generates an momentJs compatible date diff structure. Returns empty array on equal values
     *
     * @param int $a
     * @param int $b
     * @return int[]
     */
    public function momentJsCompatibleTimeDiff(int $a, int $b): array
    {
        $diffInSeconds = $b - $a;
        if ($diffInSeconds === 0)
        {
            return [];
        }
        $data = ['invert' => $diffInSeconds < 0];

        // Derived from momentJs, MIT licenced. For licence details see js/vendor/moment/LICENSE
        // ***********

        //$milliseconds = abs($diffInSeconds);
        $seconds = abs($diffInSeconds);
        $months = 0;
        $absCeil = function ($number) {
            if ($number < 0)
            {
                return \floor($number);
            }
            else
            {
                return \ceil($number);
            }
        };

        $absFloor = function ($number) {
            if ($number < 0)
            {
                // -0 -> 0
                return \ceil($number) || 0;
            }
            else
            {
                return \floor($number);
            }
        };

        $daysToMonths = function ($days) {
            // 400 years have 146097 days (taking into account leap year rules)
            // 400 years have 12 months === 4800
            return ($days * 4800) / 146097;
        };

        $monthsToDays = function ($months) {
            // the reverse of daysToMonths
            return ($months * 146097) / 4800;
        };

        // The following code bubbles up values, see the tests for
        // examples of what that means.
//        $data['f'] = (int)($milliseconds % 1000);

//        $seconds = $absFloor($milliseconds / 1000);
        $data['s'] = $seconds % 60;

        $minutes = $absFloor($seconds / 60);
        $data['i'] = $minutes % 60;

        $hours = $absFloor($minutes / 60);
        $data['h'] = $hours % 24;

        $days = $absFloor($hours / 24);

        // convert days to months
        $monthsFromDays = $absFloor($daysToMonths($days));
        $months += $monthsFromDays;
        $days -= $absCeil($monthsToDays($monthsFromDays));

        // 12 months -> 1 year
        $years = $absFloor($months / 12);
        $months %= 12;

        $data['d'] = (int)$days;
        $data['m'] = (int)$months;
        $data['y'] = (int)$years;

        // ***********

        return $data;
    }

    /**
     * @param mixed|\XF\Mvc\Entity\Entity|null $entity
     * @param string                           $relationOrGetter
     * @param string                           $backupColumn
     * @return \XF\Entity\User|null
     */
    public function getUserEntity($entity, string $relationOrGetter = 'User', string $backupColumn = 'user_id')
    {
        if (!($entity instanceof \XF\Mvc\Entity\Entity))
        {
            return null;
        }

        if ($entity instanceof \XF\Entity\User)
        {
            return $entity;
        }

        if ($entity->isValidGetter($relationOrGetter) || $entity->isValidRelation($relationOrGetter))
        {
            $user = $entity->get($relationOrGetter);
            if ($user instanceof \XF\Entity\User)
            {
                return $user;
            }
        }

        if ($entity->isValidColumn($backupColumn) || $entity->isValidGetter($backupColumn))
        {
            /** @var \XF\Entity\User $user */
            $user = \XF::app()->find('XF:User', $entity->get($backupColumn));

            return $user;
        }

        return null;
    }

    public function aliasClass(string $destClass, string $srcClass)
    {
        \class_alias($srcClass, $destClass);

        $nsEnd = \strrpos($srcClass, '\\');
        if ($nsEnd)
        {
            $srcAlias = \substr($srcClass, 0, $nsEnd) . '\\XFCP_' . \substr($srcClass, $nsEnd + 1);
        }
        else
        {
            $srcAlias = "XFCP_$srcClass";
        }
        $nsEnd = \strrpos($destClass, '\\');
        if ($nsEnd)
        {
            $destAlias = \substr($destClass, 0, $nsEnd) . '\\XFCP_' . \substr($destClass, $nsEnd + 1);
        }
        else
        {
            $destAlias = "XFCP_$destClass";
        }

        \class_alias($destAlias, $srcAlias, false);
    }
}