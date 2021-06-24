<?php

namespace SV\StandardLib\Db;

use XF\Db\Schema\Column;

class AlterColumnUnwrapper extends Column
{
    /**
     * @param Column $column
     * @return string|null
     */
    public static function getRename(Column $column)
    {
        return $column->newName;
    }
}