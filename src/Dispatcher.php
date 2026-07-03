<?php
/**
 * Dispatcher — turns fired Triggers (and the developer API) into queued
 * Notifications, and sends them from the Action Scheduler queue.
 *
 * @package Trigv
 */

declare(strict_types=1);

namespace Trigv;

defined( 'ABSPATH' ) || exit;

/**
 * Builds Notifications, enqueues them, and performs the actual send.
 */
final class Dispatcher {

	private const HOOK        = 'trigv_dispatch';
	private const GROUP       = 'trigv';
	private const MAX_ATTEMPT = 3;

	private TrigvClient $client;

	public function __construct(
		private readonly Settings $settings,
		private readonly TriggerCatalog $catalog,
		private readonly Log $log,
	) {
		$this->client = new TrigvClient();
	}

	/**
	 * Register hooks: Trigger listeners, the developer action, and the queue worker.
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'register_trigger_listeners' ), 20 );
		add_action( 'trigv_send', array( $this, 'handle_send' ), 10, 1 );
		add_action( self::HOOK, array( $this, 'handle_scheduled' ), 10, 1 );
	}

	/**
	 * Attach a listener to every enabled Trigger's WordPress hook.
	 */
	public function register_trigger_listeners(): void {
		if ( ! $this->settings->has_api_key() ) {
			return; // No key: hard-disable all Triggers.
		}

		foreach ( $this->catalog->all() as $trigger ) {
			if ( ! $this->catalog->is_enabled( $trigger->id ) ) {
				continue;
			}

			$listener = function () use ( $trigger ): void {
				$this->on_trigger_fired( $trigger, func_get_args() );
			};

			add_action( $trigger->hook, $listener, $trigger->priority, $trigger->accepted_args );
		}
	}

	/**
	 * A watched Trigger fired.
	 *
	 * @param array<int,mixed> $args Hook arguments.
	 */
	private function on_trigger_fired( Trigger $trigger, array $args ): void {
		$context = $trigger->resolve( $args );
		if ( null === $context ) {
			return; // Resolver decided this firing is not notification-worthy.
		}

		$notification = $this->build_from_trigger( $trigger, $context );

		$this->enqueue(
			$notification,
			array(
				'source'  => $trigger->id,
				'trigger' => $trigger->label,
			)
		);
	}

	/**
	 * The `trigv_send` developer action.
	 *
	 * @param array<string,mixed> $args Notification args.
	 */
	public function handle_send( array $args ): void {
		$args = array(
			'channel'          => isset( $args['channel'] ) ? sanitize_text_field( (string) $args['channel'] ) : $this->settings->default_channel(),
			'title'            => isset( $args['title'] ) ? sanitize_text_field( (string) $args['title'] ) : '',
			'description'      => isset( $args['description'] ) ? sanitize_text_field( (string) $args['description'] ) : '',
			'level'            => isset( $args['level'] ) ? sanitize_text_field( (string) $args['level'] ) : $this->settings->default_level(),
			'event_type'       => isset( $args['event_type'] ) ? sanitize_text_field( (string) $args['event_type'] ) : '',
			'delivery_urgency' => isset( $args['delivery_urgency'] ) ? sanitize_text_field( (string) $args['delivery_urgency'] ) : 'standard',
			'image_url'        => isset( $args['image_url'] ) ? esc_url_raw( (string) $args['image_url'] ) : '',
			'idempotency_key'  => isset( $args['idempotency_key'] ) ? sanitize_text_field( (string) $args['idempotency_key'] ) : '',
		);

		$this->enqueue( $args, array( 'source' => 'manual', 'trigger' => __( 'Developer API', 'wp-trigv' ) ) );
	}

	/**
	 * Build a Notification from a Trigger and its resolved Token context.
	 *
	 * @param array<string,scalar> $context Token map.
	 * @return array<string,mixed>
	 */
	private function build_from_trigger( Trigger $trigger, array $context ): array {
		$config = $this->catalog->config( $trigger->id );

		$channel     = '' !== $config['channel'] ? $config['channel'] : $this->settings->default_channel();
		$title_tpl   = '' !== $config['title'] ? $config['title'] : $trigger->default_title;
		$desc_tpl    = '' !== $config['description'] ? $config['description'] : $trigger->default_description;

		return array(
			'channel'          => $channel,
			'title'            => $this->render( $title_tpl, $context ),
			'description'      => $this->render( $desc_tpl, $context ),
			'level'            => $config['level'],
			'event_type'       => $trigger->event_type,
			'delivery_urgency' => $config['time_sensitive'] ? 'time_sensitive' : 'standard',
			'image_url'        => '',
			'idempotency_key'  => '',
		);
	}

	/**
	 * Replace {token} placeholders with resolved values.
	 *
	 * @param array<string,scalar> $context Token map.
	 */
	private function render( string $template, array $context ): string {
		$search  = array();
		$replace = array();
		foreach ( $context as $token => $value ) {
			$search[]  = '{' . $token . '}';
			$replace[] = (string) $value;
		}
		return trim( str_replace( $search, $replace, $template ) );
	}

