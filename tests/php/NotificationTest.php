<?php
/**
 * @package Trigv
 */

declare(strict_types=1);

namespace Soderlind\Trigv\Tests;

use Brain\Monkey\Functions;
use Soderlind\Trigv\Notification;
use Soderlind\Trigv\Trigger;

final class NotificationTest extends UnitTestCase {

	private function trigger(): Trigger {
		return new Trigger(
			id: 't',
			label: 'T',
			group: 'G',
			event_type: 'post.published',
			default_level: 'success',
			default_title: 'New: {post_title}',
			default_description: 'By {author}',
			tokens: array(),
			hook: 'h',
			priority: 10,
			accepted_args: 1,
			resolver: static fn() => null,
		);
	}

	public function test_from_trigger_renders_tokens_and_defaults(): void {
		$config = array( 'channel' => '', 'level' => 'success', 'title' => '', 'description' => '', 'time_sensitive' => false );

		$n = Notification::from_trigger( $this->trigger(), $config, 'general', array( 'post_title' => 'Hello', 'author' => 'Bob' ) );

		$this->assertSame( 'New: Hello', $n->title );
		$this->assertSame( 'By Bob', $n->description );
		$this->assertSame( 'general', $n->channel ); // fell back to default
		$this->assertSame( 'post.published', $n->event_type );
		$this->assertSame( 'standard', $n->delivery_urgency );
		$this->assertSame( 'success', $n->level );
	}

	public function test_from_trigger_honours_config_overrides(): void {
		$config = array( 'channel' => 'news', 'level' => 'warning', 'title' => 'Custom {post_title}', 'description' => '', 'time_sensitive' => true );

		$n = Notification::from_trigger( $this->trigger(), $config, 'general', array( 'post_title' => 'Hi', 'author' => 'A' ) );

		$this->assertSame( 'news', $n->channel );
		$this->assertSame( 'warning', $n->level );
		$this->assertSame( 'Custom Hi', $n->title );
		$this->assertSame( 'time_sensitive', $n->delivery_urgency );
	}

	public function test_render_leaves_unknown_tokens_and_trims(): void {
		$config = array( 'channel' => 'c', 'level' => 'info', 'title' => '  {post_title} {unknown}  ', 'description' => '', 'time_sensitive' => false );

		$n = Notification::from_trigger( $this->trigger(), $config, 'general', array( 'post_title' => 'X' ) );

		$this->assertSame( 'X {unknown}', $n->title );
	}

	public function test_to_payload_drops_empty_fields(): void {
		$n = new Notification( channel: 'general', title: 'Hi' );

		$payload = $n->to_payload();

		$this->assertSame(
			array( 'channel' => 'general', 'title' => 'Hi', 'level' => 'info', 'delivery_urgency' => 'standard' ),
			$payload
		);
		$this->assertArrayNotHasKey( 'description', $payload );
		$this->assertArrayNotHasKey( 'idempotency_key', $payload );
	}

	public function test_from_args_sanitises_and_falls_back_on_bad_level(): void {
		Functions\when( 'sanitize_text_field' )->alias( static fn( $v ) => $v );
		Functions\when( 'esc_url_raw' )->alias( static fn( $v ) => $v );

		$n = Notification::from_args( array( 'title' => 'Hi', 'level' => 'bogus' ), 'general', 'info' );

		$this->assertSame( 'general', $n->channel );
		$this->assertSame( 'Hi', $n->title );
		$this->assertSame( 'info', $n->level );
	}

	public function test_is_valid_requires_channel_and_title(): void {
		$this->assertTrue( ( new Notification( 'c', 't' ) )->is_valid() );
		$this->assertFalse( ( new Notification( '', 't' ) )->is_valid() );
		$this->assertFalse( ( new Notification( 'c', '' ) )->is_valid() );
	}

	public function test_with_idempotency_key_returns_copy(): void {
		$original = new Notification( 'c', 't' );
		$keyed    = $original->with_idempotency_key( 'abc' );

		$this->assertSame( '', $original->idempotency_key );
		$this->assertSame( 'abc', $keyed->idempotency_key );
		$this->assertSame( 'c', $keyed->channel );
	}
}
