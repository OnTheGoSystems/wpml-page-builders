<?php

class WPML_Page_Builders_Update_Media implements IWPML_PB_Media_Update {

	/** @var WPML_Page_Builders_Update $pb_update */
	private $pb_update;

	/** @var WPML_Translation_Element_Factory $element_factory */
	private $element_factory;

	/** @var IWPML_PB_Media_Nodes_Iterator $node_iterator */
	protected $node_iterator;

	public function __construct(
		WPML_Page_Builders_Update $pb_update,
		WPML_Translation_Element_Factory $element_factory,
		IWPML_PB_Media_Nodes_Iterator $node_iterator
	) {
		$this->pb_update       = $pb_update;
		$this->element_factory = $element_factory;
		$this->node_iterator   = $node_iterator;
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

		$lang             = $element->get_language_code();
		$source_lang      = $source_element->get_language_code();
		$original_post_id = $source_element->get_id();
		$converted_data   = $this->pb_update->get_converted_data( $original_post_id );
		$converted_data   = $this->node_iterator->translate( $converted_data, $lang, $source_lang );
		$this->pb_update->save( $post->ID, $original_post_id, $converted_data );
	}
}
