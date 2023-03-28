<?php

namespace SV\StandardLib\XF\AddOn;

use SV\StandardLib\Helper;

/**
 * Extends \XF\AddOn\DataManager
 */
class DataManager extends XFCP_DataManager
{
    /** @noinspection PhpMissingReturnTypeInspection */
    public function rebuildActiveAddOnCache()
    {
        Helper::repo()->rebuildAddOnVersionCache();
        return parent::rebuildActiveAddOnCache();
    }
}