<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\Tests\Unit\Dispatch;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use function S2J\InquiryDestinationService\build_dispatch_material;
use function S2J\InquiryDestinationService\validate_inquiry_dispatch;

final class ServiceIntegrationTest extends TestCase
{
	public function test_validate_and_build_mail_material(): void
	{
		$payload = array(
			'inquiry_type' => 'sales',
			'name'         => 'Ada Lovelace',
			'email'        => 'ada@example.com',
			'phone'        => '',
			'message'      => 'Hello',
			'subject_hint' => 'Sales inquiries',
			'locale'       => 'ja',
			'meta'         => array(),
		);

		$destination = array(
			'kind'            => 'mail',
			'inquiry_type'    => 'sales',
			'mail_to'         => array( 'team@example.com' ),
			'saas_endpoint'   => '',
			'saas_project_id' => '',
			'credentials'     => array(),
		);

		$validated = validate_inquiry_dispatch( $payload, $destination );
		Assert::assertSame( array(), $validated['deficiencies'] );

		$built = build_dispatch_material( $validated['payload'], $validated['destination'] );
		Assert::assertSame( array(), $built['deficiencies'] );
		Assert::assertSame( array( 'team@example.com' ), $built['material']['to'] );
		Assert::assertStringContainsString( 'sales', $built['material']['subject'] );
		Assert::assertSame( 'text/plain', $built['material']['content_type'] );
		Assert::assertSame(
			array( 'to', 'subject', 'body', 'content_type' ),
			array_keys( $built['material'] )
		);
	}

	public function test_custom_inquiry_type_slug(): void
	{
		$payload = array(
			'inquiry_type' => 'support',
			'name'         => 'Ada',
			'email'        => 'ada@example.com',
			'message'      => 'Hello',
			'subject_hint' => 'Support desk',
		);

		$destination = array(
			'kind'            => 'mail',
			'inquiry_type'    => 'support',
			'mail_to'         => array( 'help@example.com' ),
			'saas_endpoint'   => '',
			'saas_project_id' => '',
			'credentials'     => array(),
		);

		$validated = validate_inquiry_dispatch( $payload, $destination );
		Assert::assertSame( array(), $validated['deficiencies'] );
	}
}
