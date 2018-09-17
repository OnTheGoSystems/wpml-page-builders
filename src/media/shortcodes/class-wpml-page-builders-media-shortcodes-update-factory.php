<?php

class WPML_Page_Builders_Media_Shortcodes_Update_Factory implements IWPML_PB_Media_Update_Factory {

	/** @var WPML_PB_Config_Import_Shortcode WPML_PB_Config_Import_Shortcode */
	private $page_builder_config_import;

	public function __construct( WPML_PB_Config_Import_Shortcode $page_builder_config_import ) {
		$this->page_builder_config_import = $page_builder_config_import;
	}

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

		return new WPML_Page_Builders_Media_Shortcodes(
			$media_translate,
			$this->page_builder_config_import->get_media_settings()
		);
	}
}
