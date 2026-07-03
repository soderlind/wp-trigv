<?php
/**
 * Trigger Config — the admin's per-Trigger configuration store.
 *
 * @package Trigv
 */

declare(strict_types=1);

namespace Trigv;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes per-Trigger configuration (enabled, channel, level,
 * templates, urgency) in a non-autoloaded option, merged over each Trigger's
 * defaults from the {@see TriggerCatalog}.
 */
final class TriggerConfig {

	private const OPTION = 'trigv_trigger_settings';

	private const LEVELS = array( 'info', 'success', 'warning', 'error' );

	public function __construct(
		private readonly TriggerCatalog $catalog,
	) {
	}

	/**
	 * All stored per-Trigger configuration.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function all(): array {
		$stored = get_option( self::OPTION, array() );
		return is_array( $stored ) ? $stored : array();
	}

	/**
	 * Effective configuration for a Trigger, merged over its defaults.
	 *
	 * @return array{enabled:bool,channel:string,level:string,title:string,description:string,time_sensitive:bool}
	 */
	public function for_trigger( string $id ): array {
		$trigger = $this->catalog->get( $id );
		$stored  = $this->all()[ $id ] ?? array();

		return array(
			'enabled'        => (bool) ( $stored['enabled'] ?? false ),
			'channel'        => (string) ( $stored['channel'] ?? '' ),
			'level'          => (string) ( $stored['level'] ?? ( $trigger?->default_level ?? 'info' ) ),
			'title'          => (string) ( $stored['title'] ?? '' ),
			'description'    => (string) ( $stored['description'] ?? '' ),
			'time_sensitive' => (bool) ( $stored['time_sensitive'] ?? false ),
		);
	}

	public function is_enabled( string $id ): bool {
		return $this->for_trigger( $id )['enabled'];
	}

	/**
	 * Persist configuration for known Triggers only.
	 *
	 * @param array<string,array<string,mixed>> $input Raw config keyed by Trigger id.
	 */
	public function update( array $input ): void {
		$known = $this->catalog->all();
		$clean = array();

		foreach ( $input as $id => $config ) {
			if ( ! isset( $known[ $id ] ) || ! is_array( $config ) ) {
				continue;
			}

			$level = (string) ( $config['level'] ?? '' );
			if ( ! in_array( $level, self::LEVELS, true ) ) {
				$level = $known[ $id ]->default_level;
			}

			$clean[ $id ] = array(
				'enabled'        => ! empty( $config['enabled'] ),
				'channel'        => sanitize_text_field( (string) ( $config['channel'] ?? '' ) ),
				'level'          => $level,
				'title'          => sanitize_text_field( (string) ( $config['title'] ?? '' ) ),
				'description'    => sanitize_text_field( (string) ( $config['description'] ?? '' ) ),
				'time_sensitive' => ! empty( $config['time_sensitive'] ),
			);
		}

		update_option( self::OPTION, $clean, false );
	}
}
