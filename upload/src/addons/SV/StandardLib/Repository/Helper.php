<?php

namespace SV\StandardLib\Repository;

use \XF\Mvc\Entity\Repository;

class Helper extends Repository
{
    /**
     * @param \XF\Mvc\Entity\Entity|null $entity
     *
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

        if ($entity->isValidColumn($backupColumn))
        {
            /** @var \XF\Entity\User $user */
            $user = \XF::app()->find('XF:User', $entity->get($backupColumn));
            return $user;
        }

        return null;
    }
}