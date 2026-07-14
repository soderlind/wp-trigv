<?php
/**
 * REST controller for the admin app (namespace `trigv/v1`).
 *
 * @package Trigv
 */

declare(strict_types=1);

namespace Soderlind\Trigv;

defined( 'ABSPATH' ) || exit;

use WP_REST_Request;
use WP_REST_Response;

/**
 * Exposes settings, Triggers, test-send and log endpoints to the React app.
 */
final class RestController {

	private const NS = 'trigv/v1';

	public function __construct(
		private readonly Settings $settings,
		private readonly TriggerCatalog $catalog,
		private readonly TriggerConfig $config,
		private readonly Log $log,
	) {
	}

	public function init(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Only administrators may use these routes.
	 */
	public function permission(): bool {
		return current_user_can( 'manage_options' );
	}

	public function register_routes(): void {
		$auth = array( $this, 'permission' );

		register_rest_route(
			self::NS,
			'/settings',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => $auth,
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => $auth,
				),
			)
		);

		register_rest_route(
			self::NS,
			'/triggers',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_triggers' ),
					'permission_callback' => $auth,
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'update_triggers' ),
					'permission_callback' => $auth,
				),
			)
		);

		register_rest_route(
			self::NS,
			'/test',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'send_test' ),
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			self::NS,
			'/log',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_log' ),
					'permission_callback' => $auth,
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'clear_log' ),
					'permission_callback' => $auth,
				),
			)
		);
	}

	public function get_settings(): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'has_api_key'      => $this->settings->has_api_key(),
				'masked_key'       => $this->settings->masked_key(),
				'key_from_constant' => $this->settings->is_key_from_constant(),
				'default_channel'  => $this->settings->default_channel(),
				'default_level'    => $this->settings->default_level(),
			)
		);
	}

	public function update_settings( WP_REST_Request $request ): WP_REST_Response {
		$this->settings->update(
			array(
				'api_key'         => (string) $request->get_param( 'api_key' ),
				'default_channel' => (string) $request->get_param( 'default_channel' ),
				'default_level'   => (string) $request->get_param( 'default_level' ),
			)
		);
		return $this->get_settings();
	}

	public function get_triggers(): WP_REST_Response {
		$items = array();
		foreach ( $this->catalog->all() as $trigger ) {
			$items[] = array(
				'id'                  => $trigger->id,
				'label'               => $trigger->label,
				'group'               => $trigger->group,
				'event_type'          => $trigger->event_type,
				'default_level'       => $trigger->default_level,
				'default_title'       => $trigger->default_title,
				'default_description' => $trigger->default_description,
				'tokens'              => $trigger->tokens,
				'config'              => $this->config->for_trigger( $trigger->id ),
			);
		}
		return new WP_REST_Response( array( 'triggers' => $items ) );
	}

	public function update_triggers( WP_REST_Request $request ): WP_REST_Response {
		$input = $request->get_param( 'triggers' );
		$this->config->update( is_array( $input ) ? $input : array() );
		return $this->get_triggers();
	}

	public function send_test( WP_REST_Request $request ): WP_REST_Response {
		$channel = (string) $request->get_param( 'channel' );
		$result  = Plugin::instance()->dispatcher()->send_test( $channel );
		return new WP_REST_Response( $result, $result['ok'] ? 200 : 400 );
	}

	public function get_log(): WP_REST_Response {
		return new WP_REST_Response( array( 'entries' => $this->log->all() ) );
	}

	public function clear_log(): WP_REST_Response {
		$this->log->clear();
		return new WP_REST_Response( array( 'entries' => array() ) );
	}
}
