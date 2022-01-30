<?php

namespace SV\StandardLib\Db;

use XF\Db\Schema\Column as DbColumnSchema;
use XF\Db\Schema\Alter as DbAlterSchema;
use XF\Db\Schema\Index as DbIndexSchema;

/**
 * @version 1.10.0
 */
abstract class AlterTableUnwrapper extends DbAlterSchema
{
    /**
     * @param DbAlterSchema $table
     *
     * @return DbColumnSchema[]
     */
    public static function getChangeColumns(DbAlterSchema $table): array
    {
        return $table->changeColumns;
    }

    /**
     * @since 1.10.0
     *
     * @param DbAlterSchema $table
     *
     * @return bool
     */
    public static function resetChangeColumns(DbAlterSchema $table) : bool
    {
        $table->changeColumns = [];

        return true;
    }

    /**
     * @since 1.10.0
     *
     * @param DbAlterSchema $table
     *
     * @return DbIndexSchema[]
     */
    public static function getAddIndexes(DbAlterSchema $table) : array
    {
        return $table->addIndexes;
    }

    /**
     * @since 1.10.0
     *
     * @param DbAlterSchema $table
     *
     * @return bool
     */
    public static function resetAddIndexes(DbAlterSchema $table) : bool
    {
        $table->addIndexes = [];

        return true;
    }

    /**
     * @since 1.10.0
     *
     * @param DbAlterSchema $table
     *
     * @return int|null
     */
    public static function getAutoIncrementIndex(DbAlterSchema $table)
    {
        return $table->autoIncrementIndex;
    }

    /**
     * @since 1.10.0
     *
     * @param DbAlterSchema $table
     *
     * @return bool
     */
    public static function resetAutoIncrementIndex(DbAlterSchema $table) : bool
    {
        $table->autoIncrementIndex = null;

        return true;
    }

    /**
     * @since 1.10.0
     *
     * @param DbAlterSchema $table
     *
     * @return DbColumnSchema[]
     */
    public static function getAddColumns(DbAlterSchema $table) : array
    {
        return $table->addColumns;
    }

    /**
     * @since 1.10.0
     *
     * @param DbAlterSchema $table
     *
     * @return bool
     */
    public static function resetAddColumns(DbAlterSchema $table) : bool
    {
        $table->addColumns = [];

        return true;
    }
}