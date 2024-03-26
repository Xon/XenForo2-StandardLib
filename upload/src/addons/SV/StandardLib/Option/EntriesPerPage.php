<?php

namespace SV\StandardLib\Option;

use XF\Option\AbstractOption;
use function count;
use function is_int;
use function is_string;
use function sort;
use function strlen;
use function strval;

abstract class EntriesPerPage extends AbstractOption
{
    public static function renderOption(\XF\Entity\Option $option, array $htmlParams): string
    {
        $choices = [];
        foreach ($option->option_value AS $perPage)
        {
            $choices[] = [
                'value' => $perPage,
            ];
        }

        return self::getTemplate(
            'admin:option_template_svStandardLib_entriesPerPageChoices',
            $option,
            $htmlParams,
            [
                'choices' => $choices,
                'nextCounter' => count($choices)
            ]
        );
    }

    public static function verifyOption(array &$value): bool
    {
        $output = [];

        foreach ($value AS $perPage)
        {
            $value = is_int($perPage) ? $perPage : (int)$perPage['value'];
            if ($value === 0)
            {
                continue;
            }

            $output[] = $value;
        }

        sort($output);

        if (count($output) === 0)
        {
            $output = [25, 50];
        }

        $value = $output;

        return true;
    }
}
