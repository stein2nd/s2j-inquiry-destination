<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\Tests\Unit\Options;

use PHPUnit\Framework\TestCase;
use S2J\InquiryDestination\Options\TypeOptions;

final class TypeOptionsTest extends TestCase
{
	public function test_valid_slug(): void
	{
		$this->assertTrue( TypeOptions::isValidSlug( 'support' ) );
		$this->assertFalse( TypeOptions::isValidSlug( 'Support' ) );
		$this->assertFalse( TypeOptions::isValidSlug( '1bad' ) );
	}

	public function test_sanitize_from_post_adds_custom_type(): void
	{
		$result = TypeOptions::sanitizeFromPost(
			array(
				'sales' => array(
					'label'   => 'Sales',
					'mail_to' => "team@example.com\n",
				),
				'_new'  => array(
					'slug'    => 'support',
					'label'   => 'Support',
					'mail_to' => '',
				),
			)
		);

		$this->assertNull( $result['error'] );
		$this->assertArrayHasKey( 'support', $result['types'] );
		$this->assertSame( 'Support', $result['types']['support']['label'] );
	}
}
