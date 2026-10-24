<?php

declare(strict_types=1);

if (! function_exists('s2j_inquiry_destination_is_array')) {
    /**
     * @param mixed $value Value to test.
     */
    function s2j_inquiry_destination_is_array(mixed $value): bool
    {
        return is_array($value);
    }
}

if (! function_exists('s2j_inquiry_destination_text_matches_search')) {
    function s2j_inquiry_destination_text_matches_search(string $text, string $search): bool
    {
        if ('' === $search) {
            return true;
        }

        return false !== stripos($text, $search);
    }
}

if (! function_exists('s2j_inquiry_destination_count_label')) {
    function s2j_inquiry_destination_count_label(string $template, int $count): string
    {
        return str_replace('%d', (string) $count, $template);
    }
}

if (! function_exists('s2j_inquiry_destination_strcasecmp')) {
    function s2j_inquiry_destination_strcasecmp(string $left, string $right): int
    {
        return strcasecmp($left, $right);
    }
}
