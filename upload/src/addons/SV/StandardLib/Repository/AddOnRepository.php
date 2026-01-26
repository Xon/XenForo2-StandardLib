<?php

namespace SV\StandardLib\Repository;

use SV\StandardLib\Helper as HelperUtil;
use SV\StandardLib\Repository\Helper as HelperRepo;
use XF\AddOn\AddOn;
use XF\Mvc\Entity\ArrayCollection;
use XF\Mvc\Entity\Repository;
use XF\PreEscaped;
use function array_fill_keys;
use function array_keys;
use function array_reverse;
use function count;
use function explode;
use function is_array;
use function is_numeric;
use function phpversion;
use function strpos;
use function strtolower;
use function version_compare;

class AddOnRepository extends Repository
{
    public static function get(): self
    {
        return HelperUtil::repository(self::class);
    }

    protected $keyOrder = ['require', 'optional-hard', 'optional-soft', 'advisory'];

    /**
     * @param array<string,array> $upgradeableAddOns
     * @return array
     */
    public function sortByDependencies(array $upgradeableAddOns): array
    {
        $installList = [];
        /** @var AddOn[] $complex */
        $complex = [];
        // init the list
        foreach ($upgradeableAddOns as $addOnId => $addOnJson)
        {
            $config = [];

            // decompose the json into a sequence of dependencies
            $requires = $addOnJson['require'] ?? [];
            unset($requires['php']);
            unset($requires['XF']);
            if (count($requires) !== 0)
            {
                $config['require'] = array_fill_keys(array_keys($requires), false);
            }
            $requires = $addOnJson['require-soft'] ?? [];
            if (!is_array($requires))
            {
                $requires = [];
            }
            foreach ($requires as $key => $require)
            {
                if (is_array($require) && count($require) >= 2)
                {
                    if (count($require) === 2)
                    {
                        $config['advisory'][$key] = false;
                    }
                    else if ($require[2])
                    {
                        $config['optional-hard'][$key] = false;
                    }
                    else
                    {
                        $config['optional-soft'][$key] = false;
                    }
                }
            }

            $installList[$addOnId] = [
                'config' => (new ArrayCollection($config))->sortByList($this->keyOrder)->toArray(),
                'addon'  => $addOnJson,
            ];
            if (count($config) !== 0)
            {
                $complex[$addOnId] = $addOnJson;
            }
        }

        // build the graph of dependencies
        foreach ($complex as $addOnId => $addOnJson)
        {
            $config = $installList[$addOnId]['config'] ?? [];
            foreach ($config as $key => $requires)
            {
                foreach ($requires as $productKey => $requirement)
                {
                    if (empty($installList[$productKey]))
                    {
                        continue;
                    }
                    if (empty($installList[$addOnId]['config'][$key][$productKey]))
                    {
                        $installList[$addOnId]['config'][$key][$productKey] = &$installList[$productKey];
                    }
                }
            }
        }

        // resolve the dependencies in order of priority
        $finalList = [];
        $deferred = [];
        foreach ($this->keyOrder as $key)
        {
            foreach ($installList as $addOnId => $addOnJson)
            {
                if (!empty($finalList[$addOnId]))
                {
                    continue;
                }

                if (empty($addOnJson['config'][$key]))
                {
                    $deferred[$addOnId] = $addOnId;
                    continue;
                }

                $loopDetection = [];
                $dependencies = $this->resolveDependencies($installList, $key, $addOnId, $loopDetection);
                if (count($dependencies) === 0)
                {
                    $deferred[$addOnId] = $addOnId;
                    continue;
                }

                unset($deferred[$addOnId]);
                $finalList = $finalList + $dependencies;
                $finalList[$addOnId] = $addOnJson['addon'];
            }
        }
        // dump unmet dependencies at the end
        foreach ($deferred as $addOnId)
        {
            $finalList[$addOnId] = $installList[$addOnId]['addon'];
        }

        return $finalList;
    }

