<?php

namespace SV\StandardLib\XF\Entity;

use function floatval;
use function strval;

/**
 * @extends \XF\Entity\Option
 */
class Option extends XFCP_Option
{
    public function getOptionValue()
    {
        // XF bug: https://xenforo.com/community/threads/xf-options-do-not-round-trip-integer-boolean-values-as-expected.229806/
        // int/bool types are not cast as expected, which is then cached into \XF::options()
        if ($this->isDataTypeNumeric())
        {
            /** @noinspection PhpWrongStringConcatenationInspection */
            return strval(floatval($this->option_value_)) + 0;
        }
        else if ($this->data_type === 'bool')
        {
            return (bool)$this->option_value_;
        }

        return parent::getOptionValue();
    }

    public function getDefaultValue()
    {
        // XF bug: https://xenforo.com/community/threads/xf-options-do-not-round-trip-integer-boolean-values-as-expected.229806/
        // int/bool types are not cast as expected, which is then cached into \XF::options()
        if ($this->isDataTypeNumeric())
        {
            return (int)$this->default_value_;
        }
        else if ($this->data_type === 'bool')
        {
            return (bool)$this->default_value_;
        }

        return parent::getDefaultValue();
    }
}