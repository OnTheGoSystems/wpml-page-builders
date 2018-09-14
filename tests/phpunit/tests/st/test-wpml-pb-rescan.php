<?php

/**
 * Class Test_WPML_PR_Register_Shortcodes
 */
class Test_WPML_PB_Rescan extends \OTGS\PHPUnit\Tools\TestCase {
	/**
	 * @var WPML_PB_Integration
	 */
	private $integrator;

	function setUp() {
		parent::setUp();

		$this->integrator = $this->getMockBuilder( 'WPML_PB_Integration' )
			->disableOriginalConstructor()
			->setMethods( array( 'register_all_strings_for_translation', 'update_translated_posts_form_original' ) )
			->getMock();
	}

	/**
	 * @test
	 */
	function it_does_not_rescan_if_post_already_contains_package() {
		$translation_package = array( 'translation_package' );
		$string_package = array( 'string_package' );
		$post = new stdClass();
		$post->ID = 5;

		\WP_Mock::onFilter( 'wpml_st_get_post_string_packages' )
		        ->with( false, $post->ID )
		        ->reply( $string_package );

		$this->integrator->expects( $this->never() )->method( 'register_all_strings_for_translation' )->with( $post );
		$this->integrator->expects( $this->never() )->method( 'update_translated_posts_form_original' )->with( $post );

		$subject = new WPML_PB_Integration_Rescan( $this->integrator );
		$this->assertEquals( $translation_package, $subject->rescan( $translation_package, $post ) );
	}

	/**
	 * @test
	 */
	function it_rescans_if_post_does_not_contain_package() {
		$translation_package = array( 'translation_package' );
		$post = new stdClass();
		$post->ID = 5;

		\WP_Mock::onFilter( 'wpml_st_get_post_string_packages' )
		        ->with( false, $post->ID )
		        ->reply( false );

		$this->integrator->expects( $this->once() )->method( 'register_all_strings_for_translation' )->with( $post );

		$subject = new WPML_PB_Integration_Rescan( $this->integrator );
		$this->assertEquals( $translation_package, $subject->rescan( $translation_package, $post ) );
	}
}
