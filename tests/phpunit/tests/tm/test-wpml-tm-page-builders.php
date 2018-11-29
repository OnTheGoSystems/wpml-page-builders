<?php

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @group page-builders
 */
class Test_WPML_TM_Page_Builders extends \OTGS\PHPUnit\Tools\TestCase {

	function setUp() {
		parent::setUp();

		WP_Mock::wpFunction( 'wp_list_pluck', array(
			'return' => function ( $list, $field, $index_key ) {
				$result = array();
				foreach ( $list as $row ) {
					$result[ $row[ $index_key ] ] = $row[ $field ];
				}

				return $result;
			}
		) );
	}

	/**
	 * @test
	 */
	function it_does_not_filter_translation_job_data_when_type_is_external() {
		$translation_package = $this->prepare_translation_package( WPML_TM_Page_Builders::PACKAGE_TYPE_EXTERNAL );
		$post                = $this->get_a_post_object();

		$strings    = $this->prepare_package_strings();
		$package_id = rand( 1, 100 );
		$this->prepare_string_package_mock( $package_id, $strings, $post );

		$subject = $this->get_subject();
		$this->assertEquals( $translation_package, $subject->translation_job_data_filter( $translation_package, $post ) );
	}

	/**
	 * @test
	 */
	function it_does_not_filter_translation_job_data_when_post_id_is_not_set() {
		$translation_package = $this->prepare_translation_package( 'post' );
		$post                = new stdClass();

		$strings    = $this->prepare_package_strings();
		$package_id = rand( 1, 100 );
		$this->prepare_string_package_mock( $package_id, $strings, $post );

		$subject = $this->get_subject();
		$this->assertEquals( $translation_package, $subject->translation_job_data_filter( $translation_package, $post ) );
	}

	/**
	 * @test
	 */
	function it_does_not_filter_translation_job_data_when_package_is_empty() {
		$translation_package = $this->prepare_translation_package( 'post' );
		$post                = $this->get_a_post_object();

		$this->set_hardcoded_wpml_post_element( null, rand_str( 2 ) );

		\WP_Mock::onFilter( 'wpml_st_get_post_string_packages' )
		        ->with( false, $post->ID )
		        ->reply( array() );

		$subject = $this->get_subject();
		$this->assertEquals( $translation_package, $subject->translation_job_data_filter( $translation_package, $post ) );
	}

	/**
	 * @test
	 */
	function it_does_not_filter_translation_job_data_when_package_has_no_strings() {
		$translation_package = $this->prepare_translation_package( 'post' );
		$post                = $this->get_a_post_object();

		$this->set_hardcoded_wpml_post_element( null, rand_str( 2 ) );

		$package_id = rand( 1, 100 );
		$this->prepare_string_package_mock( $package_id, array(), $post );

		$subject = $this->get_subject();
		$this->assertEquals( $translation_package, $subject->translation_job_data_filter( $translation_package, $post ) );
	}

	/**
	 * @test
	 */
	function it_filters_translation_job_data() {
		$translation_package = $this->prepare_translation_package( 'post' );
		$post                = $this->get_a_post_object();

		$this->set_hardcoded_wpml_post_element( null, rand_str( 2 ) );

		$strings    = $this->prepare_package_strings();
		$package_id = rand( 1, 100 );
		$this->prepare_string_package_mock( $package_id, $strings, $post );

		$subject                      = $this->get_subject();
		$filtered_translation_package = $subject->translation_job_data_filter( $translation_package, $post );

		$expected_translation_package = $translation_package;
		$expected_translation_package['contents']['body']['translate'] = 0;
		foreach ( $strings as $string ) {
			$key = WPML_TM_Page_Builders_Field_Wrapper::generate_field_slug( $package_id, $string->id );
			$expected_translation_package['contents'][ $key ] = array(
				'translate' => 1,
				'data'      => base64_encode( $string->value ),
				'format'    => 'base64',
			);
		}

		$this->assertEquals( $expected_translation_package, $filtered_translation_package );
	}

	/**
	 * @test
	 */
	function it_does_not_return_strings_of_link_type() {
		$translation_package = $this->prepare_translation_package( 'post' );
		$post                = $this->get_a_post_object();
		$strings = $this->prepare_package_strings();
		foreach ( array_keys( $strings) as $key ) {
			$strings[ $key ]->type = 'LINK';
		}

		$this->set_hardcoded_wpml_post_element( null, rand_str( 2 ) );

		$package_id = rand( 1, 100 );
		$this->prepare_string_package_mock( $package_id, $strings, $post );

		$expected_translation_package = $translation_package;
		$expected_translation_package['contents']['body']['translate'] = 0;

		$subject = $this->get_subject();
		$filtered_package = $subject->translation_job_data_filter( $translation_package, $post );
		$this->assertEquals( $expected_translation_package, $filtered_package );
	}

