<?php

namespace SV\StandardLib\XF\Template;

use function array_filter;
use function explode;
use function in_array;
use function is_array;
use function is_string;
use function stripos;
use function substr;

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
        if (\XF::$versionId >= 2030000)
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
                foreach ($extraIncludeJsArr as $extraIncludeJs)
                {
                    parent::includeJs($extraIncludeJs);
                }
            }
        }

        parent::includeJs($options);
    }

    public function formSelect(array $controlOptions, array $choices)
    {
        $value = $controlOptions['value'] ?? null;
        if (!is_array($value) && !is_string($value) && $value !== null)
        {
            return parent::formSelect($controlOptions, $choices);
        }

        $init = explode(' ', $controlOptions['data-xf-init'] ?? '');
        if (!in_array('sv-choices', $init, true))
        {
            return parent::formSelect($controlOptions, $choices);
        }

        $class = $controlOptions['class'] ?? '';
        if (stripos($class, 'u-noJsOnly') === false)
        {
            $controlOptions['class'] = $class . ' u-noJsOnly';
        }

        if (in_array('sv-choices-loader', $init, true))
        {
            return parent::formSelect($controlOptions, $choices);
        }

        if ($controlOptions['skip-rendering'] ?? false)
        {
            unset($controlOptions['skip-rendering']);
            return parent::formSelect($controlOptions, $choices);
        }

        $name = $controlOptions['name'];
        $multiple = (bool)($controlOptions['multiple'] ?? false);
        if ($multiple)
        {
            if ($name && substr($name, -2) != '[]')
            {
                $name .= '[]';
            }
        }
        $placeholder = $controlOptions['placeholder'] ?? $controlOptions['data-placeholder'] ?? '';

        $selectedChoices = [];
        $i = 1;
        $extractChoices = function (array $choices) use (&$i, &$extractChoices, &$selectedChoices, $value, $multiple) {
            foreach ($choices as $choice)
            {
                $type = $choice['_type'] ?? 'option';
                if ($type == 'optgroup')
                {
                    $extractChoices($choice['options']);
                }
                else if ($this->isChoiceSelected($choice, $value, $multiple))
                {
                    $choice['span'] = $choice['data-label-class'] ?? '';
                    $selectedChoices[$i] = $choice;
                    $i++;
                }
            }
        };
        $extractChoices($choices);

        // signal to javascript about pre-rendering
        $controlOptions['data-rendered'] = 1;

        $selectHtml = parent::formSelect($controlOptions, $choices);

        return $this->callMacro('', 'public:svStandardLib_macros::choices_static_render', [
            'name' => $name,
            'value' => $value,
            'multiple' => $multiple,
            'placeholder' => $placeholder,
            'controlOptions' => $controlOptions,
            'selectHtml' => $selectHtml,
            'choices' => $choices,
            'selectedChoices' => $selectedChoices,
        ], $this->defaultParams);
    }
}
