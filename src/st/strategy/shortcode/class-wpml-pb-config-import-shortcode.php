<?php

class WPML_PB_Config_Import_Shortcode {

	const PB_SHORTCODE_SETTING       = 'pb_shortcode';
	const PB_MEDIA_SHORTCODE_SETTING = 'wpml_pb_media_shortcode';

	/** @var  WPML_ST_Settings $st_settings */
	private $st_settings;

	public function __construct( WPML_ST_Settings $st_settings ) {
		$this->st_settings = $st_settings;
	}

	public function add_hooks() {
		add_filter( 'wpml_config_array', array( $this, 'wpml_config_filter' ) );
	}

	public function wpml_config_filter( $config_data ) {
		$this->update_shortcodes_config( $config_data );
		$this->update_media_shortcodes_config( $config_data );

		return $config_data;
	}

	/** @param array $config_data */
	private function update_shortcodes_config( $config_data ) {
		$old_shortcode_data = $this->get_settings();

		$shortcode_data = array();
		if ( isset ( $config_data['wpml-config']['shortcodes']['shortcode'] ) ) {
			foreach ( $config_data['wpml-config']['shortcodes']['shortcode'] as $data ) {

				if ( isset( $data['tag']['attr']['media-only'] ) && $data['tag']['attr']['media-only'] ) {
					continue;
				}

				$attributes = array();
				if ( isset( $data['attributes']['attribute'] ) ) {
					$single_attribute = false;
					foreach ( $data['attributes']['attribute'] as $attribute ) {
						if ( is_string( $attribute ) ) {
							$single_attribute   = true;
							$attribute_value    = $attribute;
							$attribute_encoding = '';
							$attribute_type     = '';
						} else if ( isset( $attribute['value'] ) ) {
							$attribute_value = $attribute['value'];
						}
						if ( $attribute_value ) {
							if ( $single_attribute ) {
								if ( isset( $attribute['encoding'] ) ) {
									$attribute_encoding = $attribute['encoding'];
								}
								if ( isset( $attribute['type'] ) ) {
									$attribute_type = $attribute['type'];
								}
							} else {
								$attribute_encoding = isset( $attribute['attr']['encoding'] ) ? $attribute['attr']['encoding'] : '';
								$attribute_type     = isset( $attribute['attr']['type'] ) ? $attribute['attr']['type'] : '';
								$attributes[]       = array(
									'value'    => $attribute_value,
									'encoding' => $attribute_encoding,
									'type'     => $attribute_type,
								);
							}
						}
					}
					if ( $single_attribute ) {
						$attributes[] = array(
							'value'    => $attribute_value,
							'encoding' => $attribute_encoding,
							'type'     => $attribute_type,
						);
					}
				}
				$shortcode_data[] = array(
					'tag'        => array(
						'value'              => $data['tag']['value'],
						'encoding'           => isset( $data['tag']['attr']['encoding'] ) ? $data['tag']['attr']['encoding'] : '',
						'encoding-condition' => isset( $data['tag']['attr']['encoding-condition'] ) ? $data['tag']['attr']['encoding-condition'] : '',
						'type'               => isset( $data['tag']['attr']['type'] ) ? $data['tag']['attr']['type'] : '',
						'raw-html'           => isset( $data['tag']['attr']['raw-html'] ) ? $data['tag']['attr']['raw-html'] : '',
					),
					'attributes' => $attributes,
				);
			}
		}

		if ( $shortcode_data != $old_shortcode_data ) {
			$this->st_settings->update_setting( self::PB_SHORTCODE_SETTING, $shortcode_data, true );
		}
	}

	/** @param array $config_data */
	private function update_media_shortcodes_config( $config_data ) {
		$old_shortcodes_data = $this->get_media_settings();
		$shortcodes_data     = array();

		if ( isset ( $config_data['wpml-config']['shortcodes']['shortcode'] ) ) {

			foreach ( $config_data['wpml-config']['shortcodes']['shortcode'] as $data ) {
				$shortcode_data = array();

				if ( isset( $data['media-attributes']['media-attribute'] ) ) {
					$attributes       = array();
					$single_attribute = false;

					foreach ( $data['media-attributes']['media-attribute'] as $attribute ) {

						if ( is_string( $attribute ) ) {
							$single_attribute = true;
							$attribute_value  = $attribute;
							$attribute_type   = '';
						} elseif ( isset( $attribute['value'] ) ) {
							$attribute_value = $attribute['value'];
						}

						if ( ! empty( $attribute_value ) ) {

							if ( $single_attribute ) {

								if ( isset( $attribute['type'] ) ) {
									$attribute_type = $attribute['type'];
								}
							} else {
								$attribute_type = isset( $attribute['attr']['type'] ) ? $attribute['attr']['type'] : '';
								$attributes[ $attribute_value ] = array( 'type' => $attribute_type );
							}
						}
					}

					if ( $single_attribute ) {
						$attributes[ $attribute_value ] = array( 'type' => $attribute_type );
					}

					$shortcode_data['attributes'] = $attributes;
				}

				if ( isset( $data['tag']['attr']['content-type'] )
				     && 'media-url' === $data['tag']['attr']['content-type']
				) {
					$shortcode_data['content'] = array( 'type' => 'url' );
				}

				if ( $shortcode_data ) {
					$shortcode_data['tag'] = array( 'name' => $data['tag']['value'] );
					$shortcodes_data[]     = $shortcode_data;
				}
			}
		}

		if ( $shortcodes_data != $old_shortcodes_data ) {
			update_option( self::PB_MEDIA_SHORTCODE_SETTING, $shortcodes_data, true );
		}
	}

	public function get_settings() {
		return $this->st_settings->get_setting( self::PB_SHORTCODE_SETTING );
	}

	public function get_media_settings() {
		return get_option( self::PB_MEDIA_SHORTCODE_SETTING, array() );
	}

	public function has_settings() {
		$settings = $this->get_settings();

		return ! empty( $settings );
	}
}
