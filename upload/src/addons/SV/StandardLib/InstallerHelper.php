<?php

namespace SV\StandardLib;

use LogicException;
use SV\StandardLib\Db\AlterColumnUnwrapper;
use SV\StandardLib\Db\AlterTableUnwrapper;
use XF\AddOn\AddOn;
use XF\Admin\App;
use XF\Db\AbstractAdapter;
use XF\Db\Schema\AbstractDdl;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Column as DbColumnSchema;
use XF\Db\Schema\Create;
use XF\Db\SchemaManager;
use XF\Entity\Option;
use XF\Entity\Phrase as PhraseEntity;
use XF\Entity\StyleProperty;
use XF\Finder\Phrase as PhraseFinder;
use XF\PreEscaped;
use XF\PrintableException;
use XFES\Elasticsearch\Exception;
use XFES\Listener;
use XFES\Service\Configurer;
use XFES\Service\Optimizer;
use function array_keys;
use function count;
use function explode;
use function file_exists;
use function in_array;
use function is_array;
use function phpversion;
use function preg_replace;
use function str_replace;
use function strlen;
use function strpos;
use function strtolower;
use function unserialize;
use function version_compare;
use function sprintf;

/**
 * @version 1.10.0
 *
 * @property AddOn addOn
 *
 * @method AbstractAdapter db()
 * @method SchemaManager schemaManager()
 */
trait InstallerHelper
{
    /**
     * Default checkRequirements which triggers various actions,
     *
     * @param array $errors
     * @param array $warnings
     */
    public function checkRequirements(&$errors = [], &$warnings = [])
    {
        $this->checkComposer($errors);
        $this->checkSoftRequires($errors, $warnings);
        $this->isCliRecommended($warnings);
    }

    protected function addonExists(string $addonId, int $minVersion = 0): bool
    {
        return Helper::isAddOnActive($addonId, $minVersion ?: null);
    }

    /**
     * @param string $title
     * @param string $value
     * @param bool   $deOwn
     * @throws PrintableException
     */
    protected function addDefaultPhrase(string $title, string $value, bool $deOwn = true): void
    {
        $phrase = Helper::finder(PhraseFinder::class)
                        ->where('title', '=', $title)
                        ->where('language_id', '=', 0)
                        ->fetchOne();
        if ($phrase === null)
        {
            $phrase = Helper::createEntity(PhraseEntity::class);
            $phrase->language_id = 0;
            $phrase->title = $title;
            $phrase->phrase_text = $value;
            $phrase->global_cache = false;
            $phrase->addon_id = '';
            $phrase->save(false);
        }
        else if ($deOwn && $phrase->addon_id === $this->addOn->getAddOnId())
        {
            $phrase->addon_id = '';
            $phrase->save(false);
        }
    }

    /**
     * @param string $groupId
     * @param string $permissionId
     * @param int[]  $userGroups
     * @throws \XF\Db\Exception
     */
    protected function applyGlobalPermissionByGroup(string $groupId, string $permissionId, array $userGroups): void
    {
        foreach($userGroups as $userGroupId)
        {
            $this->applyGlobalPermissionForGroup($groupId, $permissionId, $userGroupId);
        }
    }

    /**
     * @param string $applyGroupId
     * @param string $applyPermissionId
     * @param int    $userGroupId
     * @throws \XF\Db\Exception
     */
    public function applyGlobalPermissionForGroup(string $applyGroupId, string $applyPermissionId, int $userGroupId): void
    {
        $this->db()->query(
            "INSERT IGNORE INTO xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int) VALUES
                (?, 0, ?, ?, 'allow', '0')
            ", [$userGroupId, $applyGroupId, $applyPermissionId]
        );
    }

    /**
     * @param string $applyGroupId
     * @param string $applyPermissionId
     * @param int $applyValue
     * @param int $userGroupId
     *
     * @throws \XF\Db\Exception
     */
    public function applyGlobalPermissionIntForGroup(string $applyGroupId, string $applyPermissionId, int $applyValue, int $userGroupId): void
    {
        $this->db()->query(
            "INSERT IGNORE INTO xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int) VALUES
                (?, 0, ?, ?, 'use_int', ?)
            ", [$userGroupId, $applyGroupId, $applyPermissionId, $applyValue]
        );
    }

