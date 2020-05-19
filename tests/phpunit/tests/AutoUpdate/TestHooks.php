<?php

namespace WPML\PB\AutoUpdate;

use OTGS\PHPUnit\Tools\TestCase;
use tad\FunctionMocker\FunctionMocker;

/**
 * @group auto-update
 */
class TestHooks extends  TestCase {

	/**
	 * @test
	 */
	public function itAddsHooks() {
		$subject = $this->getSubject();

		\WP_Mock::expectFilterAdded( 'wpml_tm_post_md5_content', [ $subject, 'getMd5ContentFromPackageStrings' ], 10, 2 );
		\WP_Mock::expectActionAdded( 'wpml_after_save_post', [ $subject, 'resaveTranslationsAfterSavePost' ], 10, 4 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itDoesNotAlterMd5PostContentIfNoPackage() {
		$post    = (object) [ 'ID' => 123 ];
		$content = 'some content';

		\WP_Mock::onFilter( 'wpml_st_get_post_string_packages' )
			->with( [], $post->ID )
			->reply( [] );

		$subject = $this->getSubject();

		$this->assertEquals( $content, $subject->getMd5ContentFromPackageStrings( $content, $post ) );
	}

	/**
	 * @test
	 */
	public function itGetsMd5PostContentFromPackagesStrings() {
		$post    = (object) [ 'ID' => 123 ];
		$content = 'some content';

		$package1 = (object) [
			'string_data' => [
				'string1B' => 'The string 1B',
				'string1A' => 'The string 1A',
			],
		];

		$package2 = (object) [
			'string_data' => [
				'string2A' => 'The string 2A',
				'string2B' => 'The string 2B',
			],
		];

		$packageWithMissingStringData = (object) [];

		// keys are sorted inside each package
		$expectedPostContentMd5 = 'string1A' . Hooks::HASH_SEP . 'string1B' . Hooks::HASH_SEP . 'string2A' . Hooks::HASH_SEP . 'string2B-';

		\WP_Mock::onFilter( 'wpml_st_get_post_string_packages' )
			->with( [], $post->ID )
			->reply( [ $package1, $package2, $packageWithMissingStringData ] );

		$subject = $this->getSubject();

		$this->assertEquals( $expectedPostContentMd5, $subject->getMd5ContentFromPackageStrings( $content, $post ) );
	}

	/**
	 * @test
	 */
	public function itDoesNotResaveTranslationsIfNotOriginal() {
		$pbIntegration = $this->getPbIntegration();
		$pbIntegration->expects( $this->never() )->method( 'resave_post_translation_in_shutdown' );

		$factory = $this->getElementFactory();
		$factory->expects( $this->never() )->method( 'create_post' );

		$subject = $this->getSubject( $pbIntegration, $factory );

		$subject->resaveTranslationsAfterSavePost( 123, 456, 'fr', 'en' );
	}

	/**
	 * @test
	 */
	public function itDoesNotResaveTranslationsIfNoPackage() {
		$postId = 123;

		\WP_Mock::onFilter( 'wpml_st_get_post_string_packages' )
			->with( [], $postId )
			->reply( [] );

		$pbIntegration = $this->getPbIntegration();
		$pbIntegration->expects( $this->never() )->method( 'resave_post_translation_in_shutdown' );

		$factory = $this->getElementFactory();
		$factory->expects( $this->never() )->method( 'create_post' );

		$subject = $this->getSubject( $pbIntegration, $factory );

		$subject->resaveTranslationsAfterSavePost( $postId, 456, 'fr', null );
	}

	/**
	 * @test
	 */
	public function itResavesTranslationsAfterSavePost() {
		$postId = 123;

		\WP_Mock::onFilter( 'wpml_st_get_post_string_packages' )
			->with( [], $postId )
			->reply( [ 'some package' ] );

		$translation1            = $this->getElement( 'en' );
		$translation2            = $this->getElement( 'en' );
		$translationNotCompleted = $this->getElement( 'en' );

		$original = $this->getElement( null, [ $translation1, $translation2, $translationNotCompleted ] );

		$pbIntegration = $this->getPbIntegration();
		$pbIntegration->expects( $this->exactly( 2 ) )
			->method( 'resave_post_translation_in_shutdown' )
			->withConsecutive(
				[ $translation1 ],
				[ $translation2 ]
			);

		$factory = $this->getElementFactory();
		$factory->method( 'create_post' )->with( $postId )->willReturn( $original );

		FunctionMocker::replace(
			TranslationStatus::class . '::get',
			function( $element ) use ( $translationNotCompleted ){
				return $element === $translationNotCompleted ? 'not completed' : ICL_TM_COMPLETE;
			}
		);

		$subject = $this->getSubject( $pbIntegration, $factory );

		$subject->resaveTranslationsAfterSavePost( $postId, 456, 'fr', null );
	}

	private function getSubject( $pbIntegration = null, $elementFactory = null ) {
		$pbIntegration  = $pbIntegration ?: $this->getPbIntegration();
		$elementFactory = $elementFactory ?: $this->getElementFactory();

		return new Hooks( $pbIntegration, $elementFactory );
	}

	private function getPbIntegration() {
		return $this->getMockBuilder( '\WPML_PB_Integration' )
			->setMethods( [ 'resave_post_translation_in_shutdown' ] )
			->disableOriginalConstructor()
			->getMock();
	}

	private function getElementFactory( $postId = null, $element = null ) {
		$factory = $this->getMockBuilder( '\WPML_Translation_Element_Factory' )
			->setMethods( [ 'create_post' ] )
			->disableOriginalConstructor()
			->getMock();

		if ( $postId && $element ) {
			$factory->method( 'create_post' )
				->with( $postId )
				->willReturn( $element );
		}

		return $factory;
	}

	private function getElement( $sourceLang, $translations = [] ) {
		$element = $this->getMockBuilder( '\WPML_Post_Element' )
			->setMethods( [ 'get_source_language_code', 'get_translations' ] )
			->disableOriginalConstructor()
			->getMock();

		$element->method( 'get_source_language_code' )
			->willReturn( $sourceLang );

		$element->method( 'get_translations' )
			->willReturn( array_merge( [ $element ], $translations ) );

		return $element;
	}
}
