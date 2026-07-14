<?php
/**
 * @package Trigv
 */

declare(strict_types=1);

namespace Soderlind\Trigv\Tests;

use Brain\Monkey\Functions;
use Soderlind\Trigv\TrigvClient;
use WP_Error;

final class TrigvClientTest extends UnitTestCase {

	/**
	 * A minimal Trigv "event" body the SDK can hydrate on a 2xx response.
	 */
	private function success_body( int $status ): string {
		return (string) json_encode(
			array(
				'event' => array(
					'public_id'           => 'evt_123',
					'event_uuid'          => '00000000-0000-0000-0000-000000000000',
					'status'              => 200 === $status ? 'duplicate' : 'queued',
					'level'               => 'info',
					'event_type'          => 'trigv.test',
					'target_device_count' => 1,
					'received_at'         => '2026-01-01T00:00:00Z',
				),
			)
		);
	}

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wp_remote_retrieve_headers' )->justReturn( array() );
		Functions\when( 'esc_html' )->returnArg();
	}

	public function test_202_is_success(): void {
		Functions\when( 'wp_remote_request' )->justReturn( array() );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 202 );
		Functions\when( 'wp_remote_retrieve_body' )->justReturn( $this->success_body( 202 ) );

		$result = ( new TrigvClient() )->send(
			array( 'channel' => 'general', 'title' => 'Hi' ),
			'trgv_key'
		);

		$this->assertTrue( $result['ok'] );
		$this->assertSame( 202, $result['http_code'] );
		$this->assertFalse( $result['retryable'] );
	}

	public function test_200_duplicate_reports_http_200(): void {
		Functions\when( 'wp_remote_request' )->justReturn( array() );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 200 );
		Functions\when( 'wp_remote_retrieve_body' )->justReturn( $this->success_body( 200 ) );

		$result = ( new TrigvClient() )->send(
			array( 'channel' => 'general', 'title' => 'Hi', 'idempotency_key' => 'dedup-1' ),
			'trgv_key'
		);

		$this->assertTrue( $result['ok'] );
		$this->assertSame( 200, $result['http_code'] );
	}

	public function test_sends_identifier_user_agent_and_bearer_header(): void {
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 202 );
		Functions\when( 'wp_remote_retrieve_body' )->justReturn( $this->success_body( 202 ) );

		$captured = null;
		Functions\expect( 'wp_remote_request' )->once()->andReturnUsing(
			function ( $url, $args ) use ( &$captured ) {
				$captured = $args;
				return array();
			}
		);

		( new TrigvClient() )->send( array( 'channel' => 'c', 'title' => 't' ), 'trgv_key' );

		$this->assertSame( 'POST', $captured['method'] );
		$this->assertSame( 'wp-trigv/test', $captured['headers']['User-Agent'] );
		$this->assertSame( 'Bearer trgv_key', $captured['headers']['Authorization'] );
	}

	public function test_503_is_retryable(): void {
		Functions\when( 'wp_remote_request' )->justReturn( array() );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 503 );
		Functions\when( 'wp_remote_retrieve_body' )->justReturn( '' );

		$result = ( new TrigvClient() )->send( array( 'channel' => 'c', 'title' => 't' ), 'k' );

		$this->assertFalse( $result['ok'] );
		$this->assertTrue( $result['retryable'] );
		$this->assertSame( 503, $result['http_code'] );
	}

	public function test_422_is_not_retryable_and_uses_api_message(): void {
		Functions\when( 'wp_remote_request' )->justReturn( array() );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 422 );
		Functions\when( 'wp_remote_retrieve_body' )->justReturn(
			(string) json_encode( array( 'message' => 'The channel field is required.' ) )
		);

		$result = ( new TrigvClient() )->send( array( 'channel' => 'c', 'title' => 't' ), 'k' );

		$this->assertFalse( $result['ok'] );
		$this->assertFalse( $result['retryable'] );
		$this->assertSame( 422, $result['http_code'] );
		$this->assertSame( 'The channel field is required.', $result['error'] );
	}

	public function test_wp_error_is_retryable(): void {
		Functions\when( 'is_wp_error' )->alias( static fn( $thing ) => $thing instanceof WP_Error );
		Functions\when( 'wp_remote_request' )->justReturn( new WP_Error( 'cURL error 7: Connection refused' ) );

		$result = ( new TrigvClient() )->send( array( 'channel' => 'c', 'title' => 't' ), 'k' );

		$this->assertFalse( $result['ok'] );
		$this->assertTrue( $result['retryable'] );
		$this->assertSame( 'cURL error 7: Connection refused', $result['error'] );
	}
}
