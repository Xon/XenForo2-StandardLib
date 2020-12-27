<?php

namespace SV\StandardLib\Db;

use XF\Db\Schema\Column;

abstract class AlterTableUnwrapper extends \XF\Db\Schema\Alter
{
    /**
     * @param \XF\Db\Schema\Alter $table
     * @return Column[]
     */
    public static function getChangeColumns(\XF\Db\Schema\Alter $table): array
    {
        return $table->changeColumns;
    }
}