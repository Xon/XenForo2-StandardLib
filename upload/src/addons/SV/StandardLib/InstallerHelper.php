<?php

namespace SV\StandardLib;

use XF\Db\Schema\AbstractDdl;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Column as DbColumnSchema;
use XF\Db\Schema\Create;

/**
 * @property \XF\AddOn\AddOn addOn
 *
 * @method \XF\Db\AbstractAdapter db()
 * @method \XF\Db\SchemaManager schemaManager()
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
        $this->checkSoftRequires($errors, $warnings);
        $this->isCliRecommended($warnings);
    }

    /**
     * @param string $addonId
     * @param int    $minVersion
     * @return bool|int
     */
    protected function addonExists(string $addonId, int $minVersion = 0)
    {
        $addOns = \XF::app()->container('addon.cache');
        if (empty($addOns[$addonId]))
        {
            return false;
        }
        else if ($minVersion && ($addOns[$addonId] < $minVersion))
        {
            return false;
        }

        return $addOns[$addonId];
    }

    /**
     * @param string $title
     * @param string $value
     * @param bool   $deOwn
     * @throws \XF\PrintableException
     */
    protected function addDefaultPhrase(string $title, string $value, bool $deOwn = true)
    {
        /** @var \XF\Entity\Phrase $phrase */
        $phrase = \XF::app()->finder('XF:Phrase')
                     ->where('title', '=', $title)
                     ->where('language_id', '=', 0)
                     ->fetchOne();
        if (!$phrase)
        {
            $phrase = \XF::em()->create('XF:Phrase');
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
    protected function applyGlobalPermissionByGroup(string $groupId, string $permissionId, array $userGroups)
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
    public function applyGlobalPermissionForGroup(string $applyGroupId, string $applyPermissionId, int $userGroupId)
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
    public function applyGlobalPermissionIntForGroup(string $applyGroupId, string $applyPermissionId, int $applyValue, int $userGroupId)
    {
        $this->db()->query(
            "INSERT IGNORE INTO xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int) VALUES
                (?, 0, ?, ?, 'use_int', ?)
            ", [$userGroupId, $applyGroupId, $applyPermissionId, $applyValue]
        );
    }

    protected function applyRegistrationDefaults(array $newRegistrationDefaults)
    {
        /** @var \XF\Entity\Option $option */
        $option = \XF::app()->finder('XF:Option')
                            ->where('option_id', '=', 'registrationDefaults')
                            ->fetchOne();

        if (!$option)
        {
            // Option: Mr. XenForo I don't feel so good
            throw new \LogicException("XenForo installation is damaged. Expected option 'registrationDefaults' to exist.");
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
    protected function renamePermission(string $oldGroupId, string $oldPermissionId, string $newGroupId, string $newPermissionId)
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

    protected function renameOption(string $old, string $new, bool $takeOwnership = false)
    {
        /** @var \XF\Entity\Option $optionOld */
        $optionOld = \XF::finder('XF:Option')->whereId($old)->fetchOne();
        /** @var \XF\Entity\Option $optionNew */
        $optionNew = \XF::finder('XF:Option')->whereId($new)->fetchOne();
        if ($optionOld && !$optionNew)
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
        else if ($takeOwnership && $optionOld && $optionNew)
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

    protected function renamePhrases(array $map, bool $deOwn = false)
    {
        $db = $this->db();

        foreach ($map AS $from => $to)
        {
            $mySqlRegex = '^' . str_replace('*', '[a-zA-Z0-9_]+', $from) . '$';
            $phpRegex = '/^' . str_replace('*', '([a-zA-Z0-9_]+)', $from) . '$/';
            $replace = str_replace('*', '$1', $to);

            $results = $db->fetchPairs("
				SELECT phrase_id, title
				FROM xf_phrase
				WHERE title RLIKE ?
					AND addon_id = ''
			", $mySqlRegex);

            if ($results)
            {
                /** @var \XF\Entity\Phrase[] $phrases */
                $phrases = \XF::em()->findByIds('XF:Phrase', array_keys($results));
                foreach ($results AS $phraseId => $oldTitle)
                {
                    if (isset($phrases[$phraseId]))
                    {
                        $newTitle = preg_replace($phpRegex, $replace, $oldTitle);

                        $phrase = $phrases[$phraseId];
                        $phrase->title = $newTitle;
                        $phrase->global_cache = false;
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
                }
            }
        }
    }

    /**
     * @param string[] $map
     * @throws \XF\PrintableException
     */
    protected function deletePhrases(array $map)
    {
        $titles = [];
        foreach($map as $titlePattern)
        {
            $titles[] = ['title', 'LIKE', $titlePattern];
        }

        /** @var \XF\Finder\Phrase $phraseFinder */
        $phraseFinder = \XF::finder('XF:Phrase');
        /** @var \XF\Entity\Phrase[] $phrases */
        $phrases = $phraseFinder
            ->where('language_id', 0)
            ->whereOr($titles)
            ->fetch();

        foreach ($phrases as $phrase)
        {
            $phrase->delete();
        }
    }

    protected function renameStyleProperty(string $old, string $new)
    {
        /** @var \XF\Entity\StyleProperty $optionOld */
        $optionOld = \XF::finder('XF:StyleProperty')->where('property_name', '=', $old)->fetchOne();
        $optionNew = \XF::finder('XF:StyleProperty')->where('property_name', '=', $new)->fetchOne();
        if ($optionOld && !$optionNew)
        {
            if ($optionOld->hasBehavior('XF:DevOutputWritable'))
            {
                $optionOld->getBehavior('XF:DevOutputWritable')->setOption('write_dev_output', false);
            }
            $optionOld->property_name = $new;
            $optionOld->saveIfChanged();
        }
    }

    protected function migrateTable(string $old, string $new, bool $dropOldIfNewExists = false)
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
     * @return DbColumnSchema
     */
    protected function addOrChangeColumn(AbstractDdl $table, string $name, string $type = null, $length = null) : DbColumnSchema
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

            return $table->addColumn($name, $type, $length);
        }

        throw new \LogicException('Unknown schema DDL type ' . \get_class($table));
    }

    protected function isCliRecommendedCheck(int $minAddonVersion, int $maxThreads, int $maxPosts, int $maxUsers) : bool
    {
        $totals = \XF::app()->db()->fetchOne("
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
        if (\XF::app() instanceof \XF\Admin\App && $this->isCliRecommendedCheck($minAddonVersion, $maxThreads, $maxPosts, $maxUsers))
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

            $warnings[] = new \XF\PreEscaped($html);

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
    protected function checkSoftRequires(array &$errors, array &$warnings)
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
            list ($version, $product) = $requirement;
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
                $versionValid = ($version === '*' || ($enabled && $addOns[$productKey] >= $version));
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
                    $errors[] = new \XF\PreEscaped(sprintf(
                        '%s requires %s.%s',
                        $json['title'],
                        $product,
                        $reason
                    ));
                }
                else
                {
                    $warnings[] = new \XF\PreEscaped(sprintf(
                        '%s recommends %s.%s',
                        $json['title'],
                        $product,
                        $reason
                    ));
                }
            }
        }
    }

    protected function checkElasticSearchOptimizableState()
    {
        $es = \XFES\Listener::getElasticsearchApi();

        /** @var \XFES\Service\Configurer $configurer */
        $configurer = \XF::service('XFES:Configurer', $es);
        $version = null;
        $testError = null;
        $stats = null;
        $isOptimizable = false;
        $analyzerConfig = null;

        if ($configurer->hasActiveConfig())
        {
            try
            {
                $version = $es->version();

                if ($version && $es->test($testError))
                {
                    if ($es->indexExists())
                    {
                        /** @var \XFES\Service\Optimizer $optimizer */
                        $optimizer = \XF::service('XFES:Optimizer', $es);
                        $isOptimizable = $optimizer->isOptimizable();
                    }
                    else
                    {
                        $isOptimizable = true;
                    }
                }
            }
            catch (\XFES\Elasticsearch\Exception $e) {}
        }

        if ($isOptimizable)
        {
            \XF::logError('Elasticsearch index must be rebuilt to include custom mappings.', true);
        }
    }
}
