<?php

declare(strict_types=1);

namespace S2J\InquiryDestination\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use S2J\InquiryDestination\Support\ServiceGate;

final class ServiceGateTest extends TestCase
{
	public function test_service_package_is_available_when_vendor_installed(): void
	{
		$this->assertTrue( ServiceGate::isAvailable() );
	}
}
