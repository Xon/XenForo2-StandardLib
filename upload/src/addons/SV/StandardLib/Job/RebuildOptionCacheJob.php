<?php

namespace SV\StandardLib\Job;

use SV\StandardLib\Helper;
use XF\Job\AbstractJob;
use XF\Job\JobResult;
use XF\Repository\Option as OptionRepo;

class RebuildOptionCacheJob extends AbstractJob
{
    public static function enqueue(): void
    {
        \XF::app()->jobManager()->enqueue(self::class, [], false);
    }

    /**
     * @param float|int $maxRunTime
     * @return JobResult
     */
    public function run($maxRunTime): JobResult
    {
        Helper::repository(OptionRepo::class)->rebuildOptionCache();

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