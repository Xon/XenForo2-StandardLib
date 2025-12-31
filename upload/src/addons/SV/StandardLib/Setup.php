<?php

namespace SV\StandardLib;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Entity\Phrase;
use XF\Repository\Option as OptionRepo;

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
        /** @var Phrase[] $phrases */
        $phrases = Helper::finder(\XF\Finder\Phrase::class)
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

    public function upgrade2001230000Step1()//: void
    {
        if (\XF::$versionId >= 2030000)
        {
            return;
        }
        // XF2.1/XF2.2 compat
        $this->alterTable('xf_style_property', function (Alter $table) {
            $this->addOrChangeColumn($table, 'has_variations', 'tinyint')->setDefault(0)->after('value_parameters');
        });
    }

    public function postInstall(array &$stateChanges)// : void
    {
        parent::postInstall($stateChanges);
        Helper::repo()->rebuildAddOnVersionCache();
        Helper::repo()->clearShimCache();
        Helper::repository(OptionRepo::class)->rebuildOptionCache();
    }

    public function postUpgrade($previousVersion, array &$stateChanges)// : void
    {
        parent::postUpgrade($previousVersion, $stateChanges);
        Helper::repo()->rebuildAddOnVersionCache();
        Helper::repo()->clearShimCache();
        Helper::repository(OptionRepo::class)->rebuildOptionCache();
    }

    public function postRebuild()// : void
    {
        parent::postRebuild();
        Helper::repo()->rebuildAddOnVersionCache();
        Helper::repo()->clearShimCache();
        Helper::repository(OptionRepo::class)->rebuildOptionCache();
    }
}
