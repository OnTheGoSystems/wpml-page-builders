<?php
/**
 * Test_WPML_TM_Page_Builders_Field_Wrapper class file.
 *
 * @package wpml-page-builders
 */

/**
 * Class Test_WPML_TM_Page_Builders_Field_Wrapper
 *
 * @group page-builders
 */
class Test_WPML_TM_Page_Builders_Field_Wrapper extends \OTGS\PHPUnit\Tools\TestCase {

	/**
	 * Package id.
	 *
	 * @var int
	 */
	private $package_id;

	/**
	 * String id.
	 *
	 * @var int
	 */
	private $string_id;

	/**
	 * String.
	 *
	 * @var string
	 */
	private $string;

	/**
	 * String title.
	 *
	 * @var string
	 */
	private $string_title;

	/**
	 * Setup test.
	 */
	public function setUp() {
		parent::setUp();

		WP_Mock::wpFunction(
			'wp_list_pluck',
			array(
				'return' => function ( $list, $field, $index_key ) {
					$result = array();
					foreach ( $list as $row ) {
						$result[ $row[ $index_key ] ] = $row[ $field ];
					}

					return $result;
				},
			)
		);

		$this->package_id = 10;
		$this->string_id  = 12;

		$this->string       = new stdClass();
		$this->string->id   = $this->string_id;
		$this->string->name = 'string_name';

		$this->string_title = rand_str( 10 );

		\WP_Mock::onFilter( 'wpml_string_title_from_id' )
		        ->with( $this->string_id )
		        ->reply( $this->string_title );
	}

	/**
	 * It gets package id.
	 *
	 * @test
	 */
	public function it_gets_package_id() {
		$subject = $this->create_subject();

		$this->assertEquals( $this->package_id, $subject->get_package_id() );
	}

	/**
	 * It gets string id.
	 *
	 * @test
	 */
	public function it_gets_string_id() {
		$subject = $this->create_subject();

		$this->assertEquals( $this->string_id, $subject->get_string_id() );
	}

	/**
	 * It gets package.
	 *
	 * @test
	 * @depends it_gets_package_id
	 */
	public function it_gets_package() {
		$subject = $this->create_subject();

		$expected_result     = new stdClass();
		$expected_result->id = 1;

		\WP_Mock::onFilter( 'wpml_st_get_string_package' )
		        ->with( false, $this->package_id )
		        ->reply( $expected_result );

		$this->assertEquals( $expected_result, $subject->get_package() );
	}

	/**
	 * It cannot get package when id is invalid.
	 *
	 * @test
	 * @depends it_gets_package_id
	 */
	public function it_cannnot_get_package_when_id_is_invalid() {
		$this->package_id = 'text';
		$subject          = $this->create_subject();

		$this->assertNull( $subject->get_package() );
	}

	/**
	 * It validates without checking of package.
	 *
	 * @test
	 * @dataProvider is_valid_data_provider
	 *
	 * @param mixed $package_id      Package id.
	 * @param mixed $string_id       String id.
	 * @param bool  $expected_result Expected result.
	 */
	public function it_validates_without_checking_of_package( $package_id, $string_id, $expected_result ) {
		$this->package_id = $package_id;
		$this->string_id  = $string_id;

		$this->string       = new stdClass();
		$this->string->id   = $this->string_id;
		$this->string->name = 'string_name';

		$subject = $this->create_subject();

		$this->assertEquals( $expected_result, $subject->is_valid() );
	}

	/**
	 * Data provider for it_validates_without_checking_of_package().
	 *
	 * @return array
	 */
	public function is_valid_data_provider() {
		return array(
			'Validation successful'         => array( 10, 12, true ),
			'Invalid package id'            => array( 'text', 12, false ),
			'Invalid string id'             => array( 10, 'text', false ),
			'Invalid package and string id' => array( 'text', 'text', false ),
		);
	}

	/**
	 * It validates with chacking of package when package exists
	 *
	 * @test
	 */
	public function it_validates_with_checking_of_package_when_package_exists() {
		$subject = $this->create_subject();

		\WP_Mock::onFilter( 'wpml_st_get_string_package' )
		        ->with( false, $this->package_id )
		        ->reply( new stdClass() );

		$this->assertTrue( $subject->is_valid( true ) );
	}

