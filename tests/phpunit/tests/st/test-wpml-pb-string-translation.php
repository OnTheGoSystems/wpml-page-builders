<?php

/**
 * @group page-builders
 */
class Test_WPML_PB_String_Translation extends WPML_PB_TestCase {
	/**
	 * @dataProvider new_translations_data_provider
	 */
	public function test_new_translations( $post_id ) {
		$translated_string_id = mt_rand();
		$string_package_id    = mt_rand();
		$language_1           = rand_str( 4 );
		$language_2           = rand_str( 4 );
		while ( $language_2 == $language_1 ) {
			$language_2 = rand_str( 4 );
		}

		list( $factory_mock, $strategy_mock ) = $this->get_factory_and_strategy_mock( $string_package_id, $language_1, $language_2, $post_id );
		$wpdb_mock = $this->get_wpdb_mock( $string_package_id, $language_1, $language_2 );

		$pb_string_translation = new WPML_PB_String_Translation( $wpdb_mock, $factory_mock, $strategy_mock );
		$pb_string_translation->new_translation( $translated_string_id );
		$pb_string_translation->new_translation( $translated_string_id );
		$pb_string_translation->save_translations_to_post();
	}

	public function new_translations_data_provider() {
		return array(
			'package with post id'    => array( mt_rand( 1, 100 ) ),
			'package with no post id' => array( 0),
		);
	}

	private function get_factory_and_strategy_mock( $string_package_id, $language_1, $language_2, $post_id ) {
		$package_kind = rand_str( 32 );

		$factory       = $this->getMockBuilder( 'WPML_PB_Factory' )
		                      ->setMethods( array( 'get_wpml_package' ) )
		                      ->disableOriginalConstructor()
		                      ->getMock();
		$package       = $this->getMockBuilder( 'WPML_Package' )
		                      ->setMethods()
		                      ->disableOriginalConstructor()
		                      ->getMock();
		$package->kind = $package_kind;
		$package->post_id = $post_id;
		$factory->expects( $this->exactly( 2 ) )
		        ->method( 'get_wpml_package' )
		        ->with( $this->equalTo( $string_package_id ) )
		        ->willReturn( $package );
		$update = $this->getMockBuilder( 'WPML_PB_Update_Post' )
		               ->setMethods( array( 'update' ) )
		               ->disableOriginalConstructor()
		               ->getMock();
		$update->expects( $post_id ? $this->once() : $this->never() )
		       ->method( 'update' );


		$strategy = $this->getMockBuilder( 'WPML_PB_Shortcode_Strategy' )
		                 ->setMethods( array( 'get_package_kind', 'get_update_post' ) )
		                 ->disableOriginalConstructor()
		                 ->getMock();
		$strategy->expects( $this->exactly( $post_id ? 3 : 0 ) )
		         ->method( 'get_package_kind' )
		         ->willReturn( $package_kind );
		$package_data = array( 'package' => $package, 'languages' => array( $language_1, $language_2 ) );
		$strategy->expects( $post_id ? $this->once() : $this->never() )
		         ->method( 'get_update_post' )
		         ->with( $this->equalTo( $package_data ) )
		         ->willReturn( $update );


		return array( $factory, $strategy );
	}

	private function get_wpdb_mock( $string_package_id, $language_1, $language_2 ) {
		$wpdb = $this->stubs->wpdb();

		$result_1                    = new stdClass();
		$result_1->string_package_id = $string_package_id;
		$result_1->id                = "don't care";
		$result_1->language          = $language_1;

		$result_2                    = new stdClass();
		$result_2->string_package_id = $string_package_id;
		$result_2->id                = "don't care";
		$result_2->language          = $language_2;

		$wpdb->method( 'get_row' )->will( $this->onConsecutiveCalls( $result_1, $result_2 ) );

		return $wpdb;
	}

	/**
	 * @test
	 *
	 * @dataProvider remove_string_db
	 */
	public function remove_string( $delete_count, $job_translated ) {
		$context = rand_str();
		$name = rand_str();
		$job_id = mt_rand();
		$translated_string_id = mt_rand();
		$string_package_id = mt_rand();

		\WP_Mock::wpFunction( 'icl_unregister_string', array(
			'times' => 1, // a string must be always unregistered contrary to a record in `icl_translate`
			'args'  => array( $context, $name ),
		) );



		$factory_mock = $this->getMockBuilder( 'WPML_PB_Factory' )
		                    ->disableOriginalConstructor()
		                    ->getMock();

		$strategy_mock = $this->getMockBuilder( 'IWPML_PB_Strategy' )
		                     ->disableOriginalConstructor()
		                     ->getMock();

		$wpdb = $this->stubs->wpdb();

		$field_type = 'package-string-' . $string_package_id . '-' . $translated_string_id;
		$wpdb->expects( $this->exactly( 2 ) )->method( 'get_var' )->willReturnOnConsecutiveCalls( $job_id, $job_translated );
		$wpdb->expects( $this->exactly( $delete_count ) )->method( 'delete' )->with( $wpdb->prefix . 'icl_translate', array( 'field_type' => $field_type ), array( '%s' ) );
		$pb_string_translation = new WPML_PB_String_Translation( $wpdb, $factory_mock, $strategy_mock );
		$string_data = array(
			'context'    => $context,
			'name'       => $name,
			'package_id' => $string_package_id,
			'id'         => $translated_string_id,
		);
		$pb_string_translation->remove_string( $string_data );
	}

	/**
	 * @test
	 * @group wpmlst-1215
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlst-1215
	 */
	public function it_should_remove_string_if_job_id_is_null() {
		$context = rand_str();
		$name = rand_str();
		$job_id = null;
		$translated_string_id = mt_rand();
		$string_package_id = mt_rand();

		\WP_Mock::wpFunction( 'icl_unregister_string', array(
			'times' => 1,
			'args'  => array( $context, $name ),
		) );

		$factory_mock = $this->getMockBuilder( 'WPML_PB_Factory' )
		                     ->disableOriginalConstructor()
		                     ->getMock();

		$strategy_mock = $this->getMockBuilder( 'IWPML_PB_Strategy' )
		                      ->disableOriginalConstructor()
		                      ->getMock();

		$wpdb = $this->stubs->wpdb();

		$field_type = 'package-string-' . $string_package_id . '-' . $translated_string_id;
		$wpdb->expects( $this->exactly( 1 ) )->method( 'get_var' )->willReturn( $job_id );
		$wpdb->expects( $this->exactly( 1 ) )->method( 'delete' )->with( $wpdb->prefix . 'icl_translate', array( 'field_type' => $field_type ), array( '%s' ) );
		$pb_string_translation = new WPML_PB_String_Translation( $wpdb, $factory_mock, $strategy_mock );
		$string_data = array(
			'context'    => $context,
			'name'       => $name,
			'package_id' => $string_package_id,
			'id'         => $translated_string_id,
		);
		$pb_string_translation->remove_string( $string_data );
	}

	public function remove_string_db() {
		return array(
			array( 1, true ),
			array( 0, false ),
		);
	}
}
