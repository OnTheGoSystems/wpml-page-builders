<?php

/**
 * @group wpmlcore-6120
 */
class Test_WPML_PB_Last_Edit_Mode extends \OTGS\PHPUnit\Tools\TestCase {

	/**
	 * @test
	 */
	public function it_should_verify_native_editor() {
		$post_id = 123;

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args'   => array( $post_id, WPML_PB_Last_Translation_Edit_Mode::POST_META_KEY, true ),
			'return' => WPML_PB_Last_Translation_Edit_Mode::NATIVE_EDITOR,
		));

		$subject = $this->get_subject();

		$this->assertTrue( $subject->is_native_editor( $post_id ) );
		$this->assertFalse( $subject->is_translation_editor( $post_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_verify_translation_editor() {
		$post_id = 123;

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args'   => array( $post_id, WPML_PB_Last_Translation_Edit_Mode::POST_META_KEY, true ),
			'return' => WPML_PB_Last_Translation_Edit_Mode::TRANSLATION_EDITOR,
		));

		$subject = $this->get_subject();

		$this->assertFalse( $subject->is_native_editor( $post_id ) );
		$this->assertTrue( $subject->is_translation_editor( $post_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_set_native_editor() {
		$post_id = 123;

		\WP_Mock::userFunction( 'update_post_meta', array(
			'times' => 1,
			'args'  => array(
				$post_id,
				WPML_PB_Last_Translation_Edit_Mode::POST_META_KEY,
				WPML_PB_Last_Translation_Edit_Mode::NATIVE_EDITOR,
			),
		));

		$subject = $this->get_subject();

		$subject->set_native_editor( $post_id );
	}

	/**
	 * @test
	 */
	public function it_should_set_translation_editor() {
		$post_id = 123;

		\WP_Mock::userFunction( 'update_post_meta', array(
			'times' => 1,
			'args'  => array(
				$post_id,
				WPML_PB_Last_Translation_Edit_Mode::POST_META_KEY,
				WPML_PB_Last_Translation_Edit_Mode::TRANSLATION_EDITOR,
			),
		));

		$subject = $this->get_subject();

		$subject->set_translation_editor( $post_id );
	}

	private function get_subject() {
		return new WPML_PB_Last_Translation_Edit_Mode();
	}
}