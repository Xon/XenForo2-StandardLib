<?php

namespace SV\StandardLib\Permissions;

abstract class PermissionCache extends \XF\PermissionCache
{
    /**
     * Do not use this function, use `\SV\StandardLib\Helper::perms()->getGlobalPermissions()
     *
     * @param \XF\PermissionCache|null $permissionCache
     * @return array
     */
    public static function getCachedGlobalPermissions(\XF\PermissionCache $permissionCache = null): array
    {
        $permissionCache = $permissionCache ?? \XF::permissionCache();
        return $permissionCache->globalPerms;
    }

    /**
     * Do not use this function, use `\SV\StandardLib\Helper::perms()->getContentPermissions()
     *
     * @param \XF\PermissionCache|null $permissionCache
     * @return array
     */
    public static function getCachedContentPermissions(\XF\PermissionCache $permissionCache = null): array
    {
        $permissionCache = $permissionCache ?? \XF::permissionCache();
        return $permissionCache->contentPerms;
    }
}
