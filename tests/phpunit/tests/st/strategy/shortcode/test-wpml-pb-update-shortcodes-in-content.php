<?php

/**
 * @group page-builders
 * @group page-builders-shortcodes
 * @group wpmlcore-3705
 */
class Test_WPML_PB_Update_Shortcodes_In_Content extends WPML_PB_TestCase {
	/**
	 * @var WPML_PB_Shortcode_Strategy
	 */
	private $shortcode_strategy;

	/**
	 * @var WPML_PB_Shortcodes
	 */
	private $shortcode_parser;

	private $shortcode_attribute_encoding;

	function setUp() {
		$that = $this;
		parent::setUp();

		$this->shortcode_strategy = $this->getMockBuilder( 'WPML_PB_Shortcode_Strategy' )
		                                 ->disableOriginalConstructor()
		                                 ->setMethods( array(
			                                 'get_shortcode_parser',
			                                 'get_shortcodes',
			                                 'get_shortcode_tag_encoding',
			                                 'get_shortcode_attributes',
			                                 'get_shortcode_attribute_encoding',
		                                 ) )
		                                 ->getMock();

		$this->shortcode_parser = $this->getMockBuilder( 'WPML_PB_Shortcodes' )
		                               ->disableOriginalConstructor()
		                               ->setMethods( array( 'get_shortcodes' ) )
		                               ->getMock();

		$this->shortcode_strategy->method( 'get_shortcode_parser' )->willReturn( $this->shortcode_parser );
		$this->shortcode_strategy->method( 'get_shortcode_tag_encoding' )->willReturn( '' );
		$this->shortcode_strategy->method( 'get_shortcode_attribute_encoding' )
            ->willReturnCallback( function() use ( $that ){
				return $that->shortcode_attribute_encoding;
		});

		$this->shortcode_attribute_encoding = '';

		WP_Mock::wpFunction( 'shortcode_parse_atts', array(
			'return' => function ( $attribs ) {
				$result = array();
				if ( ! $attribs ) {
					return $result;
				}

				$attribs_arr = explode( '=', trim( $attribs ) );

				if ( 2 === count( $attribs_arr ) ) {
					list( $name, $value ) = $attribs_arr;
					$result[ $name ] = trim( $value, '""' );
				} else {
					$attribs_arr = explode( '" ', trim( $attribs ) );

					foreach ( $attribs_arr as $attrib ) {
						$single_attrib_arr = explode( '=', $attrib );
						$result[ trim( $single_attrib_arr[0], '""' ) ] = trim( $single_attrib_arr[1], '""' );
					}
				}

				return $result;
			},
		) );
	}

	/**
	 * @test
	 * @dataProvider update_content_data_provider
	 * @group wpmlst-1536
	 */
	function it_updates_content( $original_content, $expected_content, $short_codes, $shortcode_attribs, $parsed_shortcodes, $translations ) {
		$this->shortcode_strategy->method( 'get_shortcodes' )->willReturn( $short_codes );
		$this->shortcode_strategy->method( 'get_shortcode_attributes' )->willReturnCallback( function ( $tag ) use ( $shortcode_attribs ) {
			return isset( $shortcode_attribs[ $tag ] ) ? $shortcode_attribs[ $tag ] : array();
		} );

		$this->shortcode_parser->method( 'get_shortcodes' )
			->with( WPML_PB_Shortcode_Content_Wrapper::maybeWrap( $original_content, $short_codes ) )
			->willReturn( $parsed_shortcodes );

		$subject = new WPML_PB_Update_Shortcodes_In_Content( $this->shortcode_strategy, new WPML_PB_Shortcode_Encoding() );
		$actual  = $subject->update_content( $original_content, $translations, 'fr' );

		$this->assertSame( $expected_content, $actual );
	}

