<?php

/**
 * @group media
 */
class Test_WPML_PB_Config_Import extends \OTGS\PHPUnit\Tools\TestCase {
	/**
	 * @param $shortcode
	 * @param $expected_value
	 *
	 * @dataProvider filter_data_provider
	 */
	public function test_filter( $shortcode, $expected_value ) {
		\WP_Mock::passthruFunction( 'get_option' );
		\WP_Mock::passthruFunction( 'update_option' );

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
			'tag only with ignore content'  => array(
				array(
					'tag'        => array( 'value' => 'tag1', 'attr' => array( 'ignore-content' => '1' ) ),
				),
				array(),
			),
			'tag with ignore content and 1 media attribute'  => array(
				array(
					'tag'        => array( 'value' => 'tag1', 'attr' => array( 'ignore-content' => '1' ) ),
					'attributes' => array(
						'attribute' => array(
							'value' => 'attribute4',
							'attr'  => array( 'type' => 'media-url' ),
						)
					),
				),
				array(),
			),
			'tag with ignore content and 2 media attributes'  => array(
				array(
					'tag'        => array( 'value' => 'tag1', 'attr' => array( 'ignore-content' => '1' ) ),
					'attributes' => array(
						'attribute' => array(
							array(
								'value' => 'attribute4',
								'attr'  => array( 'type' => 'media-url' ),
							),array(
								'value' => 'attribute5',
								'attr'  => array( 'type' => 'media-ids' ),
							)
						)
					),
				),
				array(),
			),

		);
	}

	/**
	 * @test
	 * @dataProvider dp_update_media_shortcodes_config
	 * @group media
	 *
	 * @param array $raw_media_shortcode
	 * @param array $expected_shortcodes_config
	 */
	public function it_should_filter_and_update_media_shortcodes_config( $raw_media_shortcode, $expected_shortcodes_config ) {
		$raw_config = array(
			'wpml-config' => array(
				'shortcodes' => array(
					'shortcode' => $raw_media_shortcode,
				),
			),
		);

		\WP_Mock::userFunction( 'get_option', array(
			'args'   => array( WPML_PB_Config_Import_Shortcode::PB_MEDIA_SHORTCODE_SETTING, array() ),
			'return' => array( 'something' ),
		));

		\WP_Mock::userFunction( 'update_option', array(
			'args'  => array(
				WPML_PB_Config_Import_Shortcode::PB_MEDIA_SHORTCODE_SETTING,
				$expected_shortcodes_config,
				true
			),
			'times' => 1,
		));

		$settings = $this->get_settings_mock_for_filter();
		$subject  = new WPML_PB_Config_Import_Shortcode( $settings );

		$this->assertEquals( $raw_config, $subject->wpml_config_filter( $raw_config ) );
	}

	public function dp_update_media_shortcodes_config() {
		return array(
			'tag with 1 attribute' => array(
				array(
					array(
						'tag'        => array( 'value' => 'tag1' ),
						'attributes' => array(
							'attribute' => array(
								'value' => 'attribute1',
								'attr'  => array( 'type' => 'media-url' ),
							),
						),
					),
				),
				array(
					array(
						'tag'        => array( 'name' => 'tag1' ),
						'attributes' => array(
							'attribute1' => array( 'type' => 'media-url' ),
						),
					),
				),
			),
			'tag with 2 attributes' => array(
				array(
					array(
						'tag'        => array( 'value' => 'tag1' ),
						'attributes' => array(
							'attribute' => array(
								array( 'value' => 'attribute1', 'attr' => array( 'type' => 'media-url' ) ),
								array( 'value' => 'attribute2', 'attr' => array( 'type' => 'media-ids' ) ),
							),
						),
					),
				),
				array(
					array(
						'tag'        => array( 'name' => 'tag1' ),
						'attributes' => array(
							'attribute1' => array( 'type' => 'media-url' ),
							'attribute2' => array( 'type' => 'media-ids' ),
						),
					),
				),
			),
			'tag with 1 attribute and media content' => array(
				array(
					array(
						'tag'        => array(
							'value' => 'tag1',
							'attr'  => array( 'type' => 'media-url' ),
						),
						'attributes' => array(
							'attribute' => array(
								'value' => 'attribute1',
								'attr' => array( 'type' => 'media-url' ),
							),
						),
					),
				),
				array(
					array(
						'tag'        => array( 'name' => 'tag1' ),
						'attributes' => array(
							'attribute1' => array( 'type' => 'media-url' ),
						),
						'content'    => array( 'type' => 'media-url' ),
					),
				),
			),
			'tags with no media' => array(
				array(
					array(
						'tag'        => array(
							'value' => 'tag1',
							'attr'  => array( 'type' => 'not-related-to-media' ),
						),
						'attributes' => array(),
					),
					array(
						'tag' => array( 'value' => 'tag1' ),
					),
				),
				array(),
			),
		);
	}

	/**
	 * @test
	 * @group media
	 */
	public function it_should_filter_and_not_update_media_shortcodes_config_if_same_value() {
		$media_config = array(
			array(
				'tag'        => array( 'name' => 'tag1' ),
				'attributes' => array(
					'attribute1' => array( 'type' => 'media-url' ),
				),
			),
		);

		$raw_config = array(
			'wpml-config' => array(
				'shortcodes' => array(
					'shortcode' => array(
						array(
							'tag'        => array( 'value' => 'tag1' ),
							'attributes' => array(
								'attribute' => array(
									'value' => 'attribute1',
									'attr' => array( 'type' => 'media-url' ),
								),
							),
						),
					),
				),
			),
		);

		\WP_Mock::userFunction( 'get_option', array(
			'args'   => array( WPML_PB_Config_Import_Shortcode::PB_MEDIA_SHORTCODE_SETTING, array() ),
			'return' => $media_config,
		));

		\WP_Mock::userFunction( 'update_option', array(
			'times' => 0,
		));

		$settings = $this->get_settings_mock_for_filter();
		$subject  = new WPML_PB_Config_Import_Shortcode( $settings );

		$this->assertEquals( $raw_config, $subject->wpml_config_filter( $raw_config ) );
	}

	/**
	 * @test
	 * @group media
	 */
	public function it_should_return_media_settings() {
		$media_config = array(
			array(
				'tag'        => array( 'name' => 'tag1' ),
				'attributes' => array(
					'attribute1' => array( 'type' => 'url' ),
				),
			),
		);

		\WP_Mock::userFunction( 'get_option', array(
			'args'   => array( WPML_PB_Config_Import_Shortcode::PB_MEDIA_SHORTCODE_SETTING, array() ),
			'return' => $media_config,
		));

		$settings = $this->get_settings_mock_for_filter();
		$subject  = new WPML_PB_Config_Import_Shortcode( $settings );

		$this->assertEquals( $media_config, $subject->get_media_settings() );
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

	private function get_settings_mock_for_filter( $expected_value = null ) {
		$settings_mock = $this->getMockBuilder( 'WPML_ST_Settings' )
		                      ->setMethods( array( 'update_setting', 'get_setting' ) )
		                      ->disableOriginalConstructor()
		                      ->getMock();

		if ( $expected_value ) {
			$settings_mock->expects( $this->once() )
			              ->method( 'update_setting' )
			              ->with( WPML_PB_Config_Import_Shortcode::PB_SHORTCODE_SETTING, array(
				              $expected_value
			              ) );
		}

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
