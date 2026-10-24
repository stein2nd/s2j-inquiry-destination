<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\Options;

final class TypeOptions
{
    public const OPTION_KEY = 's2j_inquiry_destination_types';

    private const SLUG_PATTERN = '/^[a-z][a-z0-9_]{0,63}$/';

    /**
     * @return array<string, array{label: string, mail_to: list<string>, saas_project_id: string, issue_type_id: string, priority_id: string}>
     */
    public static function defaults(): array
    {
        return array(
            'sales'   => self::defaultRow(__('Sales inquiries', 's2j-inquiry-destination')),
            'recruit' => self::defaultRow(__('Recruiting', 's2j-inquiry-destination')),
        );
    }

    /**
     * @return array{label: string, mail_to: list<string>, saas_project_id: string, issue_type_id: string, priority_id: string}
     */
    private static function defaultRow(string $label): array
    {
        return array(
            'label'            => $label,
            'mail_to'          => array(),
            'saas_project_id'  => '',
            'issue_type_id'    => '',
            'priority_id'      => '',
        );
    }

    public static function isValidSlug(string $slug): bool
    {
        return (bool) preg_match(self::SLUG_PATTERN, $slug);
    }

    public static function isNewRowKey(string $key): bool
    {
        return '_new' === $key || str_starts_with($key, '_new_');
    }

    /**
     * @param array<mixed, mixed> $input
     * @param list<string>       $slugs
     *
     * @return array<mixed, mixed>
     */
    public static function markRemovedInInput(array $input, array $slugs): array
    {
        foreach ($slugs as $slug) {
            if (! self::isValidSlug($slug)) {
                continue;
            }
            if (! isset($input[ $slug ]) || ! is_array($input[ $slug ])) {
                $input[ $slug ] = array();
            }
            $input[ $slug ]['remove'] = '1';
        }

        return $input;
    }

    /**
     * @return array<string, array{label: string, mail_to: list<string>, saas_project_id: string, issue_type_id: string, priority_id: string}>
     */
    public static function get(): array
    {
        $stored = get_option(self::OPTION_KEY, null);
        if (! is_array($stored)) {
            return self::defaults();
        }

        $out = array();
        foreach ($stored as $type => $row) {
            if (! is_string($type) || ! self::isValidSlug($type) || ! is_array($row)) {
                continue;
            }

            $out[ $type ] = self::normalizeRow($type, $row);
        }

        if ($out === array()) {
            return self::defaults();
        }

        return $out;
    }

    /**
     * @param array<mixed, mixed> $row
     *
     * @return array{label: string, mail_to: list<string>, saas_project_id: string, issue_type_id: string, priority_id: string}
     */
    private static function normalizeRow(string $type, array $row): array
    {
        $label = isset($row['label']) && is_string($row['label'])
            ? sanitize_text_field($row['label'])
            : $type;

        return array(
            'label'           => $label,
            'mail_to'         => self::sanitizeMailToList($row['mail_to'] ?? array()),
            'saas_project_id' => self::sanitizeIdField($row['saas_project_id'] ?? ''),
            'issue_type_id'   => self::sanitizeIdField($row['issue_type_id'] ?? ''),
            'priority_id'     => self::sanitizeIdField($row['priority_id'] ?? ''),
        );
    }