	public function update_content_data_provider() {
		if ( ! defined( 'ICL_TM_COMPLETE' ) ) {
			define( 'ICL_TM_COMPLETE', 10 );
		}

		$long_original    = rand_long_str( WPML_PB_Update_Shortcodes_In_Content::LONG_STRING_THRESHOLD + 1 );
		$long_translation = rand_long_str( WPML_PB_Update_Shortcodes_In_Content::LONG_STRING_THRESHOLD + 2 );

		return array(
			array(
				'[et_row][et_shortcode1 name="Shortcode 1 name"]Some inner text [gallery][/et_shortcode1][et_shortcode2]Shortcode 2 inner text[/et_shortcode2][/et_row]',
				'[et_row][et_shortcode1 name="&#91;text&#93; Shortcode 1 name fr"]fr Some inner text [gallery][/et_shortcode1][et_shortcode2]fr Shortcode 2 inner text[/et_shortcode2][/et_row]',
				array( 'et_shortcode1', 'et_shortcode2' ),
				array( 'et_shortcode1' => array( 'name' ) ),
				array(
					array(
						'block'      => '[et_shortcode1 name="Shortcode 1 name"]Some inner text [gallery][/et_shortcode1]',
						'tag'        => 'et_shortcode1',
						'attributes' => ' name="Shortcode 1 name"',
						'content'    => 'Some inner text [gallery]',
					),
					array(
						'block'      => '[et_shortcode2]Shortcode 2 inner text[/et_shortcode2]',
						'tag'        => 'et_shortcode2',
						'attributes' => '',
						'content'    => 'Shortcode 2 inner text',
					),
				),
				array(
					md5( 'Shortcode 1 name' )          => array(
						'fr' => array(
							'status' => ICL_TM_COMPLETE,
							'value'  => '[text] Shortcode 1 name fr', // test escaping of special chars from attributes
						),
					),
					md5( 'Some inner text [gallery]' ) => array(
						'fr' => array(
							'status' => ICL_TM_COMPLETE,
							'value'  => 'fr Some inner text [gallery]',
						),
					),
					md5( 'Shortcode 2 inner text' )    => array(
						'fr' => array(
							'status' => ICL_TM_COMPLETE,
							'value'  => 'fr Shortcode 2 inner text',
						),
					),
				),
			),
			'Test attributes escaping'                                    => array(
				'[et_shortcode title="title value" /]',
				'[et_shortcode title="&#91;&lt;text&gt;&#93; fr title value" /]',
				array( 'et_shortcode' ),
				array( 'et_shortcode' => array( 'title' ) ),
				array(
					array(
						'block'      => '[et_shortcode title="title value" /]]',
						'tag'        => 'et_shortcode',
						'attributes' => ' title="title value"',
						'content'    => '',
					),
				),
				array(
					md5( 'title value' ) => array(
						'fr' => array(
							'status' => ICL_TM_COMPLETE,
							'value'  => '[<text>] fr title value', // test escaping of special chars from attributes
						),
					),
				),
			),
			"Don't escape already escaped chars" => array(
				'[et_shortcode title="My text: A &amp; B and other" /]',
				'[et_shortcode title="FR My text: A &amp; B and other" /]',
				array( 'et_shortcode' ),
				array( 'et_shortcode' => array( 'title' ) ),
				array(
					array(
						'block'      => '[et_shortcode title="My text: A &amp; B and other" /]]',
						'tag'        => 'et_shortcode',
						'attributes' => ' title="My text: A &amp; B and other"',
						'content'    => '',
					),
				),
				array(
					md5( 'My text: A &amp; B and other' ) => array(
						'fr' => array(
							'status' => ICL_TM_COMPLETE,
							'value'  => 'FR My text: A &amp; B and other',
						),
					),
				),
			),
			'Test translating only translatable attributes even when their values are the same' => array(
				'[et_shortcode title="title value" title2="title value" /]',
				'[et_shortcode title="fr title value" title2="title value" /]',
				array( 'et_shortcode' ),
				array( 'et_shortcode' => array( 'title' ) ),
				array(
					array(
						'block'      => '[et_shortcode title="title value" title2="title value" /]]',
						'tag'        => 'et_shortcode',
						'attributes' => ' title="title value" title2="title value"',
						'content'    => '',
					),
				),
				array(
					md5( 'title value' ) => array(
						'fr' => array(
							'status' => ICL_TM_COMPLETE,
							'value'  => 'fr title value',
						),
					),
				),
			),
			'Test content and attribute has same text and needs escaping' => array(
				'[et_shortcode title="some value"]some value[/et_shortcode]',
				'[et_shortcode title="&#91;&lt;text&gt;&#93; fr some value"][<text>] fr some value[/et_shortcode]',
				array( 'et_shortcode' ),
				array( 'et_shortcode' => array( 'title' ) ),
				array(
					array(
						'block'      => '[et_shortcode title="some value"]some value[/et_shortcode]',
						'tag'        => 'et_shortcode',
						'attributes' => ' title="some value"',
						'content'    => 'some value',
					),
				),
				array(
					md5( 'some value' ) => array(
						'fr' => array(
							'status' => ICL_TM_COMPLETE,
							'value'  => '[<text>] fr some value', // test escaping of special chars from attributes
						),
					),
				),
			),
			'Test attribute with slash - https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlst-1194' => array(
				'[et_shortcode title="http://some value"][/et_shortcode]',
				'[et_shortcode title="http://fr some value"][/et_shortcode]',
				array( 'et_shortcode' ),
				array( 'et_shortcode' => array( 'title' ) ),
				array(
					array(
						'block'      => '[et_shortcode title="http://some value"][/et_shortcode]',
						'tag'        => 'et_shortcode',
						'attributes' => ' title="http://some value"',
						'content'    => '',
					),
				),
				array(
					md5( 'http://some value' ) => array(
						'fr' => array(
							'status' => ICL_TM_COMPLETE,
							'value'  => 'http://fr some value',
						),
					),
				),
			),
			'Tabs with the same title - https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlst-1536' => array(
				'[et_pb_tab title="Tabs"]Blue tab[/et_pb_tab][et_pb_tab title="Tabs"]Red tab[/et_pb_tab]',
				'[et_pb_tab title="FR Tabs"]FR Blue tab[/et_pb_tab][et_pb_tab title="FR Tabs"]FR Red tab[/et_pb_tab]',
				array( 'et_pb_tab' ),
				array( 'et_pb_tab' => array( 'title' ) ),
				array(
					array(
						'block'      => '[et_pb_tab title="Tabs"]Blue tab[/et_pb_tab]',
						'tag'        => 'et_pb_tab',
						'attributes' => ' title="Tabs"',
						'content'    => 'Blue tab',
					),
					array(
						'block'      => '[et_pb_tab title="Tabs"]Red tab[/et_pb_tab]',
						'tag'        => 'et_pb_tab',
						'attributes' => ' title="Tabs"',
						'content'    => 'Red tab',
					),
				),
				array(
					md5( 'Tabs' ) => array(
						'fr' => array(
							'status' => ICL_TM_COMPLETE,
							'value'  => 'FR Tabs',
						),
					),
					md5( 'Blue tab' ) => array(
						'fr' => array(
							'status' => ICL_TM_COMPLETE,
							'value'  => 'FR Blue tab',
						),
					),
					md5( 'Red tab' ) => array(
						'fr' => array(
							'status' => ICL_TM_COMPLETE,
							'value'  => 'FR Red tab',
						),
					),
				),
			),
			'Backward reference in translation - https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-5291' => array(
				'[et_pb_tab title="$25.each"]$1999.each[/et_pb_tab]',
				'[et_pb_tab title="$4.each"]$123.each[/et_pb_tab]',
				array( 'et_pb_tab' ),
				array( 'et_pb_tab' => array( 'title' ) ),
				array(
					array(
						'block'      => '[et_pb_tab title="$25.each"]$1999.each[/et_pb_tab]',
						'tag'        => 'et_pb_tab',
						'attributes' => ' title="$25.each"',
						'content'    => '$1999.each',
					),
				),
				array(
					md5( '$25.each' ) => array(
						'fr' => array(
							'status' => ICL_TM_COMPLETE,
							'value'  => '$4.each',
						),
					),
					md5( '$1999.each' ) => array(
						'fr' => array(
							'status' => ICL_TM_COMPLETE,
							'value'  => '$123.each',
						),
					),
				),
			),
			'Wrapped content - https://onthegosystems.myjetbrains.com/youtrack/issue/wpmltm-2970' => array(
				'[shortcode_A]Unwrapped text[shortcode_B title="My title"][/shortcode_A]',
				'[shortcode_A]FR Unwrapped text[shortcode_B title="FR My title"][/shortcode_A]',
				array( 'shortcode_A', 'shortcode_B' ),
				array( 'shortcode_B' => array( 'title' ) ),
				array(
					array(
						'block'      => '[shortcode_A][wpml_string_wrapper]Unwrapped text[/wpml_string_wrapper][shortcode_B title="My title"][/shortcode_A]',
						'tag'        => 'shortcode_A',
						'attributes' => '',
						'content'    => '[wpml_string_wrapper]Unwrapped text[/wpml_string_wrapper][shortcode_B title="My title"]',
					),
					array(
						'block'      => '[wpml_string_wrapper]Unwrapped text[/wpml_string_wrapper]',
						'tag'        => 'wpml_string_wrapper',
						'attributes' => '',
						'content'    => 'Unwrapped text',
					),
					array(
						'block'      => '[shortcode_B title="My title"]',
						'tag'        => 'shortcode_B',
						'attributes' => ' title="My title"',
						'content'    => '',
					),
				),
				array(
					md5( 'Unwrapped text' ) => array(
						'fr' => array(
							'status' => ICL_TM_COMPLETE,
							'value'  => 'FR Unwrapped text',
						),
					),
					md5( 'My title' ) => array(
						'fr' => array(
							'status' => ICL_TM_COMPLETE,
							'value'  => 'FR My title',
						),
					),
				),
			),
			'Wrapped content same as attribute name - https://onthegosystems.myjetbrains.com/youtrack/issue/wpmltm-2970' => array(
				'[shortcode_A][shortcode_B title="My title"]title[/shortcode_A]',
				'[shortcode_A][shortcode_B title="FR My title"]FR title[/shortcode_A]',
				array( 'shortcode_A', 'shortcode_B' ),
				array( 'shortcode_B' => array( 'title' ) ),
				array(
					array(
						'block'      => '[shortcode_A][shortcode_B title="My title"][wpml_string_wrapper]title[/wpml_string_wrapper][/shortcode_A]',
						'tag'        => 'shortcode_A',
						'attributes' => '',
						'content'    => '[shortcode_B title="My title"][wpml_string_wrapper]title[/wpml_string_wrapper]',
					),
					array(
						'block'      => '[shortcode_B title="My title"]',
						'tag'        => 'shortcode_B',
						'attributes' => ' title="My title"',
						'content'    => '',
					),
					array(
						'block'      => '[wpml_string_wrapper]title[/wpml_string_wrapper]',
						'tag'        => 'wpml_string_wrapper',
						'attributes' => '',
						'content'    => 'title',
					),
				),
				array(
					md5( 'My title' ) => array(
						'fr' => array(
							'status' => ICL_TM_COMPLETE,
							'value'  => 'FR My title',
						),
					),
					md5( 'title' ) => array(
						'fr' => array(
							'status' => ICL_TM_COMPLETE,
							'value'  => 'FR title',
						),
					),
				),
			),
			'Long wrapped content - https://onthegosystems.myjetbrains.com/youtrack/issue/wpmltm-2970' => array(
				'[shortcode_A]' . $long_original . '[shortcode_B title="My title"][/shortcode_A]',
				'[shortcode_A]' . $long_translation . '[shortcode_B title="FR My title"][/shortcode_A]',
				array( 'shortcode_A', 'shortcode_B' ),
				array( 'shortcode_B' => array( 'title' ) ),
				array(
					array(
						'block'      => '[shortcode_A][wpml_string_wrapper]' . $long_original . '[/wpml_string_wrapper][shortcode_B title="My title"][/shortcode_A]',
						'tag'        => 'shortcode_A',
						'attributes' => '',
						'content'    => '[wpml_string_wrapper]' . $long_original . '[/wpml_string_wrapper][shortcode_B title="My title"]',
					),
					array(
						'block'      => '[wpml_string_wrapper]'. $long_original . '[/wpml_string_wrapper]',
						'tag'        => 'wpml_string_wrapper',
						'attributes' => '',
						'content'    => $long_original,
					),
					array(
						'block'      => '[shortcode_B title="My title"]',
						'tag'        => 'shortcode_B',
						'attributes' => ' title="My title"',
						'content'    => '',
					),
				),
				array(
					md5( $long_original ) => array(
						'fr' => array(
							'status' => ICL_TM_COMPLETE,
							'value'  => $long_translation,
						),
					),
					md5( 'My title' ) => array(
						'fr' => array(
							'status' => ICL_TM_COMPLETE,
							'value'  => 'FR My title',
						),
					),
				),
			),
			'Unwrapped content - https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-6971' => [
				'[shortcode_A foo="bar"]Some text[shortcode_A foo="bar2"]',
				'[shortcode_A foo="FR bar"]FR Some text[shortcode_A foo="FR bar2"]',
				[ 'shortcode_A' ],
				[ 'shortcode_A' => [ 'foo' ] ],
				[
					[
						'block'      => '[shortcode_A foo="bar"]',
						'tag'        => 'shortcode_A',
						'attributes' => ' foo="bar"',
						'content'    => '',
					],
					[
						'block'      => '[wpml_string_wrapper]Some text[/wpml_string_wrapper]',
						'tag'        => 'wpml_string_wrapper',
						'attributes' => '',
						'content'    => 'Some text',
					],
					[
						'block'      => '[shortcode_A foo="bar2"]',
						'tag'        => 'shortcode_A',
						'attributes' => ' foo="bar2"',
						'content'    => '',
					],
					[
						'block'      => '[wpml_string_wrapper][shortcode_A foo="bar"]Some text[shortcode_A foo="bar2"][/wpml_string_wrapper]',
						'tag'        => 'wpml_string_wrapper',
						'attributes' => '',
						'content'    => '[shortcode_A foo="bar"]Some text[shortcode_A foo="bar2"]',
					],
				],
				[
					md5( 'Some text' ) => [
						'fr' => [
							'status' => ICL_TM_COMPLETE,
							'value'  => 'FR Some text',
						],
					],
					md5( 'bar' ) => [
						'fr' => [
							'status' => ICL_TM_COMPLETE,
							'value'  => 'FR bar',
						],
					],
					md5( 'bar2' ) => [
						'fr' => [
							'status' => ICL_TM_COMPLETE,
							'value'  => 'FR bar2',
						],
					],
				],
			],
		);
	}

