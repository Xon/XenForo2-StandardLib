<?php
/**
 * @noinspection PhpUnusedParameterInspection
 */

namespace SV\StandardLib;

use SV\StandardLib\Helper as StandardLibHelper;
use SV\StandardLib\XF\CssRenderer;
use XF\Mvc\Entity\AbstractCollection;
use XF\Mvc\Reply\AbstractReply;
use XF\Template\Templater as BaseTemplater;
use function json_decode;
use function max;
use function method_exists, is_string, is_array, count, array_diff, array_reverse, array_unshift, abs, assert, trigger_error, trim, implode;

class TemplaterHelper
{
    /** @var BaseTemplater */
    protected $templater;
    /** @var \XF\App */
    protected $app;
    /** @var bool */
    protected $hasFromCallable;
    /** @var class-string<TemplaterAccess>|TemplaterAccess */
    protected $templaterAccessClass;
    /** @var CssRenderer|null */
    protected $cssRenderer = null;

    /**
     * @param \XF\Container $container
     * @param BaseTemplater $templater
     * @return void
     */
    public static function templaterSetup(\XF\Container $container, BaseTemplater &$templater)
    {
        $class = self::class;
        /** @noinspection PhpUnhandledExceptionInspection */
        $class = \XF::extendClass($class);
        /** @var TemplaterHelper $templateHelper */
        $templateHelper = new $class($templater);
        assert($templateHelper instanceof self);
        $templateHelper->setup();
    }

    public static function templaterGlobalData(\XF\App $app, array &$data, AbstractReply $reply = null)
    {
        $helper = self::get($app->templater());
        $helper->populateTemplaterGlobalData($data, $reply);
    }

    /**
     * @param BaseTemplater $templater
     * @return static
     * @noinspection PhpMissingReturnTypeInspection
     */
    public static function get(\XF\Template\Templater $templater)
    {
        $helper = TemplaterAccess::getDefaultParam($templater, 'svTemplateHelper');
        // make sure a non-null value is fetched
        if ($helper === null)
        {
            self::templaterSetup(\XF::app()->container(), $templater);
            $helper = TemplaterAccess::getDefaultParam($templater, 'svTemplateHelper');

        }
        assert($helper instanceof TemplaterHelper);

        return $helper;
    }

    public function __construct(BaseTemplater $templater)
    {
        $this->hasFromCallable = \is_callable('\Closure::fromCallable');
        $this->templater = $templater;
        $this->templaterAccessClass = \XF::extendClass(TemplaterAccess::class);
        $this->app = $this->templaterAccessClass::app($templater);
    }

    public function setup()
    {
        // add a reference on the templater to this class so it can be found
        $this->addDefaultParam('svTemplateHelper', $this);

        $this->addDefaultHandlers();
    }

    protected function populateTemplaterGlobalData(array &$data, AbstractReply $reply = null)
    {
    }

    protected function hasFilter(string $filter): bool
    {
        $filters = $this->templaterAccessClass::filters($this->templater);

        return isset($filters[$filter]);
    }

    protected function hasFunction(string $function): bool
    {
        return $this->function($function) !== null;
    }

    /**
     * @param string $function
     * @return callable|callable-string|null
     */
    public function function(string $function)
    {
        $functions = $this->functions();

        return $functions[$function] ?? null;
    }

    public function functions(): array
    {
        return $this->templaterAccessClass::functions($this->templater);
    }

    /**
     * @param callable|callable-string $filter
     * @param bool                     $resolveToSelf
     * @return callable
     */
    protected function mangleCallable($filter, bool $resolveToSelf = true): callable
    {
        if ($filter === null)
        {
            throw new \LogicException('Require $filter to not be null');
        }

        if (is_string($filter))
        {
            if ($resolveToSelf && method_exists($this, $filter))
            {
                $filter = [$this, $filter];
            }
            else
            {
                $filter = [$this->templater, $filter];
            }
        }

        if ($this->hasFromCallable && !($filter instanceof \Closure))
        {
            /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
            $filter = \Closure::fromCallable($filter);
        }

        return $filter;
    }

