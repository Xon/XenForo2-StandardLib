<?php
/**
 * @noinspection RedundantSuppression
 * @noinspection PhpMissingParamTypeInspection
 * @noinspection PhpMissingReturnTypeInspection
 * @noinspection PhpIllegalPsrClassPathInspection
 * @noinspection PhpMultipleClassDeclarationsInspection
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