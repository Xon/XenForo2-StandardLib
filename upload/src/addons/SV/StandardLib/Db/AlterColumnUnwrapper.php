<?php

namespace SV\StandardLib\Db;

use XF\Db\Schema\Column;

class AlterColumnUnwrapper extends Column
{
    public static function getRename(Column $column): ?string
    {
        return $column->newName;
    }
}