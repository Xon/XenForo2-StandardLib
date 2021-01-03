<?php
/**
 * @noinspection PhpRedundantOptionalArgumentInspection
 * @noinspection PhpMissingReturnTypeInspection
 * @noinspection PhpMissingParamTypeInspection
 * @noinspection PhpUndefinedClassInspection
 */

namespace SV\StandardLib\Finder;

use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\FinderExpression;
use XF\Mvc\Entity\Structure;

/**
 * Note; this avoids in-place updating of EarlyJoinFinderTrait as the Utils folder is distributed entirely with dependant add-ons
 *
 * @method int getEarlyJoinThreshold
 * @method string columnSqlName(string $column, bool $markFundamental = true)
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
    /**
     * @param array $options
     *
     * @return string
     */
    public function getQuery(array $options = [])
    {
        $options = array_merge([
            'limit' => null,
            'offset' => null,
            'countOnly' => false,
            'fetchOnly' => null
        ], $options);

        $countOnly = $options['countOnly'];
        $fetchOnly = $options['fetchOnly'];
        $primaryKey = $this->structure->primaryKey;

        if ($countOnly || is_array($primaryKey))
        {
            return parent::getQuery($options);
        }

        $limit = $options['limit'];
        if ($limit === null)
        {
            $limit = $this->limit;
        }

        $offset = $options['offset'];
        if ($offset === null)
        {
            $offset = $this->offset;
        }

        $threshold = $this->getEarlyJoinThreshold();

        if ($this->parentFinder ||
            $threshold < 0 ||
            !$limit ||
            $threshold && (($offset / $limit) < $threshold) )
        {
            return parent::getQuery($options);
        }

        $subQueryOptions = $options;
        $subQueryOptions['fetchOnly'] = [$primaryKey];

        $oldJoins = $this->joins;
        foreach($this->joins as $key => $join)
        {
            if (!$join['fundamental'])
            {
                unset($this->joins[$key]);
            }
        }
        try
        {
            // do this before the outer-joins
            $innerSql = parent::getQuery($subQueryOptions);
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

        if (is_array($fetchOnly))
        {
            if (!$fetchOnly)
            {
                throw new \InvalidArgumentException("Must specify one or more specific columns to fetch");
            }

            foreach ($fetchOnly AS $key => $fetchValue)
            {
                $fetchSql = $this->columnSqlName(is_int($key) ? $fetchValue : $key);
                $fetch[] = $fetchSql . (!is_int($key) ? " AS '$fetchValue'" : '');
            }
        }
        else
        {
            $fetch[] = '`' . $coreTable . '`.*';
        }

        foreach ($this->joins AS $join)
        {
            $joinType = $join['exists'] ? 'INNER' : 'LEFT';

            if (!empty($join['rawJoin']))
            {
                if (!empty($join['reallyFundamental']))
                {
                    $joins[] = "{$joinType} JOIN {$join['table']} AS `{$join['alias']}` ON ({$join['condition']})";
                }

                continue;
            }

            $joins[] = "$joinType JOIN `$join[table]` AS `$join[alias]` ON ($join[condition])";
            if ($join['fetch'] && !is_array($fetchOnly))
            {
                $fetch[] = "`$join[alias]`.*";
            }
        }

        if ($this->order)
        {
            $orderBy = 'ORDER BY ' . implode(', ', $this->order);
        }
        else if ($defaultOrderSql)
        {
            $orderBy = 'ORDER BY ' . implode(', ', $defaultOrderSql);
        }
        else
        {
            $orderBy = '';
        }

        $innerTable = "earlyJoinQuery_". $this->aliasCounter++;

        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $q = $this->db->limit("
			SELECT " . implode(', ', $fetch) . "
			FROM (
			$innerSql
			) as `$innerTable`
			JOIN `$coreTable` ON (`$coreTable`.`$primaryKey` = `$innerTable`.`$primaryKey`)
			" . implode("\n", $joins) . "
			$orderBy
        ", $limit);

        return $q;
    }

    /**
     * @param string $field
     * @param bool   $markJoinFundamental
     *
     * @return array
     */
    public function resolveFieldToTableAndColumn($field, $markJoinFundamental = true)
    {
        $parts = explode('.', $field);
        if (count($parts) === 2)
        {
            list($alias, $column) = $parts;
            if (!empty($this->joins[$alias]['rawJoin']) && isset($this->rawJoins[$alias][$column]))
            {
                if ($markJoinFundamental)
                {
                    $this->joins[$alias]['reallyFundamental'] = true;
                    $this->joins[$alias]['fundamental'] = true;
                }

                return [$alias, $column];
            }
        }

        return parent::resolveFieldToTableAndColumn($field, $markJoinFundamental);
    }
}