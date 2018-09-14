<?php

/**
 * Class Test_WPML_PB_Visual_Composer_Links
 *
 * @group page-builders
 * @group visual-composer
 *
 */
class Test_WPML_PB_Visual_Composer_Links extends WPML_PB_TestCase {

	/** @var WPML_PB_Shortcode_Strategy|\Mockery\MockInterface */
	private $shortcode_strategy;

	function setUp() {
		parent::setUp();

		/** @var WPML_PB_Shortcodes|\Mockery\MockInterface $shortcode_parser */
		$shortcode_parser = \Mockery::mock( 'WPML_PB_Shortcodes' );
		$shortcode_parser->shouldReceive( 'get_shortcodes' )->andReturn(
			array(
				array(
					'content' => '',
					'block' => '[vc_btn title="Button with link" link="url:http%3A%2F%2Fwpml-new-editor.local%2Ftest-editor-2%2F|title:Test%20editor|target:%20_blank|"]',
					'tag' => 'vc_btn',
					'attributes' => 'title="Button with link" link="url:http%3A%2F%2Fwpml-new-editor.local%2Ftest-editor-2%2F|title:Test%20editor|target:%20_blank|"',
				)
			)
		);

		$this->shortcode_strategy = \Mockery::mock( 'WPML_PB_Shortcode_Strategy' );
		$this->shortcode_strategy->shouldReceive( 'get_shortcode_parser' )->andReturn( $shortcode_parser );
		$this->shortcode_strategy->shouldReceive( 'get_shortcode_tag_encoding' )->andReturn( '' );
		$this->shortcode_strategy->shouldReceive( 'get_shortcode_tag_encoding_condition' )->andReturn( '' );
		$this->shortcode_strategy->shouldReceive( 'get_shortcode_tag_type' )->with( 'vc_btn' )->andReturn( 'VISUAL' );
		$this->shortcode_strategy->shouldReceive( 'get_shortcode_attribute_encoding' )->andReturnValues( array(
			                                                                                                 '',
			                                                                                                 WPML_PB_Shortcode_Encoding::ENCODE_TYPES_VISUAL_COMPOSER_LINK
		) );
		$this->shortcode_strategy->shouldReceive( 'get_shortcode_attribute_type' )->with( 'vc_btn', 'title' )->andReturn( 'LINE' );
		$this->shortcode_strategy->shouldReceive( 'get_shortcode_attribute_type' )->with( 'vc_btn', 'link' )->andReturn( 'LINE' );
		$this->shortcode_strategy->shouldReceive( 'get_package_key' )->andReturnNull();
		$this->shortcode_strategy->shouldReceive( 'get_package_strings' )->andReturn( array() );
		$this->shortcode_strategy->shouldReceive( 'get_shortcode_attributes' )->andReturn( array( 'title', 'link' ) );

		\WP_Mock::wpFunction( 'shortcode_parse_atts',
			array(
				'return' => array(
					'title' => 'Button with link',
					'link'  => 'url:http%3A%2F%2Fwpml-new-editor.local%2Ftest-editor-2%2F|title:Test%20editor|target:%20_blank|',
				)
			)
		);
		

	}

	/**
	 * @test
	 */
	public function register_shortcode_with_link() {
		$post_id = mt_rand();

		/** @var WPML_PB_String_Registration|\Mockery\MockInterface $string_handler */
		$string_handler = \Mockery::mock( 'WPML_PB_String_Registration' );
		$string_handler->shouldReceive( 'register_string' )->times( 1 )->with( $post_id, '', 'VISUAL', 'vc_btn: content', '', 1 );
		$string_handler->shouldReceive( 'register_string' )->times( 1 )->with( $post_id, 'Button with link', 'LINE', 'vc_btn: title', '', 1 );
		$string_handler->shouldReceive( 'register_string' )->times( 1 )->with( $post_id, 'http://wpml-new-editor.local/test-editor-2/', 'LINE', 'vc_btn: link url', '', 1 );
		$string_handler->shouldReceive( 'register_string' )->times( 1 )->with( $post_id, 'Test editor', 'LINE', 'vc_btn: link title', '', 1 );
		$string_handler->shouldReceive( 'get_string_id_from_package' )->andReturn( 'anything' );
		$string_handler->shouldReceive( 'get_string_title' )->andReturn( 'anything' );

		$reuse_translations_mock = Mockery::mock( 'WPML_PB_Reuse_Translations' );
		$reuse_translations_mock->shouldReceive( 'set_original_strings' );
		$reuse_translations_mock->shouldReceive( 'find_and_reuse' );

		$subject = new WPML_PB_Register_Shortcodes( $string_handler, $this->shortcode_strategy, new WPML_PB_Shortcode_Encoding(),$reuse_translations_mock );
		$subject->register_shortcode_strings( $post_id, '' );
	}

	/**
	 * @test
	 */
	public function update_link_from_translations() {

		$original = '[vc_btn title="Button with link" link="url:http%3A%2F%2Fwpml-new-editor.local%2Ftest-editor-2%2F|title:Test%20editor|target:%20_blank|" /]';
		$expected_translation = '[vc_btn title="Button with link - DE" link="url:http%3A%2F%2Fwpml-new-editor.local%2Ftest-editor-2-DE%2F|title:Test%20editor%20DE|target:%20_blank|" /]';

		$strings = array(
			md5( 'Button with link' ) => array(
				'de' => array(
					'status' => ICL_TM_COMPLETE,
					'value'  => 'Button with link - DE',
				),
			),
			md5( 'http://wpml-new-editor.local/test-editor-2/' ) => array(
				'de' => array(
					'status' => ICL_TM_COMPLETE,
					'value'  => 'http://wpml-new-editor.local/test-editor-2-DE/',
				),
			),
			md5( 'Test editor' ) => array(
				'de' => array(
					'status' => ICL_TM_COMPLETE,
					'value'  => 'Test editor DE',
				),
			),

		);

		$subject = new WPML_PB_Update_Shortcodes_In_Content( $this->shortcode_strategy, new WPML_PB_Shortcode_Encoding() );
		$translation = $subject->update_content( $original, $strings, 'de' );

		$this->assertEquals( $expected_translation, $translation );
	}

}
