<?php

/**
 * @group media
 */
class Test_WPML_Page_Builders_Media_Hooks extends \OTGS\PHPUnit\Tools\TestCase {

	const SLUG_TEST = 'the-page-builder';

	/**
	 * @test
	 */
	public function it_should_implement_iwpml_action() {
		$this->assertInstanceOf( 'IWPML_Action', $this->get_subject() );
	}

	/**
	 * @test
	 * @group wpmlmedia-550
	 */
	public function it_should_add_hooks() {
		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'wmpl_pb_get_media_updaters', array( $subject, 'add_media_updater' ) );
		\WP_Mock::expectFilterAdded( 'wpml_media_content_for_media_usage', array( $subject, 'add_package_strings_content' ), 10, 2 );
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

	/**
	 * @test
	 * @group wpmlmedia-550
	 */
	public function it_should_not_alter_content_if_no_package() {
		$content = 'Some content';
		$post    = $this->get_post();

		\WP_Mock::onFilter( 'wpml_st_get_post_string_packages' )
			->with( array(), $post->ID )->reply( array() );

		$subject = $this->get_subject();

		$this->assertSame( $content, $subject->add_package_strings_content( $content, $post ) );
	}

	/**
	 * @test
	 * @group wpmlmedia-550
	 */
	public function it_should_not_alter_content_if_no_strings_in_package() {
		$content = 'Some content';
		$post    = $this->get_post();

		\WP_Mock::onFilter( 'wpml_st_get_post_string_packages' )
			->with( array(), $post->ID )->reply( array( $this->get_strings_package() ) );

		$subject = $this->get_subject();

		$this->assertSame( $content, $subject->add_package_strings_content( $content, $post ) );
	}

	/**
	 * @test
	 * @group wpmlmedia-550
	 */
	public function it_should_add_packages_strings_to_the_content() {
		$content = 'Some content';
		$post    = $this->get_post();

		$string1 = (object) array( 'value' => 'String 1' );
		$string2 = (object) array( 'value' => 'String 2' );
		
		$package1 = $this->get_strings_package( array( $string1 ) );
		$package2 = $this->get_strings_package( array( $string2 ) );

		\WP_Mock::onFilter( 'wpml_st_get_post_string_packages' )
			->with( array(), $post->ID )->reply( array( $package1, $package2 ) );

		$subject = $this->get_subject();

		$this->assertSame(
			$content . PHP_EOL . $string1->value . PHP_EOL . $string2->value,
			$subject->add_package_strings_content( $content, $post )
		);
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

	private function get_post() {
		$post     = $this->getMockBuilder( 'WP_Post' )->getMock();
		$post->ID = mt_rand( 1, 10 );
		return $post;
	}

	private function get_strings_package( $strings = array() ) {
		$package = $this->getMockBuilder( 'WPML_Package' )
		            ->setMethods( array( 'get_package_strings' ) )
		            ->getMock();
		$package->method( 'get_package_strings' )->willReturn( $strings );
		return $package;
	}
}

if ( ! interface_exists( 'IWPML_Action' ) ) {
	interface IWPML_Action {}
}
