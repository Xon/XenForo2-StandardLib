<?php
/**
 * @noinspection RedundantSuppression
 * @noinspection PhpMissingParamTypeInspection
 * @noinspection PhpMissingReturnTypeInspection
 * @noinspection PhpIllegalPsrClassPathInspection
 * @noinspection PhpMultipleClassDeclarationsInspection
 */
namespace XF\Entity;

/**
 * An interface for entities which have a date.
 */
interface DatableInterface
{
    /**
     * The name of the date column.
     */
    public function getContentDateColumn(): string;

    /**
     * The value of the date.
     */
    public function getContentDate(): int;
}