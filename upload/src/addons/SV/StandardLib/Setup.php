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

    protected function upgrade1110001Step1()// : void
    {
        $this->renameOption('svAdvancedBbCodeLogLessFunc', 'svLogLessFunc', true);
    }

    public function uninstallStep1()// : void
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

    public function uninstallStep2()// : void
    {
        Helper::repo()->resetAddOnVersionCache();
    }

    public function postInstall(array &$stateChanges)// : void
    {
        parent::postInstall($stateChanges);
        Helper::repo()->rebuildAddOnVersionCache();
    }

    public function postUpgrade($previousVersion, array &$stateChanges)// : void
    {
        parent::postUpgrade($previousVersion, $stateChanges);
        Helper::repo()->rebuildAddOnVersionCache();
    }

    public function postRebuild()// : void
    {
        parent::postRebuild();
        Helper::repo()->rebuildAddOnVersionCache();
    }
}
