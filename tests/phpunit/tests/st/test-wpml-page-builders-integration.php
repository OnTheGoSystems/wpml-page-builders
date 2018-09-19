<?php

/**
 * Class Test_WPML_Page_Builders_Integration
 *
 * @group page-builders
 * @group beaver-builder
 * @group elementor
 */
class Test_WPML_Page_Builders_Integration extends \OTGS\PHPUnit\Tools\TestCase {

	private $register_strings;
	private $update_translation;
	private $data_settings;

	public function setUp() {
		parent::setUp();

		$this->register_strings = $this->getMockBuilder( 'WPML_Page_Builders_Register_Strings' )
		                         ->disableOriginalConstructor()
		                         ->getMock();

		$this->update_translation = $this->getMockBuilder( 'WPML_Page_Builders_Update_Translation' )
		                           ->disableOriginalConstructor()
		                           ->getMock();

		$this->data_settings = $this->getMockBuilder( 'IWPML_Page_Builders_Data_Settings' )
			->setMethods( array(
				'add_hooks',
				'get_meta_field',
				'get_node_id_field',
				'get_fields_to_copy',
				'get_fields_to_save',
				'convert_data_to_array',
				'prepare_data_for_saving',
				'get_pb_name',
				'add_data_custom_field_to_md5',
				'should_copy_post_body' ) )
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * @test
	 * @dataProvider dp_pb_plugins
	 */
	public function it_adds_hooks( $plugin ) {

		$this->data_settings->method( 'get_pb_name' )
			->willReturn( $plugin );

		$this->data_settings->expects( $this->once() )
			->method( 'add_hooks' );

		$subject = new WPML_Page_Builders_Integration( $this->register_strings, $this->update_translation, $this->data_settings );

		\WP_Mock::expectFilterAdded( 'wpml_page_builder_support_required', array( $subject, 'support_required' ) );
		\WP_Mock::expectActionAdded( 'wpml_page_builder_register_strings', array( $subject, 'register_pb_strings' ), 10, 2 );
		\WP_Mock::expectActionAdded( 'wpml_page_builder_string_translated', array( $subject, 'update_translated_post' ), 10, 5 );
		\WP_Mock::expectFilterAdded( 'wpml_get_translatable_types', array( $subject, 'remove_shortcode_strings_type_filter' ), 12, 1);

		$subject->add_hooks();
	}

	/**
	 * @test
	 * @dataProvider dp_pb_plugins
	 */
	public function support_required_adds_pb( $plugin ) {

		$this->data_settings->method( 'get_pb_name' )
		                    ->willReturn( $plugin );

		$subject = new WPML_Page_Builders_Integration( $this->register_strings, $this->update_translation, $this->data_settings );

		$plugins = array(
			rand_str( 10 ),
		);

		$expected = $plugins;
		$expected[] = $plugin;

		$this->assertEquals( $expected, $subject->support_required( $plugins ) );
	}

	/**
	 * @test
	 * @dataProvider dp_pb_plugins
	 */
	public function it_register_pb_strings( $plugin ) {

		$this->data_settings->method( 'get_pb_name' )
		                    ->willReturn( $plugin );

		$post = $this->get_wp_post_stub();
		$package = array( 'kind' => $plugin );

		$this->register_strings->expects( $this->once() )
			->method( 'register_strings' )
			->with( $post, $package );

		$subject = new WPML_Page_Builders_Integration( $this->register_strings, $this->update_translation, $this->data_settings );
		$subject->register_pb_strings( $post, $package );
	}

	/**
	 * @test
	 * @dataProvider dp_pb_plugins
	 */
	public function it_update_translated_post( $plugin ) {

		$this->data_settings->method( 'get_pb_name' )
		                    ->willReturn( $plugin );

		$translated_post_id = mt_rand();
		$original_post = $this->get_wp_post_stub();
		$string_translations = array( 'something' );
		$lang = 'en';

		$this->update_translation->expects( $this->once() )
		                       ->method( 'update' )
		                       ->with( $translated_post_id, $original_post, $string_translations, $lang );

		$subject = new WPML_Page_Builders_Integration( $this->register_strings, $this->update_translation, $this->data_settings );
		$subject->update_translated_post( $plugin, $translated_post_id, $original_post, $string_translations, $lang );
	}

	/**
	 * @test
	 * @dataProvider dp_pb_plugins
	 */
	public function it_should_remove_translatable_type( $plugin ) {

		$this->data_settings->method( 'get_pb_name' )
		                    ->willReturn( $plugin );

		\WP_Mock::wpFunction( 'sanitize_title_with_dashes', array(
			'args' => array( $plugin ),
			'return' => str_replace( ' ', '-', strtolower( $plugin ) ),
		));
		$types = array( 'post' => 'anything', 'page' => 'anything', sanitize_title_with_dashes( $plugin ) => 'anything' );

		$subject = new WPML_Page_Builders_Integration( $this->register_strings, $this->update_translation, $this->data_settings );
		$filtered_types = $subject->remove_shortcode_strings_type_filter( $types );
		$this->assertEquals( array( 'post' => 'anything', 'page' => 'anything' ), $filtered_types );
	}

	private function get_wp_post_stub() {
		return $this->getMockBuilder( 'WP_Post' )
					->disableOriginalConstructor()
					->getMock();
	}

	public function dp_pb_plugins() {
		return array(
			array( 'Elementor' ),
			array( 'Beaver builder' ),
		);
	}

	public function tearDown() {
		unset( $this->register_strings, $this->update_translation, $this->data_settings );
	}
}
