<?php

abstract class WPML_PB_TestCase extends OTGS_TestCase {

	function setUp() {
		parent::setUp();

		if ( ! defined( 'ICL_TM_COMPLETE' ) ) {
			define( 'ICL_TM_COMPLETE', 10 );
		}

		if ( ! defined( 'ICL_TM_NEEDS_UPDATE' ) ) {
			define( 'ICL_TM_NEEDS_UPDATE', 3 );
		}

		Mockery::mock( 'AbsoluteLinks' );
		Mockery::mock( 'WPML_Absolute_To_Permalinks' );
		Mockery::mock( 'WPML_Translate_Link_Targets' );
		Mockery::mock( 'WPML_Page_Builder_Settings' );
	}

	protected function get_factory( $wpdb, $sitepress ) {
		$factory = new WPML_PB_Factory( $wpdb, $sitepress );

		return $factory;
	}

	protected function get_shortcode_strategy( WPML_PB_Factory $factory, $encoding = '', $encoding_condition = '' ) {
		$page_builder_settings = Mockery::mock( 'WPML_Page_Builder_Settings' );
		$strategy = new WPML_PB_Shortcode_Strategy( $page_builder_settings );
		$strategy->add_shortcodes(
			array(
				array(
					'tag'        => array(
						'value'              => 'vc_column_text',
						'encoding'           => $encoding,
						'encoding-condition' => $encoding_condition,
						'type'               => '',
					),
					'attributes' => array(
						array( 'value' => 'text', 'encoding' => $encoding, 'type' => '' ),
						array( 'value' => 'heading', 'encoding' => $encoding, 'type' => '' ),
						array( 'value' => 'title', 'encoding' => $encoding, 'type' => '' ),
					),
				),
				array(
					'tag' => array(
						'value'              => 'vc_text_separator',
						'encoding'           => $encoding,
						'encoding-condition' => $encoding_condition,
						'type'               => '',
					),
					'attributes' => array(
						array( 'value' => 'text', 'encoding' => $encoding, 'type' => '' ),
						array( 'value' => 'heading', 'encoding' => $encoding, 'type' => '' ),
						array( 'value' => 'title', 'encoding' => $encoding, 'type' => '' ),
					),
				),
				array(
					'tag' => array(
						'value'              => 'vc_message',
						'encoding'           => $encoding,
						'encoding-condition' => $encoding_condition,
					),
				),
				array(
					'tag' => array(
						'value'              => 'tag_with_link',
						'encoding'           => $encoding,
						'encoding-condition' => $encoding_condition,
						'type'               => 'link',
					),
					'attributes' => array(
						array( 'value' => 'link', 'encoding' => $encoding, 'type' => 'link' ),
						array( 'value' => 'title', 'encoding' => $encoding, 'type' => '' ),
					),
				),
			)
		);

		$this->assertEquals( $encoding, $strategy->get_shortcode_tag_encoding( 'vc_column_text' ) );
		$this->assertEquals( 'VISUAL', $strategy->get_shortcode_tag_type( 'vc_column_text' ) );

		$this->assertEquals( 'LINK', $strategy->get_shortcode_tag_type( 'tag_with_link' ) );
		$this->assertEquals( 'LINK', $strategy->get_shortcode_attribute_type( 'tag_with_link', 'link' ) );
		$this->assertEquals( 'LINE', $strategy->get_shortcode_attribute_type( 'tag_with_link', 'title' ) );

		$strategy->set_factory( $factory );
		return $strategy;
	}

	protected function get_api_hooks_strategy( WPML_PB_Factory $factory ) {
		$strategy = new WPML_PB_API_Hooks_Strategy( 'Layout' );
		$strategy->set_factory( $factory );
		return $strategy;
	}

	protected function get_post_and_package( $name = '' ) {
		if ( ! $name ) {
			$name = rand_str();
		}
		$post_id = rand();

		$post = new stdClass();
		$post->ID = $post_id;

		$package = array(
			'kind'    => $name,
			'name'    => $post_id,
			'title'   => 'Page Builder Page ' . $post_id,
			'post_id' => $post_id,
		);

		return array( $name, $post, $package );
	}
}