	/**
	 * @test
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlst-1318
	 * @group wpmlst-1318
	 */
	function it_updates_content_with_attribute_allowing_html_tags() {
		$original_content  = '[et_shortcode heading="<b>Hello</b>"][/et_shortcode]';
		$expected_content  = '[et_shortcode heading="<b>Salut</b>"][/et_shortcode]';
		$shortcodes        = array( 'et_shortcode' );
		$shortcode_attribs = array( 'et_shortcode' => array( 'heading' ) );
		$parsed_shortcodes = array(
			array(
				'block'      => '[et_shortcode heading="<b>Hello</b>"][/et_shortcode]',
				'tag'        => 'et_shortcode',
				'attributes' => ' heading="<b>Hello</b>"',
				'content'    => '',
			),
		);
		$translations = array(
			md5( '<b>Hello</b>' ) => array(
				'fr' => array(
					'status' => ICL_TM_COMPLETE,
					'value'  => '<b>Salut</b>',
				),
			),
		);

		$this->shortcode_strategy->method( 'get_shortcodes' )->willReturn( $shortcodes );
		$this->shortcode_strategy->method( 'get_shortcode_attributes' )->willReturnCallback( function ( $tag ) use ( $shortcode_attribs ) {
			return isset( $shortcode_attribs[ $tag ] ) ? $shortcode_attribs[ $tag ] : array();
		} );

		$this->shortcode_attribute_encoding = 'allow_html_tags';

		$this->shortcode_parser->method( 'get_shortcodes' )->with( $original_content )->willReturn( $parsed_shortcodes );

		$subject = new WPML_PB_Update_Shortcodes_In_Content( $this->shortcode_strategy, new WPML_PB_Shortcode_Encoding() );
		$actual  = $subject->update_content( $original_content, $translations, 'fr' );

		$this->assertSame( $expected_content, $actual );
	}

