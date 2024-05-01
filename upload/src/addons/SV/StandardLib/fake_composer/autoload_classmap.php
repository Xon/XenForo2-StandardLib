<?php
/**
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */

$classmap = [];
if (\XF::$versionId < 2030036)
{
    $classmap[\XF\Entity\ViewableInterface::class] = __DIR__ . '/shim/XF/Entity/ViewableInterface.php';
    if (\XF::$versionId > 2030000)
    {
        $classmap[\XF\Api\Result\ResultInterface::class] = __DIR__ . '/shim/XF/Api/Result/ResultInterface.php';
    }
}
if (\XF::$versionId < 2030031)
{
    $classmap[\XF\Entity\ResultInterface::class] = __DIR__ . '/shim/XF/Entity/ResultInterface.php';

    $classmap[\XF\Entity\ContainableInterface::class] = __DIR__ . '/shim/XF/Entity/ContainableInterface.php';
    $classmap[\XF\Entity\ContainableTrait::class] = __DIR__ . '/shim/XF/Entity/ContainableTrait.php';

    $classmap[\XF\Entity\DatableInterface::class] = __DIR__ . '/shim/XF/Entity/DatableInterface.php';
    $classmap[\XF\Entity\DatableTrait::class] = __DIR__ . '/shim/XF/Entity/DatableTrait.php';
}
if (\XF::$versionId < 2020031)
{
    $classmap[\XF\Entity\LinkableInterface::class] = __DIR__ . '/shim/XF/Entity/LinkableInterface.php';
}

return $classmap;