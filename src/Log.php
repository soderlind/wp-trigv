<?php
/**
 * Dispatch log — a capped ring buffer of recent dispatches.
 *
 * @package Trigv
 */

declare(strict_types=1);

namespace Trigv;

defined( 'ABSPATH' ) || exit;

/**
 * Stores the last {@see MAX} dispatches in a non-autoloaded option.
 */
final class Log {

	private const OPTION = 'trigv_log';
	private const MAX    = 50;

	/**
	 * Append an entry, trimming to the most recent MAX.
	 *
	 * @param array{trigger?:string,channel?:string,title?:string,level?:string,status:string,http_code?:int,error?:string} $entry Entry.
	 */
	public function add( array $entry ): void {
		$entry['time'] = time();

		$entries = $this->all();
		array_unshift( $entries, $entry );
		$entries = array_slice( $entries, 0, self::MAX );

		update_option( self::OPTION, $entries, false );
	}

	/**
	 * All log entries, newest first.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function all(): array {
		$entries = get_option( self::OPTION, array() );
		return is_array( $entries ) ? $entries : array();
	}

	public function clear(): void {
		update_option( self::OPTION, array(), false );
	}
}