    protected function applyRegistrationDefaults(array $newRegistrationDefaults): void
    {
        $option = Helper::find(Option::class, 'registrationDefaults');
        if ($option === null)
        {
            throw new LogicException("XenForo installation is damaged. Expected option 'registrationDefaults' to exist.");
        }

        $registrationDefaults = $option->option_value;
        foreach ($newRegistrationDefaults AS $optionName => $optionDefault)
        {
            if (!isset($registrationDefaults[$optionName]))
            {
                $registrationDefaults[$optionName] = $optionDefault;
            }
        }

        $option->option_value = $registrationDefaults;
        $option->saveIfChanged();
    }

    /**
     * @param string $oldGroupId
     * @param string $oldPermissionId
     * @param string $newGroupId
     * @param string $newPermissionId
     *
     * @throws \XF\Db\Exception
     */
    protected function renamePermission(string $oldGroupId, string $oldPermissionId, string $newGroupId, string $newPermissionId): void
    {
        $this->db()->query('
            UPDATE IGNORE xf_permission_entry
            SET permission_group_id = ?, permission_id = ?
            WHERE permission_group_id = ? AND permission_id = ?
        ', [$newGroupId, $newPermissionId, $oldGroupId, $oldPermissionId]);

        $this->db()->query('
            UPDATE IGNORE xf_permission_entry_content
            SET permission_group_id = ?, permission_id = ?
            WHERE permission_group_id = ? AND permission_id = ?
        ', [$newGroupId, $newPermissionId, $oldGroupId, $oldPermissionId]);

        $this->db()->query('
            DELETE FROM xf_permission_entry
            WHERE permission_group_id = ? AND permission_id = ?
        ', [$oldGroupId, $oldPermissionId]);

        $this->db()->query('
            DELETE FROM xf_permission_entry_content
            WHERE permission_group_id = ? AND permission_id = ?
        ', [$oldGroupId, $oldPermissionId]);
    }

    protected function renameOption(string $old, string $new, bool $takeOwnership = false): void
    {
        $optionOld = Helper::find(Option::class, $old);
        $optionNew = Helper::find(Option::class, $new);
        if ($optionOld !== null && $optionNew === null)
        {
            $optionOld->option_id = $new;
            if ($takeOwnership)
            {
                $optionOld->addon_id = $this->addOn->getAddOnId();
            }
            if ($optionOld->hasBehavior('XF:DevOutputWritable'))
            {
                $optionOld->getBehavior('XF:DevOutputWritable')->setOption('write_dev_output', false);
            }
            $optionOld->saveIfChanged();
        }
        else if ($takeOwnership && $optionOld !== null && $optionNew !== null)
        {
            $optionNew->option_value = $optionOld->option_value;
            $optionNew->addon_id = $this->addOn->getAddOnId();
            if ($optionNew->hasBehavior('XF:DevOutputWritable'))
            {
                $optionNew->getBehavior('XF:DevOutputWritable')->setOption('write_dev_output', false);
            }
            $optionNew->save();
            $optionOld->delete();
        }
    }

    protected function renamePhrases(array $map, bool $deOwn = false, bool $replace = true): void
    {
        $db = $this->db();

        foreach ($map AS $from => $to)
        {
            $mySqlRegex = '^' . str_replace('*', '[a-zA-Z0-9_]+', $from) . '$';
            $phpRegex = '/^' . str_replace('*', '([a-zA-Z0-9_]+)', $from) . '$/';
            $replacePhrase = str_replace('*', '$1', $to);

            $results = $db->fetchPairs("
				SELECT phrase_id, title
				FROM xf_phrase
				WHERE CONVERT(title USING utf8mb4) RLIKE ?
					AND addon_id = ''
			", $mySqlRegex);

            if ($results)
            {
                /** @var PhraseEntity[] $phrases */
                $phrases = Helper::findByIds(PhraseEntity::class, array_keys($results));
                foreach ($results AS $phraseId => $oldTitle)
                {
                    if (isset($phrases[$phraseId]))
                    {
                        $newTitle = preg_replace($phpRegex, $replacePhrase, $oldTitle);
                        $phrase = $phrases[$phraseId];

                        $db->beginTransaction();

                        /** @var PhraseEntity $newPhrase */
                        $newPhrase = $replace
                            ? Helper::finder(PhraseFinder::class)
                                    ->where('title', '=', $newTitle)
                                    ->fetchOne()
                            : null;

                        if ($newPhrase)
                        {
                            // already exists, replace the value and delete
                            $newPhrase->set('title', $phrase->phrase_text, ['forceSet' => true]);
                            $newPhrase->set('global_cache', false, ['forceSet' => true]);
                            if ($deOwn)
                            {
                                $newPhrase->addon_id = '';
                            }
                            if ($newPhrase->hasBehavior('XF:DevOutputWritable'))
                            {
                                $newPhrase->getBehavior('XF:DevOutputWritable')->setOption('write_dev_output', false);
                            }
                            $newPhrase->save(false);
                            $phrase->delete(false);
                        }
                        else
                        {
                            $phrase->set('title', $newTitle, ['forceSet' => true]);
                            $phrase->set('global_cache', false, ['forceSet' => true]);
                            if ($deOwn)
                            {
                                $phrase->addon_id = '';
                            }
                            if ($phrase->hasBehavior('XF:DevOutputWritable'))
                            {
                                $phrase->getBehavior('XF:DevOutputWritable')->setOption('write_dev_output', false);
                            }
                            $phrase->save(false);
                        }

                        $db->commit();
                    }
                }
            }
        }
    }

    /**
     * @param string[] $map
     * @throws PrintableException
     */
    protected function deletePhrases(array $map): void
    {
        $titles = [];
        foreach($map as $titlePattern)
        {
            $titles[] = ['title', 'LIKE', $titlePattern];
        }

        $phraseFinder = Helper::finder(PhraseFinder::class);
        /** @var PhraseEntity[] $phrases */
        $phrases = $phraseFinder
            ->where('language_id', 0)
            ->whereOr($titles)
            ->fetch();

        foreach ($phrases as $phrase)
        {
            $phrase->delete();
        }
    }

    protected function renameStyleProperty(string $old, string $new): void
    {
        /** @var StyleProperty $optionOld */
        $optionOld = Helper::finder(\XF\Finder\StyleProperty::class)->where('property_name', '=', $old)->fetchOne();
        $optionNew = Helper::finder(\XF\Finder\StyleProperty::class)->where('property_name', '=', $new)->fetchOne();
        if ($optionOld !== null && $optionNew === null)
        {
            if ($optionOld->hasBehavior('XF:DevOutputWritable'))
            {
                $optionOld->getBehavior('XF:DevOutputWritable')->setOption('write_dev_output', false);
            }
            $optionOld->property_name = $new;
            $optionOld->saveIfChanged();
        }
    }

    protected function migrateTable(string $old, string $new, bool $dropOldIfNewExists = false): void
    {
        $sm = $this->schemaManager();
        if ($sm->tableExists($old))
        {
            if (!$sm->tableExists($new))
            {
                $sm->renameTable($old, $new);
            }
            else if ($dropOldIfNewExists)
            {
                $sm->dropTable($old);
            }
        }
    }

    /**
     * @param AbstractDdl       $table
     * @param string            $name
     * @param string|null       $type
     * @param int|string[]|null $length
     * @param string[]          $oldNames
     * @return DbColumnSchema
     */
    protected function addOrChangeColumn(AbstractDdl $table, string $name, ?string $type = null, $length = null, array $oldNames = []) : DbColumnSchema
    {
        if ($table instanceof Create)
        {
            $table->checkExists(true);

            return $table->addColumn($name, $type, $length);
        }
        else if ($table instanceof Alter)
        {
            if ($table->getColumnDefinition($name))
            {
                return $table->changeColumn($name, $type, $length);
            }

            // check for pending renames
            $hasOldNames = (bool)$oldNames;
            $changeColumns = AlterTableUnwrapper::getChangeColumns($table);
            foreach($changeColumns as $changeColumn)
            {

                $newName = AlterColumnUnwrapper::getRename($changeColumn);
                if ($newName)
                {
                    if ($newName === $name)
                    {
                        return $changeColumn->type($type)->length($length);
                    }
                }
                else if ($hasOldNames)
                {
                    $colName = $changeColumn->getName();
                    if (in_array($colName, $oldNames, true))
                    {
                        return $changeColumn->renameTo($name);
                    }
                    else if ($colName === $name)
                    {
                        return $changeColumn;
                    }
                }
            }
            // check for renames to be done
            foreach($oldNames as $oldName)
            {
                if ($table->getColumnDefinition($oldName))
                {
                    return $table->changeColumn($oldName, $type, $length)
                                 ->renameTo($name);
                }
            }

            return $table->addColumn($name, $type, $length);
        }

        throw new LogicException('Unknown schema DDL type ' . \get_class($table));
    }

    /**
     * @since 1.10.0
     *
     * @param Alter $alter
     * @param Alter $table
     *
     * @return void
     */
    protected function reverseTableAlter(Alter $alter, Alter $table): void
    {
        $addIndexes = AlterTableUnwrapper::getAddIndexes($alter);
        $addColumns = AlterTableUnwrapper::getAddColumns($alter);
        $changeColumns = AlterTableUnwrapper::getChangeColumns($alter);

        foreach ($addIndexes AS $addIndex)
        {
            $table->dropIndexes($addIndex->getIndexName());
        }

        foreach ($addColumns AS $addColumn)
        {
            $table->dropColumns($addColumn->getName());
        }

        foreach ($changeColumns AS $changeColumn)
        {
            if (!$changeColumn->isRename())
            {
                continue;
            }

            $newName = AlterColumnUnwrapper::getRename($changeColumn);
            if (!$alter->getColumnDefinition($newName))
            {
                continue;
            }

            $table->renameColumn($newName, $changeColumn->getName());
        }
    }

    /**
     * @since 1.10.0
     *
     * @param array<string, callable|callable-string> $tables
     *
     * @return array<string, Alter>
     */
    protected function getReversedAlterTables(array $tables) : array
    {
        $sm = $this->schemaManager();

        foreach ($tables AS $tableName => $toApply)
        {
            $tables[$tableName] = function (Alter $table) use ($sm, $tableName, $toApply)
            {
                $alter = $sm->newAlter($tableName);
                $toApply($alter);

                $this->reverseTableAlter($alter, $table);
            };
        }

        return $tables;
    }

    protected function checkComposer(array &$errors): void
    {
        $json = $this->addOn->getJson();
        $composerPath = $json['composer_autoload'] ?? '';
        if (strlen($composerPath) === 0)
        {
            $vendorDirectory = $this->addOn->getAddOnDirectory() . \XF::$DS . $composerPath;
            if (!file_exists($vendorDirectory))
            {
                $errors[] = 'Composer vendor folder does not exist';
            }
        }
    }

    protected function isCliRecommendedCheck(int $minAddonVersion, int $maxThreads, int $maxPosts, int $maxUsers) : bool
    {
        $totals = \XF::db()->fetchOne("
			SELECT data_value
			FROM xf_data_registry
			WHERE data_key IN ('boardTotals', 'forumStatistics')
			LIMIT 1
		");
        if (!$totals)
        {
            return false;
        }

        $totals = @unserialize($totals);
        if (!$totals)
        {
            return false;
        }

        if ($maxPosts && !empty($totals['messages']) && $totals['messages'] >= $maxPosts)
        {
            return true;
        }

        if ($maxUsers && !empty($totals['users']) && $totals['users'] >= $maxUsers)
        {
            return true;
        }

        if ($maxThreads && !empty($totals['threads']) && $totals['threads'] >= $maxThreads)
        {
            return true;
        }

        if ($minAddonVersion)
        {
            $existing = $this->addOn->getInstalledAddOn();
            if ($existing === null || $existing->version_id < $minAddonVersion)
            {
                return true;
            }
        }

        return false;
    }

    public function isCliRecommended(array &$warnings, int $minAddonVersion = 0, int $maxThreads = 0, int $maxPosts = 500000, int $maxUsers = 50000) : bool
    {
        if (\XF::app() instanceof App && $this->isCliRecommendedCheck($minAddonVersion, $maxThreads, $maxPosts, $maxUsers))
        {
            $existing = $this->addOn->getInstalledAddOn();
            if ($existing)
            {
                $html = 'Your XenForo installation is large. You may wish to upgrade via the command line.<br/>
			Simply run this command from within the root XenForo directory and follow the on-screen instructions:<br/>
			<pre style="margin: 1em 2em">php cmd.php xf-addon:upgrade ' . \XF::escapeString($this->addOn->getAddOnId()) . '</pre>
			You can continue with the browser-based upgrade, but large queries may cause browser timeouts<br/>
			that will force you to reload the page.';
            }
            else
            {
                $html = 'Your XenForo installation is large. You may wish to install via the command line.<br/>
			Simply run this command from within the root XenForo directory and follow the on-screen instructions:<br/>
			<pre style="margin: 1em 2em">php cmd.php xf-addon:install ' . \XF::escapeString($this->addOn->getAddOnId()) . '</pre>
			You can continue with the browser-based install, but large queries may cause browser timeouts<br/>
			that will force you to reload the page.';
            }

            $warnings[] = new PreEscaped($html);

            return true;
        }

        return false;
    }

    /**
     * Supports a 'require-soft' section with near identical structure to 'require'
     *
     * An example;
    "require-soft" :{
    "SV/Threadmarks": [
    2000370,
    "Threadmarks v2.0.3+",
    false,
    "Please provide feedback if you are unable to upgrade."
    ]
    },
     * The 3rd array argument has 3 supported values, null/true/false
     *   null/no exists - this is advisory for "Extra Cli Tools" when determining bulk install order, and isn't actually checked
     *   false - if the item exists and is below the minimum version, log as a warning
     *   true - if the item exists and is below the minimum version, log as an error
     *
     * The 4th array argument is extra help text intended to offer a more detailed explanation why
     * this version is required. For instance, if you're checking for PHP 7.2.0+, you can explain
     * that you plan to bump the minimum version going forward.
     *
     * @param string[] $errors
     * @param string[] $warnings
     */
    protected function checkSoftRequires(array &$errors, array &$warnings): void
    {
        $json = $this->addOn->getJson();
        if (empty($json['require-soft']))
        {
            return;
        }

        $addOns = \XF::app()->container('addon.cache');
        foreach ((array)$json['require-soft'] as $productKey => $requirement)
        {
            if (!is_array($requirement))
            {
                continue;
            }
            [$version, $product] = $requirement;
            $errorType = count($requirement) >= 3 ? $requirement[2] : null;

            // advisory
            if ($errorType === null)
            {
                continue;
            }

            $enabled = false;
            $versionValid = false;

            if (strpos($productKey, 'php-ext') === 0)
            {
                $parts = explode('/', $productKey, 2);
                if (isset($parts[1]))
                {
                    $enabled = phpversion($parts[1]) !== false;
                    $versionValid = ($version === '*') || (version_compare(phpversion($parts[1]), $version, 'ge'));
                }
            }
            else if (strpos($productKey, 'php') === 0)
            {
                $enabled = true;
                $versionValid = version_compare(phpversion(), $version, 'ge');
            }
            else if (strpos($productKey, 'mysql') === 0)
            {
                $mySqlVersion = \XF::db()->getServerVersion();
                if ($mySqlVersion)
                {
                    $enabled = true;
                    $versionValid = version_compare(strtolower($mySqlVersion), $version, 'ge');
                }
            }
            else
            {
                $enabled = isset($addOns[$productKey]);
                $versionValid = Helper::isAddOnActive($productKey, $version);
            }

            if (!$enabled)
            {
                continue;
            }

            if (!$versionValid)
            {
                $reason = count($requirement) >= 4 ? (' ' . $requirement[3]) : '';

                if ($errorType)
                {
                    $errors[] = new PreEscaped(sprintf(
                        '%s requires %s.%s',
                        $json['title'],
                        $product,
                        $reason
                    ));
                }
                else
                {
                    $warnings[] = new PreEscaped(sprintf(
                        '%s recommends %s.%s',
                        $json['title'],
                        $product,
                        $reason
                    ));
                }
            }
        }
    }

    protected function checkElasticSearchOptimizableState(): void
    {
        if (!Helper::isAddOnActive('XFES')) {
            return;
        }

        $es = Listener::getElasticsearchApi();

        $configurer = Helper::service(Configurer::class, $es);
        $testError = null;
        $isOptimizable = false;

        if ($configurer->hasActiveConfig())
        {
            try
            {
                $version = $es->version();

                if ($version && $es->test($testError))
                {
                    if ($es->indexExists())
                    {
                        $optimizer = Helper::service(Optimizer::class, $es);
                        $isOptimizable = $optimizer->isOptimizable();
                    }
                    else
                    {
                        $isOptimizable = true;
                    }
                }
            }
            catch (Exception $e) {}
        }

        if ($isOptimizable)
        {
            \XF::logError('Elasticsearch index must be rebuilt to include custom mappings.', true);
        }
    }

    /**
     * Determine whether a permission is currently in use by another add-on.  Installers can use this to determine
     * whether a permission that was formerly associated with a different add-on should receive default settings or
     * should be left alone.
     *
     * For example, if SV/FooBar is split into two add-ons, SV/Foo and SV/Bar, the two new add-ons will need to avoid
     * overwriting permissions that have already been configured as part of SV/FooBar.
     *
     * @param string $permissionGroupId
     * @param string $permissionId
     * @return bool
     * @since 1.10.3
     */
    public function isPermissionInUse(string $permissionGroupId, string $permissionId): bool
    {
        return (bool)\XF::db()->fetchOne(
            '
                SELECT
                    EXISTS(SELECT * FROM xf_permission WHERE permission_group_id = ? AND permission_id = ?)
                    OR EXISTS(SELECT * FROM xf_permission_entry WHERE permission_group_id = ? AND permission_id = ?)
                    OR EXISTS(SELECT * FROM xf_permission_entry_content WHERE permission_group_id = ? AND permission_id = ?)
            ',
            [
                $permissionGroupId,
                $permissionId,
                $permissionGroupId,
                $permissionId,
                $permissionGroupId,
                $permissionId,
            ]
        );
    }
}
