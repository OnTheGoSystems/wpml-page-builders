<?php

/**
 * @group media
 */
class Test_WPML_Page_Builders_Update_Media extends \OTGS\PHPUnit\Tools\TestCase {

	/**
	 * @test
	 */
	public function it_should_not_translate_if_post_is_source() {
		$post     = $this->getMockBuilder( 'WP_Post' )->getMock();
		$post->ID = mt_rand( 1, 10 );

		$updater = $this->get_updater();
		$updater->expects( $this->never() )->method( 'save' );

		$element = $this->get_element();
		$element->method( 'get_source_element' )->willReturn( null );

		$factory = $this->get_element_factory();
		$factory->method( 'create_post' )->with( $post->ID )->willReturn( $element );

		$iterator = $this->get_node_iterator();
		$iterator->expects( $this->never() )->method( 'translate' );

		$subject = $this->get_subject( $updater, $factory, $iterator );

		$subject->translate( $post );
	}

	/**
	 * @test
	 * @dataProvider dp_has_media_usage
	 * @group wpmlcore-5834
	 *
	 * @param bool $has_media_usage
	 */
	public function it_should_translate_if_post_is_a_translation( $has_media_usage ) {
		$lang            = 'fr';
		$source_lang     = 'en';
		$original_id     = mt_rand( 11, 20 );
		$original_data   = array( 'original data' );
		$translated_data = array( 'translated data' );

		$post     = $this->getMockBuilder( 'WP_Post' )->getMock();
		$post->ID = mt_rand( 1, 10 );

		$updater = $this->get_updater();
		$updater->method( 'get_converted_data' )->with( $post->ID )->willReturn( $original_data );
		$updater->expects( $this->once() )->method( 'save' )->with( $post->ID, $original_id, $translated_data );

		$source_element = $this->get_element();
		$source_element->method( 'get_language_code' )->willReturn( $source_lang );
		$source_element->method( 'get_id' )->willReturn( $original_id );

		$element = $this->get_element();
		$element->method( 'get_source_element' )->willReturn( $source_element );
		$element->method( 'get_language_code' )->willReturn( $lang );

		$factory = $this->get_element_factory();
		$factory->method( 'create_post' )->with( $post->ID )->willReturn( $element );

		$iterator = $this->get_node_iterator();
		$iterator->expects( $this->once() )->method( 'translate' )->with( $original_data )->willReturn( $translated_data );

		$media_usage = null;
		if ( $has_media_usage ) {
			$media_usage = $this->get_media_usage();
			$media_usage->expects( $this->once() )->method( 'update' )->with( $original_id );
		}

		$subject = $this->get_subject( $updater, $factory, $iterator, $media_usage );

		$subject->translate( $post );
	}

	public function dp_has_media_usage() {
		return array(
			'without media usage' => array( false ),
			'with media usage'    => array( true ),
		);
	}

	private function get_subject( $updater = null, $factory = null, $iterator = null, $media_usage = null ) {
		return new WPML_Page_Builders_Update_Media( $updater, $factory, $iterator, $media_usage );
	}

	private function get_updater() {
		return $this->getMockBuilder( 'WPML_Page_Builders_Update' )
		            ->setMethods( array( 'get_converted_data', 'save' ) )
		            ->disableOriginalConstructor()->getMock();
	}

	private function get_element_factory() {
		return $this->getMockBuilder( 'WPML_Translation_Element_Factory' )
		            ->setMethods( array( 'create_post' ) )
		            ->disableOriginalConstructor()->getMock();
	}

	private function get_element() {
		return $this->getMockBuilder( 'WPML_Post_Element' )
		            ->setMethods( array( 'get_language_code', 'get_source_element', 'get_id' ) )
		            ->disableOriginalConstructor()->getMock();
	}

	private function get_node_iterator() {
		return $this->getMockBuilder( 'IWPML_PB_Media_Nodes_Iterator' )
		            ->setMethods( array( 'translate' ) )
		            ->disableOriginalConstructor()->getMock();
	}

	private function get_media_usage() {
		return $this->getMockBuilder( 'WPML_Page_Builders_Media_Usage' )
		            ->setMethods( array( 'update' ) )
		            ->disableOriginalConstructor()->getMock();
	}
}
