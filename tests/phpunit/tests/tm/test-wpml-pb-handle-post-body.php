<?php

/**
 * Class Test_WPML_PB_Handle_Post_Body
 *
 * @group wpmlcore-5684
 */
class Test_WPML_PB_Handle_Post_Body extends \OTGS\PHPUnit\Tools\TestCase {

	/**
	 * @test
	 */
	public function it_adds_hooks() {
		$page_builders_built = $this->getMockBuilder( 'WPML_Page_Builders_Page_Built' )
		                            ->disableOriginalConstructor()
		                            ->getMock();

		$subject = new WPML_PB_Handle_Post_Body( $page_builders_built );
		\WP_Mock::expectFilterAdded( 'wpml_pb_should_body_be_translated', array(
			$subject,
			'should_translate'
		), 10, 3 );
		\WP_Mock::expectActionAdded( 'wpml_pb_finished_adding_string_translations', array( $subject, 'copy' ), 10, 3 );
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_returns_0_if_page_is_built_with_page_builders() {
		$page_builders_built = $this->getMockBuilder( 'WPML_Page_Builders_Page_Built' )
		                            ->disableOriginalConstructor()
		                            ->getMock();

		$post = $this->getMockBuilder( 'WP_Post' )
		             ->disableOriginalConstructor()
		             ->getMock();

		$page_builders_built->method( 'is_page_builder_page' )
		                    ->with( $post )
		                    ->willReturn( true );

		$subject = new WPML_PB_Handle_Post_Body( $page_builders_built );
		$this->assertEquals( 0, $subject->should_translate( 1, $post, false ) );
	}

	/**
	 * @test
	 */
	public function it_returns_unfiltered_data_if_page_is_not_a_page_builder_page_neither_has_string_packages() {
		$page_builders_built = $this->getMockBuilder( 'WPML_Page_Builders_Page_Built' )
		                            ->disableOriginalConstructor()
		                            ->getMock();

		$post = $this->getMockBuilder( 'WP_Post' )
		             ->disableOriginalConstructor()
		             ->getMock();

		$page_builders_built->method( 'is_page_builder_page' )
		                    ->with( $post )
		                    ->willReturn( false );

		$subject = new WPML_PB_Handle_Post_Body( $page_builders_built );
		$this->assertEquals( 1, $subject->should_translate( 1, $post, false ) );
	}

	/**
	 * @test
	 */
	public function it_copies_post_content_from_original_to_translation_when_it_is_page_builder_page_and_has_no_string_package() {
		$page_builders_built = $this->getMockBuilder( 'WPML_Page_Builders_Page_Built' )
		                            ->disableOriginalConstructor()
		                            ->getMock();

		$new_post_id                 = 2;
		$original_post_id            = 1;
		$original_post               = $this->getMockBuilder( 'WP_Post' )
		                                    ->disableOriginalConstructor()
		                                    ->getMock();
		$original_post->post_content = 'my-content';

		$page_builders_built->method( 'is_page_builder_page' )
		                    ->with( $original_post )
		                    ->willReturn( true );

		$fields = array(
			'not-a-package' => 'something',
		);

		\WP_Mock::wpFunction( 'get_post', array(
			'args'   => $original_post_id,
			'return' => $original_post,
		) );

		\WP_Mock::wpFunction( 'wp_update_post', array(
			'times' => 1,
			'args'  => array(
				array( 'ID' => $new_post_id, 'post_content' => $original_post->post_content ),
			)
		) );

		$page_builders_built->method( 'is_page_builder_page' )
		                    ->with( $original_post )
		                    ->willReturn( true );

		$subject = new WPML_PB_Handle_Post_Body( $page_builders_built );
		$subject->copy( $new_post_id, $original_post_id, $fields );
	}

	/**
	 * @test
	 */
	public function it_does_not_copy_post_content_from_original_to_translation_when_it_is_not_page_builder_page() {
		$page_builders_built = $this->getMockBuilder( 'WPML_Page_Builders_Page_Built' )
		                            ->disableOriginalConstructor()
		                            ->getMock();

		$new_post_id                 = 2;
		$original_post_id            = 1;
		$original_post               = $this->getMockBuilder( 'WP_Post' )
		                                    ->disableOriginalConstructor()
		                                    ->getMock();
		$original_post->post_content = 'my-content';

		$page_builders_built->method( 'is_page_builder_page' )
		                    ->with( $original_post )
		                    ->willReturn( false );

		$fields = array(
			'not-a-package' => 'something',
		);

		\WP_Mock::wpFunction( 'get_post', array(
			'args'   => $original_post_id,
			'return' => $original_post,
		) );

		\WP_Mock::wpFunction( 'wp_update_post', array(
			'times' => 0,
			'args'  => array(
				'ID'           => $new_post_id,
				'post_content' => $original_post->post_content,
			)
		) );

		$page_builders_built->method( 'is_page_builder_page' )
		                    ->with( $original_post )
		                    ->willReturn( true );

		$subject = new WPML_PB_Handle_Post_Body( $page_builders_built );
		$subject->copy( $new_post_id, $original_post_id, $fields );
	}

	/**
	 * @test
	 */
	public function it_does_not_copy_post_content_from_original_to_translation_when_page_contains_string_package() {
		$page_builders_built = $this->getMockBuilder( 'WPML_Page_Builders_Page_Built' )
		                            ->disableOriginalConstructor()
		                            ->getMock();

		$new_post_id                 = 2;
		$original_post_id            = 1;
		$original_post               = $this->getMockBuilder( 'WP_Post' )
		                                    ->disableOriginalConstructor()
		                                    ->getMock();
		$original_post->post_content = 'my-content';

		$page_builders_built->method( 'is_page_builder_page' )
		                    ->with( $original_post )
		                    ->willReturn( true );

		$fields = array(
			'package-something' => 'something',
		);

		\WP_Mock::wpFunction( 'get_post', array(
			'args'   => $original_post_id,
			'return' => $original_post,
		) );

		\WP_Mock::wpFunction( 'wp_update_post', array(
			'times' => 0,
			'args'  => array(
				'ID'           => $new_post_id,
				'post_content' => $original_post->post_content,
			)
		) );

		$page_builders_built->method( 'is_page_builder_page' )
		                    ->with( $original_post )
		                    ->willReturn( true );

		$subject = new WPML_PB_Handle_Post_Body( $page_builders_built );
		$subject->copy( $new_post_id, $original_post_id, $fields );
	}
}