	/**
	 * @test
	 * @group wpmlcore-4613
	 */
	function it_filters_string_translations_before_content_update() {
		$original_title   = rand_str();
		$translated_title = rand_str();

		$original_content  = '[et_shortcode title="' . $original_title . '" /]';
		$expected_content  = '[et_shortcode title="' . $translated_title . '" /]';
		$short_codes       = array( 'et_shortcode' );
		$shortcode_attribs = array( 'et_shortcode' => array( 'title' ) );
		$parsed_shortcodes = array(
			array(
				'block'      => '[et_shortcode title="' . $original_title . '" /]]',
				'tag'        => 'et_shortcode',
				'attributes' => ' title="' . $original_title . '"',
				'content'    => '',
			),
		);
		$translations = array(
			md5( $original_title ) => array(
				'fr' => array(
					'status' => ICL_TM_COMPLETE,
					'value'  => $translated_title,
				),
			),
		);

		$this->shortcode_strategy->method( 'get_shortcodes' )->willReturn( $short_codes );
		$this->shortcode_strategy->method( 'get_shortcode_attributes' )->willReturnCallback( function ( $tag ) use ( $shortcode_attribs ) {
			return isset( $shortcode_attribs[ $tag ] ) ? $shortcode_attribs[ $tag ] : array();
		} );

		$this->shortcode_parser->method( 'get_shortcodes' )->with( $original_content )->willReturn( $parsed_shortcodes );

		\WP_Mock::onFilter( 'wpml_pb_before_replace_string_with_translation' )
			->with( $original_title, true )
			->reply( $translated_title );

		$subject = new WPML_PB_Update_Shortcodes_In_Content( $this->shortcode_strategy, new WPML_PB_Shortcode_Encoding() );
		$actual  = $subject->update_content( $original_content, $translations, 'fr' );

		$this->assertSame( $expected_content, $actual );
	}

