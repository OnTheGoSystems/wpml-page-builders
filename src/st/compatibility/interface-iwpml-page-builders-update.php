<?php

interface IWPML_Page_Builders_Update {

	/**
	 * @param int $post_id
	 *
	 * @return array
	 */
	public function get_converted_data( $post_id );

	/**
	 * @param int   $post_id
	 * @param int   $original_post_id
	 * @param array $converted_data
	 */
	public function save( $post_id, $original_post_id, $converted_data );
}
