<?php

namespace SV\StandardLib\XF\Template;

use function count;
use function explode;

if (\XF::$versionId < 2020000)
{
    class TemplaterXF21Patch extends XFCP_TemplaterXF21Patch
    {
        /**
         * @noinspection PhpSignatureMismatchDuringInheritanceInspection
         */
        public function callMacro($template, $name, array $arguments, array $globalVars)
        {
            if (!$template)
            {
                $nameParts = explode('::', $name, 2);
                if (count($nameParts) === 2)
                {
                    $template = $nameParts[0];
                    $name = $nameParts[1];
                }
            }

            return parent::callMacro($template, $name, $arguments, $globalVars);
        }
    }
}
else
{
    class TemplaterXF21Patch extends XFCP_TemplaterXF21Patch { }
}