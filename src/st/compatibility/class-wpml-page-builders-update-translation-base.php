<?php

class WPML_Page_Builders_Update_Translation_Base extends WPML_Page_Builders_Update_Base
	implements IWPML_Page_Builders_Update_Translation {

	/** @var callable $applyStringTranslations :: ( array, array, string ) -> array */
	private $applyStringTranslations;

	public function __construct(
		callable $getConvertedData,
		callable $savePost,
		callable $applyStringTranslations
	) {
		parent::__construct( $getConvertedData, $savePost );
		$this->applyStringTranslations = $applyStringTranslations;
	}

	/**
	 * @param int $translated_post_id
	 * @param $original_post
	 * @param $string_translations
	 * @param string $lang
	 */
	public function update( $translated_post_id, $original_post, $string_translations, $lang ) {
		$converted_data = $this->get_converted_data( $original_post->ID );
		$converted_data = call_user_func( $this->applyStringTranslations, $converted_data, $string_translations, $lang );
		$this->save( $translated_post_id, $original_post->ID, $converted_data );
	}
}
