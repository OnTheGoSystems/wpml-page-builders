<?php

namespace WPML\PB\AutoUpdate;

use OTGS\PHPUnit\Tools\TestCase;
use tad\FunctionMocker\FunctionMocker;

/**
 * @group auto-update
 */
class TestSettings extends TestCase{

	/**
	 * @test
	 */
	public function itShouldBeEnabledByDefault() {
		$defined = FunctionMocker::replace( 'defined', false );

		$this->assertTrue( Settings::isEnabled() );

		$defined->wasCalledWithOnce( [ 'WPML_TRANSLATION_AUTO_UPDATE_ENABLED' ] );
	}

	/**
	 * @test
	 */
	public function itShouldBeDisabledByConstant() {
		$defined  = FunctionMocker::replace( 'defined', true );
		$constant = FunctionMocker::replace( 'constant', true );

		$this->assertTrue( Settings::isEnabled() );

		$defined->wasCalledWithOnce( [ 'WPML_TRANSLATION_AUTO_UPDATE_ENABLED' ] );
		$constant->wasCalledWithOnce( [ 'WPML_TRANSLATION_AUTO_UPDATE_ENABLED' ] );
	}
}
