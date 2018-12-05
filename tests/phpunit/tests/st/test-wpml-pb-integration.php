<?php

/**
 * Class Test_WPML_PB_Integration
 *
 * @group pb-integration
 */
class Test_WPML_PB_Integration extends WPML_PB_TestCase {

	/**
	 * @test
	 */
	public function register_all_strings_for_translation() {
		$post = $this->get_post();


		$sitepress_mock = $this->get_sitepress_mock( $post->ID );
		$factory_mock   = $this->get_factory_mock_for_register( $post->ID, $post );
		$strategy       = $this->get_shortcode_strategy( $factory_mock );

		$pb_integration = new WPML_PB_Integration( $sitepress_mock, $factory_mock );
		$pb_integration->add_strategy( $strategy );
		$pb_integration->register_all_strings_for_translation( $post );

		$other_post_id = 2;
		$post->ID      = $other_post_id;
		$pb_integration->register_all_strings_for_translation( $post );
	}

	public function test_translations() {
		$translated_string_id = 1;
		$sitepress_mock       = $this->get_sitepress_mock();
		$factory_mock         = $this->get_factory_mock_for_register_translations( $translated_string_id );
		$strategy             = $this->get_shortcode_strategy( $factory_mock );
		$pb_integration       = new WPML_PB_Integration( $sitepress_mock, $factory_mock );
		$pb_integration->add_strategy( $strategy );
		$pb_integration->save_translations_to_post();
		$pb_integration->new_translation( $translated_string_id );
		$pb_integration->save_translations_to_post();
	}

