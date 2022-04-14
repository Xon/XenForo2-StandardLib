<?php

namespace SV\StandardLib;

class Helper
{
    /**
     * Private constructor, use statically.
     */
    private function __construct() { }

    public static function repo(): \SV\StandardLib\Repository\Helper
    {
        /** @var \SV\StandardLib\Repository\Helper $repo */
        $repo = \XF::repository('SV\StandardLib:Helper');

        return $repo;
    }
}