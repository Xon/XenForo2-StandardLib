<?php

namespace SV\StandardLib\XF;

use function preg_match;
use function preg_replace_callback;
use function trigger_error;
use function var_export;

/**
 * @Extends \XF\CssRenderer
 */
class CssRenderer extends XFCP_CssRenderer
{
    /** @var \Less_Parser|null */
    protected $svSimpleLessParser = null;

    protected function getSimpleLessParser(): \Less_Parser
    {
        if ($this->svSimpleLessParser !== null)
        {
            $this->svSimpleLessParser->Reset();
            return $this->svSimpleLessParser;
        }

        $isRtl = $this->templater->getLanguage()->isRtl();
        if (\XF::$versionId < 2030000)
        {
            /**
             * @noinspection PhpUndefinedClassInspection
             * @noinspection PhpFullyQualifiedNameUsageInspection
             * @noinspection PhpUndefinedNamespaceInspection
             */
            $plugins = [
                new \XF\Less\RtlVisitorPre(),
                new \XF\Less\RtlVisitor($isRtl)
            ];
        }
        else
        {
            /**
             * @noinspection PhpUndefinedClassInspection
             * @noinspection PhpFullyQualifiedNameUsageInspection
             * @noinspection PhpUndefinedNamespaceInspection
             */
            $plugins = [
                new \XF\Less\Visitor\RtlVisitorPre(),
                new \XF\Less\Visitor\RtlVisitor($isRtl),
            ];
        }

        $this->svSimpleLessParser = new \Less_Parser([
            'strictMath' => true,
            'import_callback' => [$this, 'handleLessImport'],
            'compress' => false,
            'plugins' => $plugins,
        ]);

        return $this->svSimpleLessParser;
    }

    /**
     * @param callable(\Less_Parser):mixed $func
     * @return mixed
     */
    protected function withSimpleLessParser(callable $func)
    {
        $style = $this->style;

        $suppressVariations = \XF::$versionId > 2030000 && $style->isVariationsEnabled();
        if ($suppressVariations)
        {
            $oldVariation = $this->style->getVariation();
            $style->setVariation('default');
        }
        try
        {
            $parser = $this->getSimpleLessParser();
            return $func($parser);
        }
        finally
        {
            if ($suppressVariations)
            {
                $style->setVariation($oldVariation);
            }
        }
    }

    /** @noinspection PhpUnnecessaryLocalVariableInspection */
    protected function svProcessLessVariablesToRaw(string $value): string
    {
        $value = preg_replace_callback(
            '/var\(--xf-([^)]+)\)/i',
            function (array $match): string
            {
                $prop = $this->style->getProperty($match[1],  null);
                if ($prop === null)
                {
                    return $match[0];
                }

                return $prop;
            }, $value);

        return $value;
    }

    /**
     * When given a color value which may contain a mix of XF and `Less` functions test and return the parsed color.
     * If the provided Less is invalid, or no valid color found, returns null.
     *
     * @param string $value
     * @param bool   $forceDebug
     * @return null|string
     * @noinspection PhpMissingParamTypeInspection
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function parseLessColorFuncValue(string $value, $forceDebug = false)
    {
        return $this->withSimpleLessParser(function(\Less_Parser $parser) use ($value, $forceDebug) {
            $value = '@someVar: ' . $value . '; #test { color: @someVar; }';
            $value = $this->prepareLessForRendering($value);
            $value = $this->svProcessLessVariablesToRaw($value);
            try
            {
                $css = $parser->parse($value)->getCss();
            }
            catch (\Exception $e)
            {
                if (\XF::$debugMode)
                {
                    // Note: this is intentionally triggering a warning rather than an exception.
                    // This will commonly trigger in templates, and we will still be able to render in that case.
                    trigger_error("parse_less_func({$value}) render error:" . $e->getMessage(), E_USER_WARNING);
                }

                \XF::logException($e, false, "parse_less_func({$value}) render error:");

                return null;
            }

            preg_match('/color:\s*(.*?)\s*;?\s*}$/si', $css, $matches);

            if ($forceDebug || (\XF::options()->svLogLessFunc ?? false))
            {
                \XF::logError("parse_less_func({$value}), " . $css . ', matches:' . var_export($matches, true), true);
            }

            if (!$matches || !isset($matches[1]))
            {
                return null;
            }

            return $matches[1];
        });
    }
}