	public function test_add_hooks() {
		$sitepress_mock = $this->get_sitepress_mock();
		$factory_mock   = $this->get_factory( null, $sitepress_mock );
		$pb_integration = new WPML_PB_Integration( $sitepress_mock, $factory_mock );
		\WP_Mock::expectActionAdded( 'save_post', array(
			$pb_integration,
			'queue_save_post_actions'
		), PHP_INT_MAX, 2 );
		\WP_Mock::expectActionAdded( 'wpml_pb_resave_post_translation', array(
			$pb_integration,
			'resave_post_translation_in_shutdown'
		), 10, 1 );
		\WP_Mock::expectActionAdded( 'icl_st_add_string_translation', array(
			$pb_integration,
			'new_translation'
		), 10, 1 );
		\WP_Mock::expectActionAdded( 'shutdown', array( $pb_integration, 'do_shutdown_action' ) );
		\WP_Mock::expectActionAdded( 'wpml_pro_translation_completed', array(
			$pb_integration,
			'cleanup_strings_after_translation_completed',
		),	10, 3 );
		\WP_Mock::expectFilterAdded( 'wpml_tm_translation_job_data', array( $pb_integration, 'rescan' ), 9, 2 );
		\WP_Mock::expectActionAdded( 'wpml_pb_finished_adding_string_translations', array( $pb_integration, 'save_translations_to_post' ) );

		$pb_integration->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_should_not_cleanup_strings_if_not_a_post_translation_job() {
		/** @var SitePress|PHPUnit_Framework_MockObject_MockObject $factory_mock */
		$sitepress_mock = $this->getMockBuilder( 'SitePress' )->setMethods( array( 'get_original_element_id' ) )
			->disableOriginalConstructor()->getMock();
		$sitepress_mock->expects( $this->never() )->method( 'get_original_element_id' );
		/** @var WPML_PB_Factory|PHPUnit_Framework_MockObject_MockObject $factory_mock */
		$factory_mock   = $this->getMockBuilder( 'WPML_PB_Factory' )->disableOriginalConstructor()->getMock();
		$pb_integration = new WPML_PB_Integration( $sitepress_mock, $factory_mock );

		\WP_Mock::wpFunction( 'get_post', array(
			'times' => 0
		));

		$job = (object) array(
		    'element_type_prefix' => 'package',
		);

		$pb_integration->cleanup_strings_after_translation_completed( mt_rand( 1, 100 ), array(), $job );
	}

	/**
	 * @test
	 */
	public function it_should_cleanup_strings_after_translation_completed() {
		$original_post = $this->get_post();

		$sitepress_mock = $this->get_sitepress_mock( $original_post->ID );
		$factory_mock   = $this->get_factory_mock_for_register( $original_post->ID, $original_post );
		$strategy       = $this->get_shortcode_strategy( $factory_mock );
		$pb_integration = new WPML_PB_Integration( $sitepress_mock, $factory_mock );

		$pb_integration->add_strategy( $strategy );

		\WP_Mock::wpFunction( 'get_post', array(
			'args'   => array( $original_post->ID ),
			'return' => $original_post,
		));

		$job = (object) array(
			'original_doc_id'     => $original_post->ID,
		    'element_type_prefix' => 'post',
		);

		$pb_integration->cleanup_strings_after_translation_completed( mt_rand( 1, 100 ), array(), $job );
	}

	/**
	 * @test
	 * @group wpmlcore-5872
	 */
	public function it_should_register_all_strings_without_adding_new_translation() {
		$original_post = $this->get_post();

		$sitepress_mock = $this->get_sitepress_mock( $original_post->ID );
		$factory_mock = $this->getMockBuilder( 'WPML_PB_Factory' )
		                     ->setMethods( array( 'get_string_translations' ) )
		                     ->disableOriginalConstructor()
		                     ->getMock();
		$factory_mock->expects( $this->never() )->method( 'get_string_translations' );

		$strategy = $this->getMockBuilder( 'WPML_PB_Shortcode_Strategy' )
		                 ->setMethods( array( ) )
		                 ->disableOriginalConstructor()->getMock();

		$pb_integration = new WPML_PB_Integration( $sitepress_mock, $factory_mock );

		$pb_integration->add_strategy( $strategy );

		$strategy->method( 'register_strings' )
		         ->with( $original_post )
		         ->willReturnCallback( function() use ( $pb_integration ) {
			         $pb_integration->new_translation( mt_rand( 1, 1000 ) );
		         });

		\WP_Mock::wpFunction( 'get_post', array(
			'args'   => array( $original_post->ID ),
			'return' => $original_post,
		));

		$job = (object) array(
			'original_doc_id'     => $original_post->ID,
			'element_type_prefix' => 'post',
		);

		$pb_integration->cleanup_strings_after_translation_completed( mt_rand( 1, 100 ), array(), $job );
	}

	/**
	 * @dataProvider dp_do_shutdown_action
	 * @group wpmlpb-160
	 *
	 * @param bool $wpml_media_enabled
	 */
	public function test_do_shutdown_action( $wpml_media_enabled ) {
		$original_post   = $this->get_post( 1 );
		$translated_post = $this->get_post( 2 );

		$wp_api = $this->getMockBuilder( 'constant' )->setMethods( array( 'constant' ) )->getMock();
		$wp_api->method( 'constant' )->with( 'WPML_MEDIA_VERSION' )->willReturn( $wpml_media_enabled );

		$sitepress_mock  = $this->get_sitepress_mock();
		$sitepress_mock->method( 'get_wp_api' )->willReturn( $wp_api );
		$sitepress_mock->method( 'get_original_element_id' )
		               ->willReturnCallback( function( $id ) use ( $original_post ) {
		               	    if ( $id !== $original_post->ID ) {
		               	    	return $original_post->ID;
		                    }

		                    return $id;
		               });
		$factory_mock   = $this->get_factory_mock_for_shutdown();
		$strategy       = $this->get_shortcode_strategy( $factory_mock );
		$pb_integration = new WPML_PB_Integration( $sitepress_mock, $factory_mock );
		$pb_integration->add_strategy( $strategy );
		$pb_integration->queue_save_post_actions( $original_post->ID, $original_post );
		$pb_integration->queue_save_post_actions( $translated_post->ID, $translated_post );

		if ( $wpml_media_enabled ) {
			$media_updater = $this->getMockBuilder( 'IWPML_PB_Media_Update' )
			                      ->setMethods( array( 'translate' ) )->getMock();
			$media_updater->expects( $this->once() )->method( 'translate' )->with( $translated_post );

			\WP_Mock::onFilter( 'wmpl_pb_get_media_updaters' )
			        ->with( array() )
			        ->reply( array( $media_updater ) );
		}

		$pb_integration->do_shutdown_action();
	}

	/**
	 * @dataProvider dp_do_shutdown_action
	 * @group wpmlcore-5765
	 *
	 * @param bool $wpml_media_enabled
	 */
	public function test_do_shutdown_action_with_resaved_post_element( $wpml_media_enabled ) {
		$target_lang        = 'fr';
		$original_post      = $this->get_post( 1 );
		$original_element   = $this->get_post_element( $original_post->ID, $original_post, 'en' );
		$translated_post    = $this->get_post( 2 );
		$translated_element = $this->get_post_element( $translated_post->ID, $translated_post, $target_lang, $original_element );

		\WP_Mock::wpFunction( 'did_action', array(
             'args'   => array( 'shutdown' ),
             'return' => 0,
         ));

		$wp_api = $this->getMockBuilder( 'constant' )->setMethods( array( 'constant' ) )->getMock();
		$wp_api->method( 'constant' )->with( 'WPML_MEDIA_VERSION' )->willReturn( $wpml_media_enabled );

		$sitepress_mock  = $this->get_sitepress_mock();
		$sitepress_mock->method( 'get_wp_api' )->willReturn( $wp_api );
		$sitepress_mock->method( 'get_original_element_id' )
		               ->willReturnCallback( function( $id ) use ( $original_post ) {
			               if ( $id !== $original_post->ID ) {
				               return $original_post->ID;
			               }

			               return $id;
		               });

		$updated_package = $this->getMockBuilder( 'WPML_Package' )
								->disableOriginalConstructor()->getMock();

		$string_translation = $this->getMockBuilder( 'WPML_PB_String_Translation' )
		                           ->setMethods( array( 'save_translations_to_post', 'add_package_to_update_list' ) )
		                           ->disableOriginalConstructor()
		                           ->getMock();

		$string_translation->expects( $this->once() )->method( 'save_translations_to_post' );
		$string_translation->expects( $this->once() )->method( 'add_package_to_update_list' )
							->with( $updated_package, $target_lang );

		$factory_mock = $this->getMockBuilder( 'WPML_PB_Factory' )
		                ->setMethods( array(
				                'get_update_translated_posts_from_original',
				                'get_string_translations',
				                'get_package_strings_resave',
				                'get_last_translation_edit_mode',
				                'get_post_element',
                            )
		                )->disableOriginalConstructor()
		                ->getMock();

		$strategy = $this->get_shortcode_strategy( $factory_mock );

		$factory_mock->method( 'get_string_translations' )->with( $strategy )->willReturn( $string_translation );

		$last_edit_mode = $this->get_last_edit_mode();
		$last_edit_mode->method( 'is_native_editor' )->willReturn( false );

		$factory_mock->method( 'get_last_translation_edit_mode' )->willReturn( $last_edit_mode );

		$post_element = $this->getMockBuilder( 'WPML_Post_Element' )
		                     ->setMethods( array( 'get_source_language_code' ) )->getMock();
		$factory_mock->method( 'get_post_element' )->willReturn( $post_element );

		$package_strings_resave = $this->getMockBuilder( 'WPML_PB_Package_Strings_Resave' )
									->setMethods( array( 'from_element' ) )->disableOriginalConstructor()->getMock();
		$package_strings_resave->expects( $this->once() )->method( 'from_element' )->with( $translated_element )->willReturn( array( $updated_package ) );

		$factory_mock->method( 'get_package_strings_resave' )->willReturn( $package_strings_resave );

		$pb_integration = new WPML_PB_Integration( $sitepress_mock, $factory_mock );
		$pb_integration->add_strategy( $strategy );
		$pb_integration->resave_post_translation_in_shutdown( $original_element );
		$pb_integration->resave_post_translation_in_shutdown( $translated_element );

		if ( $wpml_media_enabled ) {
			$media_updater = $this->getMockBuilder( 'IWPML_PB_Media_Update' )
			                      ->setMethods( array( 'translate' ) )->getMock();
			$media_updater->expects( $this->once() )->method( 'translate' )->with( $translated_post );

			\WP_Mock::onFilter( 'wmpl_pb_get_media_updaters' )
			        ->with( array() )
			        ->reply( array( $media_updater ) );
		}

		$pb_integration->do_shutdown_action();
	}

	/**
	 * @dataProvider dp_do_shutdown_action
	 * @group wpmlcore-5935
	 *
	 * @param bool $wpml_media_enabled
	 */
	public function test_do_shutdown_action_with_resaved_post_element_without_string_packages( $wpml_media_enabled ) {
		$target_lang        = 'fr';
		$original_post      = $this->get_post( 1 );
		$original_element   = $this->get_post_element( $original_post->ID, $original_post, 'en' );
		$translated_post    = $this->get_post( 2 );
		$translated_element = $this->get_post_element( $translated_post->ID, $translated_post, $target_lang, $original_element );

		\WP_Mock::wpFunction( 'did_action', array(
             'args'   => array( 'shutdown' ),
             'return' => 0,
         ));

		$wp_api = $this->getMockBuilder( 'constant' )->setMethods( array( 'constant' ) )->getMock();
		$wp_api->method( 'constant' )->with( 'WPML_MEDIA_VERSION' )->willReturn( $wpml_media_enabled );

		$sitepress_mock  = $this->get_sitepress_mock();
		$sitepress_mock->method( 'get_wp_api' )->willReturn( $wp_api );
		$sitepress_mock->method( 'get_original_element_id' )
		               ->willReturnCallback( function( $id ) use ( $original_post ) {
			               if ( $id !== $original_post->ID ) {
				               return $original_post->ID;
			               }

			               return $id;
		               });

		$updated_package = $this->getMockBuilder( 'WPML_Package' )
								->disableOriginalConstructor()->getMock();

		$string_translation = $this->getMockBuilder( 'WPML_PB_String_Translation' )
		                           ->disableOriginalConstructor()
		                           ->getMock();

		$factory_mock = $this->getMockBuilder( 'WPML_PB_Factory' )
		                ->setMethods( array(
				                'get_update_translated_posts_from_original',
				                'get_string_translations',
				                'get_package_strings_resave',
				                'get_handle_post_body',
				                'get_last_translation_edit_mode',
				                'get_post_element',
                            )
		                )->disableOriginalConstructor()
		                ->getMock();

		$strategy = $this->get_shortcode_strategy( $factory_mock );

		$factory_mock->method( 'get_string_translations' )->with( $strategy )->willReturn( $string_translation );

		$last_edit_mode = $this->get_last_edit_mode();
		$last_edit_mode->method( 'is_native_editor' )->willReturn( false );

		$factory_mock->method( 'get_last_translation_edit_mode' )->willReturn( $last_edit_mode );

		$post_element = $this->getMockBuilder( 'WPML_Post_Element' )
		                     ->setMethods( array( 'get_source_language_code' ) )->getMock();
		$factory_mock->method( 'get_post_element' )->willReturn( $post_element );

		$package_strings_resave = $this->getMockBuilder( 'WPML_PB_Package_Strings_Resave' )
									->setMethods( array( 'from_element' ) )->disableOriginalConstructor()->getMock();
		$package_strings_resave->expects( $this->once() )->method( 'from_element' )
			->with( $translated_element )->willReturn( array() );

		$factory_mock->method( 'get_package_strings_resave' )->willReturn( $package_strings_resave );

		$handle_post_body = $this->getMockBuilder( 'WPML_PB_Handle_Post_Body' )
			->setMethods( array( 'copy' ) )->disableOriginalConstructor()->getMock();

		$handle_post_body->expects( $this->once() )
			->method( 'copy' )->with( $translated_post->ID, $original_post->ID, array() );

		$factory_mock->method( 'get_handle_post_body' )->willReturn( $handle_post_body );

		$pb_integration = new WPML_PB_Integration( $sitepress_mock, $factory_mock );
		$pb_integration->add_strategy( $strategy );
		$pb_integration->resave_post_translation_in_shutdown( $original_element );
		$pb_integration->resave_post_translation_in_shutdown( $translated_element );

		if ( $wpml_media_enabled ) {
			$media_updater = $this->getMockBuilder( 'IWPML_PB_Media_Update' )
			                      ->setMethods( array( 'translate' ) )->getMock();
			$media_updater->expects( $this->once() )->method( 'translate' )->with( $translated_post );

			\WP_Mock::onFilter( 'wmpl_pb_get_media_updaters' )
			        ->with( array() )
			        ->reply( array( $media_updater ) );
		}

		$pb_integration->do_shutdown_action();
	}

	public function dp_do_shutdown_action() {
		return array(
			'WPML Media deactivated' => array( false ),
			'WPML Media activated' => array( true ),
		);
	}

	/**
	 * @test
	 */
	public function it_should_not_rescan_if_not_a_post_object() {
		$translation_package = array( 'translation_package' );
		$post                = $this->getMockBuilder( 'WPML_Package' )->disableOriginalConstructor()->getMock();

		$rescan = $this->getMockBuilder( 'WPML_PB_Integration_Rescan' )
		               ->disableOriginalConstructor()
		               ->setMethods( array( 'rescan' ) )
		               ->getMock();

		$rescan->expects( $this->never() )->method( 'rescan' );

		$sitepress_mock = $this->get_sitepress_mock();
		$factory_mock   = $this->get_factory( null, $sitepress_mock );
		$subject        = new WPML_PB_Integration( $sitepress_mock, $factory_mock );

		$subject->set_rescan( $rescan );
		$this->assertEquals( $translation_package, $subject->rescan( $translation_package, $post ) );
	}

	public function test_rescan() {
		$translation_package = array( 'translation_package' );
		$post                = $this->getMockBuilder( 'WP_Post' )->disableOriginalConstructor()->getMock();

		$rescan = $this->getMockBuilder( 'WPML_PB_Integration_Rescan' )
		               ->disableOriginalConstructor()
		               ->setMethods( array( 'rescan' ) )
		               ->getMock();

		$rescan->expects( $this->once() )->method( 'rescan' )->with( $translation_package, $post )->willReturn( $translation_package );

		$sitepress_mock = $this->get_sitepress_mock();
		$factory_mock   = $this->get_factory( null, $sitepress_mock );
		$subject        = new WPML_PB_Integration( $sitepress_mock, $factory_mock );

		$subject->set_rescan( $rescan );
		$this->assertEquals( $translation_package, $subject->rescan( $translation_package, $post ) );
	}

	/**
	 * @group page-builders
	 * @group wpmlst-1171
	 * @group migrate-location
	 */
	public function test_migrate_location_no_strings() {
		$post_id = mt_rand();

		$wpdb = $this->getMockBuilder( 'wpdb' )
		             ->setMethods( array( 'prepare', 'get_var' ) )
		             ->disableOriginalConstructor()
		             ->getMock();

		$wpdb->prefix          = rand_str();
		$string_packages_table = $wpdb->prefix . 'icl_string_packages';
		$wpdb->method( 'prepare' )
		     ->with( 'SELECT COUNT(ID) FROM ' . $string_packages_table . ' WHERE post_id = %d', $post_id )
		     ->willReturn( 'prepared' );

		$wpdb->method( 'get_var' )
		     ->withConsecutive(
			     array( "SHOW TABLES LIKE '" . $string_packages_table . "'" ),
			     array( 'prepared' )
		     )
		     ->willReturnOnConsecutiveCalls( $string_packages_table, 0 );

		$sitepress_mock = \Mockery::mock( 'SitePress' );
		$sitepress_mock->shouldReceive( 'get_wpdb' )->andReturn( $wpdb );

		$factory_mock = $this->get_factory( null, $sitepress_mock );

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'times' => 0,
			'args'  => array( $post_id, WPML_PB_Integration::MIGRATION_DONE_POST_META, true ),
		) );

