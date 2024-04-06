<?php
/**
 * @noinspection PhpVariableVariableInspection
 * @noinspection PhpExpressionAlwaysNullInspection
 */

namespace SV\StandardLib;

use function get_class;

class BypassAccessStatus
{
    /**
     * @param object $obj
     * @param string $attribute
     * @param mixed  $context
     * @return \Closure
     */
    public function getPrivate(object $obj, string $attribute, $context = null) : \Closure
    {
        $getter = function () use ($attribute) { return $this->$attribute; };
        if (!$context)
        {
            $context = get_class($obj);
        }

        return \Closure::bind($getter, $obj, $context);
    }

    /**
     * @param object $obj
     * @param string $attribute
     * @param mixed  $context
     * @return \Closure
     */
    public function setPrivate(object $obj, string $attribute, $context = null) : \Closure
    {
        $setter = function ($value) use ($attribute) { $this->$attribute = $value; };
        if (!$context)
        {
            $context = get_class($obj);
        }

        return \Closure::bind($setter, $obj, $context);
    }

    /**
     * @param string|object $obj
     * @param string $attribute
     * @return \Closure
     */
    public function getStaticPrivate($obj, string $attribute) : \Closure
    {
        $getter = function () use ($attribute) {
            return static::$$attribute;
        };

        return \Closure::bind($getter, null, $obj);
    }

    /**
     * @param string|object $obj
     * @param string $attribute
     * @return \Closure
     */
    public function setStaticPrivate($obj, string $attribute) : \Closure
    {
        $setter = function ($value) use ($attribute) {
            static::$$attribute = $value;
        };

        return \Closure::bind($setter, null, $obj);
    }
}