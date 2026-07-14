<?php
/**
 * Connection settings (API key, default channel/level).
 *
 * @package Trigv
 */

declare(strict_types=1);

namespace Soderlind\Trigv;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes the plugin's connection settings.
 *
 * The API key is never returned to the browser; callers use {@see has_api_key()}
 * and {@see masked_key()} for display. A `TRIGV_API_KEY` constant, when defined,
 * overrides the stored option.
 */
final class Settings {

	private const OPTION = 'trigv_settings';

	private const LEVELS = array( 'info', 'success', 'warning', 'error' );

	/**
	 * Full raw settings array.
	 *
	 * @return array{api_key:string, default_channel:string, default_level:string}
	 */
	public function all(): array {
		$stored = get_option( self::OPTION, array() );

		return array(
			'api_key'         => is_array( $stored ) ? (string) ( $stored['api_key'] ?? '' ) : '',
			'default_channel' => is_array( $stored ) ? (string) ( $stored['default_channel'] ?? 'general' ) : 'general',
			'default_level'   => is_array( $stored ) ? (string) ( $stored['default_level'] ?? 'info' ) : 'info',
		);
	}

	/**
	 * The effective API key: constant override first, then stored option.
	 */
	public function get_api_key(): string {
		if ( defined( 'TRIGV_API_KEY' ) && is_string( TRIGV_API_KEY ) && '' !== TRIGV_API_KEY ) {
			return TRIGV_API_KEY;
		}
		return $this->all()['api_key'];
	}

	public function has_api_key(): bool {
		return '' !== $this->get_api_key();
	}

	public function is_key_from_constant(): bool {
		return defined( 'TRIGV_API_KEY' ) && is_string( TRIGV_API_KEY ) && '' !== TRIGV_API_KEY;
	}

	/**
	 * A safe, masked hint for the UI — never the full key.
	 */
	public function masked_key(): string {
		$key = $this->get_api_key();
		if ( '' === $key ) {
			return '';
		}
		$prefix = substr( $key, 0, 9 );
		return $prefix . '…';
	}

	public function default_channel(): string {
		return $this->all()['default_channel'];
	}

	public function default_level(): string {
		return $this->all()['default_level'];
	}

	/**
	 * Update settings. An empty `api_key` leaves the stored key unchanged.
	 *
	 * @param array<string,mixed> $input Raw input.
	 */
	public function update( array $input ): void {
		$current = $this->all();

		$api_key = isset( $input['api_key'] ) ? trim( (string) $input['api_key'] ) : '';
		if ( '' === $api_key ) {
			$api_key = $current['api_key'];
		}

		$channel = isset( $input['default_channel'] )
			? sanitize_text_field( (string) $input['default_channel'] )
			: $current['default_channel'];
		if ( '' === $channel ) {
			$channel = 'general';
		}

		$level = isset( $input['default_level'] ) ? (string) $input['default_level'] : $current['default_level'];
		if ( ! in_array( $level, self::LEVELS, true ) ) {
			$level = 'info';
		}

		update_option(
			self::OPTION,
			array(
				'api_key'         => $api_key,
				'default_channel' => $channel,
				'default_level'   => $level,
			),
			false
		);
	}
}
