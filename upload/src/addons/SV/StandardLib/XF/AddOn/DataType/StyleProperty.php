<?php
/**
 * @noinspection PhpMissingReturnTypeInspection
 */

namespace SV\StandardLib\XF\AddOn\DataType;


use XF\Mvc\Entity\Entity;
use function array_unshift;

if (\XF::$versionId < 2030000)
{
    /**
     * @extends \XF\AddOn\DataType\StyleProperty
     */
    class StyleProperty extends XFCP_StyleProperty
    {
        protected function exportMappedAttributes(\DOMElement $tag, Entity $entity)
        {
            $entity->setOption('svShimPropertyValue', false);
            try
            {
                parent::exportMappedAttributes($tag, $entity);
            }
            finally
            {
                $entity->setOption('svShimPropertyValue', false);
            }
        }

        protected function getMappedAttributes()
        {
            $attributes = parent::getMappedAttributes();
            array_unshift($attributes, 'has_variations');

            return $attributes;
        }
    }
}
else
{
    class StyleProperty extends XFCP_StyleProperty {}
}