<?php

/**
 * Class WPML_Page_Builders_Defined
 */
class WPML_Page_Builders_Defined {

	private $settings;

	public function __construct() {
		$this->init_settings();
	}

	public function has( $page_builder ) {
		if ( $this->settings[ $page_builder ]['constant'] ) {
			return defined( $this->settings[ $page_builder ]['constant'] );
		}

		if ( $this->settings[ $page_builder ]['function'] ) {
			return function_exists( $this->settings[ $page_builder ]['function'] );
		}

		return false;
	}

	/**
	 * @param array $components
	 *
	 * @return array
	 */
	public function add_components( $components ) {
		if ( isset( $components['page-builders'] ) ) {
			foreach ( $this->settings as $key => $data ) {
				$components['page-builders'][ $key ] = $data;
				$components['page-builders'][ $key ]['notices-display'] = array( 'wpml-translation-editor' );
			}
		}

		return $components;
	}

	public function init_settings() {
		$this->settings = array(
			'beaver-builder' => array(
				'name'     => 'Beaver Builder',
				'constant' => 'FL_BUILDER_VERSION',
				'factory'  => 'WPML_Beaver_Builder_Integration_Factory',
			),
			'elementor' => array(
				'name'     => 'Elementor',
				'constant' => 'ELEMENTOR_VERSION',
				'factory'  => 'WPML_Elementor_Integration_Factory',
			),
			'gutenberg' => array(
				'name'     => 'Gutenberg',
				'constant' => 'GUTENBERG_VERSION',
				'factory'  => 'WPML_Gutenberg_Integration_Factory',
			),
			'cornerstone' => array(
				'name'     => 'Cornerstone',
				'function' => 'cornerstone_plugin_init',
				'factory'  => 'WPML_Cornerstone_Integration_Factory',
			),
		);

		$this->adjust_settings();
	}

	private function adjust_settings() {
		$defaults = array(
			'constant' => null,
			'function' => null,
		);

		foreach ( $this->settings as &$setting ) {
			$setting = array_merge( $defaults, $setting );
		}

	}

	/**
	 * @return array
	 */
	public function get_settings() {
		return $this->settings;
	}

}
