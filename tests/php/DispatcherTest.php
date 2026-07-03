<?php
/**
 * @package Trigv
 */

declare(strict_types=1);

namespace Trigv\Tests;

use ReflectionMethod;
use Trigv\Dispatcher;
use Trigv\Log;
use Trigv\Settings;
use Trigv\TriggerCatalog;

final class DispatcherTest extends UnitTestCase {

	private function render( string $template, array $context ): string {
		$dispatcher = new Dispatcher( new Settings(), new TriggerCatalog(), new Log() );
		$method     = new ReflectionMethod( $dispatcher, 'render' );
		$method->setAccessible( true );

		return $method->invoke( $dispatcher, $template, $context );
	}

	public function test_render_replaces_tokens(): void {
		$result = $this->render(
			'New post: {post_title} by {author}',
			array( 'post_title' => 'Hello World', 'author' => 'Bob' )
		);

		$this->assertSame( 'New post: Hello World by Bob', $result );
	}

	public function test_render_trims_and_leaves_unknown_tokens(): void {
		$result = $this->render(
			'  {known} {unknown}  ',
			array( 'known' => 'X' )
		);

		$this->assertSame( 'X {unknown}', $result );
	}
}
