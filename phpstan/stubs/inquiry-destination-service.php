<?php

declare(strict_types=1);

namespace S2J\InquiryDestinationService;

/**
 * Stubs until Packagist install; remove when vendor provides real functions.
 */

/** @param array<string, mixed> $payload @return array<string, mixed> */
function normalize_inquiry_payload( array $payload ): array {
	return $payload;
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, mixed> $destination
 *
 * @return array{payload: array<string, mixed>, destination: array<string, mixed>, deficiencies: list<string>}
 */
function validate_inquiry_dispatch( array $payload, array $destination ): array {
	unset( $payload, $destination );
	return array(
		'payload'      => array(),
		'destination'  => array(),
		'deficiencies' => array(),
	);
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, mixed> $destination
 *
 * @return array{material: array<string, mixed>|null, deficiencies: list<string>}
 */
function build_dispatch_material( array $payload, array $destination ): array {
	unset( $payload, $destination );
	return array(
		'material'     => null,
		'deficiencies' => array(),
	);
}

/**
 * @param array<string, mixed> $context
 *
 * @return array<string, mixed>
 */
function map_dispatch_result( string $kind, mixed $execution_outcome, array $context = array() ): array {
	unset( $kind, $execution_outcome, $context );
	return array();
}
