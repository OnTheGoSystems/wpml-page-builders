<?php

/**
 * Class Test_WPML_PB_Config_Import
 */
class Test_WPML_PB_Config_Import extends \OTGS\PHPUnit\Tools\TestCase {
	/**
	 * @param $shortcode
	 * @param $expected_value
	 *
	 * @dataProvider filter_data_provider
	 */
	public function test_filter( $shortcode, $expected_value ) {
		$data     = array(
			'wpml-config' => array(
				'shortcodes' => array(
					'shortcode' => array( $shortcode ),
				),
			),
		);
		$settings = $this->get_settings_mock_for_filter( $expected_value );
		$subject  = new WPML_PB_Config_Import_Shortcode( $settings );
		$subject->wpml_config_filter( $data );
	}

	/**
	 * @return array
	 */
	public function filter_data_provider() {
		return array(
			'Many attributes'    => array(
				array(
					'tag'        => array( 'value' => 'tag1' ),
					'attributes' => array(
						'attribute' => array(
							array( 'value' => 'attribute1' ),
							array( 'value' => 'attribute2' ),
						),
					),
				),
				array(
					'tag'        => array( 'value' => 'tag1', 'encoding' => '', 'encoding-condition' => '', 'type' => '', 'raw-html' => '' ),
					'attributes' => array(
						array( 'value' => 'attribute1', 'encoding' => '', 'type' => '' ),
						array( 'value' => 'attribute2', 'encoding' => '', 'type' => '' ),
					),
				),
			),
			'Only one attribute' => array(
				array(
					'tag'        => array( 'value' => 'tag2' ),
					'attributes' => array( 'attribute' => array( 'value' => 'attribute3' ) ),
				),
				array(
					'tag'        => array( 'value' => 'tag2', 'encoding' => '', 'encoding-condition' => '', 'type' => '', 'raw-html' => '' ),
					'attributes' => array(
						array( 'value' => 'attribute3', 'encoding' => '', 'type' => '' ),
					),
				),
			),
			'Without arguments'  => array(
				array(
					'tag' => array( 'value' => 'tag3' ),
				),
				array(
					'tag'        => array( 'value' => 'tag3', 'encoding' => '', 'encoding-condition' => '', 'type' => '', 'raw-html' => '' ),
					'attributes' => array(),
				),
			),
			'encoded tag'        => array(
				array(
					'tag' => array( 'value' => 'tag4', 'attr' => array( 'encoding' => 'encoding1' ) ),
				),
				array(
					'tag'        => array( 'value' => 'tag4', 'encoding' => 'encoding1', 'encoding-condition' => '', 'type' => '', 'raw-html' => '' ),
					'attributes' => array(),
				),
			),
			'encoded tag with condition'        => array(
				array(
					'tag' => array( 'value' => 'tag4', 'attr' => array( 'encoding' => 'encoding1', 'encoding-condition' => 'option:something=1' ) ),
				),
				array(
					'tag'        => array( 'value' => 'tag4', 'encoding' => 'encoding1', 'encoding-condition' => 'option:something=1', 'type' => '', 'raw-html' => '' ),
					'attributes' => array(),
				),
			),
			'encoded attribute'  => array(
				array(
					'tag'        => array( 'value' => 'tag5' ),
					'attributes' => array(
						'attribute' => array(
							'value' => 'attribute4',
							'attr'  => array( 'encoding' => 'encoding2' ),
						)
					),
				),
				array(
					'tag'        => array( 'value' => 'tag5', 'encoding' => '', 'encoding-condition' => '', 'type' => '', 'raw-html' => '' ),
					'attributes' => array(
						array( 'value' => 'attribute4', 'encoding' => 'encoding2', 'type' => '' ),
					),
				),
			),
			'tag with raw html'  => array(
				array(
					'tag'        => array( 'value' => 'tag1', 'attr' => array( 'raw-html' => '1' ) ),
				),
				array(
					'tag'        => array( 'value' => 'tag1', 'encoding' => '', 'encoding-condition' => '', 'type' => '', 'raw-html' => '1' ),
					'attributes' => array(),
				),
			),

		);
	}

	/**
	 * @dataProvider settings_provider
	 */
	public function test_has_settings( $has_settings ) {
		$settings = $this->get_settings_mock_for_has_settings( $has_settings );
		$subject  = new WPML_PB_Config_Import_Shortcode( $settings );
		$this->assertEquals( $has_settings, $subject->has_settings() );

	}

	public function settings_provider() {
		return array(
			array( true ),
			array( false )
		);
	}

	public function test_add_hooks() {
		$settings_mock = $this->getMockBuilder( 'WPML_ST_Settings' )
		                      ->disableOriginalConstructor()
		                      ->getMock();
		$subject       = new WPML_PB_Config_Import_Shortcode( $settings_mock );
		\WP_Mock::expectFilterAdded( 'wpml_config_array', array( $subject, 'wpml_config_filter' ), 10, 1 );

		$subject->add_hooks();
	}

	public function test_link_attribute() {
		$shortcode = array(
			'tag'        => array( 'value' => 'tag1', 'attr' => array( 'type' => 'link' ) ),
			'attributes' => array(
				'attribute' => array(
					array( 'value' => 'attribute1', 'attr' => array( 'type' => 'link' ) ),
					array( 'value' => 'attribute2' ),
				),
			),
		);


		$data = array(
			'wpml-config' => array(
				'shortcodes' => array(
					'shortcode' => array( $shortcode ),
				),
			),
		);

		$expected_value = array(
			'tag'        => array( 'value' => 'tag1', 'encoding' => '', 'encoding-condition' => '', 'type' => 'link', 'raw-html' => '' ),
			'attributes' => array(
				array( 'value' => 'attribute1', 'encoding' => '', 'type' => 'link' ),
				array( 'value' => 'attribute2', 'encoding' => '', 'type' => '' ),
			)
		);

		$settings = $this->get_settings_mock_for_filter( $expected_value );
		$subject  = new WPML_PB_Config_Import_Shortcode( $settings );
		$subject->wpml_config_filter( $data );

	}

	private function get_settings_mock_for_filter( $expected_value ) {
		$settings_mock = $this->getMockBuilder( 'WPML_ST_Settings' )
		                      ->setMethods( array( 'update_setting', 'get_setting' ) )
		                      ->disableOriginalConstructor()
		                      ->getMock();

		$settings_mock->expects( $this->once() )
		              ->method( 'update_setting' )
		              ->with( WPML_PB_Config_Import_Shortcode::PB_SHORTCODE_SETTING, array(
			              $expected_value
		              ) );

		return $settings_mock;
	}

	private function get_settings_mock_for_has_settings( $has_settings ) {
		$settings_mock = $this->getMockBuilder( 'WPML_ST_Settings' )
		                      ->setMethods( array( 'get_setting' ) )
		                      ->disableOriginalConstructor()
		                      ->getMock();

		$settings_mock->expects( $this->once() )
		              ->method( 'get_setting' )
		              ->with( WPML_PB_Config_Import_Shortcode::PB_SHORTCODE_SETTING )
		              ->willReturn( $has_settings ? 'something' : false );

		return $settings_mock;
	}

}
