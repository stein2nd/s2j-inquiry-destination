<?php
/**
 * S2J Inquiry Destination
 *
 * @package S2J_Inquiry_Destination
 * @author Koutarou ISHIKAWA
 * @license GPL-3.0-or-later
 *
 * Plugin Name: S2J Inquiry Destination
 * Plugin URI: https://github.com/stein2nd/s2j-inquiry-destination
 * Description: Route Snow Monkey Forms administrator mail through inquiry-type destinations (mail or inquiry-management SaaS).
 * Version: 0.2.0
 * Author: Koutarou ISHIKAWA
 * Author URI: https://stein2nd.wordpress.com
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: s2j-inquiry-destination
 * Domain Path: /languages
 * Requires at least: 6.3
 * Requires PHP: 8.3
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'S2J_INQUIRY_DESTINATION_VERSION', '0.2.0' );
define( 'S2J_INQUIRY_DESTINATION_PLUGIN_FILE', __FILE__ );
define( 'S2J_INQUIRY_DESTINATION_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'S2J_INQUIRY_DESTINATION_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'S2J_INQUIRY_DESTINATION_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

$autoload = S2J_INQUIRY_DESTINATION_PLUGIN_DIR . 'vendor/autoload.php';
if ( ! file_exists( $autoload ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'S2J Inquiry Destination: run composer install in the plugin directory.', 's2j-inquiry-destination' );
			echo '</p></div>';
		}
	);
	return;
}

require_once $autoload;

S2J\InquiryDestination\Plugin::init();
