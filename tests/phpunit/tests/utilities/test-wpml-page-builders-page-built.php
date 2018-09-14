<?php

/**
 * Class Test_WPML_Page_Builders_Page_Built
 *
 * @group wpmlpb-148
 */
class Test_WPML_Page_Builders_Page_Built extends WPML_PageBuilders_TestCase {

	/**
	 * @test
	 */
	public function it_returns_true_when_it_is_a_page_builder_page() {
		$post = $this->getMockBuilder( 'WP_Post' )
		             ->disableOriginalConstructor()
		             ->getMock();

		$post->post_content = '[vc_row][/vc_row]';

		$config = $this->getMockBuilder( 'WPML_Config_Built_With_Page_Builders' )
		               ->setMethods( array( 'get' ) )
		               ->disableOriginalConstructor()
		               ->getMock();

		$config->method( 'get' )
		       ->willReturn( array( '/\[vc_row\]/' ) );

		$subject = new WPML_Page_Builders_Page_Built( $config );
		$this->assertTrue( $subject->is_page_builder_page( $post ) );
	}

	/**
	 * @test
	 */
	public function it_returns_false_when_it_is_not_a_page_builder_page() {
		$post = $this->getMockBuilder( 'WP_Post' )
		             ->disableOriginalConstructor()
		             ->getMock();

		$post->post_content = 'my content';

		$config = $this->getMockBuilder( 'WPML_Config_Built_With_Page_Builders' )
		               ->setMethods( array( 'get' ) )
		               ->disableOriginalConstructor()
		               ->getMock();

		$config->method( 'get' )
		       ->willReturn( array( '/\[vc_row\]/' ) );

		$subject = new WPML_Page_Builders_Page_Built( $config );
		$this->assertFalse( $subject->is_page_builder_page( $post ) );
	}
}
