<?php
/**
 * Plugin Name:       Trigv
 * Plugin URI:        https://github.com/soderlind/wp-trigv
 * Description:       Send WordPress events as push notifications via Trigv. Choose which events to watch, map them to Trigv channels, and dispatch asynchronously.
 * Version:           1.2.1
 * Requires at least: 6.8
 * Requires PHP:      8.3
 * Author:            Per Soderlind
 * Author URI:        https://soderlind.no
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-trigv
 * Domain Path:       /languages
 *
 * @package Trigv
 */

declare(strict_types=1);

namespace Trigv;

defined( 'ABSPATH' ) || exit;

const VERSION = '1.2.1';

define( 'Trigv\\FILE', __FILE__ );
define( 'Trigv\\DIR', plugin_dir_path( __FILE__ ) );
define( 'Trigv\\URL', plugin_dir_url( __FILE__ ) );

$trigv_autoload = __DIR__ . '/vendor/autoload.php';
if ( ! is_readable( $trigv_autoload ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'Trigv is missing its dependencies. Run "composer install" or install the release build.', 'wp-trigv' )
			);
		}
	);
	return;
}
require $trigv_autoload;

// Action Scheduler ships its own bootstrap that self-initializes on load.
require_once __DIR__ . '/vendor/woocommerce/action-scheduler/action-scheduler.php';

/**
 * Boot the plugin once WordPress and all plugins are loaded.
 */
add_action(
	'plugins_loaded',
	static function (): void {
		Plugin::instance()->init();
	}
);

/**
 * Wire up self-hosted updates from GitHub releases.
 */
add_action(
	'init',
	static function (): void {
		if ( ! class_exists( \Soderlind\WordPress\GitHubUpdater::class ) ) {
			return;
		}
		\Soderlind\WordPress\GitHubUpdater::init(
			github_url: 'https://github.com/soderlind/wp-trigv',
			plugin_file: __FILE__,
			plugin_slug: 'wp-trigv',
			name_regex: '/wp-trigv\.zip/',
			branch: 'main',
			check_period: 6,
			auth_token: defined( 'TRIGV_GITHUB_TOKEN' ) ? TRIGV_GITHUB_TOKEN : '',
		);
	}
);
