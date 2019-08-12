<?php

/**
 * @group media
 */
class Test_WPML_Page_Builders_Media_Shortcodes_Update extends \OTGS\PHPUnit\Tools\TestCase {

	const ORIGINAL_POST_ID   = 9;
	const TRANSLATED_POST_ID = 57;

	/**
	 * @test
	 */
	public function it_should_not_translate_if_post_is_source() {
		$post = $this->get_post( '[shortcodeA]Hello[/shortcodeA]' );

		\WP_Mock::userFunction( 'wpml_update_escaped_post', array(
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
	public function it_should_not_translate_if_post_has_NO_media_shortcodes() {
		$target_lang = 'fr';
		$source_lang = 'en';
		$original_post_id   = self::ORIGINAL_POST_ID;
		$original_content   = '[shortcodeA image="http://example.com/union-jack.jpg" /]';
		$translated_content = '[shortcodeA image="http://example.com/drapeau-francais.jpg" /]';

		$post = $this->get_post( $original_content );

		\WP_Mock::userFunction(
			'wp_get_post_tags',
			array(
				'times' => 0,
			)
		);

		\WP_Mock::userFunction(
			'wpml_update_escaped_post',
			array(
				'times' => 0,
			)
		);

		$source_element = $this->get_element( $source_lang, null );
		$source_element->method( 'get_id' )->willReturn( $original_post_id );

		$element = $this->get_element( $target_lang, $source_lang );
		$element->method( 'get_source_element' )->willReturn( $source_element );

		$factory = $this->get_element_factory();
		$factory->method( 'create_post' )->with( $post->ID )->willReturn( $element );

		$media_shortcodes = $this->getMockBuilder( 'WPML_Page_Builders_Media_Shortcodes' )
		                   ->setMethods( array( 'set_target_lang', 'set_source_lang', 'translate', 'has_media_shortcode' ) )
		                   ->disableOriginalConstructor()->getMock();
		$media_shortcodes->method( 'has_media_shortcode' )->willReturn( false );
		$media_shortcodes->method( 'set_target_lang' )->with( $target_lang )->willReturnSelf();
		$media_shortcodes->method( 'set_source_lang' )->with( $source_lang )->willReturnSelf();
		$media_shortcodes->method( 'translate' )->with( $original_content )->willReturn( $translated_content );

		$subject = $this->get_subject( $factory, $media_shortcodes );
		$subject->translate( $post );
	}

	/**
	 * @test
	 * @group wpmlcore-5834
	 */
	public function it_should_translate() {
		$target_lang = 'fr';
		$source_lang = 'en';
		$original_post_id   = self::ORIGINAL_POST_ID;
		$original_content   = '[shortcodeA image="http://example.com/union-jack.jpg" /]';
		$translated_content = '[shortcodeA image="http://example.com/drapeau-francais.jpg" /]';

		$post = $this->get_post( $original_content );

		\WP_Mock::userFunction(
			'wp_get_post_tags',
			array(
				'times'  => 1,
				'return' => array( 1 ),
			)
		);

		\WP_Mock::userFunction( 'wpml_update_escaped_post', array(
			'times' => 1,
		));

		$source_element = $this->get_element( $source_lang, null );
		$source_element->method( 'get_id' )->willReturn( $original_post_id );

		$element = $this->get_element( $target_lang, $source_lang );
		$element->method( 'get_source_element' )->willReturn( $source_element );

		$factory = $this->get_element_factory();
		$factory->method( 'create_post' )->with( $post->ID )->willReturn( $element );

		$media_shortcodes = $this->get_media_shortcodes();
		$media_shortcodes->expects( $this->once() )->method( 'set_target_lang' )->with( $target_lang )->willReturnSelf();
		$media_shortcodes->expects( $this->once() )->method( 'set_source_lang' )->with( $source_lang )->willReturnSelf();
		$media_shortcodes->expects( $this->once() )
		                 ->method( 'translate' )->with( $original_content )->willReturn( $translated_content );

		$pb_media_usage = $this->get_pb_media_usage();
		$pb_media_usage->expects( $this->once() )->method( 'update' )->with( $original_post_id );

		$subject = $this->get_subject( $factory, $media_shortcodes, $pb_media_usage );
		$subject->translate( $post );
	}

	private function get_subject( $factory = null, $media_shortcodes = null, $pb_media_usage = null ) {
		$factory          = $factory ? $factory : $this->get_element_factory();
		$media_shortcodes = $media_shortcodes ? $media_shortcodes : $this->get_media_shortcodes();
		$pb_media_usage   = $pb_media_usage ? $pb_media_usage : $this->get_pb_media_usage();
		return new WPML_Page_Builders_Media_Shortcodes_Update( $factory, $media_shortcodes, $pb_media_usage );
	}

	private function get_element_factory() {
		return $this->getMockBuilder( 'WPML_Translation_Element_Factory' )
		            ->setMethods( array( 'create_post' ) )->disableOriginalConstructor()->getMock();
	}

	private function get_element( $lang, $source_lang ) {
		$element = $this->getMockBuilder( 'WPML_Post_Element' )
		                ->setMethods( array( 'get_language_code', 'get_source_language_code', 'get_source_element', 'get_id' ) )
		                ->disableOriginalConstructor()->getMock();

		$element->method( 'get_language_code' )->willReturn( $lang );
		$element->method( 'get_source_language_code' )->willReturn( $source_lang );

		return $element;
	}

	private function get_media_shortcodes() {
		$shortcodes = $this->getMockBuilder( 'WPML_Page_Builders_Media_Shortcodes' )
		            ->setMethods( array( 'set_target_lang', 'set_source_lang', 'translate', 'has_media_shortcode' ) )
		            ->disableOriginalConstructor()->getMock();
		$shortcodes->method( 'has_media_shortcode' )->willReturn( true );

		return $shortcodes;
	}

	private function get_pb_media_usage() {
		return $this->getMockBuilder( 'WPML_Page_Builders_Media_Usage' )
		            ->setMethods( array( 'update' ) )
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
		$post->ID = self::TRANSLATED_POST_ID;

		return $post;
	}
}
