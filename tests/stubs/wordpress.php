<?php

declare(strict_types=1);

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( mixed $str ): string {
		return trim( strip_tags( (string) $str ) );
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( mixed $key ): string {
		$key = strtolower( (string) $key );

		return preg_replace( '/[^a-z0-9_\-]/', '', $key ) ?? '';
	}
}

if ( ! function_exists( 'sanitize_email' ) ) {
	function sanitize_email( mixed $email ): string {
		$sanitized = filter_var( trim( (string) $email ), FILTER_SANITIZE_EMAIL );

		return is_string( $sanitized ) ? $sanitized : '';
	}
}

if ( ! function_exists( 'is_email' ) ) {
	function is_email( mixed $email ): bool {
		return (bool) filter_var( $email, FILTER_VALIDATE_EMAIL );
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( string $option, mixed $default = false ): mixed {
		if ( isset( $GLOBALS['s2j_test_options'] ) && is_array( $GLOBALS['s2j_test_options'] ) && array_key_exists( $option, $GLOBALS['s2j_test_options'] ) ) {
			return $GLOBALS['s2j_test_options'][ $option ];
		}

		return $default;
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( string $url ): string {
		return trim( $url );
	}
}

if ( ! function_exists( '__' ) ) {
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

if ( ! function_exists( '_x' ) ) {
	function _x( string $text, string $context, string $domain = 'default' ): string {
		return $text;
	}
}

if ( ! class_exists( 'WP_Error', false ) ) {
	class WP_Error {
		public function __construct(
			public string $code = '',
			public string $message = ''
		) {
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( mixed $thing ): bool {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( 'wp_remote_get' ) ) {
	/**
	 * @param array<string, mixed> $args
	 *
	 * @return array<string, mixed>|WP_Error
	 */
	function wp_remote_get( string $url, array $args = array() ): array|WP_Error {
		if ( isset( $GLOBALS['s2j_test_wp_remote_get'] ) && is_callable( $GLOBALS['s2j_test_wp_remote_get'] ) ) {
			return ( $GLOBALS['s2j_test_wp_remote_get'] )( $url, $args );
		}

		return array(
			'response' => array(
				'code' => 200,
			),
		);
	}
}

if ( ! function_exists( 'wp_mail' ) ) {
	/**
	 * @param list<string>|string $to
	 * @param list<string>|string $headers
	 * @param list<string>        $attachments
	 */
	function wp_mail( $to, string $subject, string $message, $headers = '', array $attachments = array() ): bool {
		$GLOBALS['s2j_test_wp_mail_last'] = array(
			'to'          => $to,
			'subject'     => $subject,
			'message'     => $message,
			'headers'     => $headers,
			'attachments' => $attachments,
		);

		return true;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * @param mixed $data
	 */
	function wp_json_encode( $data, int $options = 0, int $depth = 512 ): string|false {
		return json_encode( $data, $options, $depth );
	}
}

if ( ! function_exists( 'error_log' ) ) {
	function error_log( string $message, int $message_type = 0, ?string $destination = null ): bool {
		unset( $message, $message_type, $destination );

		return true;
	}
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	/**
	 * @param array<string, mixed>|WP_Error $response
	 */
	function wp_remote_retrieve_response_code( array|WP_Error $response ): int {
		if ( is_wp_error( $response ) ) {
			return 0;
		}

		return (int) ( $response['response']['code'] ?? 0 );
	}
}
