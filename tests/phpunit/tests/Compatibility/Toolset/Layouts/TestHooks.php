<?php

namespace WPML\PB\Compatibility\Toolset\Layouts;

use OTGS\PHPUnit\Tools\TestCase;

/**
 * @group compatibility
 * @group toolset
 * @group layouts
 */
class TestHooks extends TestCase {

	/**
	 * @test
	 */
	public function itImplementActionLoader() {
		$subject = new Hooks();
		$this->assertInstanceOf( \IWPML_Action::class, $subject );
	}

	/**
	 * @test
	 */
	public function itShouldAddHooks() {
		$subject = new Hooks();
		\WP_Mock::expectFilterAdded( 'wpml_pb_is_page_builder_page', [ Hooks::class, 'isLayoutPage' ], 10, 2 );
		$subject->add_hooks();
	}

	/**
	 * @test
	 * @dataProvider dpItShouldNotFilterIsLayoutPageIfNotLayoutPage
	 *
	 * @param mixed $metaValue
	 */
	public function itShouldNotFilterIsLayoutPageIfNotLayoutPage( $metaValue ) {
		$isPbPage = 'some-boolean';
		$post     = $this->getPost( 123 );

		\WP_Mock::userFunction( 'get_post_meta' )
			->with( $post->ID, '_private_layouts_template_in_use', true )
			->andReturn( $metaValue );

		$this->assertEquals( $isPbPage, Hooks::isLayoutPage( $isPbPage, $post ) );
	}

	public function dpItShouldNotFilterIsLayoutPageIfNotLayoutPage() {
		return [
			[ '' ],
			[ 'no' ],
		];
	}

	/**
	 * @test
	 */
	public function itShouldNotFilterIsLayoutPage() {
		$isPbPage = 'some-boolean';
		$post     = $this->getPost( 123 );

		\WP_Mock::userFunction( 'get_post_meta' )
			->with( $post->ID, '_private_layouts_template_in_use', true )
			->andReturn( 'yes' );

		$this->assertTrue( Hooks::isLayoutPage( $isPbPage, $post ) );
	}

	/**
	 * @param int $id
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject|\WP_Post
	 */
	private function getPost( $id ) {
		$post = $this->createMock( 'WP_Post' );
		$post->ID = $id;

		return $post;
	}
}