	/**
	 * @test
	 *
	 * @group wpmltm-1837
	 */
	function it_filters_translation_job_data_with_job_source_different_from_post_source() {
		$source_post_id      = mt_rand( 101, 200 );
		$job_lang_from       = rand_str( 2 );
		$translation_package = $this->prepare_translation_package( 'post' );
		$post                = $this->get_a_post_object();

		$this->set_hardcoded_wpml_post_element( $source_post_id, $job_lang_from );

		$strings    = $this->prepare_package_strings();
		$string_translations = $this->prepare_package_string_translations( $strings, array( $job_lang_from ) );
		$package_id = rand( 1, 100 );
		$string_packages = $this->prepare_string_package_mock( $package_id, $strings, $post, $string_translations );

		\WP_Mock::onFilter( 'wpml_st_get_post_string_packages' )
		        ->with( false, $source_post_id )
		        ->reply( $string_packages );

		$expected_translation_package = $translation_package;
		$expected_translation_package['contents']['body']['translate'] = 0;
		foreach ( $strings as $string ) {
			$string_value = $string_translations[ $string->name ][ $job_lang_from ]['value'];
			$key = WPML_TM_Page_Builders_Field_Wrapper::generate_field_slug( $package_id, $string->id );
			$expected_translation_package['contents'][ $key ] = array(
				'translate' => 1,
				'data'      => base64_encode( $string_value ),
				'format'    => 'base64',
			);
		}

		$subject                      = $this->get_subject();
		$filtered_translation_package = $subject->translation_job_data_filter( $translation_package, $post );

		$this->assertEquals( $expected_translation_package, $filtered_translation_package );
	}

	private function set_hardcoded_wpml_post_element( $post_source_id, $job_lang_from ) {
		$source_post_element = null;

		if ( $post_source_id ) {
			$source_post_element = \Mockery::mock( 'Another_WPML_Post_Element' );
			$source_post_element->shouldReceive( 'get_id' )->andReturn( $post_source_id );
		}

		$post_element = \Mockery::mock( 'overload:WPML_Post_Element' );
		$post_element->shouldReceive( 'get_source_element' )->andReturn( $source_post_element );
		$post_element->shouldReceive( 'get_language_code' )->andReturn( $job_lang_from );
	}

	/**
	 * @param string $type
	 *
	 * @return array
	 */
	private function prepare_translation_package( $type ) {
		return array(
			'type'     => $type,
			'contents' => array(
				'title' => array( rand_str() ),
				'body'  => array( 'translate' => 1 ),
			),
		);
	}

	/**
	 * @return mixed
	 */
	private function prepare_package_strings() {
		$string1                    = rand( 1, 50 );
		$string2                    = rand( 51, 100 );
		$strings[ $string1 ]        = new stdClass();
		$strings[ $string1 ]->id    = $string1;
		$strings[ $string1 ]->value = rand_str();
		$strings[ $string1 ]->type  = 'VISUAL';
		$strings[ $string1 ]->name  = rand_str();
		$strings[ $string2 ]        = new stdClass();
		$strings[ $string2 ]->id    = 25;
		$strings[ $string2 ]->value = rand_str();
		$strings[ $string2 ]->type  = 'LINE';
		$strings[ $string2 ]->name  = rand_str();

		return $strings;
	}

	private function prepare_package_string_translations( $package_strings, $langs ) {
		$string_translations = array();

		foreach ( $package_strings as $package_string ) {
			foreach ( $langs as $lang ) {
				$string_translations[ $package_string->name ][ $lang ]['value'] = rand_str();
			}
		}

		return $string_translations;
	}

	/**
	 * @param $package_id
	 * @param $strings
	 * @param $post
	 * @param array $string_translations
	 *
	 * @return array
	 */
	private function prepare_string_package_mock( $package_id, $strings, $post, $string_translations = array() ) {
		$string_package = $this->getMockBuilder( 'WPML_Package' )
			->setMethods( array( 'get_package_strings', 'get_translated_strings' ) )->getMock();
		$string_package->method( 'get_package_strings' )->willReturn( $strings );
		$string_package->method( 'get_translated_strings' )->willReturn( $string_translations );
		$string_packages = array( $package_id => $string_package );

		\WP_Mock::onFilter( 'wpml_st_get_post_string_packages' )
			->with( false, isset( $post->ID ) ? $post->ID : 0 )
			->reply( $string_packages );

		return $string_packages;
	}

