<?php
/**
 * Uninstall: remove plugin options.
 *
 * @package S2J_Inquiry_Destination
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 's2j_inquiry_destination_dispatch' );
delete_option( 's2j_inquiry_destination_types' );
delete_option( 's2j_inquiry_destination_forms' );
delete_option( 's2j_inquiry_destination_saas' );
