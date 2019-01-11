<?php

/**
 * Class Test_WPML_ST_Diff
 *
 */
class Test_WPML_ST_Diff extends WPML_PB_TestCase {

	/**
	 * @dataProvider diff_provider
	 *
	 * @param $old_text
	 * @param $new_text
	 * @param $expected
	 */
	public function test_diff( $old_text, $new_text, $expected ) {

		$diff = WPML_ST_Diff::diff( preg_split( '/[\s]+/', $old_text ), preg_split( '/[\s]+/', $new_text ) );
		$this->assertEquals( $expected, $diff );
	}

	public function diff_provider() {
		return array(
			array(
				'old string',
				'new string',
				array(
					array( 'deleted' => array( 'old' ), 'inserted' => array( 'new' ) ),
					'string',
					array( 'deleted' => array(), 'inserted' => array() ),
				)
			),
			array(
				'string old',
				'string new',
				array(
					array( 'deleted' => array(), 'inserted' => array() ),
					'string',
					array( 'deleted' => array( 'old' ), 'inserted' => array( 'new' ) ),
				)
			),
		);
	}

	/**
	 * @dataProvider sameness_provider
	 *
	 * @group wpmlcore-6189
	 *
	 * @param $old_text
	 * @param $new_text
	 * @param $expected
	 */
	public function test_sameness( $old_text, $new_text, $expected ) {
		$percent = WPML_ST_Diff::get_sameness_percent( $old_text, $new_text, $expected );
		$this->assertLessThan( 0.5, abs( $expected - $percent ), 'Actual percentage: ' . $percent );
	}

	public function sameness_provider() {
		return array(
			array( 'This is the original string', 'This is the updated string', 55.55 ),
			array( 'This is the original string', 'This string is updated', 22.22 ),
			array( 'This is the original string that will be modified', 'This is the original string that has been modified', 71.42 ),
			array( 'This is the original string that will be modified', 'The original string that has been modified', 53.06 ),
			array( 'This is the original string that will be modified', 'The original string that has been modified', 53.06 ),
			array( "<p>Hello there</p>\n", "<p>Updated Hello there</p>\n", 83.33 ),
		);
	}
}
