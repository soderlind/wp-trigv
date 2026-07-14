<?php
/**
 * Dispatcher — turns fired Triggers (and the developer API) into queued
 * Notifications, and sends them from the Action Scheduler queue.
 *
 * @package Trigv
 */

declare(strict_types=1);

namespace Soderlind\Trigv;

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
		private readonly TriggerConfig $config,
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
			if ( ! $this->config->is_enabled( $trigger->id ) ) {
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

		$notification = Notification::from_trigger(
			$trigger,
			$this->config->for_trigger( $trigger->id ),
			$this->settings->default_channel(),
			$context
		);

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
		$notification = Notification::from_args(
			$args,
			$this->settings->default_channel(),
			$this->settings->default_level()
		);

		$this->enqueue(
			$notification,
			array( 'source' => 'manual', 'trigger' => __( 'Developer API', 'push-notifications-for-trigv' ) )
		);
	}

	/**
	 * Apply developer filters, validate, and schedule the send.
	 *
	 * @param array<string,mixed> $context Dispatch context (source, trigger label).
	 */
	private function enqueue( Notification $notification, array $context ): void {
		/**
		 * Filter the Notification args before dispatch.
		 *
		 * @param array<string,mixed> $args    Notification args.
		 * @param array<string,mixed> $context Dispatch context.
		 */
		$args = (array) apply_filters( 'trigv_dispatch_args', $notification->to_array(), $context );

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

		$notification = Notification::from_args( $args, $this->settings->default_channel(), $this->settings->default_level() );
		if ( ! $notification->is_valid() ) {
			return; // Trigv requires a channel and a title.
		}

		if ( '' === $notification->idempotency_key ) {
			$notification = $notification->with_idempotency_key( 'trigv_' . wp_generate_uuid4() );
		}

		$job = array(
			'notification' => $notification->to_array(),
			'_attempt'     => 1,
			'_trigger'     => (string) ( $context['trigger'] ?? '' ),
		);

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::HOOK, array( $job ), self::GROUP );
		} else {
			$this->handle_scheduled( $job ); // Fallback: send inline.
		}
	}

	/**
	 * Queue worker: perform the send, log it, and retry transient failures.
	 *
	 * @param array<string,mixed> $job Queue job (notification payload + control state).
	 */
	public function handle_scheduled( array $job ): void {
		$attempt      = (int) ( $job['_attempt'] ?? 1 );
		$trigger      = (string) ( $job['_trigger'] ?? '' );
		$notification = Notification::from_args( is_array( $job['notification'] ?? null ) ? $job['notification'] : array() );

		$api_key = $this->settings->get_api_key();
		if ( '' === $api_key ) {
			$this->log_dispatch( $notification, $trigger, 'error', 0, __( 'No API key configured.', 'push-notifications-for-trigv' ) );
			return;
		}

		$result = $this->client->send( $notification->to_payload(), $api_key );

		if ( $result['ok'] ) {
			$this->log_dispatch( $notification, $trigger, 'sent', $result['http_code'] );
			return;
		}

		// Retry transient failures with exponential backoff, reusing the key.
		if ( $result['retryable'] && $attempt < self::MAX_ATTEMPT && function_exists( 'as_schedule_single_action' ) ) {
			$job['_attempt'] = $attempt + 1;
			$delay           = 60 * ( 2 ** ( $attempt - 1 ) );
			as_schedule_single_action( time() + $delay, self::HOOK, array( $job ), self::GROUP );
			$this->log_dispatch( $notification, $trigger, 'retrying', $result['http_code'], $result['error'] );
			return;
		}

		$this->log_dispatch( $notification, $trigger, 'failed', $result['http_code'], $result['error'] );
	}

	/**
	 * Append a dispatch outcome to the log.
	 */
	private function log_dispatch( Notification $notification, string $trigger, string $status, int $http_code = 0, string $error = '' ): void {
		$entry = array(
			'trigger' => $trigger,
			'channel' => $notification->channel,
			'title'   => $notification->title,
			'level'   => $notification->level,
			'status'  => $status,
		);
		if ( 0 !== $http_code ) {
			$entry['http_code'] = $http_code;
		}
		if ( '' !== $error ) {
			$entry['error'] = $error;
		}
		$this->log->add( $entry );
	}

	/**
	 * Send a one-off test Notification synchronously (used by the admin UI).
	 *
	 * @return array{ok:bool,http_code:int,retryable:bool,error:string}
	 */
	public function send_test( string $channel ): array {
		$api_key = $this->settings->get_api_key();
		if ( '' === $api_key ) {
			return array( 'ok' => false, 'http_code' => 0, 'retryable' => false, 'error' => __( 'No API key configured.', 'push-notifications-for-trigv' ) );
		}

		$notification = new Notification(
			channel: '' !== $channel ? $channel : $this->settings->default_channel(),
			title: __( 'Trigv test notification', 'push-notifications-for-trigv' ),
			description: __( 'Your WordPress site is connected to Trigv.', 'push-notifications-for-trigv' ),
			level: 'success',
			event_type: 'trigv.test',
			idempotency_key: 'trigv_test_' . wp_generate_uuid4(),
		);

		return $this->client->send( $notification->to_payload(), $api_key );
	}
}
