<?php

/**
 * Class Test_WPML_PB_Register_Shortcodes
 *
 * @group page-builders
 * @group wpmltm-1847
 */
class Test_WPML_PB_Register_Shortcodes extends WPML_PB_TestCase {

	function setUp() {
		parent::setUp();

		\WP_Mock::wpFunction( 'get_shortcode_regex', array(
			'return' => '\[(\[?)(vc_column_text)(?![\w-])([^\]\/]*(?:\/(?!\])[^\]\/]*)*?)(?:(\/)\]|\](?:([^\[]*+(?:\[(?!\/\2\])[^\[]*+)*+)\[\/\2\])?)(\]?)',
		) );
	}

	/**
	 * @test
	 * @dataProvider encode_data_provider
	 *
	 * @param bool $encode
	 */
	public function register_shortcode_strings( $encode ) {
		$post_id              = mt_rand();
		$shortcode_content    = rand_long_str( 11 );
		$shortcode_attr_value = rand_long_str( 11 );
		$shortcode            = sprintf(
			'[vc_column_text title="%s"]%s[/vc_column_text]',
			$encode ? base64_encode( $shortcode_attr_value ) : $shortcode_attr_value,
			$encode ? base64_encode( $shortcode_content ) : $shortcode_content
		);

		\WP_Mock::wpFunction( 'shortcode_parse_atts', array(
			'return' => array( 'title' => $encode ? base64_encode( $shortcode_attr_value ) : $shortcode_attr_value ),
		) );

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'times' => 1,
			'args'  => array( $post_id, WPML_PB_Integration::MIGRATION_DONE_POST_META, true ),
		) );


		$dummy_content = rand_long_str( 100 ) . $shortcode . rand_long_str( 100 );

		$sitepress_mock      = $this->get_sitepress_mock();
		$string_handler_mock = $this->get_wpml_pb_handle_strings_mock();

		$string_handler_mock->expects( $this->exactly( 2 ) )
		                    ->method( 'register_string' )
		                    ->withConsecutive(
			                    array(
				                    $this->equalTo( $post_id ),
				                    $this->equalTo( $shortcode_content ),
				                    $this->equalTo( 'VISUAL' ),
				                    $this->equalTo( 'vc_column_text: content' ),
				                    $this->equalTo( '' ),
				                    $this->equalTo( 1 )
			                    ),
			                    array(
				                    $this->equalTo( $post_id ),
				                    $this->equalTo( $shortcode_attr_value ),
				                    $this->equalTo( 'LINE' ),
				                    $this->equalTo( 'vc_column_text: title' ),
				                    $this->equalTo( '' ),
				                    $this->equalTo( 2 )
			                    )
		                    )
		                    ->willReturn( 1 );

		$wpdb     = $this->stubs->wpdb();
		$factory  = $this->get_factory( $wpdb, $sitepress_mock );
		$strategy = $this->get_shortcode_strategy( $factory, $encode ? WPML_PB_Shortcode_Encoding::ENCODE_TYPES_BASE64 : '' );

		$reuse_translations_mock = Mockery::mock( 'WPML_PB_Reuse_Translations' );
		$reuse_translations_mock->shouldReceive( 'set_original_strings' );
		$reuse_translations_mock->shouldReceive( 'find_and_reuse' );

		$shortcode_handler = new WPML_PB_Register_Shortcodes( $string_handler_mock, $strategy, new WPML_PB_Shortcode_Encoding(), $reuse_translations_mock );
		$shortcode_handler->register_shortcode_strings( $post_id, $dummy_content );
	}

	public function encode_data_provider() {
		return array(
			array( false ),
			array( true ),
		);
	}

	/**
	 * @group page-builders
	 * @group oxygen-theme
	 * @group wpmlst-1132
	 */
	public function test_content_can_be_filtered() {
		$post_id             = mt_rand();
		$sitepress_mock      = $this->get_sitepress_mock();
		$string_handler_mock = $this->get_wpml_pb_handle_strings_mock();
		$wpdb                = $this->stubs->wpdb();
		$factory             = $this->get_factory( $wpdb, $sitepress_mock );
		$strategy            = $this->get_shortcode_strategy( $factory );

		\WP_Mock::wpFunction( 'shortcode_parse_atts', array( 'return' => array() ) );
		\WP_Mock::wpFunction( 'update_post_meta', array() );

		$empty_content     = '';
		$text_to_translate = rand_str();
		$filtered_content  = '[vc_column_text]' . $text_to_translate . '[/vc_column_text]';

		\WP_Mock::onFilter( 'wpml_pb_shortcode_content_for_translation' )
		        ->with( '', $post_id )
		        ->reply( $filtered_content );
		$string_handler_mock->expects( $this->exactly( 1 ) )
		                    ->method( 'register_string' )
		                    ->with(
			                    $this->equalTo( $post_id ),
			                    $this->equalTo( $text_to_translate ),
			                    $this->equalTo( 'VISUAL' )
		                    );

		$reuse_translations_mock = Mockery::mock( 'WPML_PB_Reuse_Translations' );
		$reuse_translations_mock->shouldReceive( 'set_original_strings' );
		$reuse_translations_mock->shouldReceive( 'find_and_reuse' );

		$shortcode_handler = new WPML_PB_Register_Shortcodes( $string_handler_mock, $strategy, new WPML_PB_Shortcode_Encoding(), $reuse_translations_mock );
		$shortcode_handler->register_shortcode_strings( $post_id, $empty_content );

	}

	private function get_sitepress_mock() {
		$sitepress_mock = $this->getMockBuilder( 'SitePress' )->setMethods( array( 'get_default_language' ) )->disableOriginalConstructor()->getMock();
		$sitepress_mock->method( 'get_default_language' )->willReturn( 'en' );

		return $sitepress_mock;
	}

	/**
	 * @return WPML_PB_String_Registration|PHPUnit_Framework_MockObject_MockObject
	 */
	private function get_wpml_pb_handle_strings_mock() {
		return $this->getMockBuilder( 'WPML_PB_String_Registration' )->setMethods( array(
			'get_string_id_from_package',
			'register_string',
			'get_package_data',
			'cleanup_shortcode_content'
		) )->disableOriginalConstructor()->getMock();
	}

	/**
	 * @test
	 */
	public function check_that_clean_up_is_called_correctly() {
		$shortcode_content    = rand_long_str( 11 );
		$shortcode_attr_value = rand_long_str( 11 );
		\WP_Mock::wpFunction( 'shortcode_parse_atts', array(
			'return' => array( 'title' => WPML_PB_Shortcode_Encoding::ENCODE_TYPES_BASE64 ? base64_encode( $shortcode_attr_value ) : $shortcode_attr_value ),
		) );

		$pb_shortcodes_mock = $this->get_pb_shortcodes_mock(
			array(
				array(
					'content'    => $shortcode_content,
					'tag'        => 'dummy_shortcode',
					'attributes' => array()
				),
			)
		);

		$string_handler_mock = $this->get_wpml_pb_handle_strings_mock();
		$strategy            = $this->get_strategy_mock();

		$string_data         = array(
			'dummy_data' => array(
				'context'    => 'dummy_context',
				'name'       => 'dummy_name',
				'id'         => mt_rand( 1, 10 ),
				'package_id' => mt_rand( 1, 10 ),
			)
		);
		$strategy->method( 'get_shortcode_tag_encoding' )->willReturn( WPML_PB_Shortcode_Encoding::ENCODE_TYPES_BASE64 );
		$strategy->method( 'get_shortcode_parser' )->willReturn( $pb_shortcodes_mock );
		$strategy->method( 'get_package_strings' )->willReturn( $string_data );

		$strategy->method( 'get_package_strings' )->willReturn( array() );
		$strategy->method( 'get_shortcode_attributes' )->willReturn( array( 'text' ) );
		$strategy->method( 'get_shortcode_attribute_encoding' )->willReturn( WPML_PB_Shortcode_Encoding::ENCODE_TYPES_BASE64 );
		$strategy->expects( $this->once() )->method( 'remove_string' )->with( $string_data['dummy_data'] );

		$reuse_translations_mock = Mockery::mock( 'WPML_PB_Reuse_Translations' );
		$reuse_translations_mock->shouldReceive( 'set_original_strings' );
		$reuse_translations_mock->shouldReceive( 'find_and_reuse' );

		$shortcode_handler = new WPML_PB_Register_Shortcodes( $string_handler_mock, $strategy, new WPML_PB_Shortcode_Encoding(), $reuse_translations_mock );
		$shortcode_handler->register_shortcode_strings( mt_rand( 1, 100 ), rand_str() );
	}

	public function test_reuse_translations() {
		$post_id = mt_rand( 1, 100 );
		$strings = array( rand_str(), rand_str(), rand_str() );

		$string_handler_mock = $this->get_wpml_pb_handle_strings_mock();

		$shortcodes_mock = Mockery::mock( 'WPML_PB_Shortcodes' );
		$shortcodes_mock->shouldReceive( 'get_shortcodes' )->andReturn( array() );

		/** @var WPML_PB_Shortcode_Strategy|\Mockery\MockInterface $strategy_mock */
		$strategy_mock = Mockery::mock( 'WPML_PB_Shortcode_Strategy' );
		$strategy_mock->shouldReceive( 'get_shortcode_parser' )->andReturn( $shortcodes_mock );
		$strategy_mock->shouldReceive( 'get_package_key' )->andReturn( 'anything' );
		$strategy_mock->shouldReceive( 'get_package_strings' )->andReturn( $strings );
		$strategy_mock->shouldReceive( 'remove_string' );

		$reuse_translations_mock = Mockery::mock( 'WPML_PB_Reuse_Translations' );
		$reuse_translations_mock->shouldReceive( 'set_original_strings' )->once()->with( $strings );
		$reuse_translations_mock->shouldReceive( 'find_and_reuse' )->once()->with( $post_id, $strings );

		$shortcode_handler = new WPML_PB_Register_Shortcodes( $string_handler_mock, $strategy_mock, new WPML_PB_Shortcode_Encoding(), $reuse_translations_mock );
		$shortcode_handler->register_shortcode_strings( $post_id, rand_str() );

	}

	public function test_register_string_catch_exception() {
		$post_id        = mt_rand( 1, 100 );
		$invalid_string = rand_str();

		/** @var WPML_PB_String_Registration|\Mockery\MockInterface $string_handler_mock */
		$string_handler_mock = Mockery::mock( 'WPML_PB_String_Registration' );
		$string_handler_mock->shouldReceive( 'get_string_id_from_package' )
		                    ->with( $invalid_string )
		                    ->andThrow( 'Exception' );

		/** @var WPML_PB_Shortcode_Strategy|\Mockery\MockInterface $strategy_mock */
		$strategy_mock = Mockery::mock( 'WPML_PB_Shortcode_Strategy' );

		$shortcode_handler = new WPML_PB_Register_Shortcodes( $string_handler_mock, $strategy_mock, new WPML_PB_Shortcode_Encoding() );
		$shortcode_handler->register_string( $post_id, $invalid_string, rand_str(), rand_str(), rand_str() );

	}

	/**
	 * @test
	 * @group wpmlcore-5894
	 */
	public function it_should_not_register_string_of_ignored_content() {
		$post_id             = mt_rand();
		$text_to_ignore      = rand_str();
		$string_handler_mock = $this->get_wpml_pb_handle_strings_mock();
		$strategy            = $this->get_strategy_mock();

		$pb_shortcodes_mock  = $this->get_pb_shortcodes_mock(
			array(
				array(
					'content'    => $text_to_ignore,
					'tag'        => 'tag_with_ignore_content',
					'attributes' => array()
				),
			)
		);

		$strategy->method( 'get_shortcode_parser' )->willReturn( $pb_shortcodes_mock );
		$strategy->method( 'get_shortcode_ignore_content' )->with( 'tag_with_ignore_content' )->willReturn( true );

		\WP_Mock::wpFunction( 'shortcode_parse_atts', array( 'return' => array() ) );
		\WP_Mock::wpFunction( 'update_post_meta', array() );

		$content = '[tag_with_ignore_content]' . $text_to_ignore . '[/tag_with_ignore_content]';

		$string_handler_mock->expects( $this->never() )->method( 'register_string' );

		$shortcode_handler = new WPML_PB_Register_Shortcodes( $string_handler_mock, $strategy, new WPML_PB_Shortcode_Encoding() );
		$shortcode_handler->register_shortcode_strings( $post_id, $content );
	}

	/**
	 * @test
	 * @dataProvider dp_media_tag_type
	 * @group wpmlcore-5894
	 *
	 * @param string $type
	 */
	public function it_should_not_register_string_of_media_content( $type ) {
		$post_id             = mt_rand();
		$text_to_ignore      = rand_str();
		$string_handler_mock = $this->get_wpml_pb_handle_strings_mock();
		$strategy            = $this->get_strategy_mock();

		$pb_shortcodes_mock  = $this->get_pb_shortcodes_mock(
			array(
				array(
					'content'    => $text_to_ignore,
					'tag'        => 'tag_with_media_content',
					'attributes' => array()
				),
			)
		);

		$strategy->method( 'get_shortcode_parser' )->willReturn( $pb_shortcodes_mock );
		$strategy->method( 'get_shortcode_ignore_content' )->with( 'tag_with_media_content' )->willReturn( false );
		$strategy->method( 'get_shortcode_tag_type' )->with( 'tag_with_media_content' )->willReturn( $type );

		\WP_Mock::wpFunction( 'shortcode_parse_atts', array( 'return' => array() ) );
		\WP_Mock::wpFunction( 'update_post_meta', array() );

		$content = '[tag_with_media_content]' . $text_to_ignore . '[/tag_with_media_content]';

		$string_handler_mock->expects( $this->never() )->method( 'register_string' );

		$shortcode_handler = new WPML_PB_Register_Shortcodes( $string_handler_mock, $strategy, new WPML_PB_Shortcode_Encoding() );
		$shortcode_handler->register_shortcode_strings( $post_id, $content );
	}

	public function dp_media_tag_type() {
		return array(
			'media-url' => array( 'media-url' ),
			'media-ids' => array( 'media-ids' ),
		);
	}

	/** @return WPML_PB_Shortcode_Strategy|PHPUnit_Framework_MockObject_MockObject */
	private function get_strategy_mock() {
		return $this->getMockBuilder( 'WPML_PB_Shortcode_Strategy' )
                    ->setMethods( array(
                                      'get_shortcode_parser',
                                      'get_package_strings',
                                      'get_shortcode_tag_encoding',
                                      'get_shortcode_tag_type',
                                      'get_shortcode_attributes',
                                      'get_shortcode_attribute_encoding',
                                      'get_shortcode_tag_encoding_condition',
                                      'get_shortcode_ignore_content',
                                      'remove_string'
                                  ) )
                    ->disableOriginalConstructor()
                    ->getMock();
	}

	/** @return WPML_PB_Shortcodes|PHPUnit_Framework_MockObject_MockObject */
	private function get_pb_shortcodes_mock( array $shortcodes ) {
		$pb_shortcodes_mock = $this->getMockBuilder( 'WPML_PB_Shortcodes' )
		                           ->setMethods( array( 'get_shortcodes' ) )
		                           ->disableOriginalConstructor()
		                           ->getMock();
		$pb_shortcodes_mock->method( 'get_shortcodes' )->willReturn( $shortcodes );

		return $pb_shortcodes_mock;
	}
}
