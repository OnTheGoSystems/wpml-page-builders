<?php

/**
 * Class Test_WPML_Page_Builders_Defined
 * @group page-builders
 * @group beaver-builder
 * @group elementor
 */
class Test_WPML_Page_Builders_Defined extends WPML_PB_TestCase2 {

	public function test_add_compontents() {

		$expected['page-builders'] = array(
			'beaver-builder' => array(
				'name'            => 'Beaver Builder',
				'constant'        => 'FL_BUILDER_VERSION',
				'notices-display' => array(
					'wpml-translation-editor',
				),
			),
			'elementor'      => array(
				'name'            => 'Elementor',
				'constant'        => 'ELEMENTOR_VERSION',
				'notices-display' => array(
					'wpml-translation-editor',
				),
			),
			'gutenberg'      => array(
				'name'            => 'Gutenberg',
				'constant'        => 'GUTENBERG_VERSION',
				'notices-display' => array(
					'wpml-translation-editor',
				),
			),
			'cornerstone'    => array(
				'name'            => 'Cornerstone',
				'function'        => 'cornerstone_plugin_init',
				'notices-display' => array(
					'wpml-translation-editor',
				),
			),
		);

		$subject    = new WPML_Page_Builders_Defined();
		$components = $subject->add_components( array( 'page-builders' => array() ) );

		$this->assertEquals( $expected, $components );
	}

	/**
	 * @test
	 */
	public function it_gets_pb_settings() {
		$pb_settings = array(
			'beaver-builder' => array(
				'constant' => 'FL_BUILDER_VERSION',
				'factory'  => 'WPML_Beaver_Builder_Integration_Factory',
			),
			'elementor'      => array(
				'constant' => 'ELEMENTOR_VERSION',
				'factory'  => 'WPML_Elementor_Integration_Factory',
			),
			'gutenberg'      => array(
				'constant' => 'GUTENBERG_VERSION',
				'factory'  => 'WPML_Gutenberg_Integration_Factory',
			),
			'cornerstone'    => array(
				'function' => 'cornerstone_plugin_init',
				'factory'  => 'WPML_Cornerstone_Integration_Factory',
			),
		);

		$subject = new WPML_Page_Builders_Defined();
		$this->assertEquals( $pb_settings, $subject->get_settings() );
	}
}
