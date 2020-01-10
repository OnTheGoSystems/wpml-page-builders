<?php

/**
 * Class Test_WPML_PB_Register_Strings
 *
 * @group page-builders
 * @group elementor
 */
class Test_WPML_PB_Register_Strings extends WPML_PB_TestCase2 {



	/**
	 * @test
	 * @group wpmlcore-6929
	 */
	public function it_does_not_register_strings_if_page_builder_is_not_handling_post() {
		list( , $post, $package ) = $this->get_post_and_package( 'Elementor' );

		WP_Mock::expectAction( 'wpml_start_string_package_registration', $package );
		WP_Mock::expectAction( 'wpml_delete_unused_package_strings', $package );

		$data_settings = \Mockery::mock( 'IWPML_Page_Builders_Data_Settings' );
		$data_settings->shouldReceive( 'is_handling_post' )->with( $post->ID )->andReturn( false );
		$data_settings->shouldNotReceive( 'convert_data_to_array' );

		$subject = new WPML_Concrete_Test_Register_Strings(
			\Mockery::mock( 'IWPML_Page_Builders_Translatable_Nodes' ),
			$data_settings,
			\Mockery::mock( 'WPML_PB_String_Registration' )
		);
		$subject->register_strings( $post, $package );
	}

	/**
	 * @test
	 * @group wpmlcore-6929
	 */
	public function it_does_not_throw_error_on_invalid_data() {
		list( $name, $post, $package ) = $this->get_post_and_package( 'Elementor' );

		$invalid_json = 'invalid_json';
		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => [ $post->ID, '_elementor_data', false ],
			'return' => $invalid_json,
		) );

		WP_Mock::expectAction( 'wpml_start_string_package_registration', $package );
		WP_Mock::expectAction( 'wpml_delete_unused_package_strings', $package );

		$data_settings = \Mockery::mock( 'IWPML_Page_Builders_Data_Settings' );
		$data_settings->shouldReceive( 'is_handling_post' )->with( $post->ID )->andReturn( true );
		$data_settings->shouldReceive( 'get_meta_field' )->andReturn( '_elementor_data' );
		$data_settings->shouldReceive( 'convert_data_to_array' )->once()->with( $invalid_json )->andReturn( null );

		$subject = new WPML_Concrete_Test_Register_Strings(
			\Mockery::mock( 'IWPML_Page_Builders_Translatable_Nodes' ),
			$data_settings,
			\Mockery::mock( 'WPML_PB_String_Registration' )
		);
		$subject->register_strings( $post, $package );
	}
}


/**
 * Concrete class to test the underlying abstract class.
 */
class WPML_Concrete_Test_Register_Strings extends WPML_Page_Builders_Register_Strings {

	/**
	 * @param array $data_array
	 * @param array $package
	 */
	protected function register_strings_for_modules( array $data_array, array $package ) {
	}
}

