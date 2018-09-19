<?php

/**
 * @group media
 */
class Test_WPML_Page_Builders_Media_Shortcodes extends \OTGS\PHPUnit\Tools\TestCase {

	/**
	 * @test
	 * @group wpmlcore-5861
	 */
	public function it_should_translate() {
		$target_lang = 'fr';
		$source_lang = 'en';

		$original_url_1   = 'http://example.org/dog.jpg';
		$translated_url_1 = 'http://example.org/chien.jpg';
		$original_url_2   = 'http://example.org/england.jpg';
		$translated_url_2 = 'http://example.org/france.jpg';
		$original_id_1    = 2;
		$translated_id_1  = 11;
		$original_id_2    = 7;
		$translated_id_2  = 19;

		$content = '
[et_pb_section bb_built="1"][et_pb_row][et_pb_column type="4_4"]
	[et_pb_gallery _builder_version="3.12" gallery_ids="' . $original_id_1 . '" zoom_icon_color="#2ea3f2" /]
	[et_pb_audio _builder_version="3.12" title="Audio block One" /]
	[et_pb_audio _builder_version="3.12" title="Audio block Two" background_image="' . $original_url_1 . '" /]
	[et_pb_cta background_image="' . $original_url_2 . '" title="The call to action" button_text="Buy me"]
		Do not miss this opportunity!
	[/et_pb_cta]
	[et_pb_gallery gallery_ids="" note="No IDs specified" /]
	[et_pb_gallery gallery_ids="' . $original_id_1 . ',' . $original_id_2 . '" zoom_icon_color="#2ea3f2" /]
	[et_pb_video_slider _builder_version="3.12" controls_color="light"]
		[et_pb_video_slider_item _builder_version="3.12" image_src="' . $original_url_2 . '" background_layout="dark" src_webm="http://wpml.local/video2.webm" /]
		[et_pb_video_slider_item _builder_version="3.12" image_src="' . $original_url_1 . '" background_layout="dark" src_webm="http://wpml.local/video1.webm" /]
	[/et_pb_video_slider]
	[et_pb_video image_src="' . $original_url_1 . '" some_image_src="Should not be translated" background_image="' . $original_url_2 . '" /]
	[shortcode_with_single_quotes gallery_ids=\'' . $original_id_1 . '\' /]
	[shortcode_with_url_content foo="bar"]' . $original_url_1 . '[/shortcode_with_url_content]
	[shortcode_with_url_content]' . $original_url_2 . '[/shortcode_with_url_content]
[/et_pb_column][/et_pb_row][/et_pb_section]
';

		$translated_content = '
[et_pb_section bb_built="1"][et_pb_row][et_pb_column type="4_4"]
	[et_pb_gallery _builder_version="3.12" gallery_ids="' . $translated_id_1 . '" zoom_icon_color="#2ea3f2" /]
	[et_pb_audio _builder_version="3.12" title="Audio block One" /]
	[et_pb_audio _builder_version="3.12" title="Audio block Two" background_image="' . $translated_url_1 . '" /]
	[et_pb_cta background_image="' . $translated_url_2 . '" title="The call to action" button_text="Buy me"]
		Do not miss this opportunity!
	[/et_pb_cta]
	[et_pb_gallery gallery_ids="" note="No IDs specified" /]
	[et_pb_gallery gallery_ids="' . $translated_id_1 . ',' . $translated_id_2 . '" zoom_icon_color="#2ea3f2" /]
	[et_pb_video_slider _builder_version="3.12" controls_color="light"]
		[et_pb_video_slider_item _builder_version="3.12" image_src="' . $translated_url_2 . '" background_layout="dark" src_webm="http://wpml.local/video2.webm" /]
		[et_pb_video_slider_item _builder_version="3.12" image_src="' . $translated_url_1 . '" background_layout="dark" src_webm="http://wpml.local/video1.webm" /]
	[/et_pb_video_slider]
	[et_pb_video image_src="' . $translated_url_1 . '" some_image_src="Should not be translated" background_image="' . $translated_url_2 . '" /]
	[shortcode_with_single_quotes gallery_ids=\'' . $translated_id_1 . '\' /]
	[shortcode_with_url_content foo="bar"]' . $translated_url_1 . '[/shortcode_with_url_content]
	[shortcode_with_url_content]' . $translated_url_2 . '[/shortcode_with_url_content]
[/et_pb_column][/et_pb_row][/et_pb_section]
';

		$config = array(
			array(
				'tag'        => array( 'name' => '*' ),
				'attributes' => array(
					'background_image' => array( 'type' => WPML_Page_Builders_Media_Shortcodes::TYPE_URL ),
				),
			),
			array(
				'tag'        => array( 'name' => 'et_pb_video_slider_item|et_pb_video' ),
				'attributes' => array(
					'image_src' => array( 'type' => WPML_Page_Builders_Media_Shortcodes::TYPE_URL ),
				),
			),
			array(
				'tag'        => array( 'name' => 'et_pb_gallery' ),
				'attributes' => array(
					'gallery_ids' => array( 'type' => WPML_Page_Builders_Media_Shortcodes::TYPE_IDS ),
				),
			),
			array(
				'tag'        => array( 'name' => 'shortcode_with_single_quotes' ),
				'attributes' => array(
					'gallery_ids' => array( 'type' => WPML_Page_Builders_Media_Shortcodes::TYPE_IDS ),
				),
			),
			array(
				'tag'     => array( 'name' => 'shortcode_with_url_content' ),
				'content' => array( 'type' => WPML_Page_Builders_Media_Shortcodes::TYPE_URL ),
			),
			// Missing tag, should not alter content
			array(
				'attributes' => array(
					'background_image' => array( 'type' => WPML_Page_Builders_Media_Shortcodes::TYPE_URL ),
				),
			),
			// No attributes, should not alter content
			array(
				'tag' => array( 'name' => 'et_pb_gallery' ),
			),
		);

		$media_translate = $this->get_media_translate();
		$media_translate->method( 'translate_id' )->willReturnMap(
			array(
				array( $original_id_1, $target_lang, $translated_id_1 ),
				array( $original_id_2, $target_lang, $translated_id_2 ),
			)
		);
		$media_translate->method( 'translate_image_url' )->willReturnMap(
			array(
				array( $original_url_1, $target_lang, $source_lang, $translated_url_1 ),
				array( $original_url_2, $target_lang, $source_lang, $translated_url_2 ),
			)
		);

		$subject = $this->get_subject( $media_translate, $config );

		$actual_content = $subject->set_target_lang( $target_lang )
		                          ->set_source_lang( $source_lang )
		                          ->translate( $content );

		$this->assertEquals( $translated_content, $actual_content );
	}

	private function get_subject( $media_translate, $config ) {
		return new WPML_Page_Builders_Media_Shortcodes( $media_translate, $config );
	}

	private function get_media_translate() {
		return $this->getMockBuilder( 'WPML_Page_Builders_Media_Translate' )
			->setMethods( array( 'translate_id', 'translate_image_url' ) )
			->disableOriginalConstructor()->getMock();
	}
}
