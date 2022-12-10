<?php
/**
 * @noinspection PhpMissingReturnTypeInspection
 * @noinspection PhpMissingParamTypeInspection
 * @noinspection PhpUndefinedClassInspection
 */

namespace SV\StandardLib\Finder;

use function count;
use function implode;
use function is_array;

/**
 * @property array joins
 * @property string[] indexHints
 * @property \XF\Db\AbstractAdapter db
 *
 * @method string columnSqlName(string $column, bool $markFundamental = true)
 */
trait SqlJoinTrait
{
    /** @var array */
    protected $rawJoins = [];
    /** @var ?array */
    protected $allJoins = null;
    /** @var bool */
    protected $hasTableExpr = false;

    public function getQuery(array $options = [])
    {
        $joins = $this->joins;
        $indexHints = $this->indexHints;
        $hasTableExpr = $this->hasTableExpr;
        if ($this->hasTableExpr)
        {
            // handle stacked instances of SqlJoinTrait
            $this->hasTableExpr = false;
            $countOnly = !empty($options['countOnly']);

            $this->allJoins = $this->joins;
            $complexJoins = [];
            foreach($this->rawJoins as $alias => $columns)
            {
                $join = $this->joins[$alias] ?? [];
                if ($join['hasTableExpr'] ?? false)
                {
                    if ($countOnly && !$join['fundamental'])
                    {
                        continue;
                    }

                    $joinType = $join['exists'] ? 'INNER' : 'LEFT';
                    $indexHintArr = $join['indexHints'] ?? [];
                    $joinHints = count($indexHintArr) === 0 ? '' : ' ' . implode(' ', $indexHintArr);

                    $complexJoins[] = "{$joinType} JOIN {$join['table']} AS `{$join['alias']}`{$joinHints} ON ({$join['condition']})";

                    unset($this->joins[$alias]);
                }
            }
            $this->indexHints[] = "\n". \implode('', $complexJoins);
        }
        try
        {
            return parent::getQuery($options);
        }
        finally
        {
            if ($hasTableExpr)
            {
                $this->joins = $joins;
                $this->allJoins = null;
                $this->indexHints = $indexHints;
                $this->hasTableExpr = $hasTableExpr;
            }
        }
    }

    /**
     * @param string $rawJoinTable
     * @param string $alias
     * @param array  $columns
     * @param bool   $mustExist
     * @param bool   $hasTableExpr
     * @return $this
     */
    public function sqlJoin(string $rawJoinTable, string $alias, array $columns, bool $mustExist = false, bool $hasTableExpr = false)
    {
        $columns = \array_fill_keys($columns, true);
        $this->rawJoins[$alias] = isset($this->rawJoins[$alias]) ? $this->rawJoins[$alias] + $columns : $columns;

        if (isset($this->joins[$alias]))
        {
            $this->joins[$alias]['exists'] = $this->joins[$alias]['exists'] || $mustExist;

            return $this;
        }
        $this->hasTableExpr = $this->hasTableExpr || $hasTableExpr;

        $this->joins[$alias] = [
            'rawJoin'        => true,
            // arbitrary table expressions need to be rewritten into index hints cos XF quotes the table name
            'hasTableExpr'   => $hasTableExpr,
            // the $this->join entry must match the following structure, with 'fetch' being false so getHydrationMap doesn't try to parse this
            'table'          => $rawJoinTable,
            'alias'          => $alias,
            'condition'      => '',
            'fetch'          => false,
            'fundamental'    => false,
            'exists'         => $mustExist,
            'reallyFundamental' => false,
            'indexHints'     => [], // XF2.2.12+

            // these are the attributes stored in the joins array, used by getHydrationMap() but not getQuery()
            'structure'      => null,
            'parentAlias'    => null,
            'proxy'          => null,
            'parentRelation' => null,
            'relation'       => null,
            'relationValue'  => null,
            'entity'         => null,
        ];

        return $this;
    }

    public function sqlJoinConditions(string $alias, array $conditions, array $indexHints = [])
    {
        if (empty($this->rawJoins[$alias]) || empty($this->joins[$alias]))
        {
            throw new \LogicException('Need to invoke sqlJoin() before sqlJoinConditions()');
        }

        $joinConditions = [];

        foreach ($conditions AS $condition)
        {
            if (\is_string($condition))
            {
                $joinConditions[] = "`$alias`.`$condition` = " . $this->columnSqlName($condition);
            }
            else
            {
                list($field, $operator, $value) = $condition;

                if (\count($condition) > 3)
                {
                    $readValue = [];
                    foreach (\array_slice($condition, 2) AS $v)
                    {
                        if ($v && $v[0] === '$')
                        {
                            $readValue[] = $this->columnSqlName(\substr($v, 1));
                        }
                        else
                        {
                            $readValue[] = $this->db->quote($v);
                        }
                    }

                    $value = 'CONCAT(' . \implode(', ', $readValue) . ')';
                }
                else if (\is_string($value) && $value && $value[0] === '$')
                {
                    $value = $this->columnSqlName(\substr($value, 1));
                }
                else
                {
                    $value = $this->db->quote($value);
                }

                if ($field[0] === '$')
                {
                    $fromJoinAlias = $this->columnSqlName(\substr($field, 1));
                }
                else
                {
                    $fromJoinAlias = "`$alias`.`$field`";
                }

                $joinConditions[] = "$fromJoinAlias $operator $value";
            }
        }
        $hints = [];
        if (count($indexHints) !== 0 && \XF::$versionId >= 2021200)
        {
            if (!is_array($indexHints[0]))
            {
                $indexHints = [[$indexHints[0], $indexHints[1]]];
            }

            foreach ($indexHints as $indexHint)
            {
                /** @noinspection PhpUndefinedMethodInspection */
                $hints[] = $this->buildIndexHint($indexHint[0], $indexHint[0]);
            }
        }

        $this->joins[$alias]['indexHints'] = $hints;
        $this->joins[$alias]['fundamental'] = (bool)$joinConditions;
        $this->joins[$alias]['condition'] = \implode(' AND ', $joinConditions);
    }

    /**
     * @param string $field
     * @param bool $markJoinFundamental
     *
     * @return array
     */
    public function resolveFieldToTableAndColumn($field, $markJoinFundamental = true)
    {
        $parts = \explode('.', $field);
        if (\count($parts) === 2)
        {
            list($alias, $column) = $parts;
            if (isset($this->rawJoins[$alias][$column]))
            {
                $this->joins[$alias]['fundamental'] = $markJoinFundamental;
                $this->joins[$alias]['reallyFundamental'] = true;

                return [$alias, $column];
            }
        }

        return parent::resolveFieldToTableAndColumn($field, $markJoinFundamental);
    }
}