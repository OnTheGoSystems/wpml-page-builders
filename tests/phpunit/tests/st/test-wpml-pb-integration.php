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

	/**
	 * @test
	 * @group wpmlcore-7188
	 */
	public function it_should_NOT_process_pb_content_with_hidden_strings_only_if_string_translation_was_added() {
		\WP_Mock::userFunction( 'did_action' )
		        ->with( "wpml_add_string_translation" )->andReturn( true );

		$sitepress = $this->get_sitepress_mock();
		$sitepress->expects( $this->never() )->method( 'get_language_for_element' );

		$factory        = $this->get_factory_mock_for_add_package_to_update_list( null );
		$strategy       = $this->get_shortcode_strategy( $factory );
		$pb_integration = new WPML_PB_Integration( $sitepress, $factory );
		$pb_integration->add_strategy( $strategy );

		$pb_integration->process_pb_content_with_hidden_strings_only( 456, 123 );
	}

	/**
	 * @test
	 * @group wpmlcore-7188
	 */
	public function it_should_NOT_process_pb_content_with_hidden_strings_only_if_NOT_a_pb_page() {
		$newPostId = 456;
		$post      = $this->get_post( $newPostId );

		\WP_Mock::userFunction( 'did_action' )
		        ->with( "wpml_add_string_translation" )->andReturn( false );
		\WP_Mock::userFunction( 'get_post' )
		        ->with( $newPostId )->andReturn( $post );
		\WP_Mock::onFilter( 'wpml_pb_is_page_builder_page' )
		        ->with( false, $post )->reply( false );

		$sitepress = $this->get_sitepress_mock();
		$sitepress->expects( $this->never() )->method( 'get_language_for_element' );

		$factory        = $this->get_factory_mock_for_add_package_to_update_list( null );
		$strategy       = $this->get_shortcode_strategy( $factory );
		$pb_integration = new WPML_PB_Integration( $sitepress, $factory );
		$pb_integration->add_strategy( $strategy );

		$pb_integration->process_pb_content_with_hidden_strings_only( $newPostId, 123 );
	}

	/**
	 * @test
	 * @group wpmlcore-7188
	 */
	public function it_should_process_pb_content_with_hidden_strings_only() {
		$newPostId        = 456;
		$originalPostId   = 123;
		$post             = $this->get_post( $newPostId );
		$package          = $this->getMockBuilder( 'WPML_Package' )->getMock();
		$package->id      = 789;
		$targetLang       = 'fr';
		$strategiesNumber = 3;

		\WP_Mock::userFunction( 'did_action' )
		        ->with( "wpml_add_string_translation" )->andReturn( false );
		\WP_Mock::userFunction( 'get_post' )
		        ->with( $newPostId )->andReturn( $post );
		\WP_Mock::onFilter( 'wpml_pb_is_page_builder_page' )
		        ->with( false, $post )->reply( true );
		\WP_Mock::userFunction( 'get_post_type' )
		        ->with( $newPostId )->andReturn( $post->post_type );
		\WP_Mock::onFilter( 'wpml_st_get_post_string_packages' )
		        ->with( [], $originalPostId )->reply( [ $package ] );

		$sitepress = $this->get_sitepress_mock();
		$sitepress->method( 'get_language_for_element' )
		          ->with( $newPostId, 'post_' . $post->post_type )
		          ->willReturn( $targetLang );

		$factory        = $this->get_factory_mock_for_add_package_to_update_list( $package, $targetLang, $strategiesNumber );
		$strategy       = $this->get_shortcode_strategy( $factory );
		$pb_integration = new WPML_PB_Integration( $sitepress, $factory );

		for ( $i = 0; $i < $strategiesNumber; $i ++ ) {
			$pb_integration->add_strategy( $strategy );
		}

		$pb_integration->process_pb_content_with_hidden_strings_only( $newPostId, $originalPostId );
		$pb_integration->save_translations_to_post();
	}

	/**
	 * @test
	 */
	public function it_should_NOT_process_shortcodes_globally_for_gutenberg_pages() {
		$newPostId = 456;
		$post      = (object) [
			'ID'           => $newPostId,
			'post_content' => '<!-- wp:paragraph --><p>Some content on the page.</p><!-- /wp:paragraph -->'
		];

		$factory = \Mockery::mock( '\WPML_PB_Factory' );
		$factory->shouldNotReceive( 'get_register_shortcodes' );

		$subject = new WPML_PB_Shortcode_Strategy( \Mockery::mock( '\WPML_Page_Builder_Settings' ) );
		$subject->set_factory( $factory );
		$subject->register_strings( $post );
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
		$factory_mock   = $this->get_factory( \Mockery::mock( 'wpdb' ), $sitepress_mock );
		$pb_integration = new WPML_PB_Integration( $sitepress_mock, $factory_mock );
		\WP_Mock::expectActionAdded( 'pre_post_update', array(
			$pb_integration,
			'migrate_location'
		) );
		\WP_Mock::expectActionAdded( 'wpml_tm_save_post', array(
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
		\WP_Mock::expectActionAdded( 'wpml_pro_translation_completed', array(
			$pb_integration,
			'cleanup_strings_after_translation_completed',
		), 10, 3 );
		\WP_Mock::expectFilterAdded( 'wpml_tm_translation_job_data', array( $pb_integration, 'rescan' ), 9, 2 );
		\WP_Mock::expectActionAdded( 'wpml_pb_finished_adding_string_translations', array(
			$pb_integration,
			'process_pb_content_with_hidden_strings_only'
		), 9, 2 );
		\WP_Mock::expectActionAdded( 'wpml_pb_finished_adding_string_translations', array(
			$pb_integration,
			'save_translations_to_post'
		), 10 );
		\WP_Mock::expectFilterAdded( 'wpml_pb_register_strings_in_content', [
			$pb_integration,
			'register_strings_in_content'
		], 10, 3 );
		\WP_Mock::expectFilterAdded( 'wpml_pb_update_translations_in_content', [
			$pb_integration,
			'update_translations_in_content'
		], 10, 2 );

		\WP_Mock::expectActionAdded(
			'wpml_pb_register_all_strings_for_translation',
			[ $pb_integration, 'register_all_strings_for_translation' ]
		);

		\WP_Mock::expectActionAdded(
			'wpml_start_GB_register_strings',
			[ $pb_integration, 'initialize_string_clean_up' ],
			10,
			1
		);

		\WP_Mock::expectActionAdded(
			'wpml_end_GB_register_strings',
			[ $pb_integration, 'clean_up_strings' ],
			10,
			1
		);

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
		) );

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
		) );

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
		$factory_mock   = $this->getMockBuilder( 'WPML_PB_Factory' )
		                       ->setMethods( array( 'get_string_translations' ) )
		                       ->disableOriginalConstructor()
		                       ->getMock();
		$factory_mock->expects( $this->never() )->method( 'get_string_translations' );

		$strategy = $this->getMockBuilder( 'WPML_PB_Shortcode_Strategy' )
		                 ->setMethods( array() )
		                 ->disableOriginalConstructor()->getMock();

		$pb_integration = new WPML_PB_Integration( $sitepress_mock, $factory_mock );

		$pb_integration->add_strategy( $strategy );

		$strategy->method( 'register_strings' )
		         ->with( $original_post )
		         ->willReturnCallback( function () use ( $pb_integration ) {
			         $pb_integration->new_translation( mt_rand( 1, 1000 ) );
		         } );

		\WP_Mock::wpFunction( 'get_post', array(
			'args'   => array( $original_post->ID ),
			'return' => $original_post,
		) );

		$job = (object) array(
			'original_doc_id'     => $original_post->ID,
			'element_type_prefix' => 'post',
		);

		$pb_integration->cleanup_strings_after_translation_completed( mt_rand( 1, 100 ), array(), $job );
	}

	/**
	 * @group wpmlpb-160
	 * @group wpmlcore-7373
	 */
	public function test_translate_media() {
		$original_post   = $this->get_post( 1 );
		$translated_post = $this->get_post( 2 );

		$sitepress_mock = $this->get_sitepress_mock();
		$sitepress_mock->method( 'get_original_element_id' )
		               ->willReturnCallback( function ( $id ) use ( $original_post ) {
			               if ( $id !== $original_post->ID ) {
				               return $original_post->ID;
			               }

			               return $id;
		               } );
		$factory_mock   = $this->get_factory_mock_for_shutdown();
		$strategy       = $this->get_shortcode_strategy( $factory_mock );
		$pb_integration = new WPML_PB_Integration( $sitepress_mock, $factory_mock );
		$pb_integration->add_strategy( $strategy );

		$media_updater = $this->getMockBuilder( 'IWPML_PB_Media_Update' )
		                      ->setMethods( array( 'translate' ) )->getMock();
		$media_updater->expects( $this->once() )->method( 'translate' )->with( $translated_post );

		\WP_Mock::onFilter( 'wpml_pb_get_media_updaters' )
		        ->with( array() )
		        ->reply( array( $media_updater ) );

		$pb_integration->translate_media( $original_post );
		$pb_integration->translate_media( $translated_post );
	}

	/**
	 * @group wpmlcore-5765
	 */
	public function test_do_shutdown_action_with_resaved_post_element() {
		$target_lang        = 'fr';
		$original_post      = $this->get_post( 1 );
		$original_element   = $this->get_post_element( $original_post->ID, $original_post, 'en' );
		$translated_post    = $this->get_post( 2 );
		$translated_element = $this->get_post_element( $translated_post->ID, $translated_post, $target_lang, $original_element );

		\WP_Mock::wpFunction( 'did_action', array(
			'args'   => array( 'shutdown' ),
			'return' => 0,
		) );

		$sitepress_mock = $this->get_sitepress_mock();
		$sitepress_mock->method( 'get_original_element_id' )
		               ->willReturnCallback( function ( $id ) use ( $original_post ) {
			               if ( $id !== $original_post->ID ) {
				               return $original_post->ID;
			               }

			               return $id;
		               } );

		$updated_package = $this->getMockBuilder( 'WPML_Package' )
		                        ->disableOriginalConstructor()->getMock();

		$string_translation = $this->getMockBuilder( 'WPML_PB_String_Translation_By_Strategy' )
		                           ->setMethods( array( 'save_translations_to_post', 'add_package_to_update_list' ) )
		                           ->disableOriginalConstructor()
		                           ->getMock();

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

		$this->assertEquals( [ $translated_post->ID => $translated_post ], $pb_integration->get_save_post_queue() );
	}

	/**
	 * @group wpmlcore-5935
	 */
	public function test_do_shutdown_action_with_resaved_post_element_without_string_packages() {
		$target_lang        = 'fr';
		$original_post      = $this->get_post( 1 );
		$original_element   = $this->get_post_element( $original_post->ID, $original_post, 'en' );
		$translated_post    = $this->get_post( 2 );
		$translated_element = $this->get_post_element( $translated_post->ID, $translated_post, $target_lang, $original_element );

		\WP_Mock::wpFunction( 'did_action', array(
			'args'   => array( 'shutdown' ),
			'return' => 0,
		) );

		$sitepress_mock = $this->get_sitepress_mock();
		$sitepress_mock->method( 'get_original_element_id' )
		               ->willReturnCallback( function ( $id ) use ( $original_post ) {
			               if ( $id !== $original_post->ID ) {
				               return $original_post->ID;
			               }

			               return $id;
		               } );

		$string_translation = $this->getMockBuilder( 'WPML_PB_String_Translation_By_Strategy' )
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

		$this->assertEquals( [ $translated_post->ID => $translated_post ], $pb_integration->get_save_post_queue() );
	}

	public function dp_do_shutdown_action() {
		return array(
			'WPML Media deactivated' => array( false ),
			'WPML Media activated'   => array( true ),
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
		$factory_mock   = $this->get_factory( \Mockery::mock( 'wpdb' ), $sitepress_mock );
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
		$factory_mock   = $this->get_factory( \Mockery::mock( 'wpdb' ), $sitepress_mock );
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

		$factory_mock = $this->get_factory( \Mockery::mock( 'wpdb' ), $sitepress_mock );

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'times' => 0,
			'args'  => array( $post_id, WPML_PB_Integration::MIGRATION_DONE_POST_META, true ),
		) );

		$subject = new WPML_PB_Integration( $sitepress_mock, $factory_mock );
		$subject->migrate_location( $post_id );
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

		$factory_mock = $this->get_factory( \Mockery::mock( 'wpdb' ), $sitepress_mock );

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
		$subject->migrate_location( $post_id );
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

		$factory_mock = $this->get_factory( \Mockery::mock( 'wpdb' ), $sitepress_mock );

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
		$subject->migrate_location( $post_id );
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

		$factory_mock = $this->get_factory( \Mockery::mock( 'wpdb' ), $sitepress_mock );

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

		$subject->migrate_location( $post->ID );
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
		$post_id = 123;

		$_POST = array(
			'action' => 'editpost',
			'ID'     => $post_id,
		);

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
	 * @group        wpmlcore-6120
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

	/**
	 * @test
	 */
	public function it_returns_false_if_no_strategy_registers_strings_in_content() {
		$post_id = 123;
		$content = 'some content';

		$subject = new WPML_PB_Integration(
			\Mockery::mock( 'SitePress' ),
			\Mockery::mock( 'WPML_PB_Factory' )
		);

		$this->assertFalse( $subject->register_strings_in_content( false, $post_id, $content ) );
	}

	/**
	 * @test
	 */
	public function it_returns_true_if_strategy_registers_strings_in_content() {
		$post_id = 123;
		$content = 'some content';

		$factory = \Mockery::mock( 'WPML_PB_Factory' );

		$subject = new WPML_PB_Integration(
			\Mockery::mock( 'SitePress' ),
			$factory
		);

		$strategy = \Mockery::mock( 'WPML_PB_Shortcode_Strategy' );
		$strategy->shouldReceive( 'get_package_key' )->andReturn( 'key' );
		$strategy->shouldReceive( 'get_package_strings' )->andReturn( [] );
		$strategy->shouldReceive( 'set_factory' );
		$strategy->shouldReceive( 'register_strings_in_content' )
		         ->with( $post_id, $content, \Mockery::type( 'WPML\PB\Shortcode\StringCleanUp' ) )
		         ->andReturn( true );
		$subject->add_strategy( $strategy );

		\WP_Mock::userFunction( 'WPML\Container\make', [
			'args' => [ WPML_PB_Shortcode_Strategy::class ],
			'return' => $strategy,
		]);

		$post = \Mockery::mock( 'WP_Post' );
		$post->ID = $post_id;
		$subject->initialize_string_clean_up( $post );

		$this->assertTrue( $subject->register_strings_in_content( false, $post_id, $content ) );
	}

	/**
	 * @test
	 */
	public function it_updates_translations_in_content() {
		$lang            = 'de';
		$content         = 'some content';
		$updated_content = 'some content[updated]';

		$string_translations = \Mockery::mock( 'WPML_PB_String_Translation_By_Strategy' );
		$string_translations->shouldReceive( 'update_translations_in_content' )
		                    ->with( $content, $lang )
		                    ->andReturn( $updated_content );

		$strategy = $this->getMockBuilder( 'WPML_PB_Shortcode_Strategy' )
		                 ->setMethods( [] )
		                 ->disableOriginalConstructor()->getMock();

		$factory = \Mockery::mock( 'WPML_PB_Factory' );
		$factory->shouldReceive( 'get_string_translations' )
		        ->with( $strategy )
		        ->andReturn( $string_translations );
		$subject = new WPML_PB_Integration( \Mockery::mock( 'SitePress' ), $factory );

		$subject->add_strategy( $strategy );

		$this->assertEquals(
			$updated_content,
			$subject->update_translations_in_content( $content, $lang )
		);
	}

	private function get_factory_mock_for_shutdown() {
		$last_translation_edit_mode = $this->get_last_edit_mode();
		$last_translation_edit_mode->method( 'is_native_editor' )->willReturn( false );

		$factory = $this->getMockBuilder( 'WPML_PB_Factory' )
		                ->setMethods(
			                array(
				                'get_update_translated_posts_from_original',
				                'get_last_translation_edit_mode',
				                'get_post_element',
			                )
		                )
		                ->disableOriginalConstructor()
		                ->getMock();

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

	private function get_factory_mock_for_add_package_to_update_list( $package, $targetLang = null, $strategiesNumber = 0 ) {
		$string_translation_mock = $this->getMockBuilder( 'WPML_PB_String_Translation_By_Strategy' )
		                                ->setMethods( [ 'add_package_to_update_list', 'save_translations_to_post' ] )
		                                ->disableOriginalConstructor()
		                                ->getMock();
		$string_translation_mock->expects( $package ? $this->exactly( $strategiesNumber ) : $this->never() )
		                        ->method( 'add_package_to_update_list' )
		                        ->with( $package, $targetLang );
		$string_translation_mock->expects( $package ? $this->exactly( $strategiesNumber ) : $this->never() )
		                        ->method( 'save_translations_to_post' );

		$factory = $this->getMockBuilder( 'WPML_PB_Factory' )
		                ->setMethods( [ 'get_string_translations' ] )
		                ->disableOriginalConstructor()
		                ->getMock();
		$factory->method( 'get_string_translations' )->willReturn( $string_translation_mock );

		return $factory;
	}

	private function get_factory_mock_for_register_translations( $translated_string_id ) {
		$string_translation_mock = $this->getMockBuilder( 'WPML_PB_String_Translation_By_Strategy' )
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
		                       ->setMethods( array(
			                       'get_original_element_id',
			                       'get_wp_api',
			                       'get_language_for_element'
		                       ) )
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
