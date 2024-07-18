# XenForo2-StandardLib

A number of helper utilities designed to ease add-on development

During use, Add the `requires` section to `addon.json` to document the dependency

```json
{
    "require": {
        "SV/StandardLib": [2001190000,"Standard Library by Xon v1.19.0+"],
        "XF": ["2.2.0", "XenForo 2.2.0+"],
        "php": ["7.2.0", "PHP 7.2.0+"]
    }
}
```
Note; `SV/StandardLib` should use a `version_id` and not a `version_string` to support sites which do not have the add-on installed yet.

## Improve add-on requirement version checks

Instead of matching on version_id, the `addon.json`'s `require` section can match on addon version strings.
Uses [version_compare](https://www.php.net/manual/en/function.version-compare.php) under the hood after some very [i]basic[/i] standardization.

php version strings support dotted versions, '1.2.3' and also each part may also support special character strings:
> any string not found in this list < dev < alpha = a < beta = b < RC | Release Candidate = rc < # < patch level | pl = p.

## Finder extension traits

### RlikeOperatorTrait
`RLIKE` operator support.

### EarlyJoinFinderTrait
MySQL implements 'early row lookup' which results in the large select statement pulling in more data than is required.
This trait allows migrating this with some minor configuration.

See [Optimized List Queries add-on](https://github.com/Xon/XenForo2-OptimizedListQueries) for examples.

### ComplexJoinTrait
Inject an entity relations at query time. This is useful to work-around XenForo lacking reverse relationships on handler-like entities

See https://github.com/Xon/XenForo2-OptimizedListQueries/blob/f3f0dbbcd58273314e92aca8a4b34d3ecc062815/upload/src/addons/SV/OptimizedListQueries/XF/Repository/Node.php#L12-L68 for an example.
Be aware that MySQL can suffer degraded performance for large number of joins, so this might not always be the best choice.

### SqlJoinTrait
Inject arbitrary SQL at query time, as join vs subquery can have massive performance differences despite being logically identical

## View template modifications

Allows viewing template modifications which are applying to a template, including generated php source code

## `patch_route_build_callback` code event

Called when a `XF\Mvc\Router` object is constructed to manipulate routes, as XF doesn't support chaining `build_callbacks`.

Usage example (using a `Pub` event hint):
```php
public static function publicLinkBuilder(\SV\StandardLib\Repository\LinkBuilder $linkBuilder, \XF\Mvc\Router $router): void
{
    $callable = function (string &$prefix, array &$route, string &$action, &$data, array &$params, \XF\Mvc\Router $router, bool &$suppressDefaultCallback) {
       if (isset($data['foo']) {
            return 'https://example.org';
       } elseif (isset($data['bar']) {
            return new RouteBuiltLink('https://example.org');
       } elseif (isset($data['foobar']) {
            // stop default build_callback usage, and use default XF processing
            $suppressDefaultCallback = true;
       }
       return null; // default XF processing
    } 
    $linkBuilder->injectLinkBuilderCallback($router, 'search', $callable);
}
```

## SV\StandardLib\Repository\Permissions
This is a helper repository designed to allow caching (and fetching) various permission in a way which can be extended

## \SV\StandardLib\Repository\Helper::getUserEntity
Helps get the user that owns an entity

```php
$helperRepo = \SV\StandardLib\Helper::repo();
$user = $helperRepo->getUserEntity($entity);
if (!$user && ($entity->isValidGetter('Content') || $entity->isValidRelation('Content')))
{
    $user = $helperRepo->getUserEntity($entity->get('Content'));
}
// XFRM support, as it doesn't have a User/Content relationship...
if (!$user && ($entity->isValidGetter('Resource') || $entity->isValidRelation('Resource')))
{
    $user = $helperRepo->getUserEntity($entity->get('Resource'));
}
```

## \SV\StandardLib\Repository\Helper::aliasClass

Allows a single XenForo class extension to map to different concrete classes to support breaking changes in class structures.
These aliases are XFCP compliant

```php
<?php

namespace SV\ElasticSearchEssentials\XF\Repository;

\SV\StandardLib\Helper::repo()->aliasClass(
    'SV\ElasticSearchEssentials\XF\Repository\Search',
    \XF::$versionId < 2020000
        ? 'SV\ElasticSearchEssentials\XF\Repository\XF2\Search'
        : 'SV\ElasticSearchEssentials\XF\Repository\XF22\Search'
);
```

## \SV\StandardLib\Repository\Helper - createEntity/finder/repository/find/findCached/service

These methods accept `::class` references, and have the return type hinted to match the argument.

```php
$obj = Helper::repository(\XF\Repository\User::class);
```
For static analysis and IDE, `$obj` will have the type `\XF\Repository\User`

# Template additions

## choices.js integration

Enrich a select box with [choices.js](https://github.com/Xon/Choices.js). 

```html
<xf:macro template="svStandardLib_macros" name="choices_setup" />

<xf:selectrow label="Select row example" name="select_row_example"
              data-xf-init="sv-choices"
              data-choices-max-item-count="1">
    <xf:option value="1">Option 1</xf:option>
    <xf:option value="2" selected="true">Option 2</xf:option>
    <xf:option value="3">Option 3</xf:option>
</xf:selectrow>
```

The initial structure is pre-rendered to reduce/prevent page jank.
To opt-out add the `skip-rendering="true"` attribute to the `<xf:select>` or `<xf:selectrow>` element


## ajax pagination

Load pagination pages via ajax instead of requiring full page-loads. Useful for overlays.

```html
<xf:js src="sv/vendor/domurl/url.js" addon="SV/StandardLib" min="1" />
<xf:js src="sv/lib/ajaxPagination.js" addon="SV/Threadmarks" min="1" />
...
<div class="block" data-xf-init="sv-ajax-pagination" data-content-wrapper=".block-body--wrapper">
    ...
    <div class="block-body--wrapper">
        ...
        <xf:pagenav ... />
        <xf:hiddenval name="final_url" value="{$finalUrl}" />
    </div>
</div>
```
`<xf:pagenav>` and `<xf:hiddenval name="final_url" />` must be inside the div which is tagged with `data-content-wrapper`'s css selector

### Template Filter: is_toggle_set

While similar to `is_goggled`, `is_toggle_set` supports specifying the default toggle state.
Stronlgy recommended to use `toggle-storage-ex` from `sv/lib/storage.js`.

Example of a default collapsed node-list:
```html
<xf:js src="sv/lib/storage.js" addon="SV/StandardLib" min="1" />
<xf:set var="$isActive" value="{{ is_toggle_set($forum.node_id, false, 'node-toggle') ? ' is-active' : '' }}"/>
<div class="block block--collapsible-child-nodes">
    <div class="block-container">
        <h3 class="block-minorHeader collapseTrigger collapseTrigger--block {$isActive} "
            data-target=".block--collapsible-child-nodes .block-body"
            data-xf-click="toggle"
            data-xf-init="toggle-storage-ex"
            data-storage-type="cookie"
            data-storage-container="node-toggle"
            data-storage-key="{$forum.node_id}"
            data-default-value="0"
        >{{ phrase('sub_forums') }}</h3>
        <div class="block-body toggleTarget {$isActive}">
            ...
        </div
```

### Template Filter: addvalue
Append a value `$newElementValue` in an array/collection. If the array is null, then a new array is returned.
If `$array` is a collection, then the return value is a collection.
```
$array|addvalue($newElementValue)
```

### Template Filter: replacevalue
Replaces a value `$elementValue` in an array/collection with `$newElementValue`. If `$newElementValue` is null, then that element is removed.
```
$array|replacevalue($elementValue, $newElementValue)
```

### Template Function: array_reverse
Reverse an array/collection. See [`array_reverse`](https://www.php.net/manual/en/function.array-reverse.php) for details.

### Template Function: array_diff
Computes the difference of arrays/collections. See [`array_diff`](https://www.php.net/manual/en/function.array-diff.php) for details.

### Template Function: sv_relative_timestamp
Provide a dynamic count up/down timestamp
```html
<abbr title="{$title|for_attr}" class="bbc-abbr">{{ sv_relative_timestamp($nowTimestamp, $otherTimestamp, $maximumDateParts, $countUp, 'bbc-time-counter') }}</abbr>
```
### Template Function: abs
Absolute value

### Template Function: parse_less_func
Similar to `parse_less_color`, except this allows parsing an arbitrary LESS expression.
Enable the `svLogLessFunc` option to log debug information
Does not return CSS variables like `parse_less_color` does.

### Template Function: phrase_dynamic
Backport `phrase_dynamic` from XF2.2 to XF2.1

### Store selected tab ID
Extend `XF.Tabs` to store the ID of the selected tab on submit via a hidden field.
Field name is set via `data-sv-store-selected-tab-input-name` added to the `data-xf-init="tabs"` element
```html
<xf:js addon="SV/StandardLib" src="sv/lib/xf/core/structure.js" min="1" />
...
<div class="hScroller"
     data-xf-init="h-scroller tabs"
     data-panes=".js-categoryTypeTabPanes"
     data-sv-store-selected-tab-input-name="category_type">
```

### date_time_input macro
Support for date/time/timezone input, returned as a unix timestamp
```html
<xf:macro template="svStandardLib_helper_macros"
          name="date_time_input{{ $asRow ? '_row' : '' }}"
          arg-name="scheduled_start_date"
          arg-timestamp="{$xf.time}" />
```

```php
$scheduledStartDate = $this->filter('scheduled_start_date', 'sv-datetime');
```

## Helper code

### InstallerHelper

`InstallerHelper` injects various setup helper features designed to allow robust installers.
Adds support for "require-soft" in `addon.json` which enables soft-dependencies

eg:
```php
class Setup extends AbstractSetup
{
    use SV\StandardLib\InstallerHelper;
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function installStep1(): void
    {
        $sm = $this->schemaManager();

        foreach ($this->getTables() as $tableName => $callback)
        {
            $sm->createTable($tableName, $callback);
            $sm->alterTable($tableName, $callback);
        }
    }

    public function installStep2(): void
    {
        $sm = $this->schemaManager();

        foreach ($this->getAlterTables() as $tableName => $callback)
        {
            if ($sm->tableExists($tableName))
            {
                $sm->alterTable($tableName, $callback);
            }
        }
    }

    public function upgrade2000000Step1(): void
    {
        $this->installStep1();
    }

    public function upgrade2000000Step2(): void
    {
        $this->installStep2();
    }

    public function uninstallStep1(): void
    {
        $sm = $this->schemaManager();

        foreach ($this->getTables() as $tableName => $callback)
        {
            $sm->dropTable($tableName);
        }
    }

    public function uninstallStep2(): void
    {
        $sm = $this->schemaManager();

        foreach ($this->getRemoveAlterTables() as $tableName => $callback)
        {
            if ($sm->tableExists($tableName))
            {
                $sm->alterTable($tableName, $callback);
            }
        }
    }

    protected function getTables(): array
    {
        return [
            'xf_sv_mytable' => function ($table) {
                /** @var Create|Alter $table */
                $this->addOrChangeColumn($table, 'id', 'int')->primaryKey();
            },
        ];
    }

    protected function getAlterTables(): array
    {
        return [
            'xf_user' => function (Alter $table) {
                $this->addOrChangeColumn($table, 'sv_my_column', 'int')->setDefault(0);
            },
        ];
    }

    protected function getRemoveAlterTables(): array
    {
        return [
            'xf_user' => function (Alter $table) {
                $table->dropColumns(['sv_my_column']);
            },
        ];
    }
```
#### Simplified uninstaller
For simply table alters (add column/index, with column renames), the following can be used instead of defining `getRemoveAlterTables`;

```php
    public function uninstallStep2(): void
    {
        $sm = $this->schemaManager();

        foreach ($this->getReversedAlterTables($this->getAlterTables()) as $tableName => $callback)
        {
            if ($sm->tableExists($tableName))
            {
                $sm->alterTable($tableName, $callback);
            }
        }
    }
```
This does not reverse column schema changes, which can be very complex to reverse.

### BypassAccessStatus - Helper code

`BypassAccessStatus` provides wrappers to easily get/set protected/private class values;
```php
$accesser = new \SV\StandardLib\BypassAccessStatus();
$setUniqueEntityId = $accesser->setPrivate($this, '_uniqueEntityId', \XF\Mvc\Entity\Entity::class);
$getEntityCounter = $accesser->getStaticPrivate(\XF\Mvc\Entity\Entity::class, '_entityCounter');
$setEntityCounter = $accesser->setStaticPrivate(\XF\Mvc\Entity\Entity::class, '_entityCounter');

$id = $getEntityCounter();
$setEntityCounter($id + 1);
```