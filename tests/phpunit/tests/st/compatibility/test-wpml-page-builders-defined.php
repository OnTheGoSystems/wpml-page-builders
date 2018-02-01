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
				'name' => 'Beaver Builder',
				'constant' => 'FL_BUILDER_VERSION',
				'notices-display' => array(
					'wpml-translation-editor',
				),
			),
			'elementor' => array(
				'name' => 'Elementor',
				'constant' => 'ELEMENTOR_VERSION',
				'notices-display' => array(
					'wpml-translation-editor',
				),
			),
			'vc-website-builder' => array(
				'name' => 'Visual Composer Website Builder',
				'constant' => 'VCV_VERSION',
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
				'factory' => 'WPML_Beaver_Builder_Integration_Factory',
			),
			'elementor' => array(
				'constant' => 'ELEMENTOR_VERSION',
				'factory' => 'WPML_Elementor_Integration_Factory',
			),
			'vc-website-builder' => array(
				'constant' => 'VCV_VERSION',
				'factory' => 'WPML_VC_Website_Builder_Integration_Factory',
			),
		);

		$subject = new WPML_Page_Builders_Defined();
		$this->assertEquals( $pb_settings, $subject->get_settings() );
	}
}
