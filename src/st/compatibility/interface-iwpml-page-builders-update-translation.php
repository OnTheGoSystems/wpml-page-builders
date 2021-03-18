<?php

interface IWPML_Page_Builders_Update_Translation extends IWPML_Page_Builders_Update {

	/**
	 * @param int $translated_post_id
	 * @param $original_post
	 * @param $string_translations
	 * @param string $lang
	 */
	public function update( $translated_post_id, $original_post, $string_translations, $lang );
}
