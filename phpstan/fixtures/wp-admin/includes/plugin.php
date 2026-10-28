<?php

declare(strict_types=1);

// PHPStan fixture: satisfies require_once ABSPATH . 'wp-admin/includes/plugin.php'.

if ( ! function_exists( 'is_plugin_active' ) ) {
	/**
	 * @param string $plugin Relative path under wp-content/plugins/.
	 */
	function is_plugin_active( string $plugin ): bool {
		unset( $plugin );

		return false;
	}
}
