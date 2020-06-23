<?php

namespace WPML\PB;

class Test_ShortCodesInGutenbergBlocks extends \WPML_PB_TestCase {

	/**
	 * @test
	 */
	public function it_records_package() {
		$package       = \Mockery::mock( '\WPML_Package' );
		$package->kind = 'Page Builder ShortCode Strings';
		$language      = 'de';

		$strategy = \Mockery::mock( '\WPML_PB_String_Translation_By_Strategy' );
		$strategy->shouldReceive( 'add_package_to_update_list' )
		         ->once()
		         ->andReturnUsing( function ( $package, $lang ) use ( $language ) {
			         $this->assertEquals( ShortCodesInGutenbergBlocks::FORCED_GUTENBERG, $package->kind );
			         $this->assertEquals( $language, $lang );
		         } );

		ShortCodesInGutenbergBlocks::recordPackage( $strategy, 'Gutenberg', $package, $language );
	}

	/**
	 * @test
	 */
	public function it_does_not_records_package_for_wrong_type() {
		$package       = \Mockery::mock( '\WPML_Package' );
		$package->kind = 'Page Builder ShortCode Strings';
		$language      = 'de';

		$strategy = \Mockery::mock( '\WPML_PB_String_Translation_By_Strategy' );
		$strategy->shouldReceive( 'add_package_to_update_list' )
		         ->never();

		ShortCodesInGutenbergBlocks::recordPackage( $strategy, 'other', $package, $language );

		$package->kind = 'other';
		ShortCodesInGutenbergBlocks::recordPackage( $strategy, 'Gutenberg', $package, $language );
	}

	/**
	 * @test
	 */
	public function it_fixes_package_kind() {
		$packageData = [ 'package' => (object) [ 'kind' => ShortCodesInGutenbergBlocks::FORCED_GUTENBERG ] ];
		$fixed = ShortCodesInGutenbergBlocks::fixupPackage( $packageData );
		$this->assertEquals( 'Gutenberg', $fixed['package']->kind );

		$packageData = [ 'package' => (object) [ 'kind' => 'other' ] ];
		$fixed = ShortCodesInGutenbergBlocks::fixupPackage( $packageData );
		$this->assertEquals( 'other', $fixed['package']->kind );
	}

	/**
	 * @test
	 */
	public function it_normalizes_packages() {
		$id1 = 'id1';
		$id2 = 'id2';
		$packageData1 = [ 'package' => (object) [ 'kind' => ShortCodesInGutenbergBlocks::FORCED_GUTENBERG ] ];
		$packageData2 = [ 'package' => (object) [ 'kind' => 'Gutenberg' ] ];

		$packages = [ $id1 => $packageData1, $id2 => $packageData2 ];
		$normalized = ShortCodesInGutenbergBlocks::normalizePackages( $packages );

		$this->assertEquals(  [ $id2 => $packageData2 ], $normalized );

	}

	/**
	 * @test
	 */
	public function it_does_not_normalizes_packages_if_less_than_two_packages() {
		$packageData = [ 'package' => (object) [ 'kind' => ShortCodesInGutenbergBlocks::FORCED_GUTENBERG ] ];

		$packages = [ $packageData ];
		$normalized = ShortCodesInGutenbergBlocks::normalizePackages( $packages );

		$this->assertEquals( $packages, $normalized );

	}


}
