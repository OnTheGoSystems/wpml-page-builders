<?php

/**
 * Class Test_WPML_PB_Handle_Custom_Fields
 *
 * @group wpmlpb-149
 */
class Test_WPML_PB_Handle_Custom_Fields extends \OTGS\PHPUnit\Tools\TestCase {

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
		$original_field_value = 'something';

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
			'args'  => array( $new_post_id, $field, $original_field_value ),
			'times' => 1,
		) );

		$subject->copy_custom_fields( $new_post_id, $original_post_id );
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
}
