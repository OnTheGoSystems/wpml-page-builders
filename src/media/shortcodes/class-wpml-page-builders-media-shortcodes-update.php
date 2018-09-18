<?php

class WPML_Page_Builders_Media_Shortcodes_Update implements IWPML_PB_Media_Update {

	/** @var WPML_Translation_Element_Factory $element_factory */
	private $element_factory;

	/** @var WPML_Page_Builders_Media_Shortcodes $media_shortcodes*/
	private $media_shortcodes;

	public function __construct(
		WPML_Translation_Element_Factory $element_factory,
		WPML_Page_Builders_Media_Shortcodes $media_shortcodes
	) {
		$this->element_factory  = $element_factory;
		$this->media_shortcodes = $media_shortcodes;
	}

	/**
	 * @param WP_Post $post
	 */
	public function translate( $post ) {
		if ( $this->has_no_shortcode( $post->post_content ) ) {
			return;
		}

		$element = $this->element_factory->create_post( $post->ID );

		if ( ! $element->get_source_language_code() ) {
			return;
		}

		$post->post_content = $this->media_shortcodes->set_target_lang( $element->get_language_code() )
		                                             ->set_source_lang( $element->get_source_language_code() )
		                                             ->translate( $post->post_content );

		wp_update_post( $post );
	}

	private function has_no_shortcode( $content ) {
		return strpos( $content, '[' ) === false;
	}
}
