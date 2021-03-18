<?php

class WPML_Page_Builders_Register_Strings_Base implements IWPML_Page_Builders_Register_Strings {

	/** @var callable $isHandlingPost :: int -> bool */
	protected $isHandlingPost;

	/** @var callable $registerStrings :: ( int, array, WPML_PB_String_Registration ) -> void */
	protected $registerStrings;

	/** @var WPML_PB_String_Registration $string_registration */
	protected $string_registration;

	/** @var WPML_PB_Reuse_Translations_By_Strategy|null $reuse_translations */
	protected $reuse_translations;

	public function __construct(
		callable $isHandlingPost,
		callable $registerStrings,
		WPML_PB_String_Registration $string_registration,
		WPML_PB_Reuse_Translations_By_Strategy $reuse_translations = null
	) {
		$this->isHandlingPost      = $isHandlingPost;
		$this->registerStrings     = $registerStrings;
		$this->string_registration = $string_registration;
		$this->reuse_translations  = $reuse_translations;
	}

	/**
	 * @param WP_Post $post
	 * @param array $package
	 */
	public function register_strings( WP_Post $post, array $package ) {

		do_action( 'wpml_start_string_package_registration', $package );

		if ( call_user_func( $this->isHandlingPost, $post->ID ) ) {

			if ( $this->reuse_translations ) {
				$existing_strings = $this->reuse_translations->get_strings( $post->ID );
				$this->reuse_translations->set_original_strings( $existing_strings );
			}

			call_user_func( $this->registerStrings, $post->ID, $package, $this->string_registration );

			if ( $this->reuse_translations ) {
				$this->reuse_translations->find_and_reuse( $post->ID, $existing_strings );
			}
		}

		do_action( 'wpml_delete_unused_package_strings', $package );
	}
}
