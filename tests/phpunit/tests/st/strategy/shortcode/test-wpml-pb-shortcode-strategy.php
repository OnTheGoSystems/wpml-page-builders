<?php

class Test_WPML_PB_Shortcode_Strategy extends \OTGS\PHPUnit\Tools\TestCase {

	/**
	 * @test
	 * @dataProvider dp_raw_html_shortcodes
	 * @group wpmlpb-154
	 *
	 * @param array $shortcode_data
	 * @param bool  $is_translatable
	 * @param array $expected_shortcodes
	 */
	public function it_should_add_shortcodes_with_raw_html( $shortcode_data, $is_translatable, $expected_shortcodes ) {

		$expected_shortcodes = array_merge(
			array( WPML_PB_Shortcode_Content_Wrapper::WRAPPER_SHORTCODE_NAME ),
			$expected_shortcodes
		);

		$page_builder_settings = $this->get_page_builder_settings();
		$page_builder_settings->method( 'is_raw_html_translatable' )->willReturn( $is_translatable );

		$subject = $this->get_subject( $page_builder_settings );

		$subject->add_shortcodes( $shortcode_data );

		$this->assertEquals( $expected_shortcodes, $subject->get_shortcodes() );
	}

	public function dp_raw_html_shortcodes() {
		$shortcode_data = array(
			array(
				'tag' => array(
					'value' => 'tag1',
					'encoding' => '',
				),
			),
			array(
				'tag' => array(
					'value' => 'tag2',
					'raw-html' => '',
					'encoding' => '',
				),
			),
			array(
				'tag' => array(
					'value' => 'tag3',
					'raw-html' => '0',
					'encoding' => '',
				),
			),
			array(
				'tag' => array(
					'value' => 'tag4',
					'raw-html' => '1',
					'encoding' => '',
				),
			),
		);

		return array(
			'raw html translatable'     => array( $shortcode_data, true, array( 'tag1', 'tag2', 'tag3', 'tag4' ) ),
			'raw html not translatable' => array( $shortcode_data, false, array( 'tag1', 'tag2', 'tag3' ) ),
		);
	}

	/**
	 * @test
	 * @dataProvider dp_add_ignore_content
	 * @group wpmlcore-5894
	 *
	 * @param array $shortcode_data
	 * @param bool  $ignore_content
	 */
	public function it_should_add_ignore_content( $shortcode_data, $ignore_content ) {

		$page_builder_settings = $this->get_page_builder_settings();

		$subject = $this->get_subject( $page_builder_settings );

		$subject->add_shortcodes( array( $shortcode_data ) );

		$this->assertSame(
			$ignore_content,
			$subject->get_shortcode_ignore_content( $shortcode_data['tag']['value'] )
		);
	}

	public function dp_add_ignore_content() {
		return array(
			'ignore content not set' => array(
				array(
					'tag' => array( 'value' => 'tag1', 'encoding' => '' ),
				),
				false,
			),
			'ignore content 0' => array(
				array(
					'tag' => array( 'value' => 'tag1', 'encoding' => '', 'ignore-content' => '0' ),
				),
				false,
			),
			'ignore content ' => array(
				array(
					'tag' => array( 'value' => 'tag1', 'encoding' => '', 'ignore-content' => '1' ),
				),
				true,
			),
		);
	}

	private function get_subject( $page_builder_settings ) {
		return new WPML_PB_Shortcode_Strategy( $page_builder_settings );
	}

	private function get_page_builder_settings() {
		return $this->getMockBuilder( 'WPML_Page_Builder_Settings' )
			->setMethods( array( 'is_raw_html_translatable' ) )->getMock();
	}
}
