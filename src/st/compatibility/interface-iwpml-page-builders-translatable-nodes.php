<?php

interface IWPML_Page_Builders_Translatable_Nodes {

	public function initialize_nodes_to_translate();

	/**
	 * @param obj|array $settings
	 * @param string $field_key
	 *
	 * @return obj|array
	 */
	public function get_field_value( $settings, $field_key );

	/**
	 * @param obj|array $settings
	 *
	 * @return string
	 */
	public function get_type( $settings );

	/**
	 * @param obj|array $settings
	 * @param string $field_key
	 * @param WPML_PB_String $string
	 *
	 * @return obj|array
	 */
	public function update_field_value( $settings, $field_key, $string );
}