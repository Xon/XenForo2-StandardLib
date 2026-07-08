<?php

namespace SV\StandardLib\Job;

use SV\StandardLib\Helper;
use XF\Job\AbstractJob;
use XF\Job\JobResult;
use XF\Repository\ClassExtension as ClassExtensionRepository;

class RebuildExtensionCacheJob extends AbstractJob
{
    public static function enqueue(): void
    {
        \XF::app()->jobManager()->enqueueLater('', \XF::$time + 1, self::class, [], false);
    }

    /**
     * @param float|int $maxRunTime
     */
    public function run($maxRunTime): JobResult
    {
        Helper::repository(ClassExtensionRepository::class)->rebuildExtensionCache();

        return $this->complete();
    }

    public function getStatusMessage(): ?string
    {
        return '';
    }

    public function canCancel(): bool
    {
        return false;
    }

    public function canTriggerByChoice(): bool
    {
        return false;
    }
}