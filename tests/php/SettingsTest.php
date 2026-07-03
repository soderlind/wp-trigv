<?php
/**
 * @package Trigv
 */

declare(strict_types=1);

namespace Trigv\Tests;

use Brain\Monkey\Functions;
use Mockery;
use Trigv\Settings;

final class SettingsTest extends UnitTestCase {

	public function test_update_keeps_existing_key_when_blank(): void {
		Functions\when( 'get_option' )->justReturn(
			array(
				'api_key'         => 'trgv_existing_key',
				'default_channel' => 'general',
				'default_level'   => 'info',
			)
		);
		Functions\when( 'sanitize_text_field' )->alias( static fn( $v ) => $v );

		Functions\expect( 'update_option' )
			->once()
			->with(
				'trigv_settings',
				Mockery::on(
					static fn( $value ) => 'trgv_existing_key' === $value['api_key']
						&& 'news' === $value['default_channel']
				),
				false
			);

		( new Settings() )->update(
			array(
				'api_key'         => '',
				'default_channel' => 'news',
				'default_level'   => 'info',
			)
		);
	}

	public function test_update_replaces_key_when_provided(): void {
		Functions\when( 'get_option' )->justReturn(
			array(
				'api_key'         => 'trgv_old',
				'default_channel' => 'general',
				'default_level'   => 'info',
			)
		);
		Functions\when( 'sanitize_text_field' )->alias( static fn( $v ) => $v );

		Functions\expect( 'update_option' )
			->once()
			->with(
				'trigv_settings',
				Mockery::on( static fn( $value ) => 'trgv_new' === $value['api_key'] ),
				false
			);

		( new Settings() )->update(
			array(
				'api_key'         => '  trgv_new  ',
				'default_channel' => 'general',
				'default_level'   => 'info',
			)
		);
	}

	public function test_update_falls_back_to_info_for_invalid_level(): void {
		Functions\when( 'get_option' )->justReturn( array() );
		Functions\when( 'sanitize_text_field' )->alias( static fn( $v ) => $v );

		Functions\expect( 'update_option' )
			->once()
			->with(
				'trigv_settings',
				Mockery::on( static fn( $value ) => 'info' === $value['default_level'] ),
				false
			);

		( new Settings() )->update(
			array(
				'api_key'         => 'trgv_x',
				'default_channel' => 'general',
				'default_level'   => 'not-a-level',
			)
		);
	}

	public function test_masked_key_shows_only_prefix(): void {
		Functions\when( 'get_option' )->justReturn(
			array(
				'api_key'         => 'trgv_abcd1234_secretsecret',
				'default_channel' => 'general',
				'default_level'   => 'info',
			)
		);

		$masked = ( new Settings() )->masked_key();

		$this->assertStringStartsWith( 'trgv_abcd', $masked );
		$this->assertStringNotContainsString( 'secretsecret', $masked );
	}

	public function test_has_api_key_is_false_when_empty(): void {
		Functions\when( 'get_option' )->justReturn( array( 'api_key' => '' ) );

		$this->assertFalse( ( new Settings() )->has_api_key() );
	}
}
