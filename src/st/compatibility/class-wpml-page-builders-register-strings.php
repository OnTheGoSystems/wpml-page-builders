<?php

/**
 * Class WPML_Page_Builders_Register_Strings
 */
abstract class WPML_Page_Builders_Register_Strings extends WPML_Page_Builders_Register_Strings_Base
	implements IWPML_Page_Builders_Register_Strings {

	/**
	 * @var IWPML_Page_Builders_Translatable_Nodes
	 */
	private $translatable_nodes;

	/**
	 * @var IWPML_Page_Builders_Data_Settings
	 */
	protected $data_settings;

	/** @var int $string_location */
	private $string_location;

	public function __construct(
		IWPML_Page_Builders_Translatable_Nodes $translatable_nodes,
		IWPML_Page_Builders_Data_Settings $data_settings,
		WPML_PB_String_Registration $string_registration,
		WPML_PB_Reuse_Translations_By_Strategy $reuse_translations = null
	) {
		parent::__construct(
			[ $this, 'is_handling_post' ],
			[ $this, 'register_strings_from_custom_field' ],
			$this->string_registration = $string_registration,
			$this->reuse_translations  = $reuse_translations
		);

		$this->data_settings      = $data_settings;
		$this->translatable_nodes = $translatable_nodes;
	}

	/**
	 * @param int $post_id
	 *
	 * @return bool
	 */
	protected function is_handling_post( $post_id ) {
		return $this->data_settings->is_handling_post( $post_id );
	}

	/**
	 * @param int   $post_id
	 * @param array $package
	 */
	protected function register_strings_from_custom_field( $post_id, $package ) {
		$data = get_post_meta( $post_id, $this->data_settings->get_meta_field(), false );

		if ( $data ) {
			$converted = $this->data_settings->convert_data_to_array( $data );
			if ( is_array( $converted ) ) {
				$this->register_strings_for_modules(
					$converted,
					$package
				);
			}
		}
	}

	/**
	 * @param string $node_id
	 * @param mixed $element
	 * @param array $package
	 */
	protected function register_strings_for_node( $node_id, $element, array $package ) {
		$strings = $this->translatable_nodes->get( $node_id, $element );
		foreach ( $strings as $string ) {
			$this->string_registration->register_string(
				$package['post_id'],
				$string->get_value(),
				$string->get_editor_type(),
				$string->get_title(),
				$string->get_name(),
				$this->string_location,
				$string->get_wrap_tag()
			);

			$this->string_location++;
		}
	}

	/**
	 * @param array $data_array
	 * @param array $package
	 */
	abstract protected function register_strings_for_modules( array $data_array, array $package );
}