    /**
     * @param array  $installList
     * @param string $key
     * @param string $addOnId
     * @param array  $loopDetection
     * @return array
     */
    protected function resolveDependencies(array $installList, string $key, string $addOnId, array &$loopDetection): array
    {
        $loopDetection[$key][$addOnId] = true;
        $finalList = [];
        foreach (($installList[$addOnId]['config'][$key] ?? []) as $childAddOnId => $addOnJson)
        {
            if (isset($loopDetection[$key][$childAddOnId]) || $addOnJson === false)
            {
                continue;
            }

            if (!empty($addOnJson['config'][$key]))
            {
                foreach (array_reverse($this->keyOrder) as $subKey)
                {
                    $dependencies = $this->resolveDependencies($installList, $subKey, $childAddOnId, $loopDetection);
                    $finalList += $dependencies;
                }
            }

            $finalList[$childAddOnId] = $addOnJson['addon'];
        }

        return $finalList;
    }

    public function checkAddOnRequirements(array $requirements, string $title, array &$errors, array &$warnings, bool $escape, bool $soft, array $existingAddOnsVersionStrings, array $existingAddOnVersionIds): void
    {
        foreach ($requirements as $productKey => $requirement)
        {
            if (!is_array($requirement))
            {
                continue;
            }
            [$version, $product] = $requirement;
            $errorType = $soft ? (count($requirement) >= 3 ? $requirement[2] : null) : true;

            // advisory
            if ($errorType === null)
            {
                continue;
            }

            [$enabled, $isVersionValid] = $this->isVersionValid($productKey, $version, $existingAddOnsVersionStrings, $existingAddOnVersionIds);
            if ($soft && !$enabled)
            {
                continue;
            }

            if (!$isVersionValid)
            {
                $reason = count($requirement) >= 4 ? (' ' . $requirement[3]) : '';
                $err = $errorType ? 'requires' : 'recommends';
                $s = "{$title} {$err} {$product}.{$reason}";

                if ($errorType)
                {
                    $errors[] = $escape ? new PreEscaped($s) : $s;
                }
                else
                {
                    $warnings[] = $escape ? new PreEscaped($s) : $s;
                }
            }
        }
    }

    protected function isVersionValid(string $productKey, string $version, array $existingAddOnsVersionStrings, array $existingAddOnVersionIds): array
    {
        $enabled = false;
        $versionValid = false;

        if (strpos($productKey, 'php-ext') === 0)
        {
            $parts = explode('/', $productKey, 2);
            if (isset($parts[1]))
            {
                $enabled = phpversion($parts[1]) !== false;
                $versionValid = ($version === '*') || (version_compare(phpversion($parts[1]), $version, 'ge'));
            }
        }
        else if (strpos($productKey, 'php') === 0)
        {
            $enabled = true;
            $versionValid = version_compare(phpversion(), $version, 'ge');
        }
        else if (strpos($productKey, 'mysql') === 0)
        {
            $mySqlVersion = \XF::db()->getServerVersion();
            if ($mySqlVersion)
            {
                $enabled = true;
                $versionValid = version_compare(strtolower($mySqlVersion), $version, 'ge');
            }
        }
        else
        {
            /** @var ?string $installedVersionString */
            $installedVersionString = $existingAddOnsVersionStrings[$productKey] ?? null;
            if ($installedVersionString === null)
            {
                return [false, false];
            }

            /** @var ?int $installedVersionId */
            $installedVersionId = $existingAddOnVersionIds[$productKey] ?? null;
            if ($installedVersionId === null)
            {
                return [false, false];
            }

            if ($version === '*' || $version === $installedVersionString)
            {
                return [true, true];
            }

            if (strpos($version, '.') === false && is_numeric($version))
            {
                $versionId = (int)$version;
                $versionValid = $installedVersionId >= $versionId;

                return [true, $versionValid];
            }

            $repo = HelperRepo::get();

            $targetVersion = $repo->sanitizeVersionString($version);
            $installedVersionString = $repo->sanitizeVersionString($installedVersionString);

            $versionValid = version_compare($installedVersionString, $targetVersion, '>=');
        }

        return [$enabled, $versionValid];
    }
}