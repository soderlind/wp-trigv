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

// The main plugin file (which defines this) is not loaded in unit tests.
if ( ! defined( 'Trigv\\VERSION' ) ) {
	define( 'Trigv\\VERSION', 'test' );
}

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

// Minimal WP_Post stand-in for resolver tests.
if ( ! class_exists( 'WP_Post' ) ) {
	class WP_Post {
		public string $post_type = 'post';
		public string $post_title = '';
		public int $post_author = 0;
		public string $post_status = '';
	}
}