	/**
	 * @test
	 */
	public function update_translated_post_content_is_empty() {

		$translated_post_id            = mt_rand();
		$original_post                 = new stdClass();
		$original_post->ID             = mt_rand();
		$original_post->post_content   = rand_str( 20 );
		$translated_post               = new stdClass();
		$translated_post->post_content = '';
		$string_translations           = array();
		$lang                          = rand_str( 5 );

		$this->shortcode_parser->method( 'get_shortcodes' )
		                       ->willReturn( array() );

		$this->shortcode_strategy->method( 'get_shortcode_parser' )
		                         ->willReturn( $this->shortcode_parser );
		$this->shortcode_strategy->method( 'get_shortcodes' )->willReturn( [] );

		\WP_Mock::wpFunction( 'get_post', array(
			'return' => $translated_post,
		) );

		\WP_Mock::userFunction( 'wpml_update_escaped_post', [
			'times' => 1,
			'args'  => [
				[
					'ID'           => $translated_post_id,
					'post_content' => $original_post->post_content,
				]
			],
		] );

		$subject = new WPML_PB_Update_Shortcodes_In_Content( $this->shortcode_strategy, new WPML_PB_Shortcode_Encoding() );
		$subject->update( $translated_post_id, $original_post, $string_translations, $lang );
	}

