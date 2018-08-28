<?php

/**
 * Class Test_WPML_PB_Reuse_Translations
 *
 * @group page-builders
 */
class Test_WPML_PB_Reuse_Translations extends WPML_PB_TestCase {

	/**
	 * @group wpmlpb-159
	 * @dataProvider status_provider
	 */
	public function test_find_and_reuse_by_location( $status, $expected_status ) {

		$post_id               = mt_rand( 1, 100 );
		$string_id_no_update   = 10;
		$string_id_to_update   = 20;
		$string_id_updated     = 30;
		$string_id_old_content = 40;
		$string_id_new_content = 50;

		$translation = (object) array(
			'status'              => $status,
			'language'            => rand_str( 4 ),
			'value'               => rand_str(),
			'translator_id'       => mt_rand( 1, 100 ),
			'translation_service' => rand_str(),
			'batch_id'            => mt_rand( 1, 100 )
		);

		$string_to_update = Mockery::mock( 'WPML_String' );
		$string_to_update->shouldReceive( 'get_translations' )->andReturn( array( $translation ) );

		$string_updated = Mockery::mock( 'WPML_String' );
		$string_updated->shouldReceive( 'set_translation' )->once()->with( $translation->language,
		                                                                   $translation->value,
		                                                                   $expected_status,
		                                                                   $translation->translator_id,
		                                                                   $translation->translation_service,
		                                                                   $translation->batch_id );

		$string_old_content = Mockery::mock( 'WPML_String' );
		$string_old_content->shouldReceive( 'get_value' )->andReturn( 'string OLD content' );
		$string_new_content = Mockery::mock( 'WPML_String' );
		$string_new_content->shouldReceive( 'get_value' )->andReturn( 'completely different new content (sameness < 50%)' );

		$original_strings = array(
			array( 'location' => 1, 'id' => $string_id_no_update, 'value' => 'string NO update' ),
			array( 'location' => 2, 'id' => $string_id_to_update, 'value' => 'string TO update' ),
			array( 'location' => 3, 'id' => $string_id_old_content, 'value' => 'string OLD content' ),
		);

		$leftover_strings = array(
			array( 'location' => 2, 'id' => $string_id_to_update, 'value' => 'string TO update' ),
			array( 'location' => 3, 'id' => $string_id_old_content, 'value' => 'string OLD content' ),
		);

		$current_strings = array(
			array( 'location' => 1, 'id' => $string_id_no_update, 'value' => 'string NO update' ),
			array( 'location' => 2, 'id' => $string_id_to_update, 'value' => 'string NO update' ),
			array( 'location' => 2, 'id' => $string_id_updated, 'value' => 'string NO update' ),
			array( 'location' => 3, 'id' => $string_id_new_content, 'value' => 'completely different new content (sameness < 50%)' ),
			array( 'location' => 3, 'id' => $string_id_new_content, 'value' => 'completely different new content (sameness < 50%)' ),
		);

		$strategy_mock = Mockery::mock( 'WPML_PB_Shortcode_Strategy' );
		$strategy_mock->shouldReceive( 'get_package_key' )->once()->with( $post_id )->andReturn( 'package_key' );
		$strategy_mock->shouldReceive( 'get_package_strings' )
		              ->once()
		              ->with( 'package_key' )
		              ->andReturn( $current_strings );

		$string_registration_mock = Mockery::mock( 'WPML_ST_String_Factory' );
		$string_registration_mock->shouldReceive( 'find_by_id' )
		                         ->once()
		                         ->with( $string_id_to_update )
		                         ->andReturn( $string_to_update );
		$string_registration_mock->shouldReceive( 'find_by_id' )
		                         ->once()
		                         ->with( $string_id_updated )
		                         ->andReturn( $string_updated );
		$string_registration_mock->shouldReceive( 'find_by_id' )
		                         ->once()
		                         ->with( $string_id_new_content )
		                         ->andReturn( $string_new_content );
		$string_registration_mock->shouldReceive( 'find_by_id' )
		                         ->once()
		                         ->with( $string_id_old_content )
		                         ->andReturn( $string_old_content );

		$subject = new WPML_PB_Reuse_Translations( $strategy_mock, $string_registration_mock );

		$subject->set_original_strings( $original_strings );
		$subject->find_and_reuse( $post_id, $leftover_strings );
	}

	/**
	 * @dataProvider status_provider
	 */
	public function test_find_and_reuse_by_diff( $status, $expected_status ) {

		$post_id             = mt_rand( 1, 100 );
		$string_id_no_update = 10;
		$string_id_to_update = 20;
		$string_id_updated   = 30;

		$original_text = 'This is the string to update';
		$updated_text  = 'This is the string to update with edit';

		$translation = (object) array(
			'status'              => $status,
			'language'            => rand_str( 4 ),
			'value'               => rand_str(),
			'translator_id'       => mt_rand( 1, 100 ),
			'translation_service' => rand_str(),
			'batch_id'            => mt_rand( 1, 100 )
		);

		$string_to_update = Mockery::mock( 'WPML_String' );
		$string_to_update->shouldReceive( 'get_translations' )->andReturn( array( $translation ) );
		$string_to_update->shouldReceive( 'get_value' )->andReturn( $original_text );

		$string_updated = Mockery::mock( 'WPML_String' );
		$string_updated->shouldReceive( 'set_translation' )->times( 1 )->with( $translation->language,
		                                                                       $translation->value,
		                                                                       $expected_status,
		                                                                       $translation->translator_id,
		                                                                       $translation->translation_service,
		                                                                       $translation->batch_id );
		$string_updated->shouldReceive( 'get_value' )->andReturn( $updated_text );

		$original_strings = array(
			array( 'location' => 0, 'id' => $string_id_no_update ),
			array( 'location' => 0, 'id' => $string_id_to_update ),
		);

		$leftover_strings = array(
			array( 'location' => 0, 'id' => $string_id_to_update ),
		);

		$current_strings = array(
			array( 'location' => 1, 'id' => $string_id_no_update ),
			array( 'location' => 0, 'id' => $string_id_to_update, 'value' => $original_text ),
			array( 'location' => 2, 'id' => $string_id_updated, 'value' => $updated_text ),
		);

		$strategy_mock = Mockery::mock( 'WPML_PB_Shortcode_Strategy' );
		$strategy_mock->shouldReceive( 'get_package_key' )->once()->with( $post_id )->andReturn( 'package_key' );
		$strategy_mock->shouldReceive( 'get_package_strings' )
		              ->once()
		              ->with( 'package_key' )
		              ->andReturn( $current_strings );

		$string_registration_mock = Mockery::mock( 'WPML_ST_String_Factory' );
		$string_registration_mock->shouldReceive( 'find_by_id' )
		                         ->with( $string_id_to_update )
		                         ->andReturn( $string_to_update );
		$string_registration_mock->shouldReceive( 'find_by_id' )
		                         ->with( $string_id_updated )
		                         ->andReturn( $string_updated );

		$subject = new WPML_PB_Reuse_Translations( $strategy_mock, $string_registration_mock );

		$subject->set_original_strings( $original_strings );

		$subject->find_and_reuse( $post_id, $leftover_strings );
	}

	public function status_provider() {
		return array(
			array( 0, 0 ),
			array( 10, 3 ), //  ICL_TM_COMPLETE, ICL_TM_NEEDS_UPDATE
		);
	}

}
