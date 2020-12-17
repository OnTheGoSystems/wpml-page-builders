<?php

class Test_WPML_PB_Loader extends WPML_PB_TestCase {

	public function setUp() {
		parent::setUp();

		\WP_Mock::userFunction( 'WPML\Container\share' )->once()
			->with( \WPML\PB\Container\Config::getSharedClasses() );
	}

	private function mockActionFilterLoader( $has_page_builder_strategy ) {
		$hooks = [
			WPML\PB\Compatibility\Toolset\Layouts\HooksFactory::class,
		];

		if ( $has_page_builder_strategy ) {
			$hooks = array_merge(
				$hooks,
				[
					WPML_PB_Handle_Post_Body::class,
					WPML\PB\AutoUpdate\Hooks::class,
					WPML\PB\Shutdown\Hooks::class,
					WPML\PB\GutenbergCleanup\ShortcodeHooks::class,
				]
			);
		}

		$actionFilterLoader = \Mockery::mock( 'WPML_Action_Filter_Loader' );
		$actionFilterLoader->shouldReceive( 'load' )->with( $hooks );

		\WP_Mock::userFunction( 'WPML\Container\make' )
			->with( 'WPML_Action_Filter_Loader' )
			->andReturn( $actionFilterLoader );
	}

	public function test_no_strategies() {
		$this->mockActionFilterLoader( false );

		$st_settings      = $this->get_wpml_st_settings();
		$integration_mock = $this->get_pb_integration_mock();
		$integration_mock->expects( $this->exactly( 0 ) )->method( 'add_hooks' );
		$integration_mock->expects( $this->exactly( 0 ) )->method( 'add_strategy' );
		new WPML_PB_Loader( $this->get_sitepress_mock(), $this->get_wpdb_mock(), $st_settings, $integration_mock );
	}

	public function test_shortcode_strategy() {
		$this->mockActionFilterLoader( true );

		$integration_mock = $this->get_pb_integration_mock();
		$integration_mock->expects( $this->exactly( 1 ) )->method( 'add_hooks' );
		$integration_mock->expects( $this->exactly( 1 ) )->method( 'add_strategy' );
		WP_Mock::userFunction('is_admin', array('return' => true));
		new WPML_PB_Loader( $this->get_sitepress_mock(),
		                    $this->get_wpdb_mock(),
		                    $this->get_settings_mock(),
		                    $integration_mock );
	}

	public function test_api_hooks_strategy() {
		$this->mockActionFilterLoader( true );

		$st_settings      = $this->get_wpml_st_settings();
		$integration_mock = $this->get_pb_integration_mock();
		$integration_mock->expects( $this->exactly( 1 ) )->method( 'add_hooks' );
		$integration_mock->expects( $this->exactly( 1 ) )->method( 'add_strategy' );
		WP_Mock::onFilter( 'wpml_page_builder_support_required' )->with( array() )->reply( array( 'something' ) );
		new WPML_PB_Loader( $this->get_sitepress_mock(), $this->get_wpdb_mock(), $st_settings, $integration_mock );
	}

	/**
	 * @group wpmlcore-6208
	 */
	public function test_two_strategies_and_assert_shortcode_strategy_is_the_last() {
		$this->mockActionFilterLoader( true );

		\WP_Mock::userFunction( 'is_admin', array( 'return' => false ) );

		$integration_mock = $this->get_pb_integration_mock();
		$integration_mock->expects( $this->exactly( 1 ) )->method( 'add_hooks' );
		$integration_mock->expects( $this->at( 1 ) )->method( 'add_strategy' )
			->with( $this->isInstanceOf( 'WPML_PB_API_Hooks_Strategy' ) );
		$integration_mock->expects( $this->at( 2 ) )->method( 'add_strategy' )
			->with( $this->isInstanceOf( 'WPML_PB_Shortcode_Strategy' ) );
		WP_Mock::onFilter( 'wpml_page_builder_support_required' )->with( array() )->reply( array( 'something' ) );
		new WPML_PB_Loader( $this->get_sitepress_mock(),
		                    $this->get_wpdb_mock(),
		                    $this->get_settings_mock(),
		                    $integration_mock );
	}

	private function get_pb_integration_mock() {
		$pb_mock = $this->getMockBuilder( 'WPML_PB_Integration' )
		                ->setMethods( array( 'add_hooks', 'add_strategy' ) )
		                ->disableOriginalConstructor()
		                ->getMock();

		return $pb_mock;
	}

	/**
	 * @return SitePress|PHPUnit_Framework_MockObject_MockObject
	 */
	private function get_sitepress_mock() {
		$sitepress_mock = $this->getMockBuilder( 'SitePress' )->disableOriginalConstructor()->getMock();

		return $sitepress_mock;
	}

	/**
	 * @return wpdb|PHPUnit_Framework_MockObject_MockObject
	 */
	private function get_wpdb_mock() {
		$wpdb_mock = $this->getMockBuilder( 'wpdb' )->disableOriginalConstructor()->getMock();

		return $wpdb_mock;
	}

	/**
	 * @return WPML_ST_Settings|PHPUnit_Framework_MockObject_MockObject
	 */
	private function get_settings_mock() {
		$settings_mock = $this->get_wpml_st_settings();

		$settings_mock->method( 'get_setting' )->willReturn( array(
			                                                     array(
				                                                     'tag' => array(
					                                                     'value'    => 'vc_message',
					                                                     'encoding' => WPML_PB_Shortcode_Encoding::ENCODE_TYPES_BASE64,
				                                                     ),
			                                                     ),
		                                                     ) );

		return $settings_mock;
	}

	/**
	 * @return PHPUnit_Framework_MockObject_MockObject
	 */
	private function get_wpml_st_settings() {
		return $this->getMockBuilder( 'WPML_ST_Settings' )
		            ->setMethods( array( 'get_setting' ) )
		            ->disableOriginalConstructor()
		            ->getMock();
	}
}
