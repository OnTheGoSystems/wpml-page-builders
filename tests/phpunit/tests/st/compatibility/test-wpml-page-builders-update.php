<?php

class Test_WPML_Page_Builders_Update extends \OTGS\PHPUnit\Tools\TestCase {

	/**
	 * @test
	 */
	public function it_should_get_converted_data() {
		$post_id        = mt_rand( 1, 100 );
		$meta_key       = 'the-meta-field-key';
		$raw_data       = array( 'raw data' );
		$converted_data = array( 'converted data' );

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args'   => array( $post_id, $meta_key, true ),
			'return' => $raw_data,
		));

		$data_settings = $this->get_data_settings();
		$data_settings->method( 'get_meta_field' )->willReturn( $meta_key );
		$data_settings->method( 'convert_data_to_array' )->with( $raw_data )->willReturn( $converted_data );

		$subject = $this->get_subject( $data_settings );

		$this->assertEquals( $converted_data, $subject->get_converted_data( $post_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_save() {
		$post_id = mt_rand( 1, 100 );
		$original_post_id = mt_rand( 101, 200 );
		$converted_data = array( 'converted data' );
		$prepared_data  = array( 'prepared data' );
		$fields_to_save = array( 'the-meta-field-key-1', 'the-meta-field-key-2' );
		$fields_to_copy = array( 'the-field-to-copy-1', 'the-field-to-copy-2' );

		foreach ( $fields_to_save as $field_to_save ) {
			\WP_Mock::userFunction( 'update_post_meta', array(
				'args'   => array( $post_id, $field_to_save, $prepared_data ),
				'times' => 1,
			));
		}

		foreach ( $fields_to_copy as $field_to_copy ) {
			$field_value = 'value of ' . $field_to_copy;
			$filterd_value = 'filtered ' . $field_value;

			\WP_Mock::userFunction( 'get_post_meta', array(
				'args'   => array( $original_post_id, $field_to_copy, true ),
				'return' => $field_value,
			));

			\WP_Mock::onFilter( 'wpml_pb_copy_meta_field' )
				->with( $field_value, $post_id, $original_post_id, $field_to_copy )
				->reply( $filterd_value );

			\WP_Mock::userFunction( 'update_post_meta', array(
				'args'   => array( $post_id, $field_to_copy, $filterd_value ),
				'times' => 1,
			));
		}

		$data_settings = $this->get_data_settings();
		$data_settings->method( 'get_fields_to_save' )->willReturn( $fields_to_save );
		$data_settings->method( 'prepare_data_for_saving' )->with( $converted_data )->willReturn( $prepared_data );
		$data_settings->method( 'get_fields_to_copy' )->willReturn( $fields_to_copy );

		$subject = $this->get_subject( $data_settings );

		$subject->save( $post_id, $original_post_id, $converted_data );
	}

	private function get_subject( $data_settings ) {
		return new WPML_Page_Builders_Update( $data_settings );
	}

	private function get_data_settings() {
		return $this->getMockBuilder( 'IWPML_Page_Builders_Data_Settings' )
			->setMethods(
				array(
					'get_meta_field',
					'convert_data_to_array',
					'get_fields_to_save',
					'prepare_data_for_saving',
					'get_fields_to_copy',
					// Abstract methods not used here but need to be declared
					'get_node_id_field',
					'get_pb_name',
					'add_hooks',
				)
			)->disableOriginalConstructor()->getMock();
	}

}
