<?php

/**
 * Class Test_WPML_String_Registration_Factory
 *
 * @group page-builders
 * @group beaver-builder
 */
class Test_WPML_String_Registration_Factory extends \OTGS\PHPUnit\Tools\TestCase {

	/**
	 * @test
	 */
	public function it_creates() {
		global $sitepress;

		$plugin_name = rand_str( 10 );

		$this->mockMake( 'WPML_Translate_Link_Targets' );
		$this->mockMake( 'WPML_ST_String_Factory' );

		$sitepress = \Mockery::mock( 'SitePress' );
		$sitepress->shouldReceive( 'get_active_languages' )->andReturn( [] );

		$subject = new WPML_String_Registration_Factory( $plugin_name );
		$this->assertInstanceOf( 'WPML_PB_String_Registration', $subject->create() );
	}

	private function mockMake( $className ) {
		\WP_Mock::userFunction( 'WPML\Container\make', [
			'args'   => $className,
			'return' => \Mockery::mock( $className ),
		] );

	}
}
