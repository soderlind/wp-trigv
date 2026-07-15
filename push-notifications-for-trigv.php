<?php
/**
 * Plugin Name:       Push Notifications for Trigv
 * Plugin URI:        https://github.com/soderlind/push-notifications-for-trigv
 * Description:       Send WordPress events as push notifications via Trigv. Choose which events to watch, map them to Trigv channels, and dispatch asynchronously.
 * Version:           2.0.2
 * Requires at least: 6.8
 * Requires PHP:      8.3
 * Author:            Per Soderlind
 * Author URI:        https://soderlind.no
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       push-notifications-for-trigv
 * Domain Path:       /languages
 *
 * @package Trigv
 */

declare(strict_types=1);

namespace Soderlind\Trigv;

defined( 'ABSPATH' ) || exit;

const VERSION = '2.0.2';

define( 'Soderlind\\Trigv\\FILE', __FILE__ );
define( 'Soderlind\\Trigv\\DIR', plugin_dir_path( __FILE__ ) );
define( 'Soderlind\\Trigv\\URL', plugin_dir_url( __FILE__ ) );

$trigv_autoload = __DIR__ . '/vendor/autoload.php';
if ( ! is_readable( $trigv_autoload ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'Trigv is missing its dependencies. Run "composer install" or install the release build.', 'push-notifications-for-trigv' )
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
