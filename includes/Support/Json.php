<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\Support;

final class Json
{
    /**
     * @return array<mixed, mixed>
     */
    public static function decodeAssoc(string $json): array
    {
        if ('' === $json) {
            return array();
        }

        $decoded = \json_decode($json, true);
        if (! \s2j_inquiry_destination_is_array($decoded)) {
            return array();
        }

        return $decoded;
    }
}
