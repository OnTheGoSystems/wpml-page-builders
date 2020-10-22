<?php

/**
 * Class Test_WPML_PB_API_Hooks_Strategy
 * @group page-builders
 */

class Test_WPML_PB_API_Hooks_Strategy extends WPML_PB_TestCase {
	function test_get_package_kind() {
		$name = rand_str();
		$subject = new WPML_PB_API_Hooks_Strategy( $name );
		$this->assertEquals( $name, $subject->get_package_kind() );
	}

	function test_get_updaters() {
		$subject = new WPML_PB_API_Hooks_Strategy( '' );
		$factory = new WPML_PB_Factory( \Mockery::mock( 'wpdb' ), \Mockery::mock( 'SitePress' ) );
		$subject->set_factory( $factory );
		$this->assertInstanceOf( 'WPML_PB_Update_Post', $subject->get_update_post( array() ) );
		$this->assertInstanceOf( 'WPML_PB_Update_API_Hooks_In_Content', $subject->get_content_updater( array() ) );
	}

	function test_update_in_content() {
		$name = rand_str();
		$strategy = new WPML_PB_API_Hooks_Strategy( $name );
		$factory = new WPML_PB_Factory( \Mockery::mock( 'wpdb' ), \Mockery::mock( 'SitePress' ) );
		$strategy->set_factory( $factory );
		$subject = new WPML_PB_Update_API_Hooks_In_Content( $strategy );

		$translated_post_id = rand();
		$original_post = (object) array(
			'ID' => rand(),
		);
		$strings = array( 'string' => rand_str() );
		$lang = rand_str( 2 );

		WP_Mock::expectAction(
			'wpml_page_builder_string_translated',
			$name,
			$translated_post_id,
			$original_post,
			$strings,
			$lang
		);

		$subject->update( $translated_post_id, $original_post, $strings, $lang );
	}

	function test_register_strings() {

		list( $name, $post, $package ) = $this->get_post_and_package();
		WP_Mock::expectAction(
			'wpml_page_builder_register_strings',
			$post,
			$package
		);
		$subject = new WPML_PB_API_Hooks_Strategy( $name );
		$subject->register_strings( $post );

	}

	/**
	 * @group wpmlcore-6232
	 */
	function test_get_package_strings() {
		$package_data    = [ 'package data' ];
		$package_strings = [ 'some strings' ];

		$string_translations = \Mockery::mock( WPML_PB_String_Translation_By_Strategy::class );
		$string_translations->shouldReceive( 'get_package_strings' )
			->with( $package_data )
			->andReturn( $package_strings );

		$factory = \Mockery::mock( WPML_PB_Factory::class );
		$factory->shouldReceive( 'get_string_translations' )->andReturn( $string_translations );

		$subject = new WPML_PB_API_Hooks_Strategy( 'Cornerstone' );
		$subject->set_factory( $factory );
		
		$this->assertEquals(
			$package_strings,
			$subject->get_package_strings( $package_data )
		);
	}
}
