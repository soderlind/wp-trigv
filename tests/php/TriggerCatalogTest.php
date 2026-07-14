<?php
/**
 * @package Trigv
 */

declare(strict_types=1);

namespace Soderlind\Trigv\Tests;

use Brain\Monkey\Functions;
use Mockery;
use Soderlind\Trigv\TriggerCatalog;

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
}
