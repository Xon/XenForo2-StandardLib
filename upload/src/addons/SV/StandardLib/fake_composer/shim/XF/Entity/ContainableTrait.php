<?php
/**
 * @noinspection RedundantSuppression
 * @noinspection PhpMissingParamTypeInspection
 * @noinspection PhpMissingReturnTypeInspection
 * @noinspection PhpIllegalPsrClassPathInspection
 * @noinspection PhpMultipleClassDeclarationsInspection
 */

namespace XF\Entity;

trait ContainableTrait
{
    public function getContentContainerId(): int
    {
        $column = $this->getContentContainerIdColumn();

        return $this->{$column};
    }
}