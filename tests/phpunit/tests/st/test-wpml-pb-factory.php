<?php

/**
 * Class Test_WPML_PB_Factory
 *
 * @group 123456
 */
class Test_WPML_PB_Factory extends WPML_PB_TestCase {

	private $wpdb_original;

	function setUp() {
		parent::setUp();
		global $wpdb;

		$this->wpdb_original = $wpdb;
	}

	function tearDown() {
		parent::tearDown();

		global $wpdb;
		$wpdb = $this->wpdb_original;

	}

	/**
	 * @test
	 */
	public function test_get_string_translations() {
		$factory            = $this->get_factory_with_mocks();
		$string_translation = $factory->get_string_translations( $this->get_shortcode_strategy( $factory ) );
		$this->assertInstanceOf( 'WPML_PB_String_Translation', $string_translation );
	}

	/**
	 * @test
	 */
	public function test_get_register_shortcodes() {
		$factory             = $this->get_factory_with_mocks();

		$register_shortcodes = $factory->get_register_shortcodes( $this->get_shortcode_strategy( $factory ) );
		$this->assertInstanceOf( 'WPML_PB_Register_Shortcodes', $register_shortcodes );
	}

	/**
	 * @test
	 */
	public function test_get_wpml_package() {
		$factory = $this->get_factory_with_mocks();
		$package = $factory->get_wpml_package( 1 );
		$this->assertInstanceOf( 'WPML_Package', $package );
	}

	/**
	 * @test
	 * @group wpmlcore-5765
	 */
	public function test_get_package_strings_resave() {
		$this->getMockBuilder( 'WPML_ST_String_Factory' )->disableOriginalConstructor()->getMock();
		$factory = $this->get_factory_with_mocks();
		$package = $factory->get_package_strings_resave();
		$this->assertInstanceOf( 'WPML_PB_Package_Strings_Resave', $package );
	}

	/**
	 * @test
	 * @group wpmlcore-5935
	 */
	public function test_get_handle_post_body() {
		$this->getMockBuilder( 'WPML_Config_Built_With_Page_Builders' )->disableOriginalConstructor()->getMock();
		$factory          = $this->get_factory_with_mocks();
		$handle_post_body = $factory->get_handle_post_body();
		$this->assertInstanceOf( 'WPML_PB_Handle_Post_Body', $handle_post_body );
	}

	/**
	 * @test
	 * @group wpmlcore-6120
	 */
	public function test_get_last_translation_edit_mode() {
		$factory = $this->get_factory_with_mocks();
		$this->assertInstanceOf( 'WPML_PB_Last_Translation_Edit_Mode', $factory->get_last_translation_edit_mode() );
	}

	private function get_factory_with_mocks() {
		global $wpdb;

		$sitepress_mock = $this->get_sitepress_mock();
		$wpdb           = $this->stubs->wpdb();
		$factory        = $this->get_factory( $wpdb, $sitepress_mock );

		return $factory;
	}

	private function get_sitepress_mock() {
		$sitepress_mock = $this->getMockBuilder( 'SitePress' )
		                       ->setMethods( array( 'get_active_languages' ) )
		                       ->disableOriginalConstructor()
		                       ->getMock();

		$sitepress_mock->method( 'get_active_languages' )->willReturn( array() );
		return $sitepress_mock;
	}

}
