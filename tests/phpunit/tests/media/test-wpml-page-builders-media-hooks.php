<?php

/**
 * @group media
 */
class Test_WPML_Page_Builders_Media_Hooks extends OTGS_TestCase {

	const SLUG_TEST = 'the-page-builder';

	/**
	 * @test
	 */
	public function it_should_implement_iwpml_action() {
		$this->assertInstanceOf( 'IWPML_Action', $this->get_subject() );
	}

	/**
	 * @test
	 */
	public function it_should_add_hooks() {
		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'wmpl_pb_get_media_updaters', array( $subject, 'add_media_updater' ) );
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_should_add_media_updater_only_once() {
		$updaters = array(
			'some-plugin' => $this->get_media_update(),
		);

		$pb_updater = $this->get_media_update();

		$factory = $this->get_media_update_factory();
		$factory->expects( $this->once() )->method( 'create' )->willReturn( $pb_updater );

		$subject = $this->get_subject( $factory );

		$filtered_updaters = $subject->add_media_updater( $updaters );
		$this->check_updaters( $updaters, $filtered_updaters );
		$filtered_updaters = $subject->add_media_updater( $filtered_updaters );
		$this->check_updaters( $updaters, $filtered_updaters );
	}

	private function check_updaters( $updaters, $filtered_updaters ) {
		$this->assertCount( 2, $filtered_updaters );
		$this->assertSame( $updaters['some-plugin'], $filtered_updaters['some-plugin'] );
		$this->assertInstanceOf( 'IWPML_PB_Media_Update', $filtered_updaters[ self::SLUG_TEST ] );
	}

	private function get_subject( $media_update_factory = null ) {
		$media_update_factory = $media_update_factory ? $media_update_factory : $this->get_media_update_factory();
		return new WPML_Page_Builders_Media_Hooks( $media_update_factory, self::SLUG_TEST );
	}

	private function get_media_update_factory() {
		return $this->getMockBuilder( 'IWPML_PB_Media_Update_Factory' )
			->setMethods( array( 'create' ) )->getMock();
	}

	private function get_media_update() {
		return $this->getMockBuilder( 'IWPML_PB_Media_Update' )->getMock();
	}
}

if ( ! interface_exists( 'IWPML_Action' ) ) {
	interface IWPML_Action {}
}