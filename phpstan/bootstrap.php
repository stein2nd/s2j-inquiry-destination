<?php

declare(strict_types=1);

require dirname( __DIR__ ) . '/vendor/autoload.php';
require __DIR__ . '/stubs/snow-monkey-forms.php';

if ( ! function_exists( 'S2J\InquiryDestinationService\validate_inquiry_dispatch' ) ) {
	require __DIR__ . '/stubs/inquiry-destination-service.php';
}

if ( ! defined( 'S2J_INQUIRY_DESTINATION_PLUGIN_BASENAME' ) ) {
	define( 'S2J_INQUIRY_DESTINATION_PLUGIN_BASENAME', 's2j-inquiry-destination/s2j-inquiry-destination.php' );
}

if ( ! defined( 'S2J_INQUIRY_DESTINATION_VERSION' ) ) {
	define( 'S2J_INQUIRY_DESTINATION_VERSION', '0.0.0-dev' );
}

if ( ! defined( 'S2J_INQUIRY_DESTINATION_PLUGIN_URL' ) ) {
	define( 'S2J_INQUIRY_DESTINATION_PLUGIN_URL', 'https://example.test/wp-content/plugins/s2j-inquiry-destination/' );
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/phpstan/fixtures/' );
}
