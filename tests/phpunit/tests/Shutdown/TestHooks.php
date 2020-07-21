<?php

namespace WPML\PB\Shutdown;

use tad\FunctionMocker\FunctionMocker;

/**
 * @group shutdown
 */
class TestHooks extends \OTGS\PHPUnit\Tools\TestCase {

	/**
	 * @test
	 */
	public function itShouldAddHooks() {
		$pbIntegration = $this->getPbIntegration();
		$subject       = $this->getSubject( $pbIntegration );

		\WP_Mock::expectActionAdded( 'shutdown', [ $subject, 'registerStrings' ], Hooks::PRIORITY_REGISTER_STRINGS );
		\WP_Mock::expectActionAdded( 'shutdown', [ $pbIntegration, 'save_translations_to_post' ], Hooks::PRIORITY_SAVE_TRANSLATIONS_TO_POST );
		\WP_Mock::expectActionAdded( 'shutdown', [ $subject, 'translateMedias' ], Hooks::PRIORITY_TRANSLATE_MEDIA );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itShouldRegisterStrings() {
		$post = \Mockery::mock( '\WP_Post' );

		$pbIntegration = $this->getPbIntegration();
		$pbIntegration->method( 'get_save_post_queue' )->willReturn( [ $post ] );
		$pbIntegration->expects( $this->once() )
			->method( 'register_all_strings_for_translation' )
			->with( $post );

		$subject = $this->getSubject( $pbIntegration );

		$subject->registerStrings();
	}

	/**
	 * @test
	 */
	public function itShouldNOTTranslateMediasIfDisabled() {
		$post = \Mockery::mock( '\WP_Post' );

		$defined = FunctionMocker::replace( 'defined', false );

		$pbIntegration = $this->getPbIntegration();
		$pbIntegration->method( 'get_save_post_queue' )->willReturn( [ $post ] );
		$pbIntegration->expects( $this->never() )->method( 'translate_media' );

		$subject = $this->getSubject( $pbIntegration );

		$subject->translateMedias();

		$defined->wasCalledWithOnce( [ 'WPML_MEDIA_VERSION' ] );
	}

	/**
	 * @test
	 */
	public function itShouldTranslateMedias() {
		$post = \Mockery::mock( '\WP_Post' );

		$defined = FunctionMocker::replace( 'defined', true );

		$pbIntegration = $this->getPbIntegration();
		$pbIntegration->method( 'get_save_post_queue' )->willReturn( [ $post ] );
		$pbIntegration->expects( $this->once() )
			->method( 'translate_media' )
			->with( $post );

		$subject = $this->getSubject( $pbIntegration );

		$subject->translateMedias();

		$defined->wasCalledWithOnce( [ 'WPML_MEDIA_VERSION' ] );
	}

	private function getSubject( $pbIntegration ) {
		return new Hooks( $pbIntegration );
	}

	private function getPbIntegration() {
		return $this->getMockBuilder( '\WPML_PB_Integration' )
			->setMethods( [
				'get_save_post_queue',
				'register_all_strings_for_translation',
				'translate_media',
			] )
			->disableOriginalConstructor()
			->getMock();
	}
}
