<?php

/*
/**
 * Class Test_WPML_PB_String_Registration
 */

class Test_WPML_PB_String_Registration extends WPML_PB_TestCase {
	/**
	 * @test
	 * @dataProvider string_name_provider
	 */
	public function register_shortcode_string( $string_name ) {
		global $post;
		$post_id         = mt_rand( 1, 90 );
		$post            = new stdClass();
		$post->ID        = $post_id;
		$post->post_type = 'page';
		$location        = mt_rand( 1, 100 );

		$expected_package_data = $this->get_expected_package( $post_id );
		$shortcode_content     = rand_long_str( 10 );
		$string_title          = rand_str();
		$expected_string_name  = $string_name ? $string_name : md5( $shortcode_content );

		$sitepress_mock = $this->get_sitepress_mock();
		\WP_Mock::expectAction( 'wpml_register_string',
		                        $shortcode_content,
		                        $expected_string_name,
		                        $expected_package_data,
		                        $string_title,
		                        'VISUAL' );

		$wpdb     = null;
		$factory  = $this->get_factory( $wpdb, $sitepress_mock );
		$strategy = $this->get_shortcode_strategy( $factory );

		$string_factory = $this->get_string_factory_mock();
		$string         = \Mockery::mock( 'WPML_ST_String' );
		$string->shouldReceive( 'set_location' )->once()->with( $location );
		$string_factory->shouldReceive( 'find_by_id' )->andReturn( $string );

		$string_handler = new WPML_PB_String_Registration( $strategy,
		                                                   $string_factory,
		                                                   $this->get_package_factory_mock(),
		                                                   Mockery::mock( 'WPML_Translate_Link_Targets' ),
		                                                   array() );
		$string_handler->register_string( $post_id,
		                                  $shortcode_content,
		                                  'VISUAL',
		                                  $string_title,
		                                  $string_name,
		                                  $location );
		unset( $post );
	}

	public function string_name_provider() {
		return array(
			array( null ),
			array( rand_str() )
		);
	}

	public function test_white_space_strings_are_not_registered() {
		$post_id  = mt_rand( 1, 100 );
		$strategy = $this->getMockBuilder( 'WPML_PB_Shortcode_Strategy' )
		                 ->setMethods( array( 'get_package_key' ) )
		                 ->disableOriginalConstructor()
		                 ->getMock();
		$strategy->expects( $this->exactly( 0 ) )->method( 'get_package_key' );
		$string_handler = new WPML_PB_String_Registration( $strategy,
		                                                   $this->get_string_factory_mock(),
		                                                   $this->get_package_factory_mock(),
		                                                   Mockery::mock( 'WPML_Translate_Link_Targets' ),
		                                                   array() );
		$string_handler->register_string( $post_id, ' ', 'VISUAL' );
	}

	public function test_translations_are_set_for_links() {
		global $post;
		$post_id         = mt_rand( 1, 90 );
		$post            = new stdClass();
		$post->ID        = $post_id;
		$post->post_type = 'page';
		$location        = mt_rand();

		$expected_package_data = $this->get_expected_package( $post_id );
		$shortcode_content     = 'http://somelink.com';

		$sitepress_mock = $this->get_sitepress_mock();
		\WP_Mock::expectAction( 'wpml_register_string',
		                        $shortcode_content,
		                        md5( $shortcode_content ),
		                        $expected_package_data,
		                        $shortcode_content,
		                        'LINK' );

		\WP_Mock::wpPassthruFunction( 'sanitize_title_with_dashes' );

		\WP_Mock::onFilter( 'wpml_string_id_from_package' )->with( null )->reply( 1 );

		$wpdb     = null;
		$factory  = $this->get_factory( $wpdb, $sitepress_mock );
		$strategy = $this->get_shortcode_strategy( $factory );

		$package_factory = $this->get_package_factory_mock();

		$string = \Mockery::mock( 'WPML_ST_String' );
		$string->shouldReceive( 'get_translation_statuses' )->andReturn( array() );
		$string->shouldReceive( 'get_language' )->andReturn( 'en' );
		$string->shouldReceive( 'get_value' )->andReturn( $shortcode_content );
		$string->shouldReceive( 'set_translation' )->once()->with( 'fr', $shortcode_content );
		$string->shouldReceive( 'set_location' )->once()->with( $location );

		$string_factory = $this->get_string_factory_mock();
		$string_factory->shouldReceive( 'find_by_id' )->andReturn( $string );

		$translate_link_targets = Mockery::mock( 'WPML_Translate_Link_Targets' );
		$translate_link_targets->shouldReceive( 'is_internal_url' )->andReturn( true );

		$string_handler = new WPML_PB_String_Registration( $strategy,
		                                                   $string_factory,
		                                                   $package_factory,
		                                                   $translate_link_targets,
		                                                   array( array( 'code' => 'fr' ) ) );
		$string_handler->register_string( $post_id, $shortcode_content, 'LINK', '', '', $location );
		unset( $post );
	}

