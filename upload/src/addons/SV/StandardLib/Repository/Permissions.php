<?php

namespace SV\StandardLib\Repository;

use SV\StandardLib\Permissions\PermissionCache;
use XF\Entity\User;
use XF\Mvc\Entity\Repository;
use function array_key_exists;
use function count;
use function implode;

class Permissions extends Repository
{
    /**
     * @param array<int> $permissionCombinationIds
     * @return void
     */
    public function cacheGlobalPermissions(array $permissionCombinationIds)
    {
        $db = $this->db();

        $cachedPerms = PermissionCache::getCachedGlobalPermissions();
        $uncachedCombinations = [];
        foreach ($permissionCombinationIds as $permissionCombinationId)
        {
            if (!array_key_exists($permissionCombinationId, $cachedPerms))
            {
                $uncachedCombinations[] = $permissionCombinationId;
            }
        }

        if (count($uncachedCombinations) === 0)
        {
            return;
        }

        $permissionCombinationIds = $db->quote($uncachedCombinations);

        $permissions = $db->fetchAll("
            SELECT permission_combination_id, cache_value
            FROM xf_permission_combination
            WHERE permission_combination_id in ({$permissionCombinationIds})
        ");

        foreach ($permissions as $permissionCombination)
        {
            \XF\Entity\PermissionCombination::instantiateProxied($permissionCombination);
        }
    }

    /**
     * @param string $contentType
     * @param string $permissionGroup
     * @param array<int,int[]> $permissionMap format; $permissionMap[$permissionCombinationId][$id] = true;
     * @return void
     */
    public function cachePermissions(string $contentType, string $permissionGroup, array $permissionMap)
    {
        $db = $this->db();

        $allCachedPermissions = PermissionCache::getCachedContentPermissions();
        $conditions = [];
        foreach ($permissionMap as $permissionCombinationId => $ids)
        {
            $uncachedCategories = [];
            foreach ($ids as $id => $null)
            {
                if (empty($allCachedPermissions[$permissionCombinationId][$permissionGroup][$id]))
                {
                    $uncachedCategories[] = $id;
                }
            }

            if ($uncachedCategories)
            {
                $categoryIds = $db->quote($uncachedCategories);
                $permissionCombinationIds = $db->quote($permissionCombinationId);
                $conditions[] = "content_id in ({$categoryIds}) AND permission_combination_id IN ({$permissionCombinationIds})";
            }
        }

        if (count($conditions) === 0)
        {
            return;
        }

        $conditions = implode(') OR (', $conditions);
        $permissions = $db->fetchAll("
            SELECT permission_combination_id, content_type, content_id, cache_value
            FROM xf_permission_cache_content
            WHERE content_type = ?
                AND ({$conditions})
        ", [$contentType]);

        foreach ($permissions as $permissionCombination)
        {
            \XF\Entity\PermissionCacheContent::instantiateProxied($permissionCombination);
        }
    }

    public function getPerContentPermissions(string $contentType, User $user = null): array
    {
        $user = $user ?? \XF::visitor();
        $permissionCombinationId = $user->permission_combination_id;
        $permissionCache = $user->PermissionSet->getPermissionCache();
        $permissionCache->cacheAllContentPerms($permissionCombinationId, $contentType);
        $contentPerms = PermissionCache::getCachedContentPermissions($permissionCache);

        return $contentPerms[$permissionCombinationId][$contentType] ?? [];
    }

    /**
     * @deprecated Replacement is getPerContentPermissions
     * @noinspection PhpUnusedParameterInspection
     */
    public function getContentPermissions(string $contentType, string $NotUseArg, User $user = null): array
    {
        return $this->getPerContentPermissions($contentType, $user);
    }

    public function getGlobalPermissions(User $user = null): array
    {
        $user = $user ?? \XF::visitor();
        $permissionCombinationId = $user->permission_combination_id;
        $permissionCache = $user->PermissionSet->getPermissionCache();
        $globalPerms = PermissionCache::getCachedGlobalPermissions($permissionCache);

        return $globalPerms[$permissionCombinationId] ?? [];
    }
}