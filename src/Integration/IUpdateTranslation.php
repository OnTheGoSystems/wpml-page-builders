<?php

namespace WPML\PB\Integration;

interface IUpdateTranslation {

	/**
	 * @param int $translated_post_id
	 * @param $original_post
	 * @param $string_translations
	 * @param string $lang
	 */
	public function update( $translated_post_id, $original_post, $string_translations, $lang );
}
