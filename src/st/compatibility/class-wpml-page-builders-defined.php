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
		$result = false;

		if ( isset( $this->settings[ $page_builder ]['constant'] ) ) {
			$result = defined( $this->settings[ $page_builder ]['constant'] );
		} elseif ( isset( $this->settings[ $page_builder ]['function'] ) ) {
			$result = function_exists( $this->settings[ $page_builder ]['function'] );
		}

		return $result;
	}

	/**
	 * @param array $components
	 *
	 * @return array
	 */
	public function add_components( $components ) {
		if ( isset( $components['page-builders'] ) ) {
			foreach (
				array(
					'beaver-builder' => 'Beaver Builder',
					'elementor'      => 'Elementor',
					'gutenberg'      => 'Gutenberg',
					'cornerstone'    => 'Cornerstone',
				) as $key => $name
			) {
				$components['page-builders'][ $key ] = array(
					'name'            => $name,
					'notices-display' => array(
						'wpml-translation-editor',
					),
				);

				if ( isset( $this->settings[ $key ]['constant'] ) ) {
					$components['page-builders'][ $key ]['constant'] = $this->settings[ $key ]['constant'];
				} elseif ( isset( $this->settings[ $key ]['function'] ) ) {
					$components['page-builders'][ $key ]['function'] = $this->settings[ $key ]['function'];
				}
			}
		}

		return $components;
	}

	public function init_settings() {
		$this->settings = array(
			'beaver-builder' => array(
				'constant' => 'FL_BUILDER_VERSION',
				'factory' => 'WPML_Beaver_Builder_Integration_Factory',
			),
			'elementor' => array(
				'constant' => 'ELEMENTOR_VERSION',
				'factory' => 'WPML_Elementor_Integration_Factory',
			),
			'gutenberg' => array(
				'constant' => 'GUTENBERG_VERSION',
				'factory' => 'WPML_Gutenberg_Integration_Factory',
			),
			'cornerstone' => array(
				'function' => 'cornerstone_plugin_init',
				'factory' => 'WPML_Cornerstone_Integration_Factory',
			),
		);
	}

	/**
	 * @return array
	 */
	public function get_settings() {
		return $this->settings;
	}

}