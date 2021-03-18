<?php

class WPML_Page_Builders_Integration_Base {

	const STRINGS_TRANSLATED_PRIORITY = 10;

	/** @var WPML_Page_Builders_Register_Strings */
	private $register_strings;

	/** @var WPML_Page_Builders_Update_Translation */
	private $update_translation;

	/** @var callable $getName :: void -> string */
	private $getName;

	public function __construct(
		WPML_Page_Builders_Register_Strings $register_strings,
		WPML_Page_Builders_Update_Translation $update_translation,
		callable $getName
	) {
		$this->register_strings   = $register_strings;
		$this->update_translation = $update_translation;
		$this->getName            = $getName;
	}

	public function add_hooks() {
		add_filter( 'wpml_page_builder_support_required', [ $this, 'support_required' ] );
		add_action( 'wpml_page_builder_register_strings', [ $this, 'register_pb_strings' ], 10, 2 );
		add_action( 'wpml_page_builder_string_translated', [ $this, 'update_translated_post' ], self::STRINGS_TRANSLATED_PRIORITY, 5 );
		add_filter( 'wpml_get_translatable_types', [ $this, 'remove_shortcode_strings_type_filter' ], 12, 1 );
	}

	private function getName() {
		return call_user_func( $this->getName );
	}

	/**
	 * @param array $page_builder_plugins
	 *
	 * @return array
	 */
	public function support_required( array $page_builder_plugins ) {
		$page_builder_plugins[] = $this->getName();

		return $page_builder_plugins;
	}

	/**
	 * @param WP_Post $post
	 * @param array   $package_key
	 */
	public function register_pb_strings( $post, $package_key ) {
		if ( $this->getName() === $package_key['kind'] ) {
			$this->register_strings->register_strings( $post, $package_key );
		}
	}

	/**
	 * @param string  $kind
	 * @param int     $translated_post_id
	 * @param WP_Post $original_post
	 * @param array   $string_translations
	 * @param string  $lang
	 */
	public function update_translated_post( $kind, $translated_post_id, WP_Post $original_post, $string_translations, $lang ) {
		if ( $this->getName() === $kind ) {
			$this->update_translation->update( $translated_post_id, $original_post, $string_translations, $lang );
		}
	}

	/**
	 * @param array $types
	 *
	 * @return array
	 */
	public function remove_shortcode_strings_type_filter( $types ) {
		unset( $types[ sanitize_title_with_dashes( $this->getName() ) ] );

		return $types;
	}

}