    /**
     * @param string $name
     * @param callable|callable-string $filter
     * @param bool   $replace
     * @return void
     */
    protected function addFilter(string $name, $filter, bool $replace = false)
    {
        if (!$replace && $this->hasFilter($name))
        {
            return;
        }

        $this->templater->addFilter($name, $this->mangleCallable($filter));
    }

    /**
     * @param string $name
     * @param callable|callable-string $filter
     * @param bool   $replace
     * @return void
     */
    protected function addFunction(string $name, $filter, bool $replace = false)
    {
        if (!$replace && $this->hasFunction($name))
        {
            return;
        }

        $this->templater->addFunction($name, $this->mangleCallable($filter));
    }

    protected function getStyle(): \XF\Style
    {
        return $this->templater->getStyle() ?? $this->app()->style();
    }

    public function uncacheTemplateData(string $type, string $template)
    {
        $this->templaterAccessClass::uncacheTemplateData($this->templater, $type, $template);
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function getDefaultParam(string $name)
    {
        return $this->templaterAccessClass::getDefaultParam($this->templater, $name);
    }

    public function addDefaultParam(string $name, $value)
    {
        $this->templater->addDefaultParam($name, $value);
    }

    public function addDefaultHandlers()
    {
        $this->addFilter('replacevalue', 'filterReplaceValue');
        $this->addFilter('addvalue', 'filterAddValue');
        $this->addFunction('array_diff', 'fnArrayDiff');
        $this->addFunction('phrase_dynamic', 'fnPhraseDynamic');
        $this->addFunction('array_reverse', 'fnArrayReverse');
        $this->addFunction('sv_array_reverse', \XF::$debugMode ? 'fnArrayReverseOld' : 'fnArrayReverse');
        $this->addFunction('sv_relative_timestamp', 'fnRelativeTimestamp');
        $this->addFunction('parse_less_func', 'fnParseLessFunc');
        $this->addFunction('abs', 'fnAbs');
        $this->addFunction('is_toggle_set', 'fnIsToggleSet');
        $this->addFunction('is_addon_active', 'fnIsAddonActive');
    }

    /**
     * @param BaseTemplater   $templater
     * @param bool            $escape
     * @param string          $addOnId
     * @param string|int|null $versionId
     * @param string          $operator
     * @return bool
     */
    public function fnIsAddonActive(BaseTemplater $templater, bool &$escape, string $addOnId, $versionId = null, string $operator = '>='): bool
    {
        return Helper::isAddOnActive($addOnId, $versionId, $operator);
    }

    /**
     * @param BaseTemplater $templater
     * @param bool          $escape
     * @param string        $storageKey
     * @param string        $storageContainer
     * @param bool|null   $default
     * @return bool
     */
    public function fnIsToggleSet(BaseTemplater $templater, bool &$escape, string $storageKey, bool $default = false, string $storageContainer = 'toggle')
    {
        $cookie = $this->app->request()->getCookie($storageContainer);
        if (!$cookie)
        {
            return $default;
        }

        $cookieDecoded = @json_decode($cookie, true);
        if (!$cookieDecoded)
        {
            return $default;
        }

        $valueBag = $cookieDecoded[$storageKey] ?? [];

        if (!is_array($valueBag) || count($valueBag) !== 3)
        {
            return $default;
        }

        $setDate = max(0, (int)($valueBag[0] ?? 0));
        if ($setDate === 0)
        {
            return $default;
        }
        $expiryOffset = max(0, (int)($valueBag[1] ?? 0));
        if ($expiryOffset === 0)
        {
            return $default;
        }
        if (($setDate+$expiryOffset) <= \XF::$time)
        {
            return $default;
        }

        return (bool)($valueBag[2] ?? $default);
    }
    /**
     * @param BaseTemplater             $templater
     * @param array|AbstractCollection  $value
     * @param bool                      $escape
     * @param mixed                     $toAdd
     * @return array|AbstractCollection
     */
    public function filterAddValue(BaseTemplater $templater, $value, bool &$escape, $toAdd)
    {
        $wasCollection = false;
        if ($value === null)
        {
            $value = [];
        }
        else if ($value instanceof AbstractCollection)
        {
            $wasCollection = true;
            $value = $value->toArray();
        }
        else if (!is_array($value))
        {
            $error = "addValue should be called on an array or an AbstractCollection";
            if (\XF::$debugMode)
            {
                trigger_error($error, E_USER_WARNING);
            }
            \XF::logError($error);

            return $value;
        }

        $value[] = $toAdd;

        return $wasCollection ? $this->em()->getBasicCollection([$value]) : $value;
    }

    /**
     * @param BaseTemplater   $templater
     * @param array|AbstractCollection  $value
     * @param bool            $escape
     * @param int|string      $toReplace
     * @param int|string|null $replaceWith
     * @return array|AbstractCollection
     */
    public function filterReplaceValue(BaseTemplater $templater, $value, bool &$escape, $toReplace, $replaceWith): array
    {
        $wasCollection = false;
        if ($value === null)
        {
            $value = [];
        }
        else if ($value instanceof AbstractCollection)
        {
            $wasCollection = true;
            $value = $value->toArray();
        }
        else if (!is_array($value))
        {
            $error = "removeValue should be called on an array or an AbstractCollection";
            if (\XF::$debugMode)
            {
                trigger_error($error, E_USER_WARNING);
            }
            \XF::logError($error);

            return $value;
        }

        foreach ($value as $key => $_val)
        {
            // deliberately using non-strict equality checks
            if ($_val == $toReplace)
            {
                if ($replaceWith === null)
                {
                    unset($value[$key]);
                }
                else
                {
                    $value[$key] = $replaceWith;
                }
            }
        }

        return $wasCollection ? $this->em()->getBasicCollection([$value]) : $value;
    }

    public function fnDynamicPhrase(BaseTemplater $templater, bool &$escape, string $value): \XF\Phrase
    {
        $escape = false;

        return \XF::phrase($value);
    }

    /**
     * XF2.1 only, as XF2.2 implements this
     *
     * @param BaseTemplater $templater
     * @param bool          $escape
     * @param string        $phraseName
     * @param array         $params
     * @return string|\XF\PreEscaped
     */
    public function fnPhraseDynamic(BaseTemplater $templater, bool &$escape, string $phraseName, array $params = [])
    {
        $phrase = $this->templater->getLanguage()->phrase($phraseName, $params);

        return $phrase->render();
    }

    /**
     * @param BaseTemplater $templater
     * @param bool          $escape
     * @param array $array1
     * @param array<array|AbstractCollection> $arrays
     * @return array
     */
    public function fnArrayDiff(BaseTemplater $templater, bool &$escape, array $array1 = null, ...$arrays): array
    {
        array_unshift($arrays, $array1);
        foreach($arrays as &$array)
        {
            if ($array === null)
            {
                $array = [];
            }
            else if ($array instanceof AbstractCollection)
            {
                $array = $array->toArray();
            }
        }

        if (count($arrays) <= 1)
        {
            return $array1;
        }

        return array_diff(...$arrays);
    }

    /**
     * @param BaseTemplater $templater
     * @param bool $escape
     * @param AbstractCollection|array $array
     * @param bool $preserveKeys
     *
     * @return array|AbstractCollection
     * @deprecated
     */
    public function fnArrayReverseOld(BaseTemplater $templater, bool &$escape, $array, bool $preserveKeys = true)
    {
        $error = "sv_array_reverse is deprecated use array_reverse instead";
        if (\XF::$debugMode)
        {
            trigger_error($error, E_USER_WARNING);
        }

        return $this->fnArrayReverse($templater, $escape, $array, $preserveKeys);
    }

    /**
     * @param BaseTemplater $templater
     * @param bool $escape
     * @param AbstractCollection|array $array
     * @param bool $preserveKeys
     *
     * @return array|AbstractCollection
     */
    public function fnArrayReverse(BaseTemplater $templater, bool &$escape, $array, bool $preserveKeys = true)
    {
        if ($array instanceof AbstractCollection)
        {
            return $array->reverse($preserveKeys);
        }
        else if (is_array($array))
        {
            return array_reverse($array, $preserveKeys);
        }

        return $array;
    }

    /**
     * @param BaseTemplater $templater
     * @param bool $escape
     * @param int $nowTimestamp
     * @param int $otherTimestamp
     * @param int $maximumDateParts
     * @param bool $countUp
     * @param string $class
     * @param string $triggerEvent
     * @param string $triggerEventOnSelector
     *
     * @return string|\XF\PreEscaped
     *
     * @throws \Exception
     */
    public function fnRelativeTimestamp(
        BaseTemplater $templater, bool &$escape, int $nowTimestamp, int $otherTimestamp,
        int $maximumDateParts = 0, bool $countUp = false, string $class = '', string $triggerEvent = '',
        string $triggerEventOnSelector = ''
    )
    {
        $escape = false;

        $timeStr = '';
        $repo = StandardLibHelper::repo();
        $interval = $repo->momentJsCompatibleTimeDiff($nowTimestamp, $otherTimestamp);

        if (isset($interval['invert']) && (!$countUp && !$interval['invert'] || $countUp && $interval['invert']))
        {
            $dateArr = $repo->buildRelativeDateString($interval, $maximumDateParts);
            if ($dateArr)
            {
                $timeStr = trim(implode(', ', $dateArr));
            }
        }
        if (!$timeStr)
        {
            $timeStr = \XF::language()->dateTime($otherTimestamp);
        }

        return $this->templater->renderMacro('public:svStandardLib_helper_macros', 'relative_timestamp', [
            'class' => $class,
            'countUp' => $countUp,
            'triggerEvent' => $triggerEvent,
            'triggerEventOnSelector' => $triggerEventOnSelector,
            'timeStr' => $timeStr,
            'otherTimestamp' => $otherTimestamp,
            'maximumDateParts' => $maximumDateParts
        ]);
    }

    /**
     * @param BaseTemplater    $templater
     * @param bool             $escape
     * @param string|float|int $value
     * @return string
     */
    public function fnAbs(BaseTemplater $templater, bool &$escape, $value): string
    {
        return (string)abs($value ?? 0);
    }

    public function fnParseLessFunc(BaseTemplater $templater, bool &$escape, string $value, bool $forceDebug = false): string
    {
        return $this->getCssRenderer()->parseLessColorFuncValue($value, $forceDebug) ?? '';
    }

    protected function getCssRenderer(): \SV\StandardLib\XF\CssRenderer
    {
        if ($this->cssRenderer === null)
        {
            $rendererClass = $this->app->extendClass('XF\CssRenderer');

            $renderer = new $rendererClass($this->app, $this->templater);
            assert($renderer instanceof \SV\StandardLib\XF\CssRenderer);
            $this->cssRenderer = $renderer;
        }

        $style = $this->getStyle();
        if ($style->getId() !== $this->cssRenderer->getStyleId())
        {
            $this->cssRenderer->setStyle($style);
        }

        return $this->cssRenderer;
    }

    protected function app(): \XF\App
    {
        return $this->app;
    }

    protected function em(): \XF\Mvc\Entity\Manager
    {
        return $this->app->em();
    }

    protected function repository(string $identifier): \XF\Mvc\Entity\Repository
    {
        return $this->app->repository($identifier);
    }
}