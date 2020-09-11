<?php

namespace WPML\PB\Config;

use OTGS\PHPUnit\Tools\TestCase;
use function WPML\FP\tap as tap;

/**
 * @group config
 */
class TestHooks extends TestCase {

	/**
	 * @test
	 */
	public function itShouldAddHooks() {
		$subject = $this->getSubject();

		\WP_Mock::expectFilterAdded( 'wpml_config_array', tap( [ $subject, 'extractConfig' ] ) );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itShouldExtractConfig() {
		$allConfig = [ 'all the config' ];
		$pbConfig  = [ 'the PB config' ];

		$parser = $this->getParser();
		$parser->method( 'extract' )
			->with( $allConfig )
			->willReturn( $pbConfig );

		$storage = $this->getStorage();
		$storage->expects( $this->once() )
			->method( 'update' )
			->with( $pbConfig );

		$subject = $this->getSubject( $parser, $storage );
		$subject->extractConfig( $allConfig );
	}

	private function getSubject( $parser = null, $storage = null ) {
		$parser  = $parser ?: $this->getParser();
		$storage = $storage ?: $this->getStorage();

		return new Hooks( $parser, $storage );
	}

	private function getParser() {
		return $this->getMockBuilder( Parser::class )
			->setMethods( [ 'extract' ] )
			->disableOriginalConstructor()->getMock();
	}

	private function getStorage() {
		return $this->getMockBuilder( Storage::class )
			->setMethods( [ 'get', 'update' ] )
			->disableOriginalConstructor()->getMock();

	}
}
