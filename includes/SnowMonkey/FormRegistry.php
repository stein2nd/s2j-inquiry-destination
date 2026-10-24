<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\SnowMonkey;

use S2J\InquiryDestination\Options\FormOptions;

final class FormRegistry
{
    public static function isMappedForm(int $form_id): bool
    {
        if ($form_id < 1) {
            return false;
        }

        $forms = FormOptions::get();

        return isset($forms[ $form_id ]);
    }

    /**
     * @return array{inquiry_type: string, fields: array{name: string, email: string, phone: string, message: string}}|null
     */
    public static function getFormRow(int $form_id): ?array
    {
        $forms = FormOptions::get();

        return $forms[ $form_id ] ?? null;
    }
}
