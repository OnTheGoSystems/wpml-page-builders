<?php

abstract class WPML_Page_Builders_Update_Media implements IWPML_PB_Media_Update {

	/** @var WPML_Page_Builders_Update $pb_update */
	private $pb_update;

	/** @var WPML_Translation_Element_Factory $element_factory */
	private $element_factory;

	public function __construct(
		WPML_Page_Builders_Update $pb_update,
		WPML_Translation_Element_Factory $element_factory
	) {
		$this->pb_update       = $pb_update;
		$this->element_factory = $element_factory;
	}

	/**
	 * @param WP_Post $post
	 */
	public function translate( $post ) {
		$element        = $this->element_factory->create_post( $post->ID );
		$source_element = $element->get_source_element();


		if ( ! $source_element ) {
			return;
		}

		$original_post_id = $source_element->get_id();
		$converted_data   = $this->pb_update->get_converted_data( $original_post_id );
		$converted_data   = $this->translate_media_in_modules( $converted_data );
		$this->pb_update->save( $post->ID, $original_post_id, $converted_data );
	}

	/**
	 * @param array $converted_data
	 *
	 * @return mixed
	 */
	abstract protected function translate_media_in_modules( array $converted_data );
}
