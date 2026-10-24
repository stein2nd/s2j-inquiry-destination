<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use S2J\InquiryDestination\Options\DispatchOptions;
use S2J\InquiryDestination\Support\SaasProductCatalog;

final class SaasProductCatalogTest extends TestCase
{
	public function test_labels_include_supported_slugs(): void
	{
		$labels = SaasProductCatalog::labels();

		$this->assertArrayHasKey( DispatchOptions::PRODUCT_BACKLOG, $labels );
		$this->assertArrayHasKey( DispatchOptions::PRODUCT_ASANA, $labels );
	}

	public function test_is_supported(): void
	{
		$this->assertTrue( SaasProductCatalog::isSupported( DispatchOptions::PRODUCT_BACKLOG ) );
		$this->assertFalse( SaasProductCatalog::isSupported( 'unknown' ) );
	}

	public function test_choice_phrase_joins_supported_product_labels(): void
	{
		$phrase = SaasProductCatalog::choicePhrase();

		$this->assertStringContainsString( 'Backlog', $phrase );
		$this->assertStringContainsString( 'Asana', $phrase );
		$this->assertStringContainsString( ' or ', $phrase );
	}
}
