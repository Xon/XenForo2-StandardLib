<?php

namespace SV\StandardLib\Repository;

use DateInterval;
use SV\InstallerAppHelper\InstallAppBootstrap;
use XF\Container;
use XF\Entity\AddOn;
use XF\Entity\User as UserEntity;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Repository;
use XF\Util\File as FileUtil;
use function abs;
use function assert;
use function ceil;
use function class_alias;
use function count;
use function floor;
use function in_array;
use function is_numeric;
use function is_string;
use function mb_strtolower;
use function md5;
use function preg_replace;
use function str_replace;
use function strpos;
use function strrpos;
use function substr;
use function trim;
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
            $addOnEntity = \SV\StandardLib\Helper::findCached(AddOn::class, $addonId);
            if ($addOnEntity instanceof AddOn)
            {
                // unlike \XF::isAddOnActive, the add-on must not be in a processing state
                $installedVersionId = $addOnEntity->is_processing ? null : $addOnEntity->version_string;
            }
            else
            {
                $addons = $this->getAddonVersions();
                $installedVersionId = $addons[$addonId] ?? null;
            }
            if ($installedVersionId === null)
            {
                return false;
            }

            if ($targetVersion === $installedVersionId && in_array($operator, ['=', '<=', '>='], true))
            {
                return true;
            }
            $targetVersion = $this->sanitizeVersionString($targetVersion);
            $installedVersionId = $this->sanitizeVersionString($installedVersionId);

            return version_compare($installedVersionId, $targetVersion, $operator);
        }

        if (\XF::$versionId < 2020000)
        {
            return (bool)$this->isAddOnActiveForXF21($addonId, $targetVersion, $operator);
        }

        return (bool)\XF::isAddOnActive($addonId, $targetVersion, $operator);
    }

    /**
     * XF2.1 support
     *
     * @param string $addOnId
     * @param int|null    $versionId
     * @param string $operator
     * @return bool|int
     */
    protected function isAddOnActiveForXF21(string $addOnId, ?int $versionId = null, string $operator = '>=')
    {
        $addOns = \XF::app()->container('addon.cache');
        $activeVersionId = $addOns[$addOnId] ?? null;
        if ($activeVersionId === null)
        {
            return false;
        }
        /** @var int $activeVersionId */
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
        $func = \XF::app()->fromRegistry('addon.versionCache', function (Container $c, string $key) {
            return $this->rebuildAddOnVersionCache();
        });

        return $func(\XF::app()->container(), 'addon.versionCache');
    }

    public function rebuildAddOnVersionCache(): array
    {
        // unlike \XF::isAddOnActive, the add-on must not be in a processing state
        $data = \XF::db()->fetchPairs('
            SELECT addon_id, version_string
            FROM xf_addon
            WHERE `active` = 1 AND is_processing = 0
        ');
        \XF::app()->registry()->set('addon.versionCache', $data);
        $this->markAsCriticalAddon();
        return $data;
    }

    protected function markAsCriticalAddon(): void
    {
        if ($this->hasDesiredAddOnVersion('SV/InstallerAppHelper', null))
        {
            InstallAppBootstrap::markAddonCritical('SV/StandardLib');
        }
    }

    public function resetAddOnVersionCache(): void
    {
        \XF::app()->registry()->delete('addon.versionCache');
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
     * @param DateInterval|array $interval
     * @param int                $maximumDateParts
     * @param string             $phraseContext
     * @param bool               $showSeconds
     * @return array
     */
    public function buildRelativeDateString($interval, int $maximumDateParts = 0, string $phraseContext = 'raw', bool $showSeconds = false): array
    {
        if ($interval instanceof DateInterval)
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

        // only show seconds in the last 2 minutes
        if (!$showSeconds && ($interval['y'] || $interval['m'] || $interval['d'] || $interval['h'] || $interval['i'] > 2))
        {
            unset($formatMaps['s']);
        }

        $dateArr = [];
        foreach ($formatMaps AS $format => $phrase)
        {
            if ($maximumDateParts && count($dateArr) >= $maximumDateParts)
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
            else if ($maximumDateParts > 0 && count($dateArr) > 0)
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
                return floor($number);
            }
            else
            {
                return ceil($number);
            }
        };

        $absFloor = function ($number) {
            if ($number < 0)
            {
                // -0 -> 0
                return ceil($number) || 0;
            }
            else
            {
                return floor($number);
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
     * @param mixed|Entity|null $entity
     * @param string            $relationOrGetter
     * @param string            $backupColumn
     * @return UserEntity|null
     */
    public function getUserEntity($entity, string $relationOrGetter = 'User', string $backupColumn = 'user_id'): ?UserEntity
    {
        if (!($entity instanceof Entity))
        {
            return null;
        }

        if ($entity instanceof UserEntity)
        {
            return $entity;
        }

        if ($entity->isValidGetter($relationOrGetter) || $entity->isValidRelation($relationOrGetter))
        {
            $user = $entity->get($relationOrGetter);
            if ($user instanceof UserEntity)
            {
                return $user;
            }
        }

        if ($entity->isValidColumn($backupColumn) || $entity->isValidGetter($backupColumn))
        {
            /** @var UserEntity|null $user */
            $user = \SV\StandardLib\Helper::find(UserEntity::class, $entity->get($backupColumn));

            return $user;
        }

        return null;
    }

    /**
     * @param string $destClass
     * @param class-string $srcClass
     * @return void
     */
    public function aliasClass(string $destClass, string $srcClass): void
    {
        if (\XF::$versionId < 2021300)
        {
            $this->aliasClassSimple($destClass, $srcClass);

            return;
        }

        if ($destClass[0] !== '\\')
        {
            $destClass = '\\'.$destClass;
        }
        if ($srcClass[0] !== '\\')
        {
            $srcClass = '\\'.$srcClass;
        }
        $file = '/svShim/'.md5($destClass.'-'.$srcClass).'.php';
        $stubFile = FileUtil::getCodeCachePath() . $file;

        // include returns false if the file doesn't exist, so don't bother with file_exists/is_readable checks
        $result = false;
        try
        {
            $result = @include($stubFile);
        }
        catch (\Throwable $e)
        {
            $this->logException($e);
        }
        if ($result === true)
        {
            return;
        }

        $php = $this->buildShimStubFile($srcClass, $destClass);

        FileUtil::writeToAbstractedPath('code-cache:/'.$file, $php);

        try
        {
            @include($stubFile);
        }
        catch (\Throwable $e)
        {
            $this->logException($e);
        }
    }

    protected function buildShimStubFile(string $srcClass, string $destClass): string
    {
        if ($srcClass === '' || $srcClass[0] !== '\\')
        {
            throw new \LogicException('Expected $finalClass to not be empty and start with a \\');
        }
        if ($destClass === '' || $destClass[0] !== '\\')
        {
            throw new \LogicException('Expected $finalClass to not be empty and start with a \\');
        }

        $nsEnd = strrpos($srcClass, '\\');
        $srcAlias = substr($srcClass, 1, $nsEnd) . 'XFCP_' . substr($srcClass, $nsEnd + 1);

        $nsEnd = strrpos($destClass, '\\');
        $namespace = substr($destClass, 1, $nsEnd - 1);
        $class = substr($destClass, $nsEnd + 1);
        $destAlias = $namespace. '\\XFCP_' . $class;

        return <<<EOL
<?php
namespace $namespace;
class $class extends $srcClass {}
class_alias('$destAlias', '$srcAlias', false);
return true;
EOL;
    }

    public function clearShimCache(): void
    {
        try
        {
            FileUtil::deleteAbstractedDirectory('code-cache://svShim');
        }
        catch (\Throwable $e)
        {
            $this->logException($e);
        }
    }

    protected function logException($e): void
    {
        // Suppress error reporting, as it is likely to be a transient issue during add-on install/upgrade that can be safely ignored
        if (\XF::$developmentMode)
        {
            \XF::logException($e, false, 'Suppressed:');
        }
    }

    /**
     * XF2.2.13+ implements \XF\Extension::inverseExtensionMap, which simplifies resolveExtendedClassToRoot significantly
     * However this is incompatible with class_alias for the top-level class extension
     *
     * @param string $destClass
     * @param string $srcClass
     * @return void
     */
    public function aliasClassSimple(string $destClass, string $srcClass): void
    {
        class_alias($srcClass, $destClass);

        $nsEnd = strrpos($srcClass, '\\');
        if ($nsEnd !== false)
        {
            $srcAlias = substr($srcClass, 0, $nsEnd) . '\\XFCP_' . substr($srcClass, $nsEnd + 1);
        }
        else
        {
            $srcAlias = "XFCP_$srcClass";
        }
        $nsEnd = strrpos($destClass, '\\');
        if ($nsEnd !== false)
        {
            $destAlias = substr($destClass, 0, $nsEnd) . '\\XFCP_' . substr($destClass, $nsEnd + 1);
        }
        else
        {
            $destAlias = "XFCP_$destClass";
        }

        class_alias($destAlias, $srcAlias, false);
    }
}