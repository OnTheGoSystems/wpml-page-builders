<?php

/**
 * @group media
 */
class Test_WPML_Page_Builders_Media_Usage extends \OTGS\PHPUnit\Tools\TestCase {

	/**
	 * @test
	 * @group wpmlcore-5834
	 */
	public function it_should_update() {
		$post_id  = mt_rand( 1, 100 );
		$media_id = mt_rand( 101, 200 );

		$media_translate = $this->getMockBuilder( 'WPML_Page_Builders_Media_Translate' )
			->setMethods( array( 'get_translated_ids', 'reset_translated_ids' ) )
			->disableOriginalConstructor()->getMock();
		$media_translate->method( 'get_translated_ids' )->willReturn( array( $media_id ) );
		$media_translate->expects( $this->once() )->method( 'reset_translated_ids' );

		$media_usage = $this->getMockBuilder( 'WPML_Media_Usage_Factory' )
			->setMethods( array( 'add_post' ) )->disableOriginalConstructor()->getMock();
		$media_usage->expects( $this->once() )->method( 'add_post' )->with( $post_id );

		$media_usage_factory = $this->getMockBuilder( 'WPML_Media_Usage_Factory' )
			->setMethods( array( 'create' ) )->disableOriginalConstructor()->getMock();
		$media_usage_factory->method( 'create' )->with( $media_id )->willReturn( $media_usage );

		$subject = new WPML_Page_Builders_Media_Usage( $media_translate, $media_usage_factory );

		$subject->update( $post_id );
	}
}
