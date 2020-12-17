<?php

namespace WPML\PB\GutenbergCleanup;

use OTGS\PHPUnit\Tools\TestCase;
use WPML\FP\Fns;

/**
 * @group gutenberg-cleanup
 */
class TestShortcodeHooks extends TestCase {

	/**
	 * @test
	 */
	public function itShouldAddHooks() {
		$subject = new ShortcodeHooks();

		\WP_Mock::expectActionAdded(
			'wp_insert_post',
			Fns::withoutRecursion( Fns::noop(), [ $subject, 'removeGutenbergFootprint' ] ),
			10, 2
		);

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itShouldNotRemoveGutenbergFootprintIfNotBuiltWithShortcodes() {
		$post = $this->getPost( '<!-- wp:paragraph -->Something<!-- /wp:paragraph -->' );

		\WP_Mock::onFilter( 'wpml_pb_is_post_built_with_shortcodes' )
			->with( false, $post )
			->reply( false );

		\WP_Mock::userFunction( 'wp_update_post' )->times( 0 );

		$subject = new ShortcodeHooks();

		$subject->removeGutenbergFootprint( $post->ID, $post );
	}

	/**
	 * @test
	 */
	public function itShouldNotRemoveGutenbergFootprintIfNoGbMetaData() {
		$post = $this->getPost( 'Something' );

		\WP_Mock::onFilter( 'wpml_pb_is_post_built_with_shortcodes' )
			->with( false, $post )
			->reply( true );

		\WP_Mock::userFunction( 'wp_update_post' )->times( 0 );

		$subject = new ShortcodeHooks();

		$subject->removeGutenbergFootprint( $post->ID, $post );
	}

	/**
	 * @test
	 */
	public function itShouldRemoveGutenbergFootprint() {
		$cleanContent = 'Something';
		$rawContent   = '
<!-- wp:auto-closing-block block which will wrap all its content in <code> html tag -->
<!-- wp:paragraph
-->'
. $cleanContent .
'<!-- /wp:paragraph -->';

		$originalPost = $this->getPost( $rawContent );

		$extraPackage = $this->getPackage( 'extra', '123' );
		$gbPackage    = $this->getPackage( 'gutenberg', '123' );

		\WP_Mock::onFilter( 'wpml_pb_is_post_built_with_shortcodes' )
			->with( false, $originalPost )
			->reply( true );

		\WP_Mock::userFunction( 'wp_update_post' )
			->times( 1 )
			->andReturn( function( $savedPost ) use ( $originalPost, $cleanContent ) {
				$this->assertSame( $originalPost->ID, $savedPost->ID );
				$this->assertSame( $cleanContent, $savedPost->post_content );
			} );

		\WP_Mock::onFilter( 'wpml_st_get_post_string_packages' )
			->with( [], $originalPost->ID )
			->reply( [ $extraPackage, $gbPackage ] );

		\WP_Mock::expectAction( 'wpml_delete_package', $gbPackage->name, $gbPackage->kind );

		$subject = new ShortcodeHooks();

		$subject->removeGutenbergFootprint( $originalPost->ID, $originalPost );
	}

	/**
	 * @param string $content
	 *
	 * @return \WP_Post|\PHPUnit_Framework_MockObject_MockObject
	 */
	private function getPost( $content ) {
		$post               = $this->createMock( \WP_Post::class );
		$post->ID           = 123;
		$post->post_content = $content;

		return $post;
	}

	/**
	 * @param string $kind
	 * @param string $name
	 *
	 * @return \WPML_Package|\PHPUnit_Framework_MockObject_MockObject
	 */
	private function getPackage( $kind, $name ) {
		$package            = $this->createMock( \WPML_Package::class );
		$package->kind_slug = strtolower( $kind );
		$package->kind      = strtoupper( $kind );
		$package->name      = $name;

		return $package;
	}
}