	/**
	 * @group page-builders
	 * @group wpmlst-1141
	 */
	public function test_links_are_registered() {
		$post_id          = mt_rand();
		$local_link_url   = 'http://local.host/for_tag';
		$offsite_link_url = 'http://www.google.com';
		$title            = rand_str();
		$location         = mt_rand();

		$translate_link_targets = \Mockery::mock( 'WPML_Translate_Link_Targets' );
		$translate_link_targets->shouldReceive( 'is_internal_url' )->with( $offsite_link_url )->andReturn( false );
		$translate_link_targets->shouldReceive( 'is_internal_url' )->with( $local_link_url )->andReturn( true );

		$sitepress_mock = $this->get_sitepress_mock();
		$wpdb           = null;
		$factory        = $this->get_factory( $wpdb, $sitepress_mock );
		$strategy       = $this->get_shortcode_strategy( $factory );

		$package_factory = $this->get_package_factory_mock();

		$string = \Mockery::mock( 'WPML_ST_String' );
		$string->shouldReceive( 'get_translation_statuses' )->andReturn( array() );
		$string->shouldReceive( 'set_location' )->times( 2 )->with( $location );

		$string_factory = $this->get_string_factory_mock();
		$string_factory->shouldReceive( 'find_by_id' )->andReturn( $string );

		$string_handler = new WPML_PB_String_Registration( $strategy,
		                                                   $string_factory,
		                                                   $package_factory,
		                                                   $translate_link_targets,
		                                                   array() );

		\WP_Mock::expectAction( 'wpml_register_string',
		                        $local_link_url,
		                        md5( $local_link_url ),
		                        $strategy->get_package_key( $post_id ),
		                        $title,
		                        'LINK' );
		$string_handler->register_string( $post_id, $local_link_url, 'LINK', $title, '', $location );

		\WP_Mock::expectAction( 'wpml_register_string',
		                        $offsite_link_url,
		                        md5( $offsite_link_url ),
		                        $strategy->get_package_key( $post_id ),
		                        $title,
		                        'LINE'  // Offsite links should be changed to LINE
		);
		$string_handler->register_string( $post_id, $offsite_link_url, 'LINK', $title, '', $location );
	}

	/**
	 * @group        page-builders
	 * @group        wpmlst-1171
	 * @group        migrate-location
	 *
	 * @dataProvider dp_migration_modes
	 *
	 * @param bool $migration_mode
	 */
	public function test_migrate_location( $migration_mode ) {

		$post_id  = mt_rand();
		$location = mt_rand();

		$sitepress_mock = $this->get_sitepress_mock();
		$wpdb           = null;
		$factory        = $this->get_factory( $wpdb, $sitepress_mock );
		$strategy       = $this->get_shortcode_strategy( $factory );

		$string = \Mockery::mock( 'WPML_ST_String' );
		$string->shouldReceive( 'set_location' )->times( 1 )->with( $location );

		$string_factory = $this->get_string_factory_mock();
		$string_factory->shouldReceive( 'find_by_id' )->andReturn( $string );

		$package_factory = $this->get_package_factory_mock();

		$string_handler = new WPML_PB_String_Registration( $strategy,
		                                                   $string_factory,
		                                                   $package_factory,
		                                                   \Mockery::mock( 'WPML_Translate_Link_Targets' ),
		                                                   array(),
			$migration_mode );

		$content     = 'something';
		$string_name = md5( $content );
		$package     = $strategy->get_package_key( $post_id );
		$title       = 'title';
		$type        = 'LINE';

		if ( $migration_mode ) {
			\WP_Mock::onAction( 'wpml_register_string' )->react( array( $this, 'on_wpml_register_string_action' ) );
		} else {
			\WP_Mock::expectAction( 'wpml_register_string', $content, $string_name, $package, $title, $type );
		}
		$string_handler->register_string( $post_id, $content, $type, $title, '', $location );
	}

	public function on_wpml_register_string_action() {
		$this->fail( 'wpml_register_string action is not expected to be called.' );
	}

	public function dp_migration_modes() {
		return array(
			'with migration mode'    => array( true ),
			'without migration mode' => array( false ),
		);
	}

	private function get_sitepress_mock() {
		$sitepress_mock = $this->getMockBuilder( 'SitePress' )
		                       ->setMethods( array( 'get_default_language' ) )
		                       ->disableOriginalConstructor()
		                       ->getMock();
		$sitepress_mock->method( 'get_default_language' )->willReturn( 'en' );

		return $sitepress_mock;
	}

	private function get_expected_package( $post_id ) {
		$expected_package_data = array(
			'kind'    => 'Page Builder ShortCode Strings',
			'name'    => $post_id,
			'title'   => 'Page Builder Page ' . $post_id,
			'post_id' => $post_id,
		);

		return $expected_package_data;
	}

	private function get_string_factory_mock() {
		return \Mockery::mock( 'WPML_ST_String_Factory' );
	}

	private function get_package_factory_mock() {
		$package = $this->getMockBuilder( 'WPML_Package' )->setMethods( array( 'sanitize_string_name' ) )->getMock();
		$package->method( 'sanitize_string_name' )->willReturn( '' );
		$package_factory = $this->getMockBuilder('WPML_ST_Package_Factory')->setMethods(array('create'))->getMock();
		$package_factory->method( 'create' )->willReturn( $package );

		return $package_factory;
	}
}