	/**
	 * @test
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmltm-1590
	 */
	public function update_translated_post_does_not_exist() {

		$translated_post_id          = mt_rand();
		$original_post               = new stdClass();
		$original_post->ID           = mt_rand();
		$original_post->post_content = rand_str( 20 );
		$string_translations         = array();
		$lang                        = rand_str( 5 );

		$this->shortcode_parser->method( 'get_shortcodes' )
		                       ->willReturn( array() );

		$this->shortcode_strategy->method( 'get_shortcode_parser' )
		                         ->willReturn( $this->shortcode_parser );
		$this->shortcode_strategy->method( 'get_shortcodes' )->willReturn( [] );

		\WP_Mock::wpFunction( 'get_post', array(
			'return' => null,
		) );

		\WP_Mock::userFunction( 'wpml_update_escaped_post', [
			'times' => 1,
			'args'  => [
				[
					'ID'           => $translated_post_id,
					'post_content' => $original_post->post_content,
				]
			],
		] );


		$subject = new WPML_PB_Update_Shortcodes_In_Content( $this->shortcode_strategy, new WPML_PB_Shortcode_Encoding() );
		$subject->update( $translated_post_id, $original_post, $string_translations, $lang );
	}

	/**
	 * @group wpmlst-1131
	 * @group page-builders
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlst-1131
	 */
	public function test_that_filter_can_override_content_when_saving() {
		$translated_post_id            = mt_rand();
		$translated_post               = new stdClass();
		$translated_post->post_content = rand_str();;

		$original_post               = new stdClass();
		$original_post->ID           = mt_rand();
		$original_post->post_content = rand_str( 20 );

		$string_translations = array();
		$lang                = rand_str( 5 );

		$filtered_content = rand_str();

		// This will make the wp_update_post run only if filters are called for original and translated post
		$filtered_translation = '';

		$this->shortcode_parser->method( 'get_shortcodes' )
		                       ->willReturn( array() );

		$this->shortcode_strategy->method( 'get_shortcode_parser' )
		                         ->willReturn( $this->shortcode_parser );
		$this->shortcode_strategy->method( 'get_shortcodes' )->willReturn( [] );

		\WP_Mock::onFilter( 'wpml_pb_shortcode_content_for_translation' )
		        ->with( $original_post->post_content, $original_post->ID )
		        ->reply( $filtered_content );

		\WP_Mock::onFilter( 'wpml_pb_shortcode_content_for_translation' )
		        ->with( $translated_post->post_content, $translated_post_id )
		        ->reply( $filtered_translation );

		\WP_Mock::wpFunction( 'get_post', array(
			'return' => $translated_post,
		) );

		\WP_Mock::userFunction( 'wpml_update_escaped_post', [
			'times' => 1,
			'args'  => [
				[
					'ID'           => $translated_post_id,
					'post_content' => $filtered_content,
				]
			],
		] );


		$subject = new WPML_PB_Update_Shortcodes_In_Content( $this->shortcode_strategy, new WPML_PB_Shortcode_Encoding() );
		$subject->update( $translated_post_id, $original_post, $string_translations, $lang );

	}