	/**
	 * @test
	 * @dataProvider pro_translation_completed_action_data_provider
	 *
	 * @param array $fields
	 * @param array $data
	 */
	public function pro_translation_completed_action( $fields, $data ) {
		$subject = $this->get_subject();

		$new_post_id = rand( 1, 100 );

		$job                      = new stdClass();
		$job->language_code       = rand_str( 2 );
		$job->translator_id       = rand( 1, 50 );
		$job->translation_service = rand( 1, 50 );
		$job->original_doc_id     = 20;

		$field_string1 = $this->find_field_with_slug( $data['string1_field_name'], $fields );
		$field_string2 = $this->find_field_with_slug( $data['string2_field_name'], $fields );

		\WP_Mock::expectAction(
			'wpml_add_string_translation',
			$data['string1_id'],
			$job->language_code,
			$field_string1['data'],
			$subject::TRANSLATION_COMPLETE,
			$job->translator_id,
			$job->translation_service
		);

		\WP_Mock::expectAction(
			'wpml_add_string_translation',
			$data['string2_id'],
			$job->language_code,
			$field_string2['data'],
			$subject::TRANSLATION_COMPLETE,
			$job->translator_id,
			$job->translation_service
		);

		\WP_Mock::expectAction( 'wpml_pb_finished_adding_string_translations', $new_post_id, $job->original_doc_id, $fields );

		$subject->pro_translation_completed_action( $new_post_id, $fields, $job );
	}

