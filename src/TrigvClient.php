<?php
/**
 * Trigv HTTP client.
 *
 * @package Trigv
 */

declare(strict_types=1);

namespace Trigv;

defined( 'ABSPATH' ) || exit;

/**
 * Thin wrapper around the Trigv "send event" endpoint.
 */
final class TrigvClient {

	private const ENDPOINT = 'https://api.trigv.com/api/v1/events';

	/**
	 * POST a wire-ready Notification payload to Trigv.
	 *
	 * @param array<string,mixed> $payload Notification payload (see Notification::to_payload()).
	 * @param string              $api_key Bearer token.
	 * @return array{ok:bool,http_code:int,retryable:bool,error:string}
	 */
	public function send( array $payload, string $api_key ): array {
		$headers = array(
			'Content-Type'  => 'application/json',
			'Accept'        => 'application/json',
			'Authorization' => 'Bearer ' . $api_key,
			'User-Agent'    => 'wp-trigv/' . VERSION,
		);

		/**
		 * Filter the HTTP headers sent with each Trigv request.
		 *
		 * @param array<string,string> $headers Request headers.
		 */
		$headers = (array) apply_filters( 'trigv_request_headers', $headers );

		$response = wp_remote_post(
			self::ENDPOINT,
			array(
				'timeout' => 15,
				'headers' => $headers,
				'body'    => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'ok'        => false,
				'http_code' => 0,
				'retryable' => true,
				'error'     => $response->get_error_message(),
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		// 2xx = success (202 queued, 200 duplicate idempotency key).
		if ( $code >= 200 && $code < 300 ) {
			return array(
				'ok'        => true,
				'http_code' => $code,
				'retryable' => false,
				'error'     => '',
			);
		}

		$message = wp_remote_retrieve_response_message( $response );
		$raw     = wp_remote_retrieve_body( $response );
		$decoded = json_decode( $raw, true );
		if ( is_array( $decoded ) && isset( $decoded['message'] ) ) {
			$message = (string) $decoded['message'];
		}

		// 429 and 5xx are transient; 4xx (bad request) are not.
		$retryable = ( 429 === $code || $code >= 500 );

		return array(
			'ok'        => false,
			'http_code' => $code,
			'retryable' => $retryable,
			'error'     => (string) $message,
		);
	}
}