	/**
	 * @group wpmlst-1131
	 * @group page-builders
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlst-1131
	 * @dataProvider translation_saved_data_provider
	 */
	public function test_filter_wpml_pb_shortcodes_save_translation( $saved ) {
		$translated_post_id            = mt_rand();
		$translated_post               = new stdClass();
		$translated_post->post_content = '';

		$original_post               = new stdClass();
		$original_post->ID           = mt_rand();
		$original_post->post_content = rand_str( 20 );

		$string_translations = array();
		$lang                = rand_str( 5 );

		$this->shortcode_parser->method( 'get_shortcodes' )
		                       ->willReturn( array() );

		$this->shortcode_strategy->method( 'get_shortcode_parser' )
		                         ->willReturn( $this->shortcode_parser );
		$this->shortcode_strategy->method( 'get_shortcodes' )->willReturn( [] );

		\WP_Mock::onFilter( 'wpml_pb_shortcodes_save_translation' )
		        ->with( false, $translated_post_id, $original_post->post_content )
		        ->reply( $saved );

		\WP_Mock::wpFunction( 'get_post', array(
			'return' => $translated_post,
		) );

		if ( $saved ) {
			\WP_Mock::wpFunction( 'wpml_update_escaped_post', array(
				'times' => 0,
			) );
		} else {
			\WP_Mock::userFunction( 'wpml_update_escaped_post', [
				'times' => 1,
				'args'  => [
					[
						'ID'           => $translated_post_id,
						'post_content' => $original_post->post_content,
					]
				],
			] );
		}

		$subject = new WPML_PB_Update_Shortcodes_In_Content( $this->shortcode_strategy, new WPML_PB_Shortcode_Encoding() );
		$subject->update( $translated_post_id, $original_post, $string_translations, $lang );

	}

