<?php
/**
 * PHPUnit bootstrap — Composer autoload + minimal WordPress stubs.
 *
 * @package Trigv
 */

declare(strict_types=1);

// Satisfy the `defined( 'ABSPATH' ) || exit;` guard in source files.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

// Minimal WP_Error stand-in for tests that exercise is_wp_error() paths.
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public function __construct( private string $message = '' ) {
		}

		public function get_error_message(): string {
			return $this->message;
		}
	}
}
