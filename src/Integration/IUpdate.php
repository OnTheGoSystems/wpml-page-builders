<?php

namespace WPML\PB\Integration;

interface IUpdate {

	/**
	 * @param int   $post_id
	 * @param int   $original_post_id
	 * @param array $converted_data
	 */
	public function save( $post_id, $original_post_id, $converted_data );
}
