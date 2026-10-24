<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\Options;

final class FormOptions
{
    public const OPTION_KEY = 's2j_inquiry_destination_forms';

    /**
     * @return array<int, array{inquiry_type: string, fields: array{name: string, email: string, phone: string, message: string}}>
     */
    public static function get(): array
    {
        $stored = get_option(self::OPTION_KEY, array());
        if (! is_array($stored)) {
            return array();
        }

        $types = TypeOptions::get();
        $out   = array();

        foreach ($stored as $form_id => $row) {
            $form_id = (int) $form_id;
            if ($form_id < 1 || ! is_array($row)) {
                continue;
            }

            $inquiry_type = isset($row['inquiry_type']) && is_string($row['inquiry_type']) ? $row['inquiry_type'] : '';
            if (! isset($types[ $inquiry_type ])) {
                continue;
            }

            $fields = isset($row['fields']) && is_array($row['fields']) ? $row['fields'] : array();
            $out[ $form_id ] = array(
                'inquiry_type' => $inquiry_type,
                'fields'       => self::sanitizeFields($fields),
            );
        }

        return $out;
    }

    /**
     * @param array<int|string, mixed> $input
     *
     * @return array{saved: array<int, array{inquiry_type: string, fields: array{name: string, email: string, phone: string, message: string}}>, error: string|null}
     */
    public static function sanitizeFromPost(array $input): array
    {
        return self::saveFromAdminRequest($input, array(), null, array());
    }

    /**
     * @param array<int|string, mixed>     $postedForms
     * @param list<int>                    $removeFormIds
     * @param string|null                  $bulkInquiryType
     * @param list<int|string>             $bulkFormIds
     *
     * @return array{saved: array<int, array{inquiry_type: string, fields: array{name: string, email: string, phone: string, message: string}}>, error: string|null}
     */
    public static function saveFromAdminRequest(
        array $postedForms,
        array $removeFormIds,
        ?string $bulkInquiryType,
        array $bulkFormIds
    ): array {
        $types = TypeOptions::get();
        $out   = self::get();

        foreach ($removeFormIds as $form_id) {
            unset($out[ (int) $form_id ]);
        }

        if (null !== $bulkInquiryType && isset($types[ $bulkInquiryType ])) {
            foreach ($bulkFormIds as $form_id) {
                $form_id = (int) $form_id;
                if ($form_id < 1 || ! isset($out[ $form_id ])) {
                    continue;
                }
                $out[ $form_id ]['inquiry_type'] = $bulkInquiryType;
            }
        }

        foreach ($postedForms as $form_id => $row) {
            $form_id = (int) $form_id;
            if ($form_id < 1 || ! is_array($row)) {
                continue;
            }

            $inquiry_type = isset($row['inquiry_type']) && is_string($row['inquiry_type']) ? $row['inquiry_type'] : '';
            if (! isset($types[ $inquiry_type ])) {
                return array(
                    'saved' => array(),
                    'error' => __('Invalid inquiry type in form mapping.', 's2j-inquiry-destination'),
                );
            }

            $fields = isset($row['fields']) && is_array($row['fields']) ? $row['fields'] : array();
            $fields = self::sanitizeFields($fields);

            if ('' === $fields['name'] || '' === $fields['email'] || '' === $fields['message']) {
                unset($out[ $form_id ]);
                continue;
            }

            $out[ $form_id ] = array(
                'inquiry_type' => $inquiry_type,
                'fields'       => $fields,
            );
        }

        return array(
            'saved' => $out,
            'error' => null,
        );
    }

    public static function isMapped(int $form_id, array $savedForms): bool
    {
        return isset($savedForms[ $form_id ]);
    }

    /**
     * @param array<string, mixed> $fields
     *
     * @return array{name: string, email: string, phone: string, message: string}
     */
    public static function sanitizeFields(array $fields): array
    {
        return array(
            'name'    => isset($fields['name']) && is_string($fields['name']) ? sanitize_key($fields['name']) : '',
            'email'   => isset($fields['email']) && is_string($fields['email']) ? sanitize_key($fields['email']) : '',
            'phone'   => isset($fields['phone']) && is_string($fields['phone']) ? sanitize_key($fields['phone']) : '',
            'message' => isset($fields['message']) && is_string($fields['message']) ? sanitize_key($fields['message']) : '',
        );
    }
}
