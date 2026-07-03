<?php
/**
 * @package Trigv
 */

declare(strict_types=1);

namespace Trigv\Tests;

use Brain\Monkey\Functions;
use Mockery;
use Trigv\TriggerCatalog;
use Trigv\TriggerConfig;

final class TriggerConfigTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( '__' )->alias( static fn( $text ) => $text );
		Functions\when( 'apply_filters' )->alias( static fn( $hook, $value = null ) => $value );
		Functions\when( 'sanitize_text_field' )->alias( static fn( $v ) => $v );
	}

	private function config(): TriggerConfig {
		return new TriggerConfig( new TriggerCatalog() );
	}

	public function test_for_trigger_merges_defaults_when_unstored(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$config = $this->config()->for_trigger( 'post_published' );

		$this->assertFalse( $config['enabled'] );
		$this->assertSame( 'success', $config['level'] ); // Trigger default.
		$this->assertSame( '', $config['channel'] );
	}

	public function test_is_enabled_reads_stored_flag(): void {
		Functions\when( 'get_option' )->justReturn( array( 'post_published' => array( 'enabled' => true ) ) );

		$this->assertTrue( $this->config()->is_enabled( 'post_published' ) );
		$this->assertFalse( $this->config()->is_enabled( 'login_failed' ) );
	}

	public function test_update_ignores_unknown_triggers(): void {
		Functions\expect( 'update_option' )
			->once()
			->with(
				'trigv_trigger_settings',
				Mockery::on(
					static fn( $value ) => isset( $value['post_published'] )
						&& ! isset( $value['bogus_trigger'] )
						&& true === $value['post_published']['enabled']
				),
				false
			);

		$this->config()->update(
			array(
				'post_published' => array( 'enabled' => true, 'channel' => 'news', 'level' => 'success' ),
				'bogus_trigger'  => array( 'enabled' => true ),
			)
		);
	}

	public function test_update_falls_back_to_default_level_when_invalid(): void {
		Functions\expect( 'update_option' )
			->once()
			->with(
				'trigv_trigger_settings',
				Mockery::on(
					static fn( $value ) => 'success' === $value['post_published']['level']
				),
				false
			);

		$this->config()->update(
			array(
				'post_published' => array( 'enabled' => true, 'level' => 'nonsense' ),
			)
		);
	}
}
