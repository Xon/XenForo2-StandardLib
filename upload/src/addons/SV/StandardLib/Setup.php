<?php

namespace SV\StandardLib;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;

class Setup extends AbstractSetup
{
    use InstallerHelper;
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function uninstallStep1()
    {
        /** @var \XF\Entity\Phrase[] $phrases */
        $phrases = \XF::finder('XF:Phrase')
                      ->where('language_id', '=', 0)
                      ->where('addon_id', '=', 'SV/StandardLib')
                      ->where('title', 'like', 'time.%')
                      ->fetch();
        foreach ($phrases as $phrase)
        {
            $phrase->addon_id = '';
            $phrase->saveIfChanged();
        }
    }

    protected function upgrade1110001Step1()
    {
        $this->renameOption('svAdvancedBbCodeLogLessFunc', 'svLogLessFunc', true);
    }
}
