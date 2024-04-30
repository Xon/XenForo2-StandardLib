<?php
/**
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */

$classmap  = [];
if (\XF::$versionId < 2030036)
{
    $classmap[\XF\Entity\ViewableInterface::class] = __DIR__ . '/shim/ViewableInterface.php';
}
if (\XF::$versionId < 2020031)
{
    $classmap[\XF\Entity\LinkableInterface::class] = __DIR__ . '/shim/LinkableInterface.php';
}

return $classmap;