	/**
	 * It validates with chacking of package when package does not exist
	 *
	 * @test
	 */
	public function it_validates_with_checking_of_package_when_package_does_not_exist() {
		$subject = $this->create_subject();

		\WP_Mock::onFilter( 'wpml_st_get_string_package' )
		        ->with( false, $this->package_id )
		        ->reply( null );

		$this->assertFalse( $subject->is_valid( true ) );
	}

	/**
	 * It gets field slug.
	 *
	 * @test
	 */
	public function it_gets_field_slug() {
		$slug    = WPML_TM_Page_Builders_Field_Wrapper::generate_field_slug( $this->package_id, $this->string->id );
		$subject = new WPML_TM_Page_Builders_Field_Wrapper( $slug );

		$this->assertEquals( $slug, $subject->get_field_slug() );
	}

	/**
	 * It gets string type
	 *
	 * @test
	 */
	public function it_gets_string_type() {
		$subject = $this->create_subject();

		$package_strings = array(
			array(
				'id'   => 9,
				'name' => 'name9',
				'type' => 'AREA',
			),
			array(
				'id'   => 12,
				'name' => 'name12',
				'type' => 'VISUAL',
			),
			array(
				'id'   => 15,
				'name' => 'name15',
				'type' => 'TEXT',
			),
		);

		$package = $this->getMockBuilder( 'WPML_Package' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'get_package_strings' ) )
		                ->getMock();

		$package->method( 'get_package_strings' )->willReturn( $package_strings );

		\WP_Mock::onFilter( 'wpml_st_get_string_package' )
		        ->with( false, $this->package_id )
		        ->reply( $package );

		$this->assertEquals( 'VISUAL', $subject->get_string_type() );
	}

	/**
	 * It cannot get string type when slug is invalid.
	 *
	 * @test
	 */
	public function it_cannot_get_string_type_when_slug_is_invalid() {
		$this->package_id = 'text';
		$subject          = $this->create_subject();

		$this->assertFalse( $subject->get_string_type() );
	}

	/**
	 * Get string title
	 *
	 * @test
	 */
	public function get_string_title() {

		\WP_Mock::onFilter( 'wpml_string_title_from_id' )
		        ->with( false, $this->string_id )
		        ->reply( $this->string_title );

		$subject = $this->create_subject();
		$this->assertEquals( $this->string_title, $subject->get_string_title() );
	}

	/**
	 * Get wrap tag
	 *
	 * @test
	 * @dataProvider get_wrap_data_provider
	 * @group        wpmltm-3081
	 *
	 * @param stdClass $string String.
	 * @param string   $expected Expected string.
	 */
	public function get_wrap_tag( $string, $expected ) {
		$wrap = WPML_TM_Page_Builders_Field_Wrapper::get_wrap_tag( $string );
		$this->assertEquals( $expected, $wrap );
	}

	/**
	 * Data provider for get_wrap_tag().
	 *
	 * @return array
	 */
	public function get_wrap_data_provider() {
		return array(
			'Empty string'       => array( $this->get_string( 10, '', '' ), '' ),
			'Normal string'      => array( $this->get_string( 10, 'title-heading-f327e9c', '' ), '' ),
			'Text editor string' => array( $this->get_string( 10, 'editor-text-editor-c12e1e6', '' ), '' ),
			'Heading'            => array( $this->get_string( 10, 'title-heading-8e0908e', '' ), '' ),
			'Heading h2'         => array( $this->get_string( 10, 'title-heading-8e0908e', 'h2' ), 'h2' ),
		);
	}

	/**
	 * Create subject.
	 *
	 * @return WPML_TM_Page_Builders_Field_Wrapper
	 */
	private function create_subject() {
		$slug = WPML_TM_Page_Builders_Field_Wrapper::generate_field_slug( $this->package_id, $this->string->id );

		return new WPML_TM_Page_Builders_Field_Wrapper( $slug );
	}

	/**
	 * Get string.
	 *
	 * @param string $id String id.
	 * @param string $name String name.
	 * @param string $wrap_tag String wrap tag.
	 *
	 * @return stdClass
	 */
	private function get_string( $id, $name = 'string_name', $wrap_tag = '' ) {
		$string           = new stdClass();
		$string->id       = $id;
		$string->name     = $name;
		$string->wrap_tag = $wrap_tag;

		return $string;
	}
}
