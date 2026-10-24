<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\Tests\Unit\Options;

use PHPUnit\Framework\TestCase;
use S2J\InquiryDestination\Options\DispatchOptions;
use S2J\InquiryDestination\Options\SaasOptions;
use S2J\InquiryDestination\Support\SaasProductCatalog;

final class SaasOptionsTest extends TestCase
{
	protected function tearDown(): void
	{
		unset( $GLOBALS['s2j_test_options'] );
		parent::tearDown();
	}

	public function test_sanitize_from_post_ignores_credentials_when_channel_is_mail(): void
	{
		$result = SaasOptions::sanitizeFromPost(
			array(
				'api_key'  => 'secret-key',
				'endpoint' => 'https://example.com/api',
			),
			DispatchOptions::CHANNEL_MAIL
		);

		$this->assertNull( $result['error'] );
		$this->assertSame( array(), $result['saas'] );
	}

	public function test_resolve_for_connection_test_uses_post_over_stored_secrets_for_backlog(): void
	{
		$GLOBALS['s2j_test_options'] = array(
			SaasOptions::OPTION_KEY => array(
				'endpoint' => 'https://stored.backlog.com',
				'api_key'  => 'stored-key',
			),
		);

		$result = SaasOptions::resolveForConnectionTest(
			array(
				'endpoint' => 'https://post.backlog.com',
				'api_key'  => 'post-key',
			),
			DispatchOptions::PRODUCT_BACKLOG
		);

		$this->assertNull( $result['error'] );
		$this->assertSame( 'https://post.backlog.com', $result['endpoint'] );
		$this->assertSame( 'post-key', $result['secret'] );
	}

	public function test_resolve_for_connection_test_requires_backlog_endpoint(): void
	{
		$result = SaasOptions::resolveForConnectionTest( array(), DispatchOptions::PRODUCT_BACKLOG );

		$this->assertNotNull( $result['error'] );
		$this->assertSame( '', $result['endpoint'] );
	}

	public function test_resolve_for_connection_test_requires_backlog_api_key_when_endpoint_known(): void
	{
		$result = SaasOptions::resolveForConnectionTest(
			array(
				'endpoint' => 'https://example.backlog.com',
			),
			DispatchOptions::PRODUCT_BACKLOG
		);

		$this->assertNotNull( $result['error'] );
		$this->assertSame( 'https://example.backlog.com', $result['endpoint'] );
	}

	public function test_resolve_for_connection_test_accepts_asana_token_from_post(): void
	{
		$result = SaasOptions::resolveForConnectionTest(
			array(
				'access_token' => 'pat-token',
			),
			DispatchOptions::PRODUCT_ASANA
		);

		$this->assertNull( $result['error'] );
		$this->assertSame( 'pat-token', $result['secret'] );
	}

	public function test_resolve_for_connection_test_unknown_product_uses_choice_phrase(): void
	{
		$result = SaasOptions::resolveForConnectionTest( array(), 'unknown-vendor' );

		$this->assertNotNull( $result['error'] );
		$this->assertStringContainsString( SaasProductCatalog::choicePhrase(), $result['error'] );
	}
}
