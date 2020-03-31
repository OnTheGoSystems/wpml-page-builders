<?php

namespace WPML\PB\Compatibility\Toolset\Layouts;

use Peast\Syntax\Node\Function_;
use tad\FunctionMocker\FunctionMocker;

/**
 * @group compatibility
 * @group toolset
 * @group layouts
 */
class TestHooksFactory extends \OTGS\PHPUnit\Tools\TestCase {

	/**
	 * @test
	 */
	public function itLoadsOnBackendAndFrontend() {
		$subject = new HooksFactory();
		$this->assertInstanceOf( '\IWPML_Backend_Action_Loader', $subject );
		$this->assertInstanceOf( '\IWPML_Frontend_Action_Loader', $subject );
	}

	/**
	 * @test
	 */
	public function itReturnsNullIfLayoutsNotActive() {
		$defined = FunctionMocker::replace( 'defined', false );

		$subject = new HooksFactory();
		$this->assertNull( $subject->create() );

		$defined->wasCalledWithOnce( [ 'WPDDL_VERSION' ] );
	}

	/**
	 * @test
	 */
	public function itReturnsHooksObjectIfLayoutsActive() {
		$defined = FunctionMocker::replace( 'defined', true );

		$subject = new HooksFactory();
		$this->assertInstanceOf( Hooks::class, $subject->create() );

		$defined->wasCalledWithOnce( [ 'WPDDL_VERSION' ] );
	}
}