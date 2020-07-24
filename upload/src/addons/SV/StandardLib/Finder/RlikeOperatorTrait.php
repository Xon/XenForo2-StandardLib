<?php

namespace SV\StandardLib\Finder;

use XF\Mvc\Entity\FinderExpression;

/**
 * @property \XF\Db\AbstractAdapter db
 * @method FinderExpression expression(string $sqlExpression, string... $columns)
 */
trait RlikeOperatorTrait
{
    public function RegexLikeExpressionLHS(string $column, string $value) : FinderExpression
    {
        $quoted = $this->db->quote($value);

        return $this->expression("%s RLIKE {$quoted}", $column);
    }

    public function RegexLikeExpressionRHS(string $column, string $value) : FinderExpression
    {
        $quoted = $this->db->quote($value);

        return $this->expression("{$quoted} RLIKE %s", $column);
    }
}