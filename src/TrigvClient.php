<?php
/**
 * Trigv client — adapts the trigv-php SDK to the plugin's dispatch contract.
 *
 * @package Trigv
 */

declare(strict_types=1);

namespace Soderlind\Trigv;

use Trigv\Client;
use Trigv\Exception\ApiException;
use Trigv\Exception\AuthenticationException;
use Trigv\Exception\AuthorizationException;
use Trigv\Exception\NetworkException;
use Trigv\Exception\NotFoundException;
use Trigv\Exception\RateLimitException;
use Trigv\Exception\TimeoutException;
use Trigv\Exception\TrigvException;
use Trigv\Exception\ValidationException;

defined( 'ABSPATH' ) || exit;

/**
 * Sends a Notification via the official trigv-php SDK over WordPress HTTP.
 *
 * Internal SDK retries are disabled (max_retries = 0); the Dispatcher retries
 * transient failures through Action Scheduler instead.
 */
final class TrigvClient {

	/**
	 * POST a wire-ready Notification payload to Trigv.
	 *
	 * @param array<string,mixed> $payload Notification payload (see Notification::to_payload()).
	 * @param string              $api_key Workspace API key.
	 * @return array{ok:bool,http_code:int,retryable:bool,error:string}
	 */
	public function send( array $payload, string $api_key ): array {
		try {
			$client = new Client(
				array(
					'api_key'     => $api_key,
					'http_client' => new WpHttpClient(),
					'max_retries' => 0,
				)
			);

			$result = $client->sendEvent( $payload );

			return array(
				'ok'        => true,
				'http_code' => $result->duplicate ? 200 : 202,
				'retryable' => false,
				'error'     => '',
			);
		} catch ( RateLimitException $e ) {
			return array(
				'ok'        => false,
				'http_code' => 429,
				'retryable' => $e->retryable,
				'error'     => $e->getMessage(),
			);
		} catch ( ApiException $e ) {
			return array(
				'ok'        => false,
				'http_code' => $e->statusCode,
				'retryable' => ( 429 === $e->statusCode || $e->statusCode >= 500 ),
				'error'     => $e->getMessage(),
			);
		} catch ( AuthenticationException | AuthorizationException | NotFoundException | ValidationException $e ) {
			$http_code = match ( true ) {
				$e instanceof AuthenticationException => 401,
				$e instanceof AuthorizationException  => 403,
				$e instanceof NotFoundException       => 404,
				default                               => 422,
			};

			return array(
				'ok'        => false,
				'http_code' => $http_code,
				'retryable' => false,
				'error'     => $e->getMessage(),
			);
		} catch ( TimeoutException | NetworkException $e ) {
			return array(
				'ok'        => false,
				'http_code' => 0,
				'retryable' => true,
				'error'     => $e->getMessage(),
			);
		} catch ( TrigvException $e ) {
			return array(
				'ok'        => false,
				'http_code' => 0,
				'retryable' => false,
				'error'     => $e->getMessage(),
			);
		}
	}
}