    private static function sanitizeIdField(mixed $value): string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return '';
        }

        return sanitize_text_field(trim((string) $value));
    }

    /**
     * @param array<mixed, mixed> $input
     *
     * @return array{types: array<string, array{label: string, mail_to: list<string>, saas_project_id: string, issue_type_id: string, priority_id: string}>, error: string|null}
     */
    public static function sanitizeFromPost(array $input): array
    {
        $out = array();

        foreach ($input as $type_key => $row) {
            if (! is_string($type_key) || ! is_array($row) || self::isNewRowKey($type_key)) {
                continue;
            }

            if (! self::isValidSlug($type_key)) {
                return array(
                    'types' => array(),
                    'error' => __('Invalid inquiry type slug.', 's2j-inquiry-destination'),
                );
            }

            if (! empty($row['remove'])) {
                if (self::countFormMappings($type_key) > 0) {
                    return array(
                        'types' => array(),
                        'error' => sprintf(
                            /* translators: %s: inquiry type slug */
                            __('Cannot remove inquiry type "%s" while a form mapping still uses it.', 's2j-inquiry-destination'),
                            $type_key
                        ),
                    );
                }
                continue;
            }

            $label = isset($row['label'])
                ? sanitize_text_field((string) $row['label'])
                : $type_key;
            $out[ $type_key ] = self::rowFromPostInput($type_key, $row, $label);
        }

        foreach ($input as $type_key => $row) {
            if (! is_string($type_key) || ! self::isNewRowKey($type_key) || ! is_array($row)) {
                continue;
            }

            $new_slug = isset($row['slug']) ? sanitize_key((string) $row['slug']) : '';
            if ('' === $new_slug) {
                continue;
            }

            if (! self::isValidSlug($new_slug)) {
                return array(
                    'types' => array(),
                    'error' => __('New inquiry type slug must start with a letter and use lowercase letters, digits, and underscores only.', 's2j-inquiry-destination'),
                );
            }
            if (isset($out[ $new_slug ])) {
                return array(
                    'types' => array(),
                    'error' => __('That inquiry type slug already exists.', 's2j-inquiry-destination'),
                );
            }

            $label = isset($row['label'])
                ? sanitize_text_field((string) $row['label'])
                : $new_slug;
            $out[ $new_slug ] = self::rowFromPostInput($new_slug, $row, $label);
        }

        if ($out === array()) {
            return array(
                'types' => array(),
                'error' => __('At least one inquiry type is required.', 's2j-inquiry-destination'),
            );
        }

        return array(
            'types' => $out,
            'error' => null,
        );
    }

    public static function countFormMappings(string $inquiry_type): int
    {
        $forms = get_option(FormOptions::OPTION_KEY, array());
        if (! is_array($forms)) {
            return 0;
        }

        $count = 0;
        foreach ($forms as $row) {
            if (! is_array($row)) {
                continue;
            }
            $type = isset($row['inquiry_type']) && is_string($row['inquiry_type']) ? $row['inquiry_type'] : '';
            if ($type === $inquiry_type) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * @param mixed $value
     *
     * @return list<string>
     */
    public static function sanitizeMailToList($value): array
    {
        if (! is_array($value)) {
            return array();
        }

        $out = array();
        foreach ($value as $email) {
            if (! is_string($email)) {
                continue;
            }
            $email = sanitize_email(trim($email));
            if ('' !== $email && is_email($email)) {
                $out[] = $email;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * @return list<string>
     */
    public static function sanitizeMailToTextarea(string $textarea): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $textarea);
        if (! is_array($lines)) {
            return array();
        }

        $out = array();
        foreach ($lines as $line) {
            $email = sanitize_email(trim((string) $line));
            if ('' !== $email && is_email($email)) {
                $out[] = $email;
            }
        }

        return array_values(array_unique($out));
    }

    public static function hasValidMailTo(array $row): bool
    {
        return isset($row['mail_to']) && is_array($row['mail_to']) && count($row['mail_to']) > 0;
    }

    public static function hasValidSaasDestination(array $row, string $saas_product): bool
    {
        $project = isset($row['saas_project_id']) && is_string($row['saas_project_id'])
            ? trim($row['saas_project_id'])
            : '';
        if ('' === $project) {
            return false;
        }

        if (DispatchOptions::PRODUCT_BACKLOG === $saas_product) {
            $issue = isset($row['issue_type_id']) && is_string($row['issue_type_id'])
                ? trim($row['issue_type_id'])
                : '';
            $priority = isset($row['priority_id']) && is_string($row['priority_id'])
                ? trim($row['priority_id'])
                : '';

            return '' !== $issue && '' !== $priority;
        }

        return true;
    }

    /**
     * @param array<mixed, mixed> $row
     *
     * @return array{label: string, mail_to: list<string>, saas_project_id: string, issue_type_id: string, priority_id: string}
     */
    private static function rowFromPostInput(string $slug, array $row, string $label): array
    {
        $mail_raw = isset($row['mail_to']) ? (string) $row['mail_to'] : '';

        return array(
            'label'           => $label,
            'mail_to'         => self::sanitizeMailToTextarea($mail_raw),
            'saas_project_id' => self::sanitizeIdField($row['saas_project_id'] ?? ''),
            'issue_type_id'   => self::sanitizeIdField($row['issue_type_id'] ?? ''),
            'priority_id'     => self::sanitizeIdField($row['priority_id'] ?? ''),
        );
    }
}
