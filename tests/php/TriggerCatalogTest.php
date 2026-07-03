<?php
/**
 * @package Trigv
 */

declare(strict_types=1);

namespace Trigv\Tests;

use Brain\Monkey\Functions;
use Mockery;
use Trigv\TriggerCatalog;

final class TriggerCatalogTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( '__' )->alias( static fn( $text ) => $text );
		Functions\when( 'apply_filters' )->alias( static fn( $hook, $value = null ) => $value );
		Functions\when( 'sanitize_text_field' )->alias( static fn( $v ) => $v );
	}

	public function test_all_contains_builtin_triggers(): void {
		$catalog = new TriggerCatalog();
		$all     = $catalog->all();

		$this->assertArrayHasKey( 'post_published', $all );
		$this->assertArrayHasKey( 'login_failed', $all );
		$this->assertSame( 'post.published', $all['post_published']->event_type );
	}

	public function test_config_merges_defaults_when_unstored(): void {
		Functions\when( 'get_option' )->justReturn( array() );

		$config = ( new TriggerCatalog() )->config( 'post_published' );

		$this->assertFalse( $config['enabled'] );
		$this->assertSame( 'success', $config['level'] ); // Trigger default.
		$this->assertSame( '', $config['channel'] );
	}

	public function test_update_config_ignores_unknown_triggers(): void {
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

		( new TriggerCatalog() )->update_config(
			array(
				'post_published' => array( 'enabled' => true, 'channel' => 'news', 'level' => 'success' ),
				'bogus_trigger'  => array( 'enabled' => true ),
			)
		);
	}

	public function test_update_config_falls_back_to_default_level_when_invalid(): void {
		Functions\expect( 'update_option' )
			->once()
			->with(
				'trigv_trigger_settings',
				Mockery::on(
					static fn( $value ) => 'success' === $value['post_published']['level']
				),
				false
			);

		( new TriggerCatalog() )->update_config(
			array(
				'post_published' => array( 'enabled' => true, 'level' => 'nonsense' ),
			)
		);
	}
}
