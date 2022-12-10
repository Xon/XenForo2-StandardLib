<?php
/**
 * @noinspection PhpRedundantOptionalArgumentInspection
 * @noinspection PhpMissingReturnTypeInspection
 * @noinspection PhpUndefinedClassInspection
 */

namespace SV\StandardLib\Finder;

use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\FinderExpression;
use XF\Mvc\Entity\Structure;
use function implode, count;

/**
 * @method int getEarlyJoinThreshold(int $offset = null, int $limit = null, array $options = [])
 * @method string columnSqlName(string $column, bool $markFundamental = true)
 * @method void whereImpossible()
 *
 * @property int aliasCounter
 * @property Finder parentFinder
 *
 * @property array order
 * @property array defaultOrder
 * @property array joins
 * @property int limit
 * @property int offset
 *
 * @property \XF\Db\AbstractAdapter $db
 * @property Structure $structure
 */
trait EarlyJoinFinderTrait
{
    //abstract protected function getEarlyJoinThreshold(int $offset = null, int $limit = null, array $options = []): int

    /**
     * @param array $options
     *
     * @return string
     */
    public function getQuery(array $options = [])
    {
        $skipEarlyJoin = $options['skipEarlyJoin'] ?? false;
        $countOnly = $options['countOnly'] ?? false;

        if ($skipEarlyJoin || $countOnly)
        {
            return parent::getQuery($options);
        }

        $offset = $options['offset'] ?? null;
        if ($offset === null)
        {
            $offset = $this->offset;
        }

        // offset is computed as page*page-size, which can be user-controlled which can make it appear as a float
        // Do not trigger a possible type error because of the url; /forums/1/page-9223372036854775807
        // Support 32bit builds where floats only have 23 bits of precision which is less than PHP_INT_MAX
        if (\is_float($offset) && $offset >= (1 << (\PHP_INT_SIZE >= 8 ? 52 : 23)))
        {
            $this->whereImpossible();

            return parent::getQuery($options);
        }

        $limit = $options['limit'] ?? null;
        if ($limit === null)
        {
            $limit = $this->limit;
        }

        // sanity check on types
        $offset = (int)$offset;
        $limit = (int)$limit;

        $threshold = \is_callable([$this, 'getEarlyJoinThreshold']) ? $this->getEarlyJoinThreshold($offset, $limit, $options) : -1;

        if ($this->parentFinder ||
            $threshold < 0 ||
            !$limit ||
            $threshold && (($offset / $limit) < $threshold) )
        {
            return parent::getQuery($options);
        }

        $primaryKey = $this->structure->primaryKey;
        $primaryKeys = \is_array($primaryKey) ? $primaryKey : [$primaryKey];
        $subQueryOptions = $options;
        $subQueryOptions['fetchOnly'] = $primaryKeys;
        $subQueryOptions['skipEarlyJoin'] = true;

        $oldJoins = $this->joins;
        foreach($this->joins as $key => $join)
        {
            if (!$join['fundamental'])
            {
                unset($this->joins[$key]);
            }
        }

        // Use property_exists and not ?? as Finder does magic _get lookups which don't like it
        $allJoins = \property_exists($this, 'allJoins') ? $this->allJoins : null;
        try
        {
            // do this before the outer-joins
            $innerSql = $this->rewriteEarlyJoinQuery($subQueryOptions, $oldJoins);
        }
        finally
        {
            $this->joins = $oldJoins;
        }

        $defaultOrderSql = [];
        if (!$this->order && $this->defaultOrder)
        {
            foreach ($this->defaultOrder AS $defaultOrder)
            {
                $defaultOrderCol = $defaultOrder[0];

                if ($defaultOrderCol instanceof FinderExpression)
                {
                    /** @noinspection PhpParamsInspection */
                    $defaultOrderCol = $defaultOrderCol->renderSql($this, true);
                }
                else
                {
                    $defaultOrderCol = $this->columnSqlName($defaultOrderCol, true);
                }

                $defaultOrderSql[] = "$defaultOrderCol $defaultOrder[1]";
            }
        }

        $fetch = [];
        $coreTable = $this->structure->table;
        $joins = [];

        $fetchOnly = $options['fetchOnly'] ?? null;
        if (\is_array($fetchOnly))
        {
            if (!$fetchOnly)
            {
                throw new \InvalidArgumentException("Must specify one or more specific columns to fetch");
            }

            foreach ($fetchOnly AS $key => $fetchValue)
            {
                $fetchSql = $this->columnSqlName(\is_int($key) ? $fetchValue : $key);
                $fetch[] = $fetchSql . (!\is_int($key) ? " AS '$fetchValue'" : '');
            }
        }
        else
        {
            $fetch[] = '`' . $coreTable . '`.*';
        }

        $srcJoins = $allJoins ?? $this->joins;
        foreach ($srcJoins AS $join)
        {
            if ($join['exists'] && !$join['fetch'] && !$join['fundamental'])
            {
                // prune if this isn't actually required
                continue;
            }

            $joinType = $join['exists'] ? 'INNER' : 'LEFT';
            $indexHintArr = $join['indexHints'] ?? [];
            $joinHints = count($indexHintArr) === 0 ? '' : ' ' . implode(' ', $indexHintArr);
            $table = $join['table'];
            if ($join['hasTableExpr'] ?? false)
            {
                // This is a table expression from SqlJoinTrait, and has already been used as a filter in the original expression
                // but is not actually used in any other columns, so it can be discarded now
                if (!($join['reallyFundamental'] ?? false))
                {
                    continue;
                }
            }
            else
            {
                $table = '`'.$table.'`';
            }

            $joins[] = "$joinType JOIN $table AS `$join[alias]`$joinHints ON ($join[condition])";
            if ($join['fetch'] && !\is_array($fetchOnly))
            {
                $fetch[] = "`$join[alias]`.*";
            }
        }

        if ($this->order)
        {
            $orderBy = 'ORDER BY ' . \implode(', ', $this->order);
        }
        else if ($defaultOrderSql)
        {
            $orderBy = 'ORDER BY ' . \implode(', ', $defaultOrderSql);
        }
        else
        {
            $orderBy = '';
        }

        $innerTable = "earlyJoinQuery_". $this->aliasCounter++;
        $primaryJoin = [];
        foreach($primaryKeys as $primaryKey)
        {
            $primaryJoin[] = "(`$coreTable`.`$primaryKey` = `$innerTable`.`$primaryKey`)";
        }
        $primaryJoinSql = \implode(' AND ', $primaryJoin);

        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $q = $this->db->limit("
			SELECT " . \implode(', ', $fetch) . "
			FROM (
			$innerSql
			) as `$innerTable`
			JOIN `$coreTable` ON ($primaryJoinSql)
			" . \implode("\n", $joins) . "
			$orderBy
        ", $limit);

        return $q;
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function rewriteEarlyJoinQuery(array $subQueryOptions, array $oldJoins)
    {
        return parent::getQuery($subQueryOptions);
    }
}
