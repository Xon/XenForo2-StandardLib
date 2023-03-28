<?php

namespace SV\StandardLib;

class Helper
{
    /**
     * Private constructor, use statically.
     */
    private function __construct() { }

    public static function repo(): \SV\StandardLib\Repository\Helper
    {
        /** @var \SV\StandardLib\Repository\Helper $repo */
        $repo = \XF::repository('SV\StandardLib:Helper');

        return $repo;
    }

    public static function perms(): \SV\StandardLib\Repository\Permissions
    {
        /** @var \SV\StandardLib\Repository\Permissions $repo */
        $repo = \XF::repository('SV\StandardLib:Permissions');

        return $repo;
    }

    /**
     * @param string     $addonId
     * @param string|int $targetVersion
     * @param string     $operator
     * @return bool
     */
    public static function isAddOnActive(string $addonId, $targetVersion = null, string $operator = '>='): bool
    {
        return self::repo()->hasDesiredAddOnVersion($addonId, $targetVersion, $operator);
    }
}