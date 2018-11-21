<?php

/**
 * @group page-builders-shortcodes
 * @group wpmltm-2970
 */
class Test_WPML_PB_Shortcode_Content_Wrapper extends WPML_PB_TestCase {

	/**
	 * @test
	 * @dataProvider dp_get_wrapped_content
	 *
	 * @param string $content
	 * @param string $expected_content
	 */
	public function it_should_get_wrapped_content( $content, $expected_content ) {
		$valid_shortcodes = array(
			'av_button',
			'av_notification',
			'shortcode_nested',
			'shortcode_A',
		);

		$subject = new WPML_PB_Shortcode_Content_Wrapper( $content, $valid_shortcodes );
		$this->assertEquals( $expected_content, $subject->get_wrapped_content() );
	}

	public function dp_get_wrapped_content() {
		$name_base     = __DIR__ . '/fixtures/content-wrapper/';
		$wrapped_files = glob( $name_base . '*-wrapped.html' );
		$data          = array();

		foreach ( $wrapped_files as $wrapped_file ) {
			$original_file                      = preg_replace( '/(.+)(-wrapped).html$/', '$1.html', $wrapped_file );
			$data[ basename( $original_file ) ] = array(
				file_get_contents( $original_file ),
				file_get_contents( $wrapped_file ),
			);
		}

		return $data;
	}
}
