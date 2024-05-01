<?php
/**
 * @noinspection PhpMissingParamTypeInspection
 * @noinspection PhpMissingReturnTypeInspection
 * @noinspection PhpIllegalPsrClassPathInspection
 */

namespace XF\Entity;

trait DatableTrait
{
    public function getContentDate(): int
    {
        $column = $this->getContentDateColumn();
        return $this->{$column};
    }
}