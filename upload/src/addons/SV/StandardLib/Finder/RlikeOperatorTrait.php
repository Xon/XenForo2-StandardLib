<?php

namespace SV\StandardLib\Finder;

use XF\Db\AbstractAdapter;
use XF\Mvc\Entity\FinderExpression;

/**
 * @property AbstractAdapter $db
 * @method FinderExpression expression(string $sqlExpression, string...$columns)
 */
trait RlikeOperatorTrait
{
    public function RegexLikeExpressionLHS(string $column, string $value): FinderExpression
    {
        $quoted = $this->db->quote($value);

        return $this->expression("%s RLIKE {$quoted}", $column);
    }

    public function RegexLikeExpressionRHS(string $column, string $value): FinderExpression
    {
        $quoted = $this->db->quote($value);

        return $this->expression("{$quoted} RLIKE %s", $column);
    }
}