	/**
	 * On remote translation job, the $fields don't have slug so we need to search in the 'field_type' key for each field.
	 *
	 * @param string $slug
	 * @param array $fields
	 *
	 * @return mixed
	 */
	private function find_field_with_slug( $slug, $fields ) {
		$result = isset( $fields[ $slug ] ) ? $fields[ $slug ] : null;

		if ( ! $result ) {
			foreach ( $fields as $field ) {
				if ( isset( $field['field_type'] ) && $field['field_type'] === $slug ) {
					$result = $field;
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public function pro_translation_completed_action_data_provider() {
		$data['string_package_id']  = rand( 1, 50 );
		$data['string1_id']         = rand( 1, 50 );
		$data['string2_id']         = rand( 51, 100 );
		$data['string1_field_name'] = WPML_TM_Page_Builders_Field_Wrapper::generate_field_slug( $data['string_package_id'], $data['string1_id'] );
		$data['string2_field_name'] = WPML_TM_Page_Builders_Field_Wrapper::generate_field_slug( $data['string_package_id'], $data['string2_id'] );

		$local_fields = array(
			'title'                     => array(
				'data' => rand_str(),
			),
			$data['string1_field_name'] => array(
				'data' => rand_str(),
			),
			$data['string2_field_name'] => array(
				'data' => rand_str(),
			),
		);

		$remote_fields = array(
			array(
				'data'       => rand_str(),
				'field_type' => 'title',
			),
			array(
				'data'       => rand_str(),
				'field_type' => $data['string1_field_name'],
			),
			array(
				'data'       => rand_str(),
				'field_type' => $data['string2_field_name'],
			),
		);

		return array(
			'Local translation'  => array( $local_fields, $data ),
			'Remote translation' => array( $remote_fields, $data ),
		);
	}

	/**
	 * @test
	 */
	public function job_layout_filter() {
		$subject = $this->get_subject();

		$layout = array(
			'title',
			WPML_TM_Page_Builders_Field_Wrapper::SLUG_BASE . '4-13',
			WPML_TM_Page_Builders_Field_Wrapper::SLUG_BASE . '5-21',
			WPML_TM_Page_Builders_Field_Wrapper::SLUG_BASE . '5-22',
		);

		$string_package4 = new stdClass();
		$string_package4->title = rand_str();
		$string_package5 = new stdClass();
		$string_package5->title = rand_str();

		\WP_Mock::onFilter( 'wpml_st_get_string_package' )
		        ->with( false, 4 )
		        ->reply( $string_package4 );

		\WP_Mock::onFilter( 'wpml_st_get_string_package' )
		        ->with( false, 5 )
		        ->reply( $string_package5 );

		$new_layout = $subject->job_layout_filter( $layout );

		$expected_layout = array(
			'title',
			array(
				'field_type'    => 'tm-section',
				'title'         => $string_package4->title,
				'fields'        => array( WPML_TM_Page_Builders_Field_Wrapper::SLUG_BASE . '4-13' ),
				'empty'         => false,
				'empty_message' => '',
				'sub_title'     => '',
			),
			array(
				'field_type'    => 'tm-section',
				'title'         => $string_package5->title,
				'fields'        => array( WPML_TM_Page_Builders_Field_Wrapper::SLUG_BASE . '5-21', WPML_TM_Page_Builders_Field_Wrapper::SLUG_BASE . '5-22' ),
				'empty'         => false,
				'empty_message' => '',
				'sub_title'     => '',
			),
		);

		$this->assertEquals( $expected_layout, $new_layout );
	}

	/**
	 * @test
	 * @group page-builders
	 */
	function it_adjusts_translation_fields_filter() {
		$slugs = array(
			WPML_TM_Page_Builders_Field_Wrapper::generate_field_slug( 1, 11 ),
			WPML_TM_Page_Builders_Field_Wrapper::generate_field_slug( 2, 12 ),
			WPML_TM_Page_Builders_Field_Wrapper::generate_field_slug( 3, 13 ),
		);

		$fields = array_map( function ( $slug ) {
			return array( 'field_type' => $slug );
		}, $slugs );

		$expected_result = $fields;
		$expected_result[0]['field_style'] = 1;
		$expected_result[1]['field_style'] = 2;
		$expected_result[2]['field_style'] = 0;

		$that = $this;
		$callback = function ( $slug ) use ( $slugs, $that ) {
			$wrapper = $that->getMockBuilder( 'WPML_TM_Page_Builders_Field_Wrapper' )
			                ->disableOriginalConstructor()
			                ->setMethods( array( 'get_string_type' ) )
			                ->getMock();

			if ( $slug === $slugs[0] ) {
				$wrapper->method( 'get_string_type' )->willReturn( WPML_TM_Page_Builders::FIELD_STYLE_AREA );
			} elseif ( $slug === $slugs[1] ) {
				$wrapper->method( 'get_string_type' )->willReturn( WPML_TM_Page_Builders::FIELD_STYLE_VISUAL );
			} else {
				$wrapper->method( 'get_string_type' )->willReturn( 'some value' );
			}

			return $wrapper;
		};

		$subject = $this->getMockBuilder( 'WPML_TM_Page_Builders' )->disableOriginalConstructor()
			->setMethods( array( 'create_field_wrapper' ) )->getMock();
		$subject->method( 'create_field_wrapper' )->willReturnCallback( $callback );
		$actual_result = $subject->adjust_translation_fields_filter( $fields, new stdClass() );

		$this->assertEquals( $expected_result, $actual_result );
	}

	/**
	 * @test
	 * @dataProvider dp_link_to_translation_filter
	 *
	 * @param string $link
	 * @param int    $status
	 * @param bool   $is_link_altered
	 */
	public function link_to_translation_filter( $link, $status, $is_link_altered ) {
		/* @var WPML_TM_Translation_Status $wpml_tm_translation_status */
		global $wpml_tm_translation_status;

		$post_id = mt_rand( 0, 101 );
		$trid    = mt_rand( 101, 200 );
		$lang    = rand_str( 2 );

		$wpml_tm_translation_status = $this->getMockBuilder( 'WPML_TM_Translation_Status' )
			->setMethods( array( 'filter_translation_status' ) )
			->disableOriginalConstructor()->getMock();

		$wpml_tm_translation_status->method( 'filter_translation_status' )->with( null, $trid, $lang )
			->willReturn( $status );

		$altered_link = 'altered-link';
		WP_Mock::userFunction( 'add_query_arg', array(
			'args'   => array(
				array(
					'update_needed' => 1,
					'trid'          => $trid,
					'language_code' => $lang,
				),
				$link,
			),
			'return' => $altered_link,
		) );

		$actual_link = $this->get_subject()->link_to_translation_filter( $link, $post_id, $lang, $trid );

		if ( $is_link_altered ) {
			$expected_link = $altered_link;

		} else {
			$expected_link = $link;
		}
		$this->assertSame( $expected_link, $actual_link );
	}

	/**
	 * @return array
	 */
	public function dp_link_to_translation_filter() {
		$link = 'http://' . rand_str() . '/';

		return array(
			'With blocked link' => array( '', ICL_TM_NEEDS_UPDATE, false ),
			'With status not ICL_TM_NEEDS_UPDATE' => array( $link, ICL_TM_COMPLETE, false ),
			'With status ICL_TM_NEEDS_UPDATE' => array( $link, ICL_TM_NEEDS_UPDATE, true ),
			'With already query args in link' => array( $link . '?foo=bar', ICL_TM_NEEDS_UPDATE, true ),
		);
	}

	/**
	 * @return WPML_TM_Page_Builders
	 */
	private function get_subject() {
		$sitepress = $this->getMockBuilder( 'SitePress' )->disableOriginalConstructor()->getMock();
		return new WPML_TM_Page_Builders( $sitepress );
	}

	/** @return stdClass */
	private function get_a_post_object() {
		$post            = new stdClass();
		$post->ID        = rand( 1, 100 );
		$post->post_type = rand_str();
		return $post;
	}
}
