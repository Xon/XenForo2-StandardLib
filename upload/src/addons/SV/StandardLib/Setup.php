<?php
/**
 * @noinspection PhpMultipleClassDeclarationsInspection
 */

namespace SV\StandardLib;

use SV\StandardLib\Job\RebuildOptionCacheJob;
use SV\StandardLib\XF\AddOn\DataType\StyleProperty as ExtendedStylePropertyDataType;
use SV\StandardLib\XF\DevelopmentOutput\StyleProperty as ExtendedDevOutputStyleProperty;
use SV\StandardLib\XF\Entity\Option as ExtendedOptionEntity;
use SV\StandardLib\XF\Entity\StyleProperty as ExtendedStylePropertyEntity;
use SV\StandardLib\XF\Template\TemplaterXF21Patch;
use XF\AddOn\AbstractSetup;
use XF\AddOn\DataType\StyleProperty as StylePropertyDataType;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\DevelopmentOutput\StyleProperty as DevOutputStyleProperty;
use XF\Entity\ClassExtension as ClassExtensionEntity;
use XF\Entity\Option as OptionEntity;
use XF\Entity\Phrase as PhraseEntity;
use XF\Entity\StyleProperty as StylePropertyEntity;
use XF\Finder\Phrase as PhraseFinder;
use XF\Repository\Option as OptionRepo;
use XF\Template\Templater;
use XF\Util\File as FileUtil;

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
        /** @var PhraseEntity[] $phrases */
        $phrases = Helper::finder(PhraseFinder::class)
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

    public function uninstallStep3()// : void
    {
        FileUtil::deleteAbstractedDirectory('code-cache://svShim');
    }

    public function upgrade2001230000Step1()//: void
    {
        $this->syncClassExtensions();

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
        $this->doRebuilds();
    }

    public function postUpgrade($previousVersion, array &$stateChanges)// : void
    {
        parent::postUpgrade($previousVersion, $stateChanges);
        $this->doRebuilds();
    }

    public function postRebuild()// : void
    {
        parent::postRebuild();
        $this->doRebuilds();
    }

    public function doRebuilds()// : void
    {
        Helper::repo()->rebuildAddOnVersionCache();
        Helper::repo()->clearShimCache();
        $this->syncClassExtensions();

        if (\XF::$versionId < 2030871)
        {
            // Execute option rebuilt in a background task as that runs after this add-on has finished being a zombie with is_processing logic
            RebuildOptionCacheJob::enqueue();
        }
    }

    public function syncClassExtensions()//: void
    {
        // only enable for pre XF2.3.8
        $this->patchClassExtension(OptionEntity::class, ExtendedOptionEntity::class, \XF::$versionId < 2030870);

        $preXF23 = \XF::$versionId < 2030000;
        $this->patchClassExtension(StylePropertyDataType::class, ExtendedStylePropertyDataType::class, $preXF23);
        $this->patchClassExtension(StylePropertyEntity::class, ExtendedStylePropertyEntity::class, $preXF23);
        $this->patchClassExtension(DevOutputStyleProperty::class, ExtendedDevOutputStyleProperty::class, $preXF23);

        $this->patchClassExtension(Templater::class, TemplaterXF21Patch::class, \XF::$versionId < 2020000);
    }

    protected function patchClassExtension(string $fromClass, string $toClass, bool $value)//: void
    {
        $classExtension = Helper::findOne(ClassExtensionEntity::class, [
            'from_class' => $fromClass,
            'to_class'   => $toClass,
        ]);
        if ($classExtension === null)
        {
            return;
        }

        $classExtension->active = $value;
        $classExtension->saveIfChanged();
    }
}
