<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\Tests\Unit\Dispatch;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use S2J\InquiryDestination\Dispatch\DestinationBuilder;
use S2J\InquiryDestination\Options\DispatchOptions;
use S2J\InquiryDestination\Options\SaasOptions;

final class DestinationBuilderTest extends TestCase
{
	protected function setUp(): void
	{
		$GLOBALS['s2j_test_options'] = array(
			DispatchOptions::OPTION_KEY => array(
				'channel'      => DispatchOptions::CHANNEL_SAAS,
				'saas_product' => DispatchOptions::PRODUCT_BACKLOG,
			),
			SaasOptions::OPTION_KEY     => array(
				'endpoint' => 'https://example.backlog.com',
				'api_key'  => 'secret',
			),
		);
	}

	protected function tearDown(): void
	{
		unset( $GLOBALS['s2j_test_options'] );
	}

	public function test_builds_backlog_destination(): void
	{
		$row = array(
			'label'           => 'Sales',
			'mail_to'         => array(),
			'saas_project_id' => '42',
			'issue_type_id'   => '1',
			'priority_id'     => '2',
		);

		$destination = DestinationBuilder::build( 'sales', $row );

		Assert::assertSame( 'saas_backlog', $destination['kind'] );
		Assert::assertSame( '42', $destination['saas_project_id'] );
		Assert::assertSame( 'secret', $destination['credentials']['api_key'] );
		Assert::assertSame( '1', $destination['credentials']['issue_type_id'] );
	}
}
