<?php

namespace SV\StandardLib\Finder;

use XF\Mvc\Entity\Structure;

/**
 * @property \XF\Mvc\Entity\Structure structure
 * @method \XF\Mvc\Entity\Finder with(string|string[] $name, bool $mustExist = false)
 */
trait ComplexJoinTrait
{
    /** @var int */
    public $complexJoinCounter = 0;
    /** @var null|Structure */
    public $originalStructure  = null;

    /**
     * @param array $condition - this is an XF Entity relationship condition
     * @param bool  $mustExist
     * @return string
     */
    public function complexJoin(array $condition, bool $mustExist = false) : string
    {
        $alias = 'complex_join_' . $this->complexJoinCounter++;

        if ($this->originalStructure === null)
        {
            // we don't want to mutate the original entity structure, so clone it. but only once
            $this->originalStructure = $this->structure;
            $this->structure = clone $this->structure;
        }
        $this->structure->relations[$alias] = $condition;

        $this->with($alias, $mustExist);

        return $alias;
    }
}