<?php

/**
 * Class WPML_Page_Builders_Defined
 */
class WPML_Page_Builders_Defined {

	private $settings;

	public function __construct() {
		$this->settings = array(
			'beaver-builder' => array(
				'name'            => 'Beaver Builder',
				'factory'         => 'WPML_Beaver_Builder_Integration_Factory',
				'notices-display' => array( 'wpml-translation-editor' ),
				'constant'        => 'FL_BUILDER_VERSION',
				'function'        => null,
			),
			'elementor'      => array(
				'name'            => 'Elementor',
				'factory'         => 'WPML_Elementor_Integration_Factory',
				'notices-display' => array( 'wpml-translation-editor' ),
				'constant'        => 'ELEMENTOR_VERSION',
				'function'        => null,
			),
			'gutenberg'      => array(
				'name'            => 'Gutenberg',
				'factory'         => 'WPML_Gutenberg_Integration_Factory',
				'notices-display' => array( 'wpml-translation-editor' ),
				'constant'        => 'GUTENBERG_VERSION',
				'function'        => null,
			),
			'cornerstone'    => array(
				'name'            => 'Cornerstone',
				'factory'         => 'WPML_Cornerstone_Integration_Factory',
				'notices-display' => array( 'wpml-translation-editor' ),
				'constant'        => null,
				'function'        => 'cornerstone_plugin_init',
			),
		);
	}

	/**
	 * @param string $page_builder
	 *
	 * @return bool
	 */
	public function has( $page_builder ) {
		global $wp_version;
		if ( 'gutenberg' === $page_builder ) {
			if ( version_compare( $wp_version, '5.0-beta1', '>=' ) ) {
				return true;
			}
		}

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
			$components['page-builders'] = array_merge( $components['page-builders'], $this->settings );
		}

		return $components;
	}

	/**
	 * @return array<string,array<string,string|string[]|null>>
	 */
	public function get_settings() {
		return $this->settings;
	}

}
