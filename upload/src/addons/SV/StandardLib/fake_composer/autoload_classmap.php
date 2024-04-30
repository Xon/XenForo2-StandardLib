<?php
/** @noinspection PhpFullyQualifiedNameUsageInspection */

if (\XF::$versionId < 2030036)
{
    return [
        'XF\Entity\ViewableInterface' => __DIR__ . '/shim/ViewableInterface.php',
    ];
}

return [];