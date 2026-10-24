<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\Options;

use S2J\InquiryDestination\Support\SaasProductCatalog;

final class DispatchOptions
{
    public const OPTION_KEY = 's2j_inquiry_destination_dispatch';

    public const CHANNEL_MAIL = 'mail';

    public const CHANNEL_SAAS = 'saas';

    public const PRODUCT_BACKLOG = 'backlog';

    public const PRODUCT_ASANA = 'asana';

    /**
     * @return array{channel: string, saas_product: string}
     */
    public static function get(): array
    {
        $stored = get_option(self::OPTION_KEY, null);
        if (! is_array($stored)) {
            return self::defaults();
        }

        $channel = isset($stored['channel']) && is_string($stored['channel'])
            ? $stored['channel']
            : self::CHANNEL_MAIL;
        if (self::CHANNEL_MAIL !== $channel && self::CHANNEL_SAAS !== $channel) {
            $channel = self::CHANNEL_MAIL;
        }

        $saas_product = isset($stored['saas_product']) && is_string($stored['saas_product'])
            ? sanitize_key($stored['saas_product'])
            : '';

        if (self::CHANNEL_SAAS === $channel && ! self::isValidProduct($saas_product)) {
            $saas_product = self::PRODUCT_BACKLOG;
        }

        if (self::CHANNEL_MAIL === $channel) {
            $saas_product = '';
        }

        return array(
            'channel'      => $channel,
            'saas_product' => $saas_product,
        );
    }

    /**
     * @return array{channel: string, saas_product: string}
     */
    public static function defaults(): array
    {
        return array(
            'channel'      => self::CHANNEL_MAIL,
            'saas_product' => '',
        );
    }

    public static function isValidProduct(string $product): bool
    {
        return SaasProductCatalog::isSupported($product);
    }

    public static function destinationKind(string $saas_product): string
    {
        return 'saas_' . $saas_product;
    }

    /**
     * @param array<mixed, mixed> $input
     *
     * @return array{settings: array{channel: string, saas_product: string}, error: string|null}
     */
    public static function sanitizeFromPost(array $input): array
    {
        $channel = isset($input['channel']) && is_string($input['channel'])
            ? sanitize_key($input['channel'])
            : self::CHANNEL_MAIL;
        if (self::CHANNEL_SAAS !== $channel) {
            $channel = self::CHANNEL_MAIL;
        }

        $saas_product = '';
        if (self::CHANNEL_SAAS === $channel) {
            $saas_product = isset($input['saas_product']) && is_string($input['saas_product'])
                ? sanitize_key($input['saas_product'])
                : '';
            if (! self::isValidProduct($saas_product)) {
                return array(
                    'settings' => self::defaults(),
                    'error'    => sprintf(
                        /* translators: %s: supported SaaS product names (e.g. "Backlog or Asana") */
                        __('Choose %s for SaaS dispatch.', 's2j-inquiry-destination'),
                        SaasProductCatalog::choicePhrase()
                    ),
                );
            }
        }

        return array(
            'settings' => array(
                'channel'      => $channel,
                'saas_product' => $saas_product,
            ),
            'error'      => null,
        );
    }
}
