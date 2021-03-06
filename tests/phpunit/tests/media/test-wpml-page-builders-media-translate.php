<?php

/**
 * @group media
 */
class Test_WPML_Page_Builders_Media_Translate extends \OTGS\PHPUnit\Tools\TestCase {

	/**
	 * @test
	 */
	public function it_should_translate_image_url_and_cache_it() {
		$url            = 'http://example/dog.jpg';
		$image_id       = mt_rand( 1, 100 );
		$translated_url = 'http://exemple/chien.jpg';
		$lang           = 'fr';
		$source_lang    = 'en';

		$image_translate = $this->get_image_translate();
		$image_translate->method( 'get_attachment_id_by_url' )->with( $url )->willReturn( $image_id );
		$image_translate->expects( $this->once() )
			->method( 'get_translated_image_by_url' )
			->with( $url, $source_lang, $lang )
			->willReturn( $translated_url );

		$subject = $this->get_subject( null, $image_translate );

		$this->assertSame( $translated_url, $subject->translate_image_url( $url, $lang, $source_lang ) );
		$this->assertSame( $translated_url, $subject->translate_image_url( $url, $lang, $source_lang ) );
	}

	/**
	 * @test
	 * @group wpmlcore-5834
	 */
	public function it_should_return_the_same_url_if_no_attachment_id_is_found() {
		$url         = 'http://example/dog.jpg';
		$image_id    = 0;
		$lang        = 'fr';
		$source_lang = 'en';

		$image_translate = $this->get_image_translate();
		$image_translate->expects( $this->once() )
		                ->method( 'get_attachment_id_by_url' )->with( $url )->willReturn( $image_id );
		$image_translate->expects( $this->never() )->method( 'get_translated_image_by_url' );

		$subject = $this->get_subject( null, $image_translate );

		$this->assertSame( $url, $subject->translate_image_url( $url, $lang, $source_lang ) );
		$this->assertSame( $url, $subject->translate_image_url( $url, $lang, $source_lang ) );
	}

	/**
	 * @test
	 */
	public function it_should_return_the_same_url_if_no_translation_is_found() {
		$url         = 'http://example/dog.jpg';
		$image_id    = mt_rand( 1, 100 );
		$lang        = 'fr';
		$source_lang = 'en';

		$image_translate = $this->get_image_translate();
		$image_translate->method( 'get_attachment_id_by_url' )->with( $url )->willReturn( $image_id );
		$image_translate->expects( $this->once() )
		                ->method( 'get_translated_image_by_url' )
		                ->with( $url, $source_lang, $lang )
		                ->willReturn( false );

		$subject = $this->get_subject( null, $image_translate );

		$this->assertSame( $url, $subject->translate_image_url( $url, $lang, $source_lang ) );
		$this->assertSame( $url, $subject->translate_image_url( $url, $lang, $source_lang ) );
	}

	/**
	 * @test
	 */
	public function it_should_translate_id_and_cache_it() {
		$id            = mt_rand( 1, 10 );
		$translated_id = mt_rand( 11, 20 );
		$lang          = 'fr';

		$translated_attachment = $this->get_wp_object( $translated_id );

		$translated_element = $this->get_element();
		$translated_element->method( 'get_wp_object' )->willReturn( $translated_attachment );

		$element = $this->get_element();
		$element->method( 'get_translation' )->with( $lang )->willReturn( $translated_element );

		$factory = $this->get_element_factory();
		$factory->expects( $this->once() )->method( 'create_post' )->with( $id )->willReturn( $element );

		$subject = $this->get_subject( $factory, null );

		$this->assertSame( $translated_id, $subject->translate_id( $id, $lang ) );
		$this->assertSame( $translated_id, $subject->translate_id( $id, $lang ) );
	}

	/**
	 * @test
	 */
	public function it_should_return_the_original_id_if_no_translation_was_found() {
		$id   = mt_rand( 1, 10 );
		$lang = 'fr';

		$element = $this->get_element();
		$element->method( 'get_translation' )->with( $lang )->willReturn( null );

		$factory = $this->get_element_factory();
		$factory->method( 'create_post' )->with( $id )->willReturn( $element );

		$subject = $this->get_subject( $factory, null );

		$this->assertSame( $id, $subject->translate_id( $id, $lang ) );
	}

	/**
	 * @test
	 */
	public function it_should_return_the_original_id_if_not_a_valid_one() {
		$id   = 'invalid ID';
		$lang = 'fr';

		$subject = $this->get_subject();

		$this->assertSame( $id, $subject->translate_id( $id, $lang ) );
	}

	/**
	 * @test
	 * @group wpmlcore-5834
	 */
	public function it_should_get_and_reset_translated_media_ids() {
		$url               = 'http://example/dog.jpg';
		$image_id          = mt_rand( 1, 100 );
		$translated_url    = 'http://exemple/chien.jpg';
		$image_id_2        = mt_rand( 101, 200 );
		$translated_id_2   = mt_rand( 1101, 1200 );
		$lang              = 'fr';
		$source_lang       = 'en';

		$image_translate = $this->get_image_translate();
		$image_translate->method( 'get_attachment_id_by_url' )->with( $url )->willReturn( $image_id );
		$image_translate->method( 'get_translated_image_by_url' )
		                ->with( $url, $source_lang, $lang )
		                ->willReturn( $translated_url );

		$translated_attachment = $this->get_wp_object( $translated_id_2 );

		$translated_element = $this->get_element();
		$translated_element->method( 'get_wp_object' )->willReturn( $translated_attachment );

		$element = $this->get_element();
		$element->method( 'get_translation' )->with( $lang )->willReturn( $translated_element );

		$element_factory = $this->get_element_factory();
		$element_factory->method( 'create_post' )->with( $image_id_2 )->willReturn( $element );

		$subject = $this->get_subject( $element_factory, $image_translate );

		$this->assertEquals( array(), $subject->get_translated_ids() );

		$subject->translate_image_url( $url, $lang, $source_lang );
		$subject->translate_id( $image_id_2, $lang );

		$this->assertEquals(
			array( $image_id, $image_id_2 ),
			$subject->get_translated_ids()
		);

		$subject->reset_translated_ids();

		$this->assertEquals( array(), $subject->get_translated_ids() );
	}

	private function get_subject( $factory = null, $image_translate = null ) {
		$factory         = $factory ? $factory : $this->get_element_factory();
		$image_translate = $image_translate ? $image_translate : $this->get_image_translate();
		return new WPML_Page_Builders_Media_Translate( $factory, $image_translate );
	}

	private function get_element_factory() {
		return $this->getMockBuilder( 'WPML_Translation_Element_Factory' )
			->setMethods( array( 'create_post' ) )
			->disableOriginalConstructor()->getMock();
	}

	private function get_element() {
		return $this->getMockBuilder( 'WPML_Post_Element' )
			->setMethods( array( 'get_translation', 'get_wp_object' ) )
			->disableOriginalConstructor()->getMock();
	}

	private function get_wp_object( $id ) {
		$wp_object = $this->getMockBuilder( 'WP_Post' )->getMock();
		$wp_object->ID = $id;
		return $wp_object;
	}

	private function get_image_translate() {
		return $this->getMockBuilder( 'WPML_Media_Image_Translate' )
			->setMethods( array( 'get_translated_image_by_url', 'get_attachment_id_by_url' ) )
			->disableOriginalConstructor()->getMock();
	}
}
