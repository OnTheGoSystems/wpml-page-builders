<?php

namespace WPML\PB\AutoUpdate;

use Exception;
use OTGS\PHPUnit\Tools\TestCase;

/**
 * @group auto-update
 */
class TestTranslationStatus extends TestCase {

	/**
	 * @test
	 */
	public function itGetsAndReturnNullIfClassIsMissing() {
		\WP_Mock::userFunction( 'WPML\Container\make' )->andReturn( null );

		$this->assertNull( TranslationStatus::get( $this->getElement( 123, 'fr' ) ) );
	}

	/**
	 * @test
	 */
	public function itGets() {
		$trid   = 123;
		$lang   = 'fr';
		$status = 999;

		$statusObject = $this->getMockBuilder( '\WPML_TM_Translation_Status' )
			->setMethods( [ 'filter_translation_status' ] )
			->getMock();

		$statusObject->method( 'filter_translation_status' )
			->with( null, $trid, $lang )
			->willReturn( $status );

		\WP_Mock::userFunction( 'WPML\Container\make' )
			->with( '\WPML_TM_Translation_Status' )
			->andReturn( $statusObject );

		$this->assertEquals( $status, TranslationStatus::get( $this->getElement( $trid, $lang ) ) );
	}

	private function getElement( $trid, $lang ) {
		$element = $this->getMockBuilder( '\WPML_Post_Element' )
			->setMethods( [ 'get_trid', 'get_language_code' ] )
			->disableOriginalConstructor()
			->getMock();

		$element->method( 'get_trid' )
			->willReturn( $trid );

		$element->method( 'get_language_code' )
			->willReturn( $lang );

		return $element;
	}
}
