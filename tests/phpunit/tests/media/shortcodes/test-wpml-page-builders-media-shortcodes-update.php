<?php

/**
 * @group media
 */
class Test_WPML_Page_Builders_Media_Shortcodes_Update extends \OTGS\PHPUnit\Tools\TestCase {

	/**
	 * @test
	 */
	public function it_should_not_translate_if_content_has_no_shortcode() {
		$post = $this->get_post( 'Some content with no shortcode' );

		\WP_Mock::userFunction( 'wp_update_post', array(
			'times' => 0,
		));

		$subject = $this->get_subject();
		$subject->translate( $post );
	}

	/**
	 * @test
	 */
	public function it_should_not_translate_if_post_is_source() {
		$post = $this->get_post( '[shortcodeA]Hello[/shortcodeA]' );

		\WP_Mock::userFunction( 'wp_update_post', array(
			'times' => 0,
		));

		$element = $this->get_element( 'fr', null );

		$factory = $this->get_element_factory();
		$factory->method( 'create_post' )->with( $post->ID )->willReturn( $element );

		$subject = $this->get_subject( $factory );
		$subject->translate( $post );
	}

	/**
	 * @test
	 */
	public function it_should_translate() {
		$target_lang = 'fr';
		$source_lang = 'en';
		$original_content   = '[shortcodeA image="http://example.com/union-jack.jpg" /]';
		$translated_content = '[shortcodeA image="http://example.com/drapeau-francais.jpg" /]';

		$post = $this->get_post( $original_content );

		\WP_Mock::userFunction( 'wp_update_post', array(
			'times' => 1,
		));

		$element = $this->get_element( $target_lang, $source_lang );

		$factory = $this->get_element_factory();
		$factory->method( 'create_post' )->with( $post->ID )->willReturn( $element );

		$media_shortcodes = $this->get_media_shortcodes();
		$media_shortcodes->expects( $this->once() )->method( 'set_target_lang' )->with( $target_lang )->willReturnSelf();
		$media_shortcodes->expects( $this->once() )->method( 'set_source_lang' )->with( $source_lang )->willReturnSelf();
		$media_shortcodes->expects( $this->once() )
		                 ->method( 'translate' )->with( $original_content )->willReturn( $translated_content );

		$subject = $this->get_subject( $factory, $media_shortcodes );
		$subject->translate( $post );
	}

	private function get_subject( $factory = null, $media_shortcodes = null ) {
		$factory          = $factory ? $factory : $this->get_element_factory();
		$media_shortcodes = $media_shortcodes ? $media_shortcodes : $this->get_media_shortcodes();
		return new WPML_Page_Builders_Media_Shortcodes_Update( $factory, $media_shortcodes );
	}

	private function get_element_factory() {
		return $this->getMockBuilder( 'WPML_Translation_Element_Factory' )
		            ->setMethods( array( 'create_post' ) )->disableOriginalConstructor()->getMock();
	}

	private function get_element( $lang, $source_lang ) {
		$element = $this->getMockBuilder( 'WPML_Post_Element' )
		                ->setMethods( array( 'get_language_code', 'get_source_language_code' ) )
		                ->disableOriginalConstructor()->getMock();

		$element->method( 'get_language_code' )->willReturn( $lang );
		$element->method( 'get_source_language_code' )->willReturn( $source_lang );

		return $element;
	}

	private function get_media_shortcodes() {
		return $this->getMockBuilder( 'WPML_Page_Builders_Media_Shortcodes' )
		            ->setMethods( array( 'set_target_lang', 'set_source_lang', 'translate' ) )
		            ->disableOriginalConstructor()->getMock();
	}

	/**
	 * @param string $content
	 *
	 * @return PHPUnit_Framework_MockObject_MockObject|WP_Post
	 */
	private function get_post( $content = '' ) {
		$post = $this->getMockBuilder( 'WP_Post' )
		             ->disableOriginalConstructor()->getMock();
		$post->post_content = $content;
		$post->ID = mt_rand( 1, 100 );

		return $post;
	}
}
