<?php
/**
 * Notification value object — the payload sent to Trigv.
 *
 * The single definition of what a Notification is: its fields, how a Trigger
 * builds one, how loose args become one, and what goes on the wire.
 *
 * @package Trigv
 */

declare(strict_types=1);

namespace Soderlind\Trigv;

defined( 'ABSPATH' ) || exit;

/**
 * An immutable Notification. Build with {@see from_trigger()} or
 * {@see from_args()}; put it on the wire with {@see to_payload()}.
 */
final class Notification {

	private const LEVELS = array( 'info', 'success', 'warning', 'error' );

	private const URGENCIES = array( 'standard', 'time_sensitive' );

	public function __construct(
		public readonly string $channel,
		public readonly string $title,
		public readonly string $description = '',
		public readonly string $level = 'info',
		public readonly string $event_type = '',
		public readonly string $delivery_urgency = 'standard',
		public readonly string $image_url = '',
		public readonly string $idempotency_key = '',
	) {
	}

	/**
	 * Build from a fired Trigger, its per-Trigger config, and the Token context.
	 *
	 * @param array<string,mixed>  $config          Per-Trigger config (channel, level, title, description, time_sensitive).
	 * @param string               $default_channel Fallback channel.
	 * @param array<string,scalar> $context         Resolved Token map.
	 */
	public static function from_trigger( Trigger $trigger, array $config, string $default_channel, array $context ): self {
		$channel   = '' !== (string) ( $config['channel'] ?? '' ) ? (string) $config['channel'] : $default_channel;
		$title_tpl = '' !== (string) ( $config['title'] ?? '' ) ? (string) $config['title'] : $trigger->default_title;
		$desc_tpl  = '' !== (string) ( $config['description'] ?? '' ) ? (string) $config['description'] : $trigger->default_description;

		return new self(
			channel: $channel,
			title: self::render( $title_tpl, $context ),
			description: self::render( $desc_tpl, $context ),
			level: self::valid_level( (string) ( $config['level'] ?? $trigger->default_level ), $trigger->default_level ),
			event_type: $trigger->event_type,
			delivery_urgency: ! empty( $config['time_sensitive'] ) ? 'time_sensitive' : 'standard',
		);
	}

	/**
	 * Build from a loose args array (the `trigv_send` action or filtered args).
	 *
	 * @param array<string,mixed> $args Raw notification args.
	 */
	public static function from_args( array $args, string $default_channel = 'general', string $default_level = 'info' ): self {
		$urgency = isset( $args['delivery_urgency'] ) ? (string) $args['delivery_urgency'] : 'standard';

		return new self(
			channel: isset( $args['channel'] ) ? sanitize_text_field( (string) $args['channel'] ) : $default_channel,
			title: isset( $args['title'] ) ? sanitize_text_field( (string) $args['title'] ) : '',
			description: isset( $args['description'] ) ? sanitize_text_field( (string) $args['description'] ) : '',
			level: self::valid_level( isset( $args['level'] ) ? (string) $args['level'] : $default_level, $default_level ),
			event_type: isset( $args['event_type'] ) ? sanitize_text_field( (string) $args['event_type'] ) : '',
			delivery_urgency: in_array( $urgency, self::URGENCIES, true ) ? $urgency : 'standard',
			image_url: isset( $args['image_url'] ) ? esc_url_raw( (string) $args['image_url'] ) : '',
			idempotency_key: isset( $args['idempotency_key'] ) ? sanitize_text_field( (string) $args['idempotency_key'] ) : '',
		);
	}

	/**
	 * Trigv requires a channel and a title.
	 */
	public function is_valid(): bool {
		return '' !== $this->channel && '' !== $this->title;
	}

	/**
	 * A copy with the given idempotency key.
	 */
	public function with_idempotency_key( string $key ): self {
		return new self(
			$this->channel,
			$this->title,
			$this->description,
			$this->level,
			$this->event_type,
			$this->delivery_urgency,
			$this->image_url,
			$key,
		);
	}

	/**
	 * All fields, including empties — for filters, logging, and the queue job.
	 *
	 * @return array<string,string>
	 */
	public function to_array(): array {
		return array(
			'channel'          => $this->channel,
			'title'            => $this->title,
			'description'      => $this->description,
			'level'            => $this->level,
			'event_type'       => $this->event_type,
			'delivery_urgency' => $this->delivery_urgency,
			'image_url'        => $this->image_url,
			'idempotency_key'  => $this->idempotency_key,
		);
	}

	/**
	 * Wire-ready payload — only the non-empty transport fields.
	 *
	 * @return array<string,string>
	 */
	public function to_payload(): array {
		return array_filter( $this->to_array(), static fn( $value ) => '' !== $value );
	}

	/**
	 * Replace {token} placeholders with resolved values.
	 *
	 * @param array<string,scalar> $context Token map.
	 */
	private static function render( string $template, array $context ): string {
		$search  = array();
		$replace = array();
		foreach ( $context as $token => $value ) {
			$search[]  = '{' . $token . '}';
			$replace[] = (string) $value;
		}
		return trim( str_replace( $search, $replace, $template ) );
	}

	private static function valid_level( string $level, string $fallback = 'info' ): string {
		return in_array( $level, self::LEVELS, true ) ? $level : $fallback;
	}
}
