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

	private function get_subject( $page_builder_settings ) {
		return new WPML_PB_Shortcode_Strategy( $page_builder_settings );
	}

	private function get_page_builder_settings() {
		return $this->getMockBuilder( 'WPML_Page_Builder_Settings' )
			->setMethods( array( 'is_raw_html_translatable' ) )->getMock();
	}
}
