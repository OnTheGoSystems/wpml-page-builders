<?php

/**
 * Class Test_WPML_TM_Page_Builders_Hooks
 *
 * @group page-builders
 */
class Test_WPML_TM_Page_Builders_Hooks extends \OTGS\PHPUnit\Tools\TestCase {

	private $worker;

	public function setUp() {
		parent::setUp();
		$this->worker = $this->getMockBuilder( 'WPML_TM_Page_Builders' )
			->disableOriginalConstructor()
			->setMethods(
				array(
					'translation_job_data_filter',
					'populate_prev_translation',
					'pro_translation_completed_action',
					'adjust_translation_fields_filter',
					'job_layout_filter',
					'link_to_translation_filter',
				)
			)
			->getMock();
	}

	/**
	 * @test
	 */
	public function init_hooks() {
		$subject = $this->get_subject( $this->worker );

		\WP_Mock::expectFilterAdded( 'wpml_tm_translation_job_data',      array( $subject, 'translation_job_data_filter' ), 10, 2 );
		\WP_Mock::expectActionAdded( 'wpml_pro_translation_completed',    array( $subject, 'pro_translation_completed_action' ), 10, 3 );
		\WP_Mock::expectFilterAdded( 'wpml_tm_adjust_translation_fields', array( $subject, 'adjust_translation_fields_filter' ), 10, 2 );
		\WP_Mock::expectFilterAdded( 'wpml_tm_job_layout',                array( $subject, 'job_layout_filter' ) );
		\WP_Mock::expectFilterAdded( 'wpml_link_to_translation',          array( $subject, 'link_to_translation_filter' ), 20, 4 );
		\WP_Mock::expectFilterAdded( 'wpml_get_translatable_types',          array( $subject, 'remove_shortcode_strings_type_filter' ), 11 );

		$subject->init_hooks();
	}

	/**
	 * @test
	 */
	public function translation_job_data_filter() {
		$input_array  = array( rand_str( 12 ) );
		$arg2         = rand_str( 18 );
		$output_array = array( rand_str( 9 ) );
		$this->worker
			->expects( $this->once() )
			->method( 'translation_job_data_filter' )
			->with( $input_array, $arg2 )->willReturn( $output_array );

		$subject = $this->get_subject( $this->worker );
		$filtered_result = $subject->translation_job_data_filter( $input_array, $arg2 );
		$this->assertEquals( $output_array, $filtered_result );
	}

	/**
	 * @test
	 */
	public function pro_translation_completed_action() {
		$input_id  = mt_rand( 1, 100 );
		$arg2      = array( rand_str( 18 ) );
		$arg3      = new stdClass();
		$this->worker
			->expects( $this->once() )
			->method( 'pro_translation_completed_action' )
			->with( $input_id, $arg2, $arg3 );

		$subject = $this->get_subject( $this->worker );
		$subject->pro_translation_completed_action( $input_id, $arg2, $arg3 );
	}

	/**
	 * @test
	 * @group page-builders
	 */
	public function adjust_translation_fields_filter() {
		$input_array  = array( rand_str( 12 ) );
		$job          = new stdClass();
		$output_array = array( rand_str( 9 ) );
		$this->worker
			->expects( $this->once() )
			->method( 'adjust_translation_fields_filter' )
			->with( $input_array, $job )
			->willReturn( $output_array );

		$subject         = $this->get_subject( $this->worker );
		$filtered_result = $subject->adjust_translation_fields_filter( $input_array, $job );
		$this->assertEquals( $output_array, $filtered_result );
	}

	/**
	 * @test
	 */
	public function job_layout_filter() {
		$input_array  = array( rand_str( 12 ) );
		$output_array = array( rand_str( 9 ) );
		$this->worker
			->expects( $this->once() )
			->method( 'job_layout_filter' )
			->with( $input_array )->willReturn( $output_array );

		$subject = $this->get_subject( $this->worker );
		$filtered_result = $subject->job_layout_filter( $input_array );
		$this->assertEquals( $output_array, $filtered_result );
	}

	/**
	 * @test
	 */
	public function link_to_translation_filter() {
		$input_string  = rand_str( 12 );
		$arg2          = mt_rand( 1, 100 );
		$arg3          = rand_str( 2 );
		$arg4          = mt_rand( 101, 200 );
		$output_string = rand_str( 9 );
		$this->worker
			->expects( $this->once() )
			->method( 'link_to_translation_filter' )
			->with( $input_string )->willReturn( $output_string );

		$subject = $this->get_subject( $this->worker );
		$filtered_result = $subject->link_to_translation_filter( $input_string, $arg2, $arg3, $arg4 );
		$this->assertEquals( $output_string, $filtered_result );
	}

	/**
	 * @param mixed $worker
	 *
	 * @return WPML_TM_Page_Builders_Hooks
	 */
	private function get_subject( $worker = null ) {
		$sitepress = $this->getMockBuilder( 'SitePress' )->disableOriginalConstructor()->getMock();
		return new WPML_TM_Page_Builders_Hooks( $worker, $sitepress );
	}

	/**
	 * @test
	 * @group wpmltm-1674
	 */
	public function remove_shortcode_strings_type_filter() {
		$subject = $this->get_subject( $this->worker );

		$types = array(
			'post' => array(),
			'page' => array(),
			'some-cpt' => array(),
			'page-builder-shortcode-strings' => array(),
		);

		$expected = array(
			'post' => array(),
			'page' => array(),
			'some-cpt' => array(),
		);

		$this->assertEquals( $expected, $subject->remove_shortcode_strings_type_filter( $types ) );
	}
}
