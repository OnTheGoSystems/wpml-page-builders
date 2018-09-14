<?php
/**
 * @author OnTheGo Systems
 */

use tad\FunctionMocker\FunctionMocker;

abstract class WPML_PageBuilders_TestCase extends \OTGS\PHPUnit\Tools\TestCase {
	function setUp() {
		parent::setUp();
		FunctionMocker::setUp();
		WP_Mock::setUp();
	}

	function tearDown() {
		WP_Mock::tearDown();
		FunctionMocker::tearDown();
		Mockery::close();
		parent::tearDown();
	}


}
