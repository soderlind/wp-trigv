<?php
/**
 * Plugin bootstrap.
 *
 * @package Trigv
 */

declare(strict_types=1);

namespace Trigv;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the plugin's services together.
 */
final class Plugin {

	private static ?Plugin $instance = null;

	private Settings $settings;
	private Log $log;
	private TriggerCatalog $catalog;
	private TriggerConfig $config;
	private Dispatcher $dispatcher;

	private function __construct() {
		$this->settings   = new Settings();
		$this->log        = new Log();
		$this->catalog    = new TriggerCatalog();
		$this->config     = new TriggerConfig( $this->catalog );
		$this->dispatcher = new Dispatcher( $this->settings, $this->catalog, $this->config, $this->log );
	}

	/**
	 * Singleton accessor.
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register hooks.
	 */
	public function init(): void {
		load_plugin_textdomain( 'wp-trigv', false, dirname( plugin_basename( FILE ) ) . '/languages' );

		$this->catalog->init();
		$this->dispatcher->init();

		( new RestController( $this->settings, $this->catalog, $this->config, $this->log ) )->init();

		if ( is_admin() ) {
			( new AdminPage( $this->settings ) )->init();
		}
	}

	public function settings(): Settings {
		return $this->settings;
	}

	public function catalog(): TriggerCatalog {
		return $this->catalog;
	}

	public function config(): TriggerConfig {
		return $this->config;
	}

	public function dispatcher(): Dispatcher {
		return $this->dispatcher;
	}

	public function log(): Log {
		return $this->log;
	}
}
