<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\Support;

use S2J\InquiryDestination\Options\DispatchOptions;

final class SaasConnectionProbe
{
    private const TIMEOUT_SECONDS = 15;

    /**
     * @return array{ok: bool, message: string}
     */
    public static function test(string $saas_product, string $endpoint, string $secret): array
    {
        if (DispatchOptions::PRODUCT_BACKLOG === $saas_product) {
            return self::testBacklog($endpoint, $secret);
        }

        if (DispatchOptions::PRODUCT_ASANA === $saas_product) {
            return self::testAsana($endpoint, $secret);
        }

        return array(
            'ok'      => false,
            'message' => __('Connection test is not available for this product.', 's2j-inquiry-destination'),
        );
    }

    /**
     * @return array{ok: bool, message: string}
     */
    private static function testBacklog(string $endpoint, string $api_key): array
    {
        $base = rtrim($endpoint, '/');
        $url  = $base . '/api/v2/users/myself?' . http_build_query(
            array(
                'apiKey' => $api_key,
            ),
            '',
            '&',
            PHP_QUERY_RFC3986
        );

        return self::requestAndInterpret($url, array());
    }

    /**
     * @return array{ok: bool, message: string}
     */
    private static function testAsana(string $endpoint, string $access_token): array
    {
        $base = '' !== $endpoint ? $endpoint : 'https://app.asana.com';
        $base = rtrim($base, '/');
        $url  = $base . '/api/1.0/users/me';

        return self::requestAndInterpret(
            $url,
            array(
                'Authorization' => 'Bearer ' . $access_token,
            )
        );
    }

    /**
     * @param array<string, string> $headers
     *
     * @return array{ok: bool, message: string}
     */
    private static function requestAndInterpret(string $url, array $headers): array
    {
        $response = wp_remote_get(
            $url,
            array(
                'timeout' => self::TIMEOUT_SECONDS,
                'headers' => $headers,
            )
        );

        if (is_wp_error($response)) {
            return array(
                'ok'      => false,
                'message' => __('Could not reach the SaaS API. Check the URL and network connectivity.', 's2j-inquiry-destination'),
            );
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if (200 === $code) {
            return array(
                'ok'      => true,
                'message' => __('Connection verified successfully.', 's2j-inquiry-destination'),
            );
        }

        return array(
            'ok'      => false,
            'message' => sprintf(
                /* translators: %d: HTTP status code */
                __('Connection failed (HTTP %d). Check credentials and the service URL.', 's2j-inquiry-destination'),
                $code
            ),
        );
    }
}
