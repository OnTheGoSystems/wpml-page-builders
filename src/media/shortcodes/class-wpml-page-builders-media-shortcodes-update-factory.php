<?php

class WPML_Page_Builders_Media_Shortcodes_Update_Factory implements IWPML_PB_Media_Update_Factory {

	public function create() {
		global $sitepress;

		$element_factory  = new WPML_Translation_Element_Factory( $sitepress );
		$media_shortcodes = $this->get_media_shortcodes( $element_factory, $sitepress );

		return new WPML_Page_Builders_Media_Shortcodes_Update( $element_factory, $media_shortcodes );
	}

	/**
	 * @param $element_factory
	 * @param $sitepress
	 *
	 * @return WPML_Page_Builders_Media_Shortcodes
	 */
	private function get_media_shortcodes( $element_factory, $sitepress ) {
		$media_translate = new WPML_Page_Builders_Media_Translate(
			$element_factory,
			new WPML_Media_Image_Translate( $sitepress, new WPML_Media_Attachment_By_URL_Factory() )
		);

		return new WPML_Page_Builders_Media_Shortcodes( $media_translate, $this->get_shortcodes_config() );
	}

	/** @return array */
	private function get_shortcodes_config() {
		/**
		 * @todo: Move configuration to wpml-config.xml
		 */
		if ( defined( 'ET_BUILDER_THEME' ) ) {
			return array(
				WPML_Page_Builders_Media_Shortcodes::ALL_TAGS => array(
					'background_image' => WPML_Page_Builders_Media_Shortcodes::TYPE_URL,
				),
				'et_pb_video_slider_item|et_pb_video'         => array(
					'image_src' => WPML_Page_Builders_Media_Shortcodes::TYPE_URL,
				),
				'et_pb_gallery'                               => array(
					'gallery_ids' => WPML_Page_Builders_Media_Shortcodes::TYPE_IDS,
				),
				'et_pb_image'                                 => array(
					'src' => WPML_Page_Builders_Media_Shortcodes::TYPE_URL,
				),
				'et_pb_slide'                                 => array(
					'image' => WPML_Page_Builders_Media_Shortcodes::TYPE_URL,
				),
				'et_pb_team_member'                           => array(
					'image_url' => WPML_Page_Builders_Media_Shortcodes::TYPE_URL,
				),
				'et_pb_testimonial'                           => array(
					'portrait_url' => WPML_Page_Builders_Media_Shortcodes::TYPE_URL,
				),
			);
		}

		return array();
	}
}
