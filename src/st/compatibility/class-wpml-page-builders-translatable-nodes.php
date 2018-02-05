<?php

abstract class WPML_Page_Builders_Translatable_Nodes implements IWPML_Page_Builders_Translatable_Nodes {

	/**
	 * @var array
	 */
	protected $nodes_to_translate;

	/**
	 * @param string|int $node_id
	 * @param array $settings
	 *
	 * @return WPML_PB_String[]
	 */
	public function get( $node_id, $settings ) {

		if ( ! $this->nodes_to_translate ) {
			$this->initialize_nodes_to_translate();
		}

		$strings = array();

		foreach ( $this->nodes_to_translate as $node_type => $node_data ) {
			if ( $this->conditions_ok( $node_data, $settings ) ) {
				foreach ( $node_data['fields'] as $field ) {
					$field_value = $this->get_field_value( $settings, $field['field'] );
					if ( $field_value && trim( $field_value ) ) {

						$string = new WPML_PB_String(
							$field_value,
							$this->get_string_name( $node_id, $field, $settings ),
							$field['type'],
							$field['editor_type']
						);

						$strings[] = $string;
					}
				}
				if ( isset( $node_data['integration-class'] ) ) {
					try {
						$node    = new $node_data['integration-class']();
						$strings = $node->get( $node_id, $settings, $strings );
					} catch ( Exception $e ) {
					}
				}
			}
		}

		return $strings;
	}

	/**
	 * @param string $node_id
	 * @param array $settings
	 * @param WPML_PB_String $string
	 *
	 * @return array
	 */
	public function update( $node_id, $settings, WPML_PB_String $string ) {

		if ( ! $this->nodes_to_translate ) {
			$this->initialize_nodes_to_translate();
		}

		foreach ( $this->nodes_to_translate as $node_type => $node_data ) {
			if ( $this->conditions_ok( $node_data, $settings ) ) {
				foreach ( $node_data['fields'] as $field ) {
					if ( $this->get_string_name( $node_id, $field, $settings ) === $string->get_name() ) {
						$settings = $this->update_field_value( $settings, $field['field'], $string );
					}
				}
				if ( isset( $node_data['integration-class'] ) ) {
					try {
						$node = new $node_data['integration-class']();
						$node->update( $node_id, $settings, $string );
					} catch ( Exception $e ) {

					}
				}
			}
		}

		return $settings;
	}

	/**
	 * @param string $node_id
	 * @param array $field
	 * @param array $settings
	 *
	 * @return string
	 */
	public function get_string_name( $node_id, $field, $settings ) {
		return $field['field'] . '-' . $this->get_type( $settings ) . '-' . $node_id;
	}

	/**
	 * @param array $node_data
	 * @param array $settings
	 *
	 * @return bool
	 */
	private function conditions_ok( $node_data, $settings ) {
		$conditions_meet = true;
		foreach ( $node_data['conditions'] as $field_key => $node_field_value ) {
			$field_value = $this->get_field_value( $settings, $field_key );
			if ( ! $field_value || $field_value !== $node_field_value ) {
				$conditions_meet = false;
				break;
			}
		}

		return $conditions_meet;
	}

	abstract public function initialize_nodes_to_translate();

	/**
	 * @param obj|array $settings
	 * @param string $field_key
	 *
	 * @return obj|array
	 */
	abstract public function get_field_value( $settings, $field_key );

	/**
	 * @param obj|array $settings
	 *
	 * @return string
	 */
	abstract public function get_type( $settings );

	abstract public function update_field_value( $settings, $field_key, $string );
}