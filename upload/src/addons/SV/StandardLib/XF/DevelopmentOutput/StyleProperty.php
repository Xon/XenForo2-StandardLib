<?php
/**
 * @noinspection PhpMissingParentCallCommonInspection
 */

namespace SV\StandardLib\XF\DevelopmentOutput;

use XF\Mvc\Entity\Entity;
use XF\Util\Json;

if (\XF::$versionId < 2030000)
{
    /**
     * XF2.1/XF2.2 compat
     *
     * @extends \XF\DevelopmentOutput\StyleProperty
     */
    class StyleProperty extends XFCP_StyleProperty
    {
        protected function getJsonStructure(\XF\Entity\StyleProperty $property): array
        {
            return [
                'group_name'       => $property->group_name,
                'title'            => $property->getValue('title'),
                'description'      => $property->getValue('description'),
                'property_type'    => $property->property_type,
                'css_components'   => $property->css_components,
                'value_type'       => $property->value_type,
                'value_parameters' => $property->value_parameters,
                'has_variations'   => $property->has_variations,
                'depends_on'       => $property->depends_on,
                'value_group'      => $property->value_group,
                'property_value'   => $property->getValue('property_value'),
                'display_order'    => $property->display_order,
            ];
        }

        /** @noinspection PhpMissingReturnTypeInspection */
        public function export(Entity $property)
        {
            /** @var \XF\Entity\StyleProperty $property */
            if (!$this->isRelevant($property))
            {
                return true;
            }

            $fileName = $this->getFileName($property);

            $json = $this->getJsonStructure($property);

            $this->queuePropertyLessCacheRebuild($property->addon_id);

            return $this->developmentOutput->writeFile(
                $this->getTypeDir(),
                $property->addon_id,
                $fileName,
                Json::jsonEncodePretty($json)
            );
        }

        /** @noinspection PhpMissingReturnTypeInspection */
        public function getLessCacheFileValue($addOnId)
        {
            return parent::getLessCacheFileValue($addOnId) . "\n";
        }
    }
}
else
{
    class StyleProperty extends XFCP_StyleProperty {}
    return;
}