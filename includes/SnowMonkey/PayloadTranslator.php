<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\SnowMonkey;

use S2J\InquiryDestination\Options\TypeOptions;
use Snow_Monkey\Plugin\Forms\App\Model\Responser;
use Snow_Monkey\Plugin\Forms\App\Model\Setting;

final class PayloadTranslator
{
    /**
     * @param array{inquiry_type: string, fields: array{name: string, email: string, phone: string, message: string}} $form_row
     *
     * @return array<string, mixed>
     */
    public static function buildRawPayload(Responser $responser, Setting $setting, array $form_row): array
    {
        $fields = $form_row['fields'];
        $types  = TypeOptions::get();
        $type   = $form_row['inquiry_type'];
        $label  = isset($types[ $type ]['label']) ? (string) $types[ $type ]['label'] : $type;

        $name    = self::fieldValue($responser, $fields['name']);
        $email   = self::fieldValue($responser, $fields['email']);
        $phone   = '' !== $fields['phone'] ? self::fieldValue($responser, $fields['phone']) : '';
        $message = self::fieldValue($responser, $fields['message']);

        $message = self::appendUnmappedControls($message, $responser, $setting, $fields);

        return array(
            'inquiry_type' => $type,
            'name'         => $name,
            'email'        => $email,
            'phone'        => $phone,
            'message'      => $message,
            'subject_hint' => $label,
            'locale'       => determine_locale(),
            'meta'         => array(),
        );
    }

    private static function fieldValue(Responser $responser, string $name): string
    {
        if ('' === $name) {
            return '';
        }

        $value = $responser->get($name);

        return is_string($value) ? trim($value) : '';
    }

    /**
     * @param array{name: string, email: string, phone: string, message: string} $mapped
     */
    private static function appendUnmappedControls(string $message, Responser $responser, Setting $setting, array $mapped): string
    {
        $mapped_names = array_filter(
            array(
                $mapped['name'],
                $mapped['email'],
                $mapped['phone'],
                $mapped['message'],
            ),
            static fn(string $n): bool => '' !== $n
        );

        $lines = array();
        foreach ($setting->get_controls() as $control) {
            if (! method_exists($control, 'get')) {
                continue;
            }
            $name = $control->get('name');
            if (! is_string($name) || '' === $name || in_array($name, $mapped_names, true)) {
                continue;
            }
            $value = $responser->get($name);
            if (! is_string($value) || '' === trim($value)) {
                continue;
            }
            $lines[] = $name . ': ' . trim($value);
        }

        if ($lines === array()) {
            return $message;
        }

        $append = implode("\n", $lines);

        if ('' === trim($message)) {
            return "\n\n" . $append;
        }

        return $message . "\n\n" . $append;
    }
}
