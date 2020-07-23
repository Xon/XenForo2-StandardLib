<?php

namespace SV\StandardLib;

class BypassAccessStatus
{
    public function getPrivate($obj, $attribute, $context = null)
    {
        $getter = function () use ($attribute) { return $this->$attribute; };
        if (!$context)
        {
            $context = get_class($obj);
        }

        return \Closure::bind($getter, $obj, $context);
    }

    public function setPrivate($obj, $attribute, $context = null)
    {
        $setter = function ($value) use ($attribute) { $this->$attribute = $value; };
        if (!$context)
        {
            $context = get_class($obj);
        }

        return \Closure::bind($setter, $obj, $context);
    }

    public function getStaticPrivate($obj, $attribute)
    {
        $getter = function () use ($attribute) {
            /** @noinspection PhpUndefinedFieldInspection */
            return static::$$attribute;
        };

        return \Closure::bind($getter, null, $obj);
    }

    public function setStaticPrivate($obj, $attribute)
    {
        $setter = function ($value) use ($attribute) {
            /** @noinspection PhpUndefinedFieldInspection */
            static::$$attribute = $value;
        };

        return \Closure::bind($setter, null, $obj);
    }
}