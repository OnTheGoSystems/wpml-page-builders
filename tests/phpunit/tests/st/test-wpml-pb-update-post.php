<?php

class Test_WPML_PB_Update_Post extends WPML_PB_TestCase {

	function setUp() {
		parent::setUp();

		\WP_Mock::wpFunction( 'get_shortcode_regex', array(
			'return' => '\[(\[?)(vc_column_text)(?![\w-])([^\]\/]*(?:\/(?!\])[^\]\/]*)*?)(?:(\/)\]|\](?:([^\[]*+(?:\[(?!\/\2\])[^\[]*+)*+)\[\/\2\])?)(\]?)',
		) );

	}

	/**
	 * @test
	 * @dataProvider encode_data_provider
	 */
	public function test_update( $encode ) {

		$string                              = rand_str();
		$translated_string                   = rand_str();
		$title_string                        = rand_str();
		$title_string_2                      = rand_str();
		$translated_title_string             = rand_str();
		$text_string                         = rand_str();
		$translated_text_string              = rand_str();
		$string_without_translation_complete = rand_str();
		$post_id                             = 123;

		/** @var \WP_Post|\PHPUnit_Framework_MockObject_MockObject $wp_post */
		$wp_post = $this->getMockBuilder( 'WP_Post' )
						->disableOriginalConstructor()
						->getMock();

		$wp_post->ID           = $post_id;
		$wp_post->post_content = '[vc_column_text title="' . ( $encode ? base64_encode( $title_string ) : $title_string ) . '" text="' . ( $encode ? base64_encode( $text_string ) : $text_string ) . '"]' . ( $encode ? base64_encode( $string ) : $string ) . '[/vc_column_text]' .
								 '[vc_column_text title="' . ( $encode ? base64_encode( $title_string_2 ) : $title_string_2 ) . '"]' . $string_without_translation_complete . '[/vc_column_text]';
		$wp_post->post_type    = 'page';

		$language                            = rand_str( 4 );

		\WP_Mock::wpFunction( 'get_post', array(
			'args'   => array( $post_id ),
			'return' => $wp_post,
		) );
		\WP_Mock::wpFunction( 'wp_update_post', array(
			'times' => 1,
			'args'  => array(
				array(
					'ID'           => $post_id,
					'post_content' => '[vc_column_text title="' . ( $encode ? base64_encode( $translated_title_string ) : $translated_title_string ) . '" text="' . ( $encode ? base64_encode( $translated_text_string ) : $translated_text_string ) . '"]' . ( $encode ? base64_encode( $translated_string ) : $translated_string ) . '[/vc_column_text]' .
									  '[vc_column_text title="' . ( $encode ? base64_encode( $translated_title_string ) : $translated_title_string ) . '"]' . $string_without_translation_complete . '[/vc_column_text]',
				)
			)
		) );

		\WP_Mock::wpFunction( 'shortcode_parse_atts', array(
			'return' => array(
				'title' => $encode ? base64_encode( $title_string ) : $title_string,
				'text'  => $encode ? base64_encode( $text_string ) : $text_string
			),
			'args'   => array( ' title="' . ( $encode ? base64_encode( $title_string ) : $title_string ) . '" text="' . ( $encode ? base64_encode( $text_string ) : $text_string ) . '"' ),
		) );
		\WP_Mock::wpFunction( 'shortcode_parse_atts', array(
			'return' => array( 'title' => $encode ? base64_encode( $title_string_2 ) : $title_string_2 ),
			'args'   => array( ' title="' . ( $encode ? base64_encode( $title_string_2 ) : $title_string_2 ) . '"' ),
		) );

		$wpdb_mock      = $this->get_wpdb_mock();
		$sitepress_mock = $this->get_sitepress_mock( $post_id, $language );
		$factory        = $this->get_factory( $wpdb_mock, $sitepress_mock );
		$strategy = $this->get_shortcode_strategy( $factory, $encode );

		$package      = $this->get_package_mock( $post_id,
			$string,
			$translated_string,
			$title_string,
			$title_string_2,
			$translated_title_string,
			$text_string,
			$translated_text_string,
			$string_without_translation_complete,
			$language );
		$package_data = array( 'package' => $package, 'languages' => array( $language ) );

		$update_post = $factory->get_update_post( $package_data, $strategy );
		$update_post->update();
	}

	public function encode_data_provider() {
		return array(
			array( false ),
			array( true )
		);
	}

	private function get_package_mock(
		$post_id,
		$string,
		$translated_string,
		$title_string,
		$title_string_2,
		$translated_title_string,
		$text_string,
		$translated_text_string,
		$string_without_translation_complete,
		$language
	) {
		$package          = $this->getMockBuilder( 'WPML_Package' )
		                         ->setMethods( array( 'get_translated_strings' ) )
		                         ->disableOriginalConstructor()
		                         ->getMock();
		$package->post_id = $post_id;
		$package->method( 'get_translated_strings' )
		        ->willReturn( array(
			        md5( $string )                              => array(
				        $language => array(
					        'value'  => $translated_string,
					        'status' => 10
				        )
			        ),
			        md5( $title_string )                        => array(
				        $language => array(
					        'value'  => $translated_title_string,
					        'status' => 10
				        )
			        ),
			        md5( $text_string )                         => array(
				        $language => array(
					        'value'  => $translated_text_string,
					        'status' => 10
				        )
			        ),
			        md5( $title_string_2 )                      => array(
				        $language => array(
					        'value'  => $translated_title_string,
					        'status' => 10
				        )
			        ),
			        md5( $string_without_translation_complete ) => array(
				        $language => array(
					        'value'  => rand_str(),
					        'status' => 0
				        )
			        ),
		        ) );

		return $package;
	}

	private function get_wpdb_mock() {
		$wpdb = $this->getMockBuilder( 'wpdb' )
		             ->setMethods( array( 'get_row' ) )
		             ->disableOriginalConstructor()
		             ->getMock();

		return $wpdb;
	}

	private function get_sitepress_mock( $post_id, $language ) {
		$sitepress_mock = $this->getMockBuilder( 'SitePress' )
		                       ->setMethods( array( 'get_element_trid', 'get_element_translations' ) )
		                       ->disableOriginalConstructor()
		                       ->getMock();
		$sitepress_mock->method( 'get_element_trid' )->willReturn( 1 );
		$element             = new stdClass();
		$element->element_id = $post_id;
		$sitepress_mock->method( 'get_element_translations' )
		               ->with( $this->isType( 'int' ), $this->isType( 'string' ), false, true )
		               ->willReturn( array( $language => $element ) );

		return $sitepress_mock;
	}

}
