<?php


namespace WPML\PB\Config;

use OTGS\PHPUnit\Tools\TestCase;

/**
 * @group config
 */
class TestFactory extends TestCase {

	/**
	 * @test
	 */
	public function itCreatesAndReturnInstance() {
		\Mockery::mock( 'alias:WPML\WP\OptionManager' );

		$subject = new SomeFactoryForTest();

		$this->assertInstanceOf( Hooks::class, $subject->create() );
	}
}

class SomeFactoryForTest extends Factory {

	const DATA = [
		'configRoot'              => 'the-root',
		'defaultConditionKey'     => 'the-condition',
		'pbKey'                   => 'the-key',
		'translatableWidgetsHook' => 'the-hook',
	];

	/**
	 * @inheritDoc
	 */
	protected function getPbData( $key ) {
		return self::DATA[ $key ];
	}
}
