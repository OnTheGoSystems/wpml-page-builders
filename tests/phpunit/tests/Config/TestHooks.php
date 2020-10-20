<?php

namespace WPML\PB\Config;

use OTGS\PHPUnit\Tools\TestCase;
use function WPML\FP\tap as tap;

/**
 * @group config
 */
class TestHooks extends TestCase {

	/** @see wpml_elementor_widgets_to_translate */
	const TRANSLATABLE_WIDGETS_HOOK = 'wpml_super_pb_widgets_to_translate';

	/**
	 * @test
	 */
	public function itShouldAddHooks() {
		$subject = $this->getSubject();

		\WP_Mock::expectFilterAdded( 'wpml_config_array', tap( [ $subject, 'extractConfig' ] ) );
		\WP_Mock::expectFilterAdded( self::TRANSLATABLE_WIDGETS_HOOK, [ $subject, 'extendTranslatableWidgets' ], Hooks::PRIORITY_AFTER_DEFAULT );

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

	/**
	 * @test
	 */
	public function itShouldExtendTranslatableWidgets() {
		$originalConfig = [
			'text-editor' => [ 'some config for text-editor' ],
		];

		$storedConfig = [
			'heading' => [ 'some config for heading' ],
		];

		$storage = $this->getStorage();
		$storage->method( 'get' )->willReturn( $storedConfig );

		$subject = $this->getSubject( null, $storage );

		$this->assertEquals(
			array_merge( $originalConfig, $storedConfig ),
			$subject->extendTranslatableWidgets( $originalConfig )
		);
	}

	private function getSubject( $parser = null, $storage = null ) {
		$parser  = $parser ?: $this->getParser();
		$storage = $storage ?: $this->getStorage();

		return new Hooks( $parser, $storage, self::TRANSLATABLE_WIDGETS_HOOK );
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
