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
 * An interface for entities which belong to a container entity.
 */
interface ContainableInterface
{
    /**
     * The name of the container ID column.
     */
    public function getContentContainerIdColumn(): string;

    /**
     * The ID of the container.
     */
    public function getContentContainerId(): int;

    /**
     * The content type of the container.
     */
    public function getContentContainerType(): string;
}