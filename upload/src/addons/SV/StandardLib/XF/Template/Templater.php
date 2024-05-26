<?php

namespace SV\StandardLib\XF\Template;

/**
 * @version 1.21.0
 */
class Templater extends XFCP_Templater
{
    protected $svIncludeJsMap = [
        'SV/StandardLib' => [
            'sv/lib/storage.js' => [
                ['dev' => 'xf/structure.js', 'prod' => 'xf/structure.min.js'],
            ],
            'sv/lib/xf/core/structure.js' => [
                ['dev' => 'xf/structure.js', 'prod' => 'xf/structure.min.js'],
            ]
        ]
    ];

    public function includeJs(array $options)
    {
        $tmpOptions = array_replace([
            'src'   => null,
            'defer' => true,
            'addon' => null,
            'min'   => null,
            'dev'   => null,
            'prod'  => null,
            'root'  => false,
        ], $options);

        $addOnJsMap = $this->svIncludeJsMap[$tmpOptions['addon']] ?? [];
        $extraIncludeJsArr = $addOnJsMap[$tmpOptions['src']] ?? [];
        if (is_array($extraIncludeJsArr))
        {
            foreach ($extraIncludeJsArr AS $extraIncludeJs)
            {
                parent::includeJs($extraIncludeJs);
            }
        }

        parent::includeJs($options);
    }
}