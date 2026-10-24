<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\Support;

final class ServiceGate
{
    public static function isAvailable(): bool
    {
        return function_exists('S2J\InquiryDestinationService\validate_inquiry_dispatch');
    }
}
