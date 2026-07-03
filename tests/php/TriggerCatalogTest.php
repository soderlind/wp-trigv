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

	public function test_page_published_trigger_exists(): void {
		$all = ( new TriggerCatalog() )->all();

		$this->assertArrayHasKey( 'page_published', $all );
		$this->assertSame( 'page.published', $all['page_published']->event_type );
		$this->assertSame( 'transition_post_status', $all['page_published']->hook );
	}

	public function test_post_published_ignores_pages_by_default(): void {
		$post            = new \WP_Post();
		$post->post_type = 'page';

		$resolved = ( new TriggerCatalog() )->get( 'post_published' )
			->resolve( array( 'publish', 'draft', $post ) );

		$this->assertNull( $resolved );
	}

	public function test_post_published_honors_post_types_filter(): void {
		Functions\when( 'apply_filters' )->alias(
			static fn( $hook, $value = null ) =>
				'trigv_post_published_types' === $hook ? array( 'post', 'page' ) : $value
		);
		Functions\when( 'get_permalink' )->justReturn( 'https://example.test/about' );
		Functions\when( 'get_the_author_meta' )->justReturn( 'Jane' );

		$post              = new \WP_Post();
		$post->post_type   = 'page';
		$post->post_title  = 'About';
		$post->post_author = 1;

		$resolved = ( new TriggerCatalog() )->get( 'post_published' )
			->resolve( array( 'publish', 'draft', $post ) );

		$this->assertNotNull( $resolved );
		$this->assertSame( 'About', $resolved['post_title'] );
		$this->assertSame( 'Jane', $resolved['author'] );
	}

	public function test_page_published_only_matches_pages(): void {
		Functions\when( 'get_permalink' )->justReturn( 'https://example.test/about' );
		Functions\when( 'get_the_author_meta' )->justReturn( 'Jane' );

		$catalog = new TriggerCatalog();

		$post            = new \WP_Post();
		$post->post_type = 'post';
		$this->assertNull(
			$catalog->get( 'page_published' )->resolve( array( 'publish', 'draft', $post ) )
		);

		$page              = new \WP_Post();
		$page->post_type   = 'page';
		$page->post_title  = 'About';
		$page->post_author = 1;
		$resolved          = $catalog->get( 'page_published' )->resolve( array( 'publish', 'draft', $page ) );
		$this->assertSame( 'About', $resolved['post_title'] );
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