		$subject = new WPML_PB_Integration( $sitepress_mock, $factory_mock );
		$subject->migrate_location( $post_id, 'anything' );
	}

	/**
	 * @group page-builders
	 * @group wpmlcore-6021
	 * @group migrate-location
	 */
	public function it_should_not_migrate_if_string_packages_table_is_not_present() {
		$post_id = mt_rand();

		$wpdb = $this->getMockBuilder( 'wpdb' )
		             ->setMethods( array( 'prepare', 'get_var' ) )
		             ->disableOriginalConstructor()
		             ->getMock();

		$wpdb->prefix          = rand_str();
		$string_packages_table = $wpdb->prefix . 'icl_string_packages';

		$wpdb->expects( $this->once() )
			->method( 'get_var' )
			->with( "SHOW TABLES LIKE '" . $string_packages_table . "'" )
			->willReturn( false );

		$sitepress_mock = \Mockery::mock( 'SitePress' );
		$sitepress_mock->shouldReceive( 'get_wpdb' )->andReturn( $wpdb );

		$factory_mock = $this->get_factory( null, $sitepress_mock );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'times'  => 0,
			'args'   => array( $post_id, WPML_PB_Integration::MIGRATION_DONE_POST_META, true ),
			'return' => true,
		) );

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'times' => 0,
			'args'  => array( $post_id, WPML_PB_Integration::MIGRATION_DONE_POST_META, true ),
		) );

		$subject = new WPML_PB_Integration( $sitepress_mock, $factory_mock );
		$subject->migrate_location( $post_id, new stdClass() );
	}

	/**
	 * @group page-builders
	 * @group wpmlst-1171
	 * @group migrate-location
	 */
	public function test_migrate_location_already_done() {
		$post_id = mt_rand();

		$wpdb = $this->getMockBuilder( 'wpdb' )
		             ->setMethods( array( 'prepare', 'get_var' ) )
		             ->disableOriginalConstructor()
		             ->getMock();

		$wpdb->prefix          = rand_str();
		$string_packages_table = $wpdb->prefix . 'icl_string_packages';

		$wpdb->method( 'prepare' )->with( "SELECT COUNT(ID) FROM {$wpdb->prefix}icl_string_packages WHERE post_id = %d", $post_id )->willReturn( 'prepared' );

		$wpdb->method( 'get_var' )
		     ->withConsecutive(
			     array( "SHOW TABLES LIKE '" . $string_packages_table . "'" ),
			     array( 'prepared' )
		     )
		     ->willReturnOnConsecutiveCalls( $string_packages_table, 1 );

		$sitepress_mock = \Mockery::mock( 'SitePress' );
		$sitepress_mock->shouldReceive( 'get_wpdb' )->andReturn( $wpdb );

		$factory_mock = $this->get_factory( null, $sitepress_mock );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'times'  => 1,
			'args'   => array( $post_id, WPML_PB_Integration::MIGRATION_DONE_POST_META, true ),
			'return' => true,
		) );

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'times' => 0,
			'args'  => array( $post_id, WPML_PB_Integration::MIGRATION_DONE_POST_META, true ),
		) );

		$subject = new WPML_PB_Integration( $sitepress_mock, $factory_mock );
		$subject->migrate_location( $post_id, 'anything' );
	}

	/**
	 * @group page-builders
	 * @group wpmlst-1171
	 * @group migrate-location
	 */
	public function test_migrate_location() {
		$post = (object) array(
			'ID'           => mt_rand(),
			'post_status'  => 'published',
			'post_type'    => 'page',
			'post_content' => rand_str(),
		);

		$wpdb         = \Mockery::mock( 'wpdb' );
		$wpdb->prefix = rand_str();

		$string_packages_table = $wpdb->prefix . 'icl_string_packages';

		$wpdb->shouldReceive( 'prepare' )
		     ->with( "SELECT COUNT(ID) FROM {$wpdb->prefix}icl_string_packages WHERE post_id = %d", $post->ID )
		     ->andReturn( 'prepared' );

		$wpdb->shouldReceive( 'get_var' )->with( "SHOW TABLES LIKE '" . $string_packages_table . "'" )->andReturn( $string_packages_table );

		$wpdb->shouldReceive( 'get_var' )->with( 'prepared' )->andReturn( 1 );
		$wpdb->posts = 'posts';
		$wpdb->shouldReceive( 'prepare' )
		     ->with( "SELECT ID, post_type, post_status, post_content FROM {$wpdb->posts} WHERE ID = %d", $post->ID )
		     ->andReturn( 'prepared_post' );
		$wpdb->shouldReceive( 'get_row' )->with( 'prepared_post' )->andReturn( $post );

		$sitepress_mock = \Mockery::mock( 'SitePress' );
		$sitepress_mock->shouldReceive( 'get_wpdb' )->andReturn( $wpdb );
		$sitepress_mock->shouldReceive( 'get_original_element_id' )->andReturn( $post->ID );

		$factory_mock = $this->get_factory( null, $sitepress_mock );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'times'  => 1,
			'args'   => array( $post->ID, WPML_PB_Integration::MIGRATION_DONE_POST_META, true ),
			'return' => false,
		) );

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'times' => 1,
			'args'  => array( $post->ID, WPML_PB_Integration::MIGRATION_DONE_POST_META, true ),
		) );

		$subject = new WPML_PB_Integration( $sitepress_mock, $factory_mock );

		$strategy = \Mockery::mock( 'WPML_PB_Shortcode_Strategy' );
		$strategy->shouldReceive( 'migrate_location' )->once()->with( $post->ID, $post->post_content );
		$subject->add_strategy( $strategy );

		$subject->migrate_location( $post->ID, 'anything' );
	}

	/**
	 * @test
	 * @group wpmlcore-6120
	 */
	public function it_should_not_resave_translation_if_last_edit_mode_is_native_editor() {
		$post_id = 123;

		$source_element = $this->get_post_element( 99, $this->get_post( 99 ) );
		$post_element   = $this->get_post_element( $post_id, $this->get_post( $post_id ), 'fr', $source_element );

		$sitepress = $this->get_sitepress_mock();
		$factory   = $this->getMockBuilder( 'WPML_PB_Factory' )
			->setMethods( array( 'get_last_translation_edit_mode', 'get_package_strings_resave' ) )
			->disableOriginalConstructor()->getMock();

		$last_edit_mode = $this->get_last_edit_mode();
		$last_edit_mode->method( 'is_native_editor' )->with( $post_id )->willReturn( true );

		$factory->expects( $this->once() )->method( 'get_last_translation_edit_mode' )->willReturn( $last_edit_mode );

		$factory->expects( $this->never() )->method( 'get_package_strings_resave' );

		$subject = new WPML_PB_Integration( $sitepress, $factory );
		
		$subject->resave_post_translation_in_shutdown( $post_element );
	}

	/**
	 * @test
	 * @group wpmlcore-6120
	 */
	public function it_should_not_update_last_editor_mode_if_source_post() {
		$post_id      = 123;
		$post         = $this->get_post( $post_id );
		$post_element = $this->get_post_element( $post_id, $post );

		$sitepress = $this->get_sitepress_mock();
		$factory   = $this->getMockBuilder( 'WPML_PB_Factory' )
			->setMethods( array( 'get_post_element', 'get_last_translation_edit_mode' ) )
			->disableOriginalConstructor()->getMock();

		$factory->method( 'get_post_element' )->with( $post_id )->willReturn( $post_element );

		$factory->expects( $this->never() )->method( 'get_last_translation_edit_mode' );

		$subject = new WPML_PB_Integration( $sitepress, $factory );

		$subject->queue_save_post_actions( $post_id, $post );
	}

	/**
	 * @test
	 * @group wpmlcore-6120
	 */
	public function it_should_set_last_editor_mode_to_native_editor() {
		$_POST = array(
			'action' => 'editpost',
		);

		$post_id      = 123;
		$post         = $this->get_post( $post_id );
		$post_element = $this->get_post_element( $post_id, $post );
		$post_element->method( 'get_source_language_code' )->willReturn( 'en' );

		$sitepress = $this->get_sitepress_mock();
		$factory   = $this->getMockBuilder( 'WPML_PB_Factory' )
			->setMethods( array( 'get_post_element', 'get_last_translation_edit_mode' ) )
			->disableOriginalConstructor()->getMock();

		$factory->method( 'get_post_element' )->with( $post_id )->willReturn( $post_element );

		$last_edit_mode = $this->get_last_edit_mode();
		$last_edit_mode->expects( $this->once() )->method( 'set_native_editor' )->with( $post_id );
		$last_edit_mode->expects( $this->never() )->method( 'set_translation_editor' )->with( $post_id );

		$factory->method( 'get_last_translation_edit_mode' )->willReturn( $last_edit_mode );

		$subject = new WPML_PB_Integration( $sitepress, $factory );

		$subject->queue_save_post_actions( $post_id, $post );
	}

	/**
	 * @test
	 * @dataProvider dp_post_payload_not_from_native_editor
	 * @group wpmlcore-6120
	 *
	 * @param array $_post_payloaad
	 */
	public function it_should_set_last_editor_mode_to_translation_editor( $_post_payloaad ) {
		$_POST = $_post_payloaad;

		$post_id      = 123;
		$post         = $this->get_post( $post_id );
		$post_element = $this->get_post_element( $post_id, $post );
		$post_element->method( 'get_source_language_code' )->willReturn( 'en' );

		$sitepress = $this->get_sitepress_mock();
		$factory   = $this->getMockBuilder( 'WPML_PB_Factory' )
			->setMethods( array( 'get_post_element', 'get_last_translation_edit_mode' ) )
			->disableOriginalConstructor()->getMock();

		$factory->method( 'get_post_element' )->with( $post_id )->willReturn( $post_element );

		$last_edit_mode = $this->get_last_edit_mode();
		$last_edit_mode->expects( $this->never() )->method( 'set_native_editor' )->with( $post_id );
		$last_edit_mode->expects( $this->once() )->method( 'set_translation_editor' )->with( $post_id );

		$factory->method( 'get_last_translation_edit_mode' )->willReturn( $last_edit_mode );

		$subject = new WPML_PB_Integration( $sitepress, $factory );

		$subject->queue_save_post_actions( $post_id, $post );
	}

	public function dp_post_payload_not_from_native_editor() {
		return array(
			array( array() ),
			array( array( 'action' => 'something' ) ),
		);
	}

	private function get_factory_mock_for_shutdown() {
		$register_shortcodes_mock = $this->getMockBuilder( 'WPML_PB_Register_Shortcodes' )
		                                 ->setMethods( array( 'register_shortcode_strings' ) )
		                                 ->disableOriginalConstructor()
		                                 ->getMock();
		$register_shortcodes_mock->expects( $this->once() )
		                         ->method( 'register_shortcode_strings' );

		$last_translation_edit_mode = $this->get_last_edit_mode();
		$last_translation_edit_mode->method( 'is_native_editor' )->willReturn( false );

		$factory = $this->getMockBuilder( 'WPML_PB_Factory' )
		                ->setMethods(
		                	array(
		                		'get_register_shortcodes',
				                'get_update_translated_posts_from_original',
				                'get_last_translation_edit_mode',
				                'get_post_element',
			                )
		                )
		                ->disableOriginalConstructor()
		                ->getMock();
		$factory->expects( $this->once() )
		        ->method( 'get_register_shortcodes' )
		        ->willReturn( $register_shortcodes_mock );

		$factory->method( 'get_last_translation_edit_mode' )->willReturn( $last_translation_edit_mode );

		$post_element = $this->getMockBuilder( 'WPML_Post_Element' )
		                     ->setMethods( array( 'get_source_language_code' ) )
		                     ->getMock();

		$factory->method( 'get_post_element' )->willReturn( $post_element );

		return $factory;

	}

	private function get_factory_mock_for_register( $post_id, $post ) {
		$register_shortcodes_mock = $this->getMockBuilder( 'WPML_PB_Register_Shortcodes' )
		                                 ->setMethods( array( 'register_shortcode_strings' ) )
		                                 ->disableOriginalConstructor()
		                                 ->getMock();
		$register_shortcodes_mock->expects( $this->once() )
		                         ->method( 'register_shortcode_strings' )
		                         ->with( $this->equalTo( $post_id ), $this->equalTo( $post->post_content ) );


		$factory = $this->getMockBuilder( 'WPML_PB_Factory' )
		                ->setMethods( array( 'get_register_shortcodes' ) )
		                ->disableOriginalConstructor()
		                ->getMock();
		$factory->method( 'get_register_shortcodes' )->willReturn( $register_shortcodes_mock );

		return $factory;
	}

	private function get_factory_mock_for_register_translations( $translated_string_id ) {
		$string_translation_mock = $this->getMockBuilder( 'WPML_PB_String_Translation' )
		                                ->setMethods( array( 'new_translation', 'save_translations_to_post' ) )
		                                ->disableOriginalConstructor()
		                                ->getMock();
		$string_translation_mock->expects( $this->once() )
		                        ->method( 'new_translation' )
		                        ->with( $this->equalTo( $translated_string_id ) );
		$string_translation_mock->expects( $this->once() )
		                        ->method( 'save_translations_to_post' );

		$factory = $this->getMockBuilder( 'WPML_PB_Factory' )
		                ->setMethods( array( 'get_string_translations' ) )
		                ->disableOriginalConstructor()
		                ->getMock();
		$factory->method( 'get_string_translations' )->willReturn( $string_translation_mock );

		return $factory;
	}

	private function get_sitepress_mock( $post_id = null ) {
		$sitepress_mock = $this->getMockBuilder( 'SitePress' )
		                       ->setMethods( array( 'get_original_element_id', 'get_wp_api' ) )
		                       ->disableOriginalConstructor()
		                       ->getMock();
		if ( $post_id ) {
			$sitepress_mock->method( 'get_original_element_id' )->willReturn( $post_id );
		}

		return $sitepress_mock;
	}

	/** @return WP_Post|PHPUnit_Framework_MockObject_MockObject */
	private function get_post( $id = 1 ) {
		$post               = $this->getMockBuilder( 'WP_Post' )->getMock();
		$post->ID           = $id;
		$post->post_status  = 'publish';
		$post->post_type    = 'page';
		$post->post_content = 'Content of post';

		return $post;
	}

	/** @return WPML_Post_Element|PHPUnit_Framework_MockObject_MockObject */
	private function get_post_element( $post_id, WP_Post $post, $lang = null, WPML_Post_Element $source_element = null ) {
		$element = $this->getMockBuilder( 'WPML_Post_Element' )
		                ->setMethods( array(
		                        'get_id',
		                        'get_wp_object',
		                        'get_language_code',
		                        'get_source_language_code',
		                        'get_source_element',
		                	)
		                )->disableOriginalConstructor()->getMock();
		$element->method( 'get_id' )->willReturn( $post_id );
		$element->method( 'get_wp_object' )->willReturn( $post );
		$element->method( 'get_language_code' )->willReturn( $lang );
		$element->method( 'get_source_element' )->willReturn( $source_element );
		return $element;
	}

	private function get_last_edit_mode() {
		return $this->getMockBuilder( 'WPML_PB_Last_Translation_Edit_Mode' )
		     ->setMethods(
		     	array(
		     		'is_native_editor',
		     		'set_native_editor',
		     		'set_translation_editor',
		        )
		     )->getMock();
	}
}