	/**
	 * Apply developer filters, validate, and schedule the send.
	 *
	 * @param array<string,mixed> $args    Notification args.
	 * @param array<string,mixed> $context Dispatch context (source, trigger label).
	 */
	private function enqueue( array $args, array $context ): void {
		/**
		 * Filter the Notification args before dispatch.
		 *
		 * @param array<string,mixed> $args    Notification args.
		 * @param array<string,mixed> $context Dispatch context.
		 */
		$args = (array) apply_filters( 'trigv_dispatch_args', $args, $context );

		/**
		 * Veto a dispatch. Return false or a WP_Error to suppress it.
		 *
		 * @param bool                $send Whether to dispatch.
		 * @param array<string,mixed> $args Notification args.
		 */
		$send = apply_filters( 'trigv_pre_dispatch', true, $args );
		if ( false === $send || is_wp_error( $send ) ) {
			$this->log->add(
				array(
					'trigger' => (string) ( $context['trigger'] ?? '' ),
					'channel' => (string) ( $args['channel'] ?? '' ),
					'title'   => (string) ( $args['title'] ?? '' ),
					'level'   => (string) ( $args['level'] ?? '' ),
					'status'  => 'vetoed',
				)
			);
			return;
		}

		if ( empty( $args['channel'] ) || empty( $args['title'] ) ) {
			return; // Trigv requires both.
		}

		if ( empty( $args['idempotency_key'] ) ) {
			$args['idempotency_key'] = 'trigv_' . wp_generate_uuid4();
		}

		$args['_attempt'] = 1;
		$args['_trigger'] = (string) ( $context['trigger'] ?? '' );

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::HOOK, array( $args ), self::GROUP );
		} else {
			$this->handle_scheduled( $args ); // Fallback: send inline.
		}
	}

	/**
	 * Queue worker: perform the send, log it, and retry transient failures.
	 *
	 * @param array<string,mixed> $args Notification args (with internal `_attempt`).
	 */
	public function handle_scheduled( array $args ): void {
		$attempt = (int) ( $args['_attempt'] ?? 1 );
		$trigger = (string) ( $args['_trigger'] ?? '' );

		$api_key = $this->settings->get_api_key();
		if ( '' === $api_key ) {
			$this->log->add(
				array(
					'trigger' => $trigger,
					'channel' => (string) $args['channel'],
					'title'   => (string) $args['title'],
					'level'   => (string) $args['level'],
					'status'  => 'error',
					'error'   => __( 'No API key configured.', 'wp-trigv' ),
				)
			);
			return;
		}

		$result = $this->client->send( $args, $api_key );

		if ( $result['ok'] ) {
			$this->log->add(
				array(
					'trigger'   => $trigger,
					'channel'   => (string) $args['channel'],
					'title'     => (string) $args['title'],
					'level'     => (string) $args['level'],
					'status'    => 'sent',
					'http_code' => $result['http_code'],
				)
			);
			return;
		}

		// Retry transient failures with exponential backoff, reusing the key.
		if ( $result['retryable'] && $attempt < self::MAX_ATTEMPT && function_exists( 'as_schedule_single_action' ) ) {
			$args['_attempt'] = $attempt + 1;
			$delay            = 60 * ( 2 ** ( $attempt - 1 ) );
			as_schedule_single_action( time() + $delay, self::HOOK, array( $args ), self::GROUP );

			$this->log->add(
				array(
					'trigger'   => $trigger,
					'channel'   => (string) $args['channel'],
					'title'     => (string) $args['title'],
					'level'     => (string) $args['level'],
					'status'    => 'retrying',
					'http_code' => $result['http_code'],
					'error'     => $result['error'],
				)
			);
			return;
		}

		$this->log->add(
			array(
				'trigger'   => $trigger,
				'channel'   => (string) $args['channel'],
				'title'     => (string) $args['title'],
				'level'     => (string) $args['level'],
				'status'    => 'failed',
				'http_code' => $result['http_code'],
				'error'     => $result['error'],
			)
		);
	}

	/**
	 * Send a one-off test Notification synchronously (used by the admin UI).
	 *
	 * @return array{ok:bool,http_code:int,retryable:bool,error:string}
	 */
	public function send_test( string $channel ): array {
		$api_key = $this->settings->get_api_key();
		if ( '' === $api_key ) {
			return array( 'ok' => false, 'http_code' => 0, 'retryable' => false, 'error' => __( 'No API key configured.', 'wp-trigv' ) );
		}

		return $this->client->send(
			array(
				'channel'         => '' !== $channel ? $channel : $this->settings->default_channel(),
				'title'           => __( 'Trigv test notification', 'wp-trigv' ),
				'description'     => __( 'Your WordPress site is connected to Trigv.', 'wp-trigv' ),
				'level'           => 'success',
				'event_type'      => 'trigv.test',
				'idempotency_key' => 'trigv_test_' . wp_generate_uuid4(),
			),
			$api_key
		);
	}
}
