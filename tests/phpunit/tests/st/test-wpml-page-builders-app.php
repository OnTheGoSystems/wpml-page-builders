<?php

/**
 * Class Test_WPML_Page_Builders_Integration
 *
 * @group page-builders
 * @group beaver-builder
 * @group elementor
 */
class Test_WPML_Page_Builders_App extends OTGS_TestCase {

	/**
	 * @test
	 */
	public function it_adds_hooks() {

		$pb_plugins = $this->getMockBuilder( 'WPML_Page_Builders_Defined' )
			->disableOriginalConstructor()
			->getMock();

		$subject = new WPML_Page_Builders_App( $pb_plugins );

		\WP_Mock::expectActionAdded( 'wpml_load_page_builders_integration', array( $subject, 'load_integration' ) );
		\WP_Mock::expectFilterAdded( 'wpml_integrations_components', array( $subject, 'add_components' ), 10, 1 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 * @dataProvider dp_integration_plugins
	 *
	 * @param $bb_enabled
	 * @param $elementor_enabled
	 */
	public function it_loads_integration( $bb_enabled, $elementor_enabled ) {

		$plugins = array(
			'beaver-builder' => array(
				'constant' => 'FL_BUILDER_VERSION',
				'factory' => 'WPML_Beaver_Builder_Integration_Factory',
				'enabled' => $bb_enabled,
			),
			'elementor' => array(
				'constant' => 'ELEMENTOR_VERSION',
				'factory' => 'WPML_Elementor_Integration_Factory',
				'enabled' => $elementor_enabled,
			)
		);

		$pb_plugins = $this->getMockBuilder( 'WPML_Page_Builders_Defined' )
		                   ->disableOriginalConstructor()
		                   ->getMock();

		$pb_plugins->method( 'has' )
		           ->withConsecutive(
		           	    array( 'beaver-builder' ),
			            array( 'elementor' )
		           )
		           ->willReturnOnConsecutiveCalls( $bb_enabled, $elementor_enabled );

		$pb_plugins->method( 'get_settings' )
			->willReturn( $plugins );

		$pb_integration = $this->getMockBuilder( 'WPML_Page_Builders_Integration' )
		                       ->disableOriginalConstructor()
		                       ->getMock();

		foreach ( $plugins as $key => $plugin ) {

			$bb_integration_factory = \Mockery::mock( 'overload:' . $plugin['factory']);
			$bb_integration_factory->shouldReceive('create')
			                       ->once()
			                       ->andReturn( $pb_integration );
		}

		$subject = new WPML_Page_Builders_App( $pb_plugins );
		$subject->load_integration();
	}

	/**
	 * @test
	 * @group wpmlcore-5375
	 */
	public function it_loads_integration_as_an_array_of_hooks_instances() {

		$plugins = array(
			'fusion-builder' => array(
				'constant' => 'FUSION_BUILDER_VERSION',
				'factory' => 'WPML_Fusion_Builder_Integration_Factory',
				'enabled' => true,
			)
		);

		$pb_plugins = $this->getMockBuilder( 'WPML_Page_Builders_Defined' )
		                   ->disableOriginalConstructor()
		                   ->getMock();

		$pb_plugins->method( 'has' )->willReturn( true );

		$pb_plugins->method( 'get_settings' )
		           ->willReturn( $plugins );

		$integration_hook_1 = $this->get_integration_mock();
		$integration_hook_1->expects( $this->once() )->method( 'add_hooks' );

		$integration_hook_2 = $this->get_integration_mock();
		$integration_hook_2->expects( $this->once() )->method( 'add_hooks' );

		foreach ( $plugins as $key => $plugin ) {

			$bb_integration_factory = \Mockery::mock( 'overload:' . $plugin['factory']);
			$bb_integration_factory->shouldReceive('create')
			                       ->once()
			                       ->andReturn( array( $integration_hook_1, $integration_hook_2 ) );
		}

		$subject = new WPML_Page_Builders_App( $pb_plugins );
		$subject->load_integration();
	}

	private function get_integration_mock() {
		return $this->getMockBuilder( 'WPML_Page_Builders_Integration' )
			->setMethods( array( 'add_hooks' ) )
		     ->disableOriginalConstructor()
		     ->getMock();
	}

	/**
	 * @test
	 */
	public function it_adds_components() {
		$components = array( rand_str( 10 ) );

		$pb_plugins = $this->getMockBuilder( 'WPML_Page_Builders_Defined' )
		                   ->disableOriginalConstructor()
		                   ->getMock();

		$pb_plugins->expects( $this->once() )
			->method( 'add_components' )
			->with( $components );

		$subject = new WPML_Page_Builders_App( $pb_plugins );
		$subject->add_components( $components );
	}

	public function dp_integration_plugins() {
		return array(
			'Elementor is activated' => array( false, true ),
			'Beaver Builder is activated' => array( true, false ),
			'Beaver Builder and Elementor are activated' => array( true, true ),
		);
	}
}