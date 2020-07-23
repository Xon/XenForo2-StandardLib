<?php

namespace SV\StandardLib\Finder;

use XF\Mvc\Entity\FinderExpression;

/**
 * @property \XF\Db\AbstractAdapter db
 * @method FinderExpression expression(string $sqlExpression, string... $columns)
 */
trait RlikeOperatorTrait
{
    /**
     * @param string $column
     * @param string $value
     * @return FinderExpression
     */
    public function RegexLikeExpressionLHS($column, $value)
    {
        $quoted = $this->db->quote($value);

        return $this->expression("%s RLIKE {$quoted}", $column);
    }

    /**
     * @param string $column
     * @param string $value
     * @return FinderExpression
     */
    public function RegexLikeExpressionRHS($column, $value)
    {
        $quoted = $this->db->quote($value);

        return $this->expression("{$quoted} RLIKE %s", $column);
    }
}