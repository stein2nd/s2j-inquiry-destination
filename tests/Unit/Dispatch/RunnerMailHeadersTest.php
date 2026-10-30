<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\Tests\Unit\Dispatch;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use S2J\InquiryDestination\Dispatch\Runner;
use S2J\InquiryDestination\SnowMonkey\RequestContext;

final class RunnerMailHeadersTest extends TestCase
{
	protected function setUp(): void
	{
		unset( $GLOBALS['s2j_test_wp_mail_last'] );
		RequestContext::reset();
	}

	public function test_execute_mail_omits_from_header_d015(): void
	{
		$validated = array(
			'payload' => array(
				'email' => 'visitor@example.com',
			),
		);
		$material = array(
			'to'           => array( 'team@example.com' ),
			'subject'        => 'Test',
			'body'           => 'Body',
			'content_type'   => 'text/plain',
		);

		$this->invokeExecuteMail( $validated, $material );

		$last = $GLOBALS['s2j_test_wp_mail_last'] ?? null;
		Assert::assertIsArray( $last );
		$headers = $last['headers'];
		Assert::assertIsArray( $headers );

		foreach ( $headers as $header_line ) {
			Assert::assertIsString( $header_line );
			Assert::assertStringNotContainsString( 'From:', $header_line );
		}

		Assert::assertContains( 'Reply-To: visitor@example.com', $headers );
		Assert::assertContains( 'Content-Type: text/plain; charset=UTF-8', $headers );
	}

	public function test_execute_mail_skips_reply_to_when_email_invalid(): void
	{
		$validated = array(
			'payload' => array(
				'email' => 'not-an-email',
			),
		);
		$material = array(
			'to'         => array( 'team@example.com' ),
			'subject'    => 'Test',
			'body'       => 'Body',
			'content_type' => 'text/plain',
		);

		$this->invokeExecuteMail( $validated, $material );

		$headers = $GLOBALS['s2j_test_wp_mail_last']['headers'];
		Assert::assertIsArray( $headers );
		foreach ( $headers as $header_line ) {
			Assert::assertStringNotContainsString( 'Reply-To:', (string) $header_line );
		}
	}

	/**
	 * @param array<string, mixed> $validated
	 * @param array<string, mixed> $material
	 */
	private function invokeExecuteMail( array $validated, array $material ): void
	{
		$reflection = new ReflectionClass( Runner::class );
		$method = $reflection->getMethod( 'executeMail' );
		$method->invoke(
			null,
			'sid-test',
			'sales',
			42,
			'mail',
			$validated,
			$material,
			array()
		);
	}
}
