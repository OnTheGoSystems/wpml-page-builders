<?php

use tad\FunctionMocker\FunctionMocker;

/**
 * Class Test_WPML_PB_Handle_Custom_Fields
 *
 * @group wpmlpb-149
 */
class Test_WPML_PB_Handle_Custom_Fields extends \OTGS\PHPUnit\Tools\TestCase {

	public function setUp() {
		parent::setUp();
		\WP_Mock::userFunction( 'wp_slash',
			[
				'return' => function ( $data ) {
					return addslashes( $data );
				}
			]
		);
	}
	/**
	 * @test
	 */
	public function it_adds_hooks() {
		$data_settings = $this->getMockBuilder( 'IWPML_Page_Builders_Data_Settings' )
		                      ->setMethods( array(
			                      'get_node_id_field',
			                      'get_fields_to_copy',
			                      'get_fields_to_save',
			                      'get_meta_field',
			                      'convert_data_to_array',
			                      'prepare_data_for_saving',
			                      'get_pb_name',
			                      'add_hooks',
		                      ) )
		                      ->disableOriginalConstructor()
		                      ->getMock();

		$subject = new WPML_PB_Handle_Custom_Fields( $data_settings );

		\WP_Mock::expectFilterAdded( 'wpml_pb_is_page_builder_page', array(
			$subject,
			'is_page_builder_page_filter'
		), 10, 2 );
		\WP_Mock::expectActionAdded( 'wpml_pb_after_page_without_elements_post_content_copy', array(
			$subject,
			'copy_custom_fields'
		), 10, 2 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_returns_true_when_post_has_the_custom_field() {
		$data_settings = $this->getMockBuilder( 'IWPML_Page_Builders_Data_Settings' )
		                      ->setMethods( array(
			                      'get_node_id_field',
			                      'get_fields_to_copy',
			                      'get_fields_to_save',
			                      'get_meta_field',
			                      'convert_data_to_array',
			                      'prepare_data_for_saving',
			                      'get_pb_name',
			                      'add_hooks',
		                      ) )
		                      ->disableOriginalConstructor()
		                      ->getMock();

		$subject = new WPML_PB_Handle_Custom_Fields( $data_settings );

		$post = $this->getMockBuilder( 'WP_Post' )
		             ->disableOriginalConstructor()
		             ->getMock();

		$post->ID    = 10;
		$field       = 'my-custom-field';
		$field_value = 'something';

		$data_settings->method( 'get_meta_field' )
		              ->willReturn( $field );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $post->ID, $field ),
			'return' => $field_value,
		) );

		$this->assertTrue( $subject->is_page_builder_page_filter( false, $post ) );
	}

	/**
	 * @test
	 */
	public function it_returns_unfiltered_result_when_post_does_not_have_the_custom_field() {
		$data_settings = $this->getMockBuilder( 'IWPML_Page_Builders_Data_Settings' )
		                      ->setMethods( array(
			                      'get_node_id_field',
			                      'get_fields_to_copy',
			                      'get_fields_to_save',
			                      'get_meta_field',
			                      'convert_data_to_array',
			                      'prepare_data_for_saving',
			                      'get_pb_name',
			                      'add_hooks',
		                      ) )
		                      ->disableOriginalConstructor()
		                      ->getMock();

		$subject = new WPML_PB_Handle_Custom_Fields( $data_settings );

		$post = $this->getMockBuilder( 'WP_Post' )
		             ->disableOriginalConstructor()
		             ->getMock();

		$post->ID = 10;
		$field    = 'my-custom-field';

		$data_settings->method( 'get_meta_field' )
		              ->willReturn( $field );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $post->ID, $field ),
			'return' => false,
		) );

		$this->assertFalse( $subject->is_page_builder_page_filter( false, $post ) );
	}

	/**
	 * @test
	 */
	public function it_copies_custom_fields_when_original_custom_field_exists() {
		$data_settings = $this->getMockBuilder( 'IWPML_Page_Builders_Data_Settings' )
		                      ->setMethods( array(
			                      'get_node_id_field',
			                      'get_fields_to_copy',
			                      'get_fields_to_save',
			                      'get_meta_field',
			                      'convert_data_to_array',
			                      'prepare_data_for_saving',
			                      'get_pb_name',
			                      'add_hooks',
		                      ) )
		                      ->disableOriginalConstructor()
		                      ->getMock();

		$subject = new WPML_PB_Handle_Custom_Fields( $data_settings );

		$field                = 'my-custom-field';

		$new_post_id      = 1;
		$original_post_id = 2;

		$data_settings->method( 'get_meta_field' )
		              ->willReturn( $field );

		$data_settings->method( 'get_fields_to_copy' )
		              ->willReturn( array( $field ) );

		$data_settings->method( 'get_fields_to_save' )
		              ->willReturn( array() );

		$copy_mock = FunctionMocker::replace( 'WPML_PB_Handle_Custom_Fields::copy_field' );

		$subject->copy_custom_fields( $new_post_id, $original_post_id );

		$copy_mock->wasCalledWithOnce( [ $new_post_id, $original_post_id, $field ] );
	}

	/**
	 * @test
	 */
	public function it_does_not_copy_custom_fields_when_original_custom_field_does_not_exists() {
		$data_settings = $this->getMockBuilder( 'IWPML_Page_Builders_Data_Settings' )
		                      ->setMethods( array(
			                      'get_node_id_field',
			                      'get_fields_to_copy',
			                      'get_fields_to_save',
			                      'get_meta_field',
			                      'convert_data_to_array',
			                      'prepare_data_for_saving',
			                      'get_pb_name',
			                      'add_hooks',
		                      ) )
		                      ->disableOriginalConstructor()
		                      ->getMock();

		$subject = new WPML_PB_Handle_Custom_Fields( $data_settings );

		$field                = 'my-custom-field';
		$original_field_value = '';

		$new_post_id      = 1;
		$original_post_id = 2;

		$data_settings->method( 'get_meta_field' )
		              ->willReturn( $field );

		$data_settings->method( 'get_fields_to_copy' )
		              ->willReturn( array( $field ) );

		$data_settings->method( 'get_fields_to_save' )
		              ->willReturn( array() );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $original_post_id, $field, true ),
			'return' => $original_field_value,
		) );

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'times' => 0,
		) );

		$subject->copy_custom_fields( $new_post_id, $original_post_id );
	}

	/**
	 * @test
	 */
	public function it_copies_fields() {
		$original = 1;
		$new = 2;
		$field = 'field_name';
		$data = 'some data';

		\WP_Mock::userFunction( 'get_post_meta',
			[
				'args' => [ $original, $field, true ],
				'return' => $data
			]
		);

		\WP_Mock::userFunction( 'update_post_meta',
			[
				'times' => 1,
				'args' => [ $new, $field, $data ],
			]
		);

		$slash_json_mock = FunctionMocker::replace( 'WPML_PB_Handle_Custom_Fields::slash_json', $data );

		WPML_PB_Handle_Custom_Fields::copy_field( $new, $original, $field );

		$slash_json_mock->wasCalledWithOnce( [ $data ] );
	}

	/**
	 * @test
	 */
	public function it_does_not_slash_non_json() {
		$not_json = 'do not escape these - \\ " \'';
		$this->assertEquals( $not_json, WPML_PB_Handle_Custom_Fields::slash_json( $not_json ) );
	}
	/**
	 * @test
	 */
	public function it_slashes_json() {
		$json = [ 'data' => 'something' ];
		$json_string = json_encode( $json );

		$this->assertEquals( addslashes( $json_string ), WPML_PB_Handle_Custom_Fields::slash_json( $json_string ) );
	}
}
