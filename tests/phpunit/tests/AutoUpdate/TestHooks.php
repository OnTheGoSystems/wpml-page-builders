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

		\WP_Mock::expectActionAdded( 'init', [ $subject, 'init' ] );
		\WP_Mock::expectFilterAdded( 'wpml_tm_post_md5_content', [ $subject, 'getMd5ContentFromPackageStrings' ], 10, 2 );
		\WP_Mock::expectActionAdded( 'shutdown', [ $subject, 'afterRegisterAllStringsInShutdown' ], \WPML\PB\Shutdown\Hooks::PRIORITY_REGISTER_STRINGS + 1 );

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

		$package1 = $this->getPackage( [
			'string1B' => 'The string 1B',
			'string1A' => 'The string 1A',
		] );

		$package2 = $this->getPackage( [
			'string2A' => 'The string 2A',
			'string2B' => 'The string 2B',
		] );

		$packageWithMissingStringData = $this->getPackage( [] );

		// keys are sorted inside each package
		$expectedPostContentMd5 = 'The string 1A' . Hooks::HASH_SEP . 'The string 1B' . Hooks::HASH_SEP . 'The string 2A' . Hooks::HASH_SEP . 'The string 2B-';

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
		$post = $this->getPost( 123 );

		\WP_Mock::userFunction( 'wpml_tm_save_post' )
			->times( 1 )
			->with( $post->ID, $post );

		$pbIntegration = $this->getPbIntegration();
		$pbIntegration->method( 'get_save_post_queue' )->willReturn( [ $post ] );
		$pbIntegration->expects( $this->never() )->method( 'resave_post_translation_in_shutdown' );

		$factory = $this->getElementFactory();
		$factory->expects( $this->never() )->method( 'create_post' );

		$subject = $this->getSubject( $pbIntegration, $factory );

		$subject->afterRegisterAllStringsInShutdown();
	}

	/**
	 * @test
	 */
	public function itDoesNotResaveTranslationsIfNoPackage() {
		$post = $this->getPost( 123 );

		\WP_Mock::userFunction( 'wpml_tm_save_post' )
		        ->times( 1 )
		        ->with( $post->ID, $post );

		\WP_Mock::onFilter( 'wpml_st_get_post_string_packages' )
			->with( [], $post->ID )
			->reply( [] );

		$pbIntegration = $this->getPbIntegration();
		$pbIntegration->method( 'get_save_post_queue' )->willReturn( [ $post ] );
		$pbIntegration->expects( $this->never() )->method( 'resave_post_translation_in_shutdown' );

		$factory = $this->getElementFactory();
		$factory->expects( $this->never() )->method( 'create_post' );

		$subject = $this->getSubject( $pbIntegration, $factory );

		$subject->afterRegisterAllStringsInShutdown();
	}

	/**
	 * @test
	 */
	public function itResavesTranslationsAfterSavePost() {
		$post1 = $this->getPost( 123 );

		\WP_Mock::userFunction( 'wpml_tm_save_post' )
		        ->times( 1 )
		        ->with( $post1->ID, $post1 );

		\WP_Mock::onFilter( 'wpml_st_get_post_string_packages' )
			->with( [], $post1->ID )
			->reply( [ 'some package' ] );

		$translation1            = $this->getElement( 'en' );
		$translation2            = $this->getElement( 'en' );
		$translationNotCompleted = $this->getElement( 'en' );

		$original = $this->getElement( null, [ $translation1, $translation2, $translationNotCompleted ] );

		$pbIntegration = $this->getPbIntegration();
		$pbIntegration->method( 'get_save_post_queue' )->willReturn( [ $post1 ] );
		$pbIntegration->expects( $this->exactly( 2 ) )
			->method( 'resave_post_translation_in_shutdown' )
			->withConsecutive(
				[ $translation1 ],
				[ $translation2 ]
			);

		$factory = $this->getElementFactory();
		$factory->method( 'create_post' )->with( $post1->ID )->willReturn( $original );

		FunctionMocker::replace(
			TranslationStatus::class . '::get',
			function( $element ) use ( $translationNotCompleted ){
				return $element === $translationNotCompleted ? 'not completed' : ICL_TM_COMPLETE;
			}
		);

		$subject = $this->getSubject( $pbIntegration, $factory );

		$subject->afterRegisterAllStringsInShutdown();
	}

	private function getSubject( $pbIntegration = null, $elementFactory = null ) {
		$pbIntegration  = $pbIntegration ?: $this->getPbIntegration();
		$elementFactory = $elementFactory ?: $this->getElementFactory();

		return new Hooks( $pbIntegration, $elementFactory );
	}

	private function getPbIntegration() {
		return $this->getMockBuilder( '\WPML_PB_Integration' )
			->setMethods( [ 'resave_post_translation_in_shutdown', 'get_save_post_queue' ] )
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

	private function getPackage( array $strings ) {
		$stringsData = [];

		foreach ( $strings as $name => $value ) {
			$stringsData[] = [
				'name'  => $name,
				'value' => $value,
			];
		}

		$package = $this->getMockBuilder( '\WPML_Package' )
			->setMethods( [ 'get_package_strings' ] )
			->disableOriginalConstructor()->getMock();

		$package->method( 'get_package_strings' )
			->willReturn( $stringsData );

		return $package;
	}

	private function getPost( $id ) {
		$post = $this->getMockBuilder( '\WP_Post' )->getMock();
		$post->ID = $id;

		return $post;
	}
}
