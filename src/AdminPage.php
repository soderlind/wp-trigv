<?php
/**
 * Admin page — registers the top-level menu and mounts the React app.
 *
 * @package Trigv
 */

declare(strict_types=1);

namespace Trigv;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the "Trigv" menu and enqueues the built admin app.
 */
final class AdminPage {

	private const SLUG = 'trigv';

	public function __construct(
		private readonly Settings $settings,
	) {
	}

	public function init(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function register_menu(): void {
		add_menu_page(
			__( 'Trigv', 'wp-trigv' ),
			__( 'Trigv', 'wp-trigv' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render' ),
			'dashicons-bell',
			80
		);
	}

	public function render(): void {
		echo '<div class="wrap"><div id="trigv-admin-root"></div></div>';
	}

	public function enqueue_assets( string $hook ): void {
		if ( 'toplevel_page_' . self::SLUG !== $hook ) {
			return;
		}

		$asset_file = DIR . 'build/index.asset.php';
		if ( ! is_readable( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			'trigv-admin',
			URL . 'build/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style(
			'trigv-admin',
			URL . 'build/index.css',
			array( 'wp-components' ),
			$asset['version']
		);

		wp_set_script_translations( 'trigv-admin', 'wp-trigv' );
	}
}
