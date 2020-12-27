<?php

namespace SV\StandardLib\Db;

use XF\Db\Schema\Column;

class AlterColumnUnwrapper extends Column
{
    /**
     * @param Column $column
     * @return string|null
     * @noinspection PhpMissingReturnTypeInspection
     */
    public static function getRename(Column $column)
    {
        return $column->newName;
    }
}