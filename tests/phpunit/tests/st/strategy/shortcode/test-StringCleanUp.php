<?php

namespace WPML\PB\Shortcode;

class Test_StringCleanUp extends \WPML_PB_TestCase {

	public function setUp() {
		parent::setUp();

		$this->existingStrings = [ md5( 'string1' ) => 'string1', md5( 'string2' ) => 'string2' ];
	}

	/**
	 * @test
	 */
	public function it_can_get_existing_strings() {
		$postId = 123;

		$subject = $this->getSubject( $postId );

		$this->assertEquals( $this->existingStrings, $subject->get() );
	}

	/**
	 * @test
	 */
	public function it_removes_string() {
		$postId = 123;

		$subject = $this->getSubject( $postId );
		$subject->remove( 'string1' );

		$remainingStrings = $subject->get();
		$this->assertCount( 1, $remainingStrings );
		$this->assertEquals( 'string2', $remainingStrings[ md5( 'string2' ) ] );
	}

	/**
	 * @test
	 */
	public function it_cleans_up() {
		$postId = 123;

		$strategy = $this->getStrategy();

		foreach( $this->existingStrings as $string ) {
			$strategy->shouldReceive( 'remove_string' )->once()->with( $string );
		}

		$subject = new StringCleanUp( $postId, $strategy );
		$subject->cleanUp();
	}

	/**
	 * @param $postId
	 *
	 * @return StringCleanUp
	 */
	private function getSubject( $postId ) {
		return new StringCleanUp( $postId, $this->getStrategy() );
	}

	/**
	 * @return \Mockery\MockInterface
	 */
	private function getStrategy() {
		$strategy = \Mockery::mock( 'WPML_PB_Shortcode_Strategy' );
		$strategy->shouldReceive( 'get_package_key' )->andReturn( 'packageKey' );
		$strategy->shouldReceive( 'get_package_strings' )->andReturn( $this->existingStrings );

		return $strategy;
	}

}
