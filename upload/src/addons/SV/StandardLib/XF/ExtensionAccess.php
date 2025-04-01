<?php

namespace SV\StandardLib\XF;

use LogicException;
use XF\Extension;
use function substr;

class ExtensionAccess extends Extension
{
    public static function patchInverseMap(string $finalClass, string $baseLineClass): void
    {
        if (\XF::$versionId < 2021300)
        {
            return;
        }

        if ($finalClass === '')
        {
            throw new LogicException('Expected $finalClass to not be empty');
        }
        if ($baseLineClass === '')
        {
            throw new LogicException('Expected $baseLineClass to not be empty');
        }
        if ($finalClass[0] === '\\')
        {
            $finalClass = substr($finalClass, 1);
        }
        if ($baseLineClass[0] === '\\')
        {
            $baseLineClass = substr($baseLineClass, 1);
        }

        $extension = \XF::extension();
        $extension->inverseExtensionMap[$finalClass] = $baseLineClass;
    }
}