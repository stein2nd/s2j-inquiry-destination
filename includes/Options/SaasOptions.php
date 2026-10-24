<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\Options;

use S2J\InquiryDestination\Support\SaasProductCatalog;

final class SaasOptions
{
    public const OPTION_KEY = 's2j_inquiry_destination_saas';

    public static function isConfiguredViaServer(string $saas_product = ''): bool
    {
        if (DispatchOptions::PRODUCT_ASANA === $saas_product) {
            return self::serverAsanaToken() !== '';
        }

        return self::serverBacklogApiKey() !== '';
    }

    public static function hasStoredApiKey(): bool
    {
        $stored = self::getStored();

        return '' !== ( $stored['api_key'] ?? '' );
    }

    public static function hasStoredAccessToken(): bool
    {
        $stored = self::getStored();

        return '' !== ( $stored['access_token'] ?? '' );
    }

    public static function isApiKeyConfigured(): bool
    {
        return self::serverBacklogApiKey() !== '' || self::hasStoredApiKey();
    }

    public static function isAccessTokenConfigured(): bool
    {
        return self::serverAsanaToken() !== '' || self::hasStoredAccessToken();
    }

    /**
     * @param array<mixed, mixed> $input
     *
     * @return array{saas: array<string, string>, error: string|null}
     */
    public static function sanitizeFromPost(array $input, string $channel = DispatchOptions::CHANNEL_MAIL, string $saas_product = ''): array
    {
        if (DispatchOptions::CHANNEL_MAIL === $channel) {
            return array(
                'saas'  => self::getStored(),
                'error' => null,
            );
        }

        if (self::isConfiguredViaServer($saas_product)) {
            return array(
                'saas'  => self::getStored(),
                'error' => null,
            );
        }

        $stored = self::getStored();

        if (DispatchOptions::PRODUCT_BACKLOG === $saas_product) {
            $api_key = isset($input['api_key']) && is_string($input['api_key'])
                ? trim($input['api_key'])
                : '';
            if ('' !== $api_key) {
                $stored['api_key'] = sanitize_text_field($api_key);
            }

            $endpoint = isset($input['endpoint']) && is_string($input['endpoint'])
                ? esc_url_raw(trim($input['endpoint']))
                : '';
            if ('' !== $endpoint) {
                $stored['endpoint'] = $endpoint;
            }
        }

        if (DispatchOptions::PRODUCT_ASANA === $saas_product) {
            $token = isset($input['access_token']) && is_string($input['access_token'])
                ? trim($input['access_token'])
                : '';
            if ('' !== $token) {
                $stored['access_token'] = sanitize_text_field($token);
            }

            $endpoint = isset($input['endpoint']) && is_string($input['endpoint'])
                ? esc_url_raw(trim($input['endpoint']))
                : '';
            if ('' !== $endpoint) {
                $stored['endpoint'] = $endpoint;
            }
        }

        return array(
            'saas'  => $stored,
            'error' => null,
        );
    }

    /**
     * Effective credentials for a connection test (POST values merged with stored / server).
     *
     * @param array<mixed, mixed> $input
     *
     * @return array{endpoint: string, secret: string, error: string|null}
     */
    public static function resolveForConnectionTest(array $input, string $saas_product): array
    {
        if (self::isConfiguredViaServer($saas_product)) {
            $endpoint = self::runtimeEndpoint($saas_product);
            $creds    = self::runtimeCredentials($saas_product);
            $secret   = DispatchOptions::PRODUCT_ASANA === $saas_product
                ? ( $creds['access_token'] ?? '' )
                : ( $creds['api_key'] ?? '' );

            if ('' === $secret) {
                return array(
                    'endpoint' => $endpoint,
                    'secret'   => '',
                    'error'    => __('SaaS credentials are not configured on the server.', 's2j-inquiry-destination'),
                );
            }

            if (DispatchOptions::PRODUCT_BACKLOG === $saas_product && '' === $endpoint) {
                return array(
                    'endpoint' => '',
                    'secret'   => $secret,
                    'error'    => __('Backlog space URL is required.', 's2j-inquiry-destination'),
                );
            }

            return array(
                'endpoint' => $endpoint,
                'secret'   => $secret,
                'error'    => null,
            );
        }

        $stored = self::getStored();

        if (DispatchOptions::PRODUCT_BACKLOG === $saas_product) {
            $endpoint = isset($input['endpoint']) && is_string($input['endpoint'])
                ? esc_url_raw(trim($input['endpoint']))
                : '';
            if ('' === $endpoint) {
                $endpoint = $stored['endpoint'] ?? '';
            }

            $api_key = isset($input['api_key']) && is_string($input['api_key'])
                ? trim($input['api_key'])
                : '';
            if ('' === $api_key) {
                $api_key = $stored['api_key'] ?? '';
            }

            if ('' === $endpoint) {
                return array(
                    'endpoint' => '',
                    'secret'   => '',
                    'error'    => __('Enter the Backlog space URL before testing the connection.', 's2j-inquiry-destination'),
                );
            }

            if ('' === $api_key) {
                return array(
                    'endpoint' => $endpoint,
                    'secret'   => '',
                    'error'    => __('Enter the API key before testing the connection.', 's2j-inquiry-destination'),
                );
            }

            return array(
                'endpoint' => $endpoint,
                'secret'   => sanitize_text_field($api_key),
                'error'    => null,
            );
        }

        if (DispatchOptions::PRODUCT_ASANA === $saas_product) {
            $token = isset($input['access_token']) && is_string($input['access_token'])
                ? trim($input['access_token'])
                : '';
            if ('' === $token) {
                $token = $stored['access_token'] ?? '';
            }

            if ('' === $token) {
                return array(
                    'endpoint' => '',
                    'secret'   => '',
                    'error'    => __('Enter the personal access token before testing the connection.', 's2j-inquiry-destination'),
                );
            }

            $endpoint = isset($input['endpoint']) && is_string($input['endpoint'])
                ? esc_url_raw(trim($input['endpoint']))
                : '';
            if ('' === $endpoint) {
                $endpoint = $stored['endpoint'] ?? '';
            }

            return array(
                'endpoint' => $endpoint,
                'secret'   => sanitize_text_field($token),
                'error'    => null,
            );
        }

        return array(
            'endpoint' => '',
            'secret'   => '',
            'error'    => sprintf(
                /* translators: %s: supported SaaS product names (e.g. "Backlog or Asana") */
                __('Select %s before testing the connection.', 's2j-inquiry-destination'),
                SaasProductCatalog::choicePhrase()
            ),
        );
    }

