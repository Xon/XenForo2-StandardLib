<?php

namespace SV\StandardLib\XF\AddOn;



use SV\StandardLib\Helper;
use function array_key_exists;
use function array_merge;
use function explode;
use function extension_loaded;
use function is_array;
use function phpversion;
use function strpos;
use function strtolower;
use function version_compare;

/**
 * Extends \XF\AddOn\Manager
 */
class Manager extends XFCP_Manager
{
    /** @noinspection PhpMissingReturnTypeInspection */
    public function checkAddOnRequirements(array $requirements, $title, &$errors = [])
    {
        $errors = [];
        $addOns = \XF::app()->container('addon.cache');

        foreach ($requirements as $productKey => $requirement)
        {
            if (!is_array($requirement))
            {
                continue;
            }
            list ($version, $product) = $requirement;

            // only apply the version string constraint if it is a known add-on
            if (!array_key_exists($productKey, $addOns))
            {
                continue;
            }

            unset($requirements[$productKey]);
            if (!Helper::isAddOnActive($productKey, $version))
            {
                $errors[] = "{$title} requires $product.";
            }
        }

        $addonErrors = $errors;

        $isValid = parent::checkAddOnRequirements($requirements, $title, $errors);
        $errors = array_merge($errors, $addonErrors);

        return count($errors) === 0 && $isValid;
    }
}