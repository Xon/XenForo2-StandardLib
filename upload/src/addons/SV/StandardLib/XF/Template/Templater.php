<?php

namespace SV\StandardLib\XF\Template;

use XF\Mvc\Entity\Entity;
use XF\Phrase;
use function array_replace;
use function explode;
use function in_array;
use function is_array;
use function stripos;
use function strval;
use function substr;

/**
 * @version 1.21.0
 */
class Templater extends XFCP_Templater
{
    protected $svIncludeJsMap = [
        'SV/StandardLib' => [
            'sv/lib/storage.js'           => [
                ['dev' => 'xf/structure.js', 'prod' => 'xf/structure.min.js'],
            ],
            'sv/lib/xf/core/structure.js' => [
                ['dev' => 'xf/structure.js', 'prod' => 'xf/structure.min.js'],
            ]
        ]
    ];

    /**
     * HTML data attribute to class name mapping for supported prerender choices class overrides
     * Must match https://github.com/Xon/Choices.js?tab=readme-ov-file#classnames entries
     *
     * @var array<string,string>
     */
    protected static $choicesClassOverrides = [
        'data-class-container-outer' => 'containerOuter',
        'data-class-container-inner' => 'containerInner',
        'data-class-list' => 'list',
        'data-class-list-items' => 'listItems',
        'data-class-item' => 'item',
        'data-class-item-selectable' => 'itemSelectable',
        'data-class-input' => 'input',
        'data-class-input-cloned' => 'inputCloned',
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

    protected function isPreRenderableChoicesInit(array $init): bool
    {
        return in_array('sv-choices', $init, true);
    }

    public function formSelect(array $controlOptions, array $choices)
    {
        $value = $controlOptions['value'] ?? null;
        if ($value instanceof Entity)
        {
            // XF2.3 supports pulling attributes out of entities, for now skip
            // $this->processEntityAttributes($controlOptions, false);
            return parent::formSelect($controlOptions, $choices);
        }

        $init = explode(' ', $controlOptions['data-xf-init'] ?? '');
        if (!$this->isPreRenderableChoicesInit($init))
        {
            return parent::formSelect($controlOptions, $choices);
        }

        $class = $controlOptions['class'] ?? '';
        if (stripos($class, 'u-noJsOnly') === false)
        {
            $controlOptions['class'] = $class . ' u-noJsOnly';
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
                    continue;
                }

                if ($this->isChoiceSelected($choice, $value, $multiple))
                {
                    $choice['span'] = $choice['data-label-class'] ?? '';
                    $selectedChoices[$i] = $choice;
                }
                $i++;
            }
        };
        $extractChoices($choices);

        // signal to javascript about pre-rendering
        $controlOptions['data-rendered'] = 1;

        $class = $this->copyChoicesClassOverridesFromDataAttributes($controlOptions);

        $selectHtml = parent::formSelect($controlOptions, $choices);

        return $this->callMacro('', 'public:svStandardLib_macros::choices_static_render', [
            'name'            => $name,
            'value'           => $value,
            'multiple'        => $multiple,
            'placeholder'     => $placeholder,
            'controlOptions'  => $controlOptions,
            'selectHtml'      => $selectHtml,
            'choices'         => $choices,
            'selectedChoices' => $selectedChoices,
            'class'           => $class,
        ], $this->defaultParams);
    }

    protected function copyChoicesClassOverridesFromDataAttributes(array $controlOptions): array
    {
        $attrs = [];
        foreach (static::$choicesClassOverrides as $attrKey => $classKey)
        {
            $value = $controlOptions[$attrKey] ?? null;
            if ($value === null)
            {
                continue;
            }

            if ($value instanceof Phrase)
            {
                // strval will do escaping of the values or the whole phrase, so get the raw value and escape that here
                $value = $value->render('raw');
            }
            else
            {
                $value = strval($value);
            }

            if ($value !== '')
            {
                $attrs[$classKey] = \XF::escapeString($value);
            }
        }

        return $attrs;
    }
}
