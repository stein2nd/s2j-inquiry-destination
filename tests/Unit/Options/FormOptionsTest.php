<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\Tests\Unit\Options;

use PHPUnit\Framework\TestCase;
use S2J\InquiryDestination\Options\FormOptions;

final class FormOptionsTest extends TestCase
{
	protected function setUp(): void
	{
		$GLOBALS['s2j_test_options'] = array();
	}

	protected function tearDown(): void
	{
		unset( $GLOBALS['s2j_test_options'] );
	}

	public function test_sanitize_from_post_rejects_unknown_inquiry_type(): void
	{
		$result = FormOptions::sanitizeFromPost(
			array(
				42 => array(
					'inquiry_type' => 'not_in_types_option',
					'fields'       => array(
						'name'    => 'your-name',
						'email'   => 'your-email',
						'phone'   => 'your-phone',
						'message' => 'your-message',
					),
				),
			)
		);

		$this->assertSame( 'Invalid inquiry type in form mapping.', $result['error'] );
		$this->assertSame( array(), $result['saved'] );
	}

	public function test_sanitize_from_post_accepts_known_inquiry_type(): void
	{
		$result = FormOptions::sanitizeFromPost(
			array(
				42 => array(
					'inquiry_type' => 'sales',
					'fields'       => array(
						'name'    => 'your-name',
						'email'   => 'your-email',
						'phone'   => '',
						'message' => 'your-message',
					),
				),
			)
		);

		$this->assertNull( $result['error'] );
		$this->assertArrayHasKey( 42, $result['saved'] );
		$this->assertSame( 'sales', $result['saved'][42]['inquiry_type'] );
	}
}