    /**
     * @return array<string, string>
     */
    public static function runtimeCredentials(string $saas_product): array
    {
        if (DispatchOptions::PRODUCT_ASANA === $saas_product) {
            $token = self::serverAsanaToken();
            if ('' === $token) {
                $stored = self::getStored();
                $token  = $stored['access_token'] ?? '';
            }

            return array(
                'access_token' => $token,
            );
        }

        $api_key = self::serverBacklogApiKey();
        if ('' === $api_key) {
            $stored  = self::getStored();
            $api_key = $stored['api_key'] ?? '';
        }

        return array(
            'api_key' => $api_key,
        );
    }

    public static function runtimeEndpoint(string $saas_product): string
    {
        if (DispatchOptions::PRODUCT_ASANA === $saas_product) {
            $url = self::serverAsanaEndpoint();
            if ('' !== $url) {
                return $url;
            }
            $stored = self::getStored();

            return $stored['endpoint'] ?? '';
        }

        $url = self::serverBacklogSpaceUrl();
        if ('' !== $url) {
            return $url;
        }
        $stored = self::getStored();

        return $stored['endpoint'] ?? '';
    }

    /**
     * @return array<string, string>
     */
    public static function getStored(): array
    {
        $stored = get_option(self::OPTION_KEY, array());
        if (! is_array($stored)) {
            return array();
        }

        $out = array();
        foreach ($stored as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $out[ $key ] = $value;
            }
        }

        return $out;
    }

    private static function serverBacklogApiKey(): string
    {
        if (defined('S2J_INQUIRY_DESTINATION_BACKLOG_API_KEY')) {
            $key = constant('S2J_INQUIRY_DESTINATION_BACKLOG_API_KEY');
            if (is_string($key) && '' !== $key) {
                return $key;
            }
        }

        if (defined('S2J_INQUIRY_DESTINATION_SAAS_API_KEY')) {
            $key = constant('S2J_INQUIRY_DESTINATION_SAAS_API_KEY');
            if (is_string($key) && '' !== $key) {
                return $key;
            }
        }

        $env = getenv('S2J_INQUIRY_DESTINATION_BACKLOG_API_KEY');
        if (is_string($env) && '' !== $env) {
            return $env;
        }

        $legacy = getenv('S2J_INQUIRY_DESTINATION_SAAS_API_KEY');
        if (is_string($legacy) && '' !== $legacy) {
            return $legacy;
        }

        return '';
    }

    private static function serverBacklogSpaceUrl(): string
    {
        if (defined('S2J_INQUIRY_DESTINATION_BACKLOG_SPACE_URL')) {
            $url = constant('S2J_INQUIRY_DESTINATION_BACKLOG_SPACE_URL');
            if (is_string($url) && '' !== $url) {
                return $url;
            }
        }

        $env = getenv('S2J_INQUIRY_DESTINATION_BACKLOG_SPACE_URL');
        if (is_string($env) && '' !== $env) {
            return $env;
        }

        return '';
    }

    private static function serverAsanaToken(): string
    {
        if (defined('S2J_INQUIRY_DESTINATION_ASANA_ACCESS_TOKEN')) {
            $token = constant('S2J_INQUIRY_DESTINATION_ASANA_ACCESS_TOKEN');
            if (is_string($token) && '' !== $token) {
                return $token;
            }
        }

        $env = getenv('S2J_INQUIRY_DESTINATION_ASANA_ACCESS_TOKEN');
        if (is_string($env) && '' !== $env) {
            return $env;
        }

        return '';
    }

    private static function serverAsanaEndpoint(): string
    {
        if (defined('S2J_INQUIRY_DESTINATION_ASANA_API_BASE')) {
            $url = constant('S2J_INQUIRY_DESTINATION_ASANA_API_BASE');
            if (is_string($url) && '' !== $url) {
                return $url;
            }
        }

        return '';
    }
}
