<?php

/**
 * @group media
 */
class Test_WPML_Page_Builders_Media_Shortcodes_Update_Factory extends \OTGS\PHPUnit\Tools\TestCase {

	public function setUp() {
		parent::setUp();
		$this->getMockBuilder( 'WPML_Translation_Element_Factory' )->disableOriginalConstructor()->getMock();
		$this->getMockBuilder( 'WPML_Media_Attachment_By_URL_Factory' )->disableOriginalConstructor()->getMock();
		$this->getMockBuilder( 'WPML_Media_Image_Translate' )->disableOriginalConstructor()->getMock();
	}

	/**
	 * @test
	 */
	public function it_should_implement_media_update_factory() {
		$subject = new WPML_Page_Builders_Media_Shortcodes_Update_Factory();
		$this->assertInstanceOf( 'IWPML_PB_Media_Update_Factory', $subject );
		$this->assertInstanceOf( 'IWPML_PB_Media_Update', $subject->create() );
	}

	/**
	 * @test
	 */
	public function it_should_create_and_return_an_instance() {
		$subject = new WPML_Page_Builders_Media_Shortcodes_Update_Factory();
		$this->assertInstanceOf( 'WPML_Page_Builders_Media_Shortcodes_Update', $subject->create() );
	}
}
