<?php

namespace SV\StandardLib\Behavior;

use XF\Mvc\Entity\Behavior;
use function is_callable;

class Cacheable extends Behavior
{
    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function getDefaultConfig(): array
    {
        return [
            'repository' => $this->entity->structure()->shortName,
            'rebuildCacheFuncName' => 'rebuildCache',
            'shouldRebuildCallable' => null,
        ];
    }

    public function postSave(): void
	{
        parent::postSave();

        if ($this->shouldRebuild())
        {
            $this->rebuildCache();
        }
	}

	public function postDelete(): void
	{
        parent::postDelete();

        $this->rebuildCache();
	}

    protected function shouldRebuild(): bool
    {
        $func = $this->config['shouldRebuildCallable'];
        if ($func !== null && is_callable($func))
        {
            return $func($this->entity);
        }

        return true;
    }

	public function rebuildCache(): void
	{
        $class = $this->config['repository'];
        $func = $this->config['rebuildCacheFuncName'];

        $repo = \SV\StandardLib\Helper::repository($class);
        $callable = [$repo, $func];
        if (is_callable($callable))
        {
            $callable();
        }
        else if (\XF::$developmentMode || \XF::$debugMode)
        {
            $e = new \LogicException('Expected '.$class.'::'.$func.' method to exist');
            if (\XF::$developmentMode)
            {
                throw $e;
            }
            else
            {
                \XF::logException($e);
            }
        }
	}
}
