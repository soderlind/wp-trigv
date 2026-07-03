<?php
/**
 * @package Trigv
 */

declare(strict_types=1);

namespace Trigv\Tests;

use Brain\Monkey\Functions;
use Trigv\TrigvClient;
use WP_Error;

final class TrigvClientTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'wp_json_encode' )->alias( static fn( $data ) => json_encode( $data ) );
		Functions\when( 'wp_remote_retrieve_response_message' )->justReturn( 'Error' );
	}

	public function test_2xx_is_success(): void {
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wp_remote_post' )->justReturn( array( 'ok' ) );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 202 );

		$result = ( new TrigvClient() )->send(
			array( 'channel' => 'general', 'title' => 'Hi' ),
			'trgv_key'
		);

		$this->assertTrue( $result['ok'] );
		$this->assertSame( 202, $result['http_code'] );
		$this->assertFalse( $result['retryable'] );
	}

	public function test_500_is_retryable(): void {
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wp_remote_post' )->justReturn( array() );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 503 );
		Functions\when( 'wp_remote_retrieve_body' )->justReturn( '' );

		$result = ( new TrigvClient() )->send( array( 'channel' => 'c', 'title' => 't' ), 'k' );

		$this->assertFalse( $result['ok'] );
		$this->assertTrue( $result['retryable'] );
	}

	public function test_422_is_not_retryable_and_uses_api_message(): void {
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wp_remote_post' )->justReturn( array() );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 422 );
		Functions\when( 'wp_remote_retrieve_body' )->justReturn(
			json_encode( array( 'message' => 'The channel field is required.' ) )
		);

		$result = ( new TrigvClient() )->send( array( 'title' => 't' ), 'k' );

		$this->assertFalse( $result['ok'] );
		$this->assertFalse( $result['retryable'] );
		$this->assertSame( 'The channel field is required.', $result['error'] );
	}

	public function test_wp_error_is_retryable(): void {
		Functions\when( 'is_wp_error' )->alias( static fn( $thing ) => $thing instanceof WP_Error );
		Functions\when( 'wp_remote_post' )->justReturn( new WP_Error( 'Connection timed out' ) );

		$result = ( new TrigvClient() )->send( array( 'channel' => 'c', 'title' => 't' ), 'k' );

		$this->assertFalse( $result['ok'] );
		$this->assertTrue( $result['retryable'] );
		$this->assertSame( 'Connection timed out', $result['error'] );
	}
}
