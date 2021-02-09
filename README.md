# XenForo2-StandardLib

A number of helper utilities designed to ease add-on development

During use, Add the requires section to `addon.json` to document the dependancy

```json
{

    "require": {
        "XF": [2010070, "XenForo 2.1.0+"],
        "php": ["7.0.0", "PHP 7.0.0+"],
        "SV/StandardLib": [1060000,"Standard Library by Xon v1.6.0+"]
    }
}
```
## Finder extension traits

### RlikeOperatorTrait
`RLIKE` operator support.

### EarlyJoinFinderTrait
MySQL implements 'early row lookup' which results in the large select statement pulling in more data than is required.
This trait allows migrating this with some minor configuration.

See https://github.com/Xon/XenForo2-OptimizedListQueries for examples.

### ComplexJoinTrait
Inject an entity relations at query time. This is useful to work-around XenForo lacking reverse relationships on handler-like entities

See https://github.com/Xon/XenForo2-OptimizedListQueries/blob/f3f0dbbcd58273314e92aca8a4b34d3ecc062815/upload/src/addons/SV/OptimizedListQueries/XF/Repository/Node.php#L12-L68 for an example.
Be aware that MySQL can suffer degraded performance for large number of joins, so this might not always be the best choice.

### SqlJoinTrait
Inject arbitrary SQL at query time, as join vs subquery can have massive performance differences despite being logically identical

## View template modifications

Allows viewing template modfications which are applying to a template, including generated php source code

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

## Template additions

### Template Filter: replacevalue
Replaces a value `$elementValue` in an array/collection with `$newElementValue`. If `$newElementValue` is null, then that element is removed.
```
$array|replacevalue($elementValue, $newElementValue)
```

### Template Function: sv_array_reverse
Reverse an array/collection (ie `array_reverse`)

### Template Function: sv_relative_timestamp
Provide a dynamic count up/down timestamp
```html
<abbr title="{$title|for_attr}" class="bbc-abbr">{{ sv_relative_timestamp($nowTimestamp, $otherTimestamp, $maximumDateParts, $countUp, 'bbc-time-counter') }}</abbr>
```

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

```php
class Setup extends AbstractSetup
{
    use SV\StandardLib\InstallerHelper;
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;
```

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