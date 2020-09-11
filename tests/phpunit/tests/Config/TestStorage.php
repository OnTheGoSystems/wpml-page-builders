<?php

namespace WPML\PB\Config;

use OTGS\PHPUnit\Tools\TestCase;
use WPML\WP\OptionManager;

/**
 * @group config
 */
class TestStorage extends TestCase {

	const PB_KEY = 'the-pb-key';

	/**
	 * @test
	 */
	public function itShouldGet() {
		$pbConfig = [ 'some PB config' ];

		$optionManager = $this->getOptionManager();
		$optionManager->method( 'get' )
			->with( Storage::OPTION_GROUP, self::PB_KEY, [] )
			->willReturn( $pbConfig );

		$subject = $this->getSubject( $optionManager );

		$this->assertEquals( $pbConfig, $subject->get() );
	}

	/**
	 * @test
	 */
	public function itShouldUpdate() {
		$pbConfig = [ 'some PB config' ];

		$optionManager = $this->getOptionManager();
		$optionManager->expects( $this->once() )
			->method( 'set' )
			->with( Storage::OPTION_GROUP, self::PB_KEY, $pbConfig, false );

		$subject = $this->getSubject( $optionManager );

		$subject->update( $pbConfig );
	}

	private function getSubject( $optionManager ) {
		return new Storage( $optionManager, self::PB_KEY );
	}

	private function getOptionManager() {
		return $this->getMockBuilder( OptionManager::class )
			->setMethods( [ 'get', 'set' ] )
			->disableOriginalConstructor()->getMock();
	}
}
