<?php
/**
 * @noinspection PhpMissingParamTypeInspection
 * @noinspection PhpMissingReturnTypeInspection
 * @noinspection PhpIllegalPsrClassPathInspection
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