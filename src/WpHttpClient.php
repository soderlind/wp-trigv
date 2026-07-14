<?php
/**
 * WordPress HTTP transport for the trigv-php SDK.
 *
 * @package Trigv
 */

declare(strict_types=1);

namespace Trigv;

use Trigv\Exception\NetworkException;
use Trigv\Exception\TimeoutException;
use Trigv\Http\HttpClientInterface;
use Trigv\Http\HttpResponse;

defined( 'ABSPATH' ) || exit;

/**
 * Routes trigv-php SDK requests through the WordPress HTTP API so they respect
 * WP proxy/SSL configuration and the `trigv_request_headers` filter.
 */
final class WpHttpClient implements HttpClientInterface {

	/**
	 * Perform an HTTP request via wp_remote_request().
	 *
	 * @param array<string,string> $headers Request headers set by the SDK.
	 */
	public function request( string $method, string $url, array $headers, ?string $body, int $timeoutSeconds ): HttpResponse {
		// Advertise the plugin (overriding the SDK's default User-Agent).
		$headers['User-Agent'] = 'wp-trigv/' . VERSION;

		/**
		 * Filter the HTTP headers sent with each Trigv request.
		 *
		 * @param array<string,string> $headers Request headers.
		 */
		$headers = (array) apply_filters( 'trigv_request_headers', $headers );

		$response = wp_remote_request(
			$url,
			array(
				'method'  => $method,
				'timeout' => $timeoutSeconds,
				'headers' => $headers,
				'body'    => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			$message = $response->get_error_message();
			if ( false !== stripos( $message, 'timed out' ) || false !== stripos( $message, 'timeout' ) ) {
				throw new TimeoutException( '' !== $message ? $message : 'Request timed out' );
			}
			throw new NetworkException( '' !== $message ? $message : 'Network request failed' );
		}

		$out_headers = array();
		foreach ( wp_remote_retrieve_headers( $response ) as $name => $value ) {
			$out_headers[ strtolower( (string) $name ) ] = is_array( $value ) ? implode( ', ', $value ) : (string) $value;
		}

		return new HttpResponse(
			(int) wp_remote_retrieve_response_code( $response ),
			(string) wp_remote_retrieve_body( $response ),
			$out_headers
		);
	}
}
