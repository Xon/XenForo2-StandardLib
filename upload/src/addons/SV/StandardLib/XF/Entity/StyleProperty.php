<?php

namespace SV\StandardLib\XF\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

if (\XF::$versionId < 2030000)
{
    /**
     * XF2.1/XF2.2 compat
     *
     * @extends \XF\Entity\StyleProperty
     * @property-read scalar|array|null $property_value_
     * @property bool                   $has_variations
     */
    class StyleProperty extends XFCP_StyleProperty
    {
        protected function getPropertyValue()
        {
            $value = $this->property_value_;

            if (is_array($value) && $this->has_variations && $this->getOption('svShimPropertyValue'))
            {
                $value = $value['default'] ?? '';
            }

            return $value;
        }

        /**
         * @param Structure $structure
         * @return Structure
         */
        public static function getStructure(Structure $structure)
        {
            $structure = parent::getStructure($structure);

            $structure->columns['has_variations'] = ['type' => self::BOOL, 'default' => false];
            $structure->getters['property_value'] = ['getter' => 'getPropertyValue', 'cache' => false];
            $structure->options['svShimPropertyValue'] = true;

            return $structure;
        }
    }
}
else
{
    class StyleProperty extends XFCP_StyleProperty {}
}