<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use S2J\InquiryDestination\Options\DispatchOptions;
use S2J\InquiryDestination\Support\SaasConnectionProbe;
use WP_Error;

final class SaasConnectionProbeTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['s2j_test_wp_remote_get']);
        parent::tearDown();
    }

    public function test_backlog_success_on_http_200(): void
    {
        $GLOBALS['s2j_test_wp_remote_get'] = static function (string $url): array {
            self::assertStringContainsString('/api/v2/users/myself', $url);
            self::assertStringContainsString('apiKey=test-key', $url);

            return array(
                'response' => array(
                    'code' => 200,
                ),
            );
        };

        $result = SaasConnectionProbe::test(
            DispatchOptions::PRODUCT_BACKLOG,
            'https://example.backlog.com/',
            'test-key'
        );

        self::assertTrue($result['ok']);
    }

    public function test_asana_failure_on_http_401(): void
    {
        $GLOBALS['s2j_test_wp_remote_get'] = static function (string $url, array $args): array {
            self::assertSame('https://app.asana.com/api/1.0/users/me', $url);
            self::assertSame('Bearer bad-token', $args['headers']['Authorization'] ?? '');

            return array(
                'response' => array(
                    'code' => 401,
                ),
            );
        };

        $result = SaasConnectionProbe::test(
            DispatchOptions::PRODUCT_ASANA,
            '',
            'bad-token'
        );

        self::assertFalse($result['ok']);
        self::assertStringContainsString('401', $result['message']);
    }

    public function test_network_error(): void
    {
        $GLOBALS['s2j_test_wp_remote_get'] = static function (): WP_Error {
            return new WP_Error('http_request_failed', 'failed');
        };

        $result = SaasConnectionProbe::test(
            DispatchOptions::PRODUCT_BACKLOG,
            'https://example.backlog.com',
            'key'
        );

        self::assertFalse($result['ok']);
    }
}
