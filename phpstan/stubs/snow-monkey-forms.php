<?php

declare(strict_types=1);

namespace Snow_Monkey\Plugin\Forms\App\Model;

class Setting
{
	/** @param mixed $key */
	public function get( $key ) {
		return '';
	}

	/** @return list<object> */
	public function get_controls(): array {
		return array();
	}
}

class Responser
{
	/** @param mixed $name */
	public function get( $name ) {
		return '';
	}
}

class MailParser
{
	public function __construct( Responser $responser, Setting $setting ) {
		unset( $responser, $setting );
	}

	/** @param mixed $body */
	public function get_attachments( $body ): array {
		unset( $body );
		return array();
	}
}
