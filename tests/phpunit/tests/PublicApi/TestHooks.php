<?php

namespace WPML\PB\PublicApi;

use OTGS\PHPUnit\Tools\TestCase;

/**
 * @group public-api
 */
class TestHooks extends TestCase {

	/**
	 * @test
	 */
	public function itShouldAddHooks() {
		$subject = $this->getSubject();

		\WP_Mock::expectFilterAdded( 'wpml_pb_post_use_translation_editor', [ $subject, 'postUseTranslationEditor' ], 10, 2 );

		$subject->addHooks();
	}

	/**
	 * @test
	 * @dataProvider dpBool
	 *
	 * @param bool $bool
	 */
	public function itShouldReturnPostUseTranslationEditor( $bool ) {
		$postId = 123;

		$lastEditMode = $this->getLastEditMode();
		$lastEditMode->method( 'is_translation_editor' )
			->with( $postId )
			->willReturn( $bool );

		$subject = $this->getSubject( $lastEditMode );

		$this->assertSame( $bool, $subject->postUseTranslationEditor( 'unused', $postId ) );
	}

	public function dpBool() {
		return [
			[ true ],
			[ false ],
		];
	}

	private function getSubject( $lastEditMode = null ) {
		$lastEditMode = $lastEditMode ?: $this->getLastEditMode();

		return new Hooks( $lastEditMode );
	}

	private function getLastEditMode() {
		return $this->getMockBuilder( \WPML_PB_Last_Translation_Edit_Mode::class )
			->setMethods( [ 'is_translation_editor' ] )
			->disableOriginalConstructor()->getMock();
	}

	/**
	 * @test
	 */
	public function itShouldCreateAndReturnInstanceOfHooks() {
		$subject = new Factory();

		$this->assertInstanceOf( Hooks::class, $subject::create() );
	}
}
