<?php

namespace WPML\PB\Config;

use OTGS\PHPUnit\Tools\TestCase;

/**
 * @group config
 */
class TestParser extends TestCase {

	const CONFIG_ROOT           = 'super-pb-widgets';
	const DEFAULT_CONDITION_KEY = 'WidgetKind';

	/**
	 * @test
	 * @dataProvider dpShouldExtract
	 *
	 * @param array $allConfig
	 * @param array $expectedPbConfig
	 */
	public function itShouldExtract( array $allConfig, array $expectedPbConfig ) {
		$this->assertEquals( $expectedPbConfig, $this->getSubject()->extract( $allConfig ) );
	}

	public function dpShouldExtract() {
		return [
			'no PB config' => [
				$this->getAllConfig(),
				[],
			],
			'empty PB config' => [
				$this->getAllConfig( [] ),
				[],
			],
			'No specified widget condition' => [
				$this->getAllConfig( [
					[
						'attr'   => [
							'name' => 'some-widget',
						],
						'fields' => [
							'field' => [
								'value' => 'title',
								'attr'  => [
									'type'        => 'The Widget Title',
									'editor_type' => 'TEXTAREA',
								],
							],
						],
					],
				] ),
				[
					'some-widget' => [
						'conditions' => [
							self::DEFAULT_CONDITION_KEY => 'some-widget',
						],
						'fields' => [
							[
								'field'       => 'title',
								'type'        => 'The Widget Title',
								'editor_type' => 'TEXTAREA',
							],
						],
					],
				],
			],
			'1 specified widget condition' => [
				$this->getAllConfig( [
					[
						'attr'   => [
							'name' => 'some-widget',
						],
						'conditions' => [
							'condition' => [
								'value' => 'the-condition-value',
								'attr'  => [
									'key' => 'the-condition-key',
								],
							],
						],
						'fields' => [
							'field' => [
								'value' => 'title',
								'attr'  => [
									'type'        => 'The Widget Title',
									'editor_type' => 'TEXTAREA',
								],
							],
						],
					],
				] ),
				[
					'some-widget' => [
						'conditions' => [
							'the-condition-key' => 'the-condition-value',
						],
						'fields' => [
							[
								'field'       => 'title',
								'type'        => 'The Widget Title',
								'editor_type' => 'TEXTAREA',
							],
						],
					],
				],
			],
			'with field having "key_of"' => [
				$this->getAllConfig( [
					[
						'attr'   => [
							'name' => 'some-widget',
						],
						'fields' => [
							'field' => [
								'value' => 'title',
								'attr'  => [
									'type'        => 'The Widget Title',
									'editor_type' => 'TEXTAREA',
									'key_of'      => 'the-key-of-the-field',
								],
							],
						],
					],
				] ),
				[
					'some-widget' => [
						'conditions' => [
							self::DEFAULT_CONDITION_KEY => 'some-widget',
						],
						'fields' => [
							'the-key-of-the-field' => [
								'field'       => 'title',
								'type'        => 'The Widget Title',
								'editor_type' => 'TEXTAREA',
							],
						],
					],
				],
			],
			'with field having "field_id"' => [
				$this->getAllConfig( [
					[
						'attr'   => [
							'name' => 'some-widget',
						],
						'fields' => [
							'field' => [
								'value' => 'title',
								'attr'  => [
									'type'        => 'The Widget Title',
									'editor_type' => 'TEXTAREA',
									'field_id'    => 'the-id-of-the-field',
								],
							],
						],
					],
				] ),
				[
					'some-widget' => [
						'conditions' => [
							self::DEFAULT_CONDITION_KEY => 'some-widget',
						],
						'fields' => [
							[
								'field'       => 'title',
								'type'        => 'The Widget Title',
								'editor_type' => 'TEXTAREA',
								'field_id'    => 'the-id-of-the-field',
							],
						],
					],
				],
			],
			'with "fields in item"' => [
				$this->getAllConfig( [
					[
						'attr'   => [
							'name' => 'some-widget',
						],
						'fields-in-item' => [
							'attr' => [
								'items_of' => 'the-key-of-item',
							],
							'field' => [
								'value' => 'title',
								'attr'  => [
									'type'        => 'The Widget Title',
									'editor_type' => 'TEXTAREA',
								],
							],
						],
					],
				] ),
				[
					'some-widget' => [
						'conditions' => [
							self::DEFAULT_CONDITION_KEY => 'some-widget',
						],
						'fields' => [],
						'fields_in_item' => [
							'the-key-of-item' => [
								[
									'field'       => 'title',
									'type'        => 'The Widget Title',
									'editor_type' => 'TEXTAREA',
								]
							],
						],
					],
				],
			],
			// https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-7337
			'with multiple occurrences of "fields in item"' => [
				$this->getAllConfig( [
					[
						'attr'   => [
							'name' => 'some-widget',
						],
						'fields-in-item' => [
							[
								'attr' => [
									'items_of' => 'the-first-key-of-item',
								],
								'field' => [
									'value' => 'first',
									'attr'  => [
										'type'        => 'The first key field',
										'editor_type' => 'TEXTAREA',
									],
								],
							],
							[
								'attr' => [
									'items_of' => 'the-second-key-of-item',
								],
								'field' => [
									'value' => 'second',
									'attr'  => [
										'type'        => 'The second key field',
										'editor_type' => 'LINE',
									],
								],
							],
						],
					],
				] ),
				[
					'some-widget' => [
						'conditions' => [
							self::DEFAULT_CONDITION_KEY => 'some-widget',
						],
						'fields' => [],
						'fields_in_item' => [
							'the-first-key-of-item' => [
								[
									'field'       => 'first',
									'type'        => 'The first key field',
									'editor_type' => 'TEXTAREA',
								],
							],
							'the-second-key-of-item' => [
								[
									'field'       => 'second',
									'type'        => 'The second key field',
									'editor_type' => 'LINE',
								],
							],
						],
					],
				],
			],
			'with "integration-classes"' => [
				$this->getAllConfig( [
					[
						'attr'   => [
							'name' => 'some-widget',
						],
						'integration-classes' => [
							'integration-class' => [
								'value' => 'TheIntegrationClass',
							],
						],
					],
				] ),
				[
					'some-widget' => [
						'conditions' => [
							self::DEFAULT_CONDITION_KEY => 'some-widget',
						],
						'fields' => [],
						'integration-class' => [
							'TheIntegrationClass'
						],
					],
				],
			],
			'with all in more than occurrences' => [
				$this->getAllConfig( [
					[
						'attr'   => [
							'name' => 'some-widget',
						],
						'conditions' => [
							'condition' => [
								[
									'value' => 'the-condition-value',
									'attr'  => [
										'key' => 'the-condition-key',
									],
								],
								[
									'value' => 'the-condition-value2',
									'attr'  => [
										'key' => 'the-condition-key2',
									],
								],
							],
						],
						'fields' => [
							'field' => [
								[
									'value' => 'title',
									'attr'  => [
										'type'        => 'The Widget Title',
										'editor_type' => 'TEXTAREA',
									],
								],
								[
									'value' => 'sub-title',
									'attr'  => [
										'type'        => 'The Widget Sub-Title',
										'editor_type' => 'TEXTAREA',
									],
								],
							],
						],
						'fields-in-item' => [
							'attr' => [
								'items_of' => 'the-key-of-item',
							],
							'field' => [
								[
									'value' => 'title',
									'attr'  => [
										'type'        => 'The Item Title',
										'editor_type' => 'TEXTAREA',
									],
								],
								[
									'value' => 'sub-title',
									'attr'  => [
										'type'        => 'The Item Sub-Title',
										'editor_type' => 'TEXTAREA',
									],
								],
								[
									'value' => 'url',
									'attr'  => [
										'type'        => 'The Item URL',
										'editor_type' => 'LINK',
										'key_of'      => 'the-key-of-item-url',
									],
								],
							],
						],
						'integration-classes' => [
							'integration-class' => [
								[ 'value' => 'TheIntegrationClass' ],
								[ 'value' => 'TheIntegrationClass2' ],
							],
						],
					],
				] ),
				[
					'some-widget' => [
						'conditions' => [
							'the-condition-key'  => 'the-condition-value',
							'the-condition-key2' => 'the-condition-value2',
						],
						'fields' => [
							[
								'field'       => 'title',
								'type'        => 'The Widget Title',
								'editor_type' => 'TEXTAREA',
							],
							[
								'field'       => 'sub-title',
								'type'        => 'The Widget Sub-Title',
								'editor_type' => 'TEXTAREA',
							],
						],
						'fields_in_item' => [
							'the-key-of-item' => [
								[
									'field'       => 'title',
									'type'        => 'The Item Title',
									'editor_type' => 'TEXTAREA',
								],
								[
									'field'       => 'sub-title',
									'type'        => 'The Item Sub-Title',
									'editor_type' => 'TEXTAREA',
								],
								'the-key-of-item-url' => [
									'field'       => 'url',
									'type'        => 'The Item URL',
									'editor_type' => 'LINK',
								],
							],
						],
						'integration-class' => [
							'TheIntegrationClass',
							'TheIntegrationClass2',
						],
					],
				],
			],
		];
	}

	private function getSubject() {
		return new Parser( self::CONFIG_ROOT, self::DEFAULT_CONDITION_KEY );
	}

	private function getAllConfig( array $widgetsConfig = null ) {
		$allConfig = [
			'wpml-config' => [
				'custom-types' => [],
				'taxonomies'   => [],
			],
		];

		if ( is_array( $widgetsConfig ) ) {
			$allConfig['wpml-config'][ self::CONFIG_ROOT ]['widget'] = $widgetsConfig;
		}

		return $allConfig;
	}
}
