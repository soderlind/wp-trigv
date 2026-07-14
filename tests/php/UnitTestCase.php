<?php
/**
 * Base test case wiring Brain Monkey in and out.
 *
 * @package Trigv
 */

declare(strict_types=1);

namespace Soderlind\Trigv\Tests;

use Brain\Monkey;
use Mockery;
use PHPUnit\Framework\TestCase;

abstract class UnitTestCase extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		$container = Mockery::getContainer();
		if ( null !== $container ) {
			$this->addToAssertionCount( $container->mockery_getExpectationCount() );
		}
		Monkey\tearDown();
		parent::tearDown();
	}
}
