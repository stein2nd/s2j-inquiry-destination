<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\Tests\Unit\Options;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use S2J\InquiryDestination\Options\DispatchOptions;
use S2J\InquiryDestination\Support\SaasProductCatalog;

final class DispatchOptionsTest extends TestCase
{
	public function test_sanitize_accepts_saas_backlog(): void
	{
		$result = DispatchOptions::sanitizeFromPost(
			array(
				'channel'      => 'saas',
				'saas_product' => 'backlog',
			)
		);

		Assert::assertNull( $result['error'] );
		Assert::assertSame( DispatchOptions::CHANNEL_SAAS, $result['settings']['channel'] );
		Assert::assertSame( DispatchOptions::PRODUCT_BACKLOG, $result['settings']['saas_product'] );
	}

	public function test_destination_kind(): void
	{
		Assert::assertSame( 'saas_asana', DispatchOptions::destinationKind( DispatchOptions::PRODUCT_ASANA ) );
	}

	public function test_sanitize_rejects_unknown_saas_product_with_choice_phrase(): void
	{
		$result = DispatchOptions::sanitizeFromPost(
			array(
				'channel'      => 'saas',
				'saas_product' => 'unknown-vendor',
			)
		);

		Assert::assertNotNull( $result['error'] );
		Assert::assertStringContainsString( SaasProductCatalog::choicePhrase(), $result['error'] );
	}
}