	/**
	 * @test
	 * @group wpmlcore-5470
	 */
	public function it_updates_shortcodes_with_long_text() {
		$original         = $this->get_long_text( 5001 );
		$original_content = '[et_row][et_shortcode1]' . $original . '[/et_shortcode1][/et_row]';

		$translation      = $this->get_long_text( 6001 );
		$expected_content = '[et_row][et_shortcode1]' . $translation . '[/et_shortcode1][/et_row]';

		$shortcodes        = array( 'et_shortcode1' );
		$shortcode_attribs = array( 'et_shortcode1' => array() );
		$parsed_shortcodes = array(
			array(
				'block'      => '[et_shortcode1]' . $original . '[/et_shortcode1]',
				'tag'        => 'et_shortcode1',
				'attributes' => '',
				'content'    => $original,
			),
		);

		$translations = array(
			md5( $original ) => array(
				'fr' => array(
					'status' => ICL_TM_COMPLETE,
					'value'  => $translation,
				),
			),
		);

		$this->shortcode_strategy->method( 'get_shortcodes' )->willReturn( $shortcodes );
		$this->shortcode_strategy->method( 'get_shortcode_attributes' )->willReturnCallback( function ( $tag ) use ( $shortcode_attribs ) {
			return isset( $shortcode_attribs[ $tag ] ) ? $shortcode_attribs[ $tag ] : array();
		} );

		$this->shortcode_parser->method( 'get_shortcodes' )->with( $original_content )->willReturn( $parsed_shortcodes );

		$subject = new WPML_PB_Update_Shortcodes_In_Content( $this->shortcode_strategy, new WPML_PB_Shortcode_Encoding() );
		$actual  = $subject->update_content( $original_content, $translations, 'fr' );

		$this->assertSame( $expected_content, $actual );
	}

	private function get_long_text( $length ) {
		$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$next_space = mt_rand( 1, 20 );
		$long_text = '';

		for ( $i = 0; $i < $length; $i++ ) {

			if ( $i === $next_space ) {
				$long_text .= ' ';
				$next_space += mt_rand( 1, 20 );
			} else {
				$long_text .= $characters[ rand( 0, strlen( $characters ) - 1 ) ];
			}
		}

		return $long_text;
	}

	public function translation_saved_data_provider() {
		return array(
			array( true ),
			array( false ),
		);
	}

}
