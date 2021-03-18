<?php

namespace WPML\PB\Integration;

class Update implements IUpdate {

	/**@var callable $getConvertedData :: int -> array */
	private $getConvertedData;

	/** @var callable $savePost :: ( int, int, array ) -> void */
	private $savePost;

	public function __construct(
		callable $getConvertedData,
		callable $savePost
	) {
		$this->getConvertedData = $getConvertedData;
		$this->savePost         = $savePost;
	}

	/**
	 * @param int $post_id
	 *
	 * @return array
	 */
	public function get_converted_data( $post_id ) {
		return (array) call_user_func( $this->getConvertedData, $post_id );
	}

	/**
	 * @param int   $post_id
	 * @param int   $original_post_id
	 * @param array $converted_data
	 */
	public function save( $post_id, $original_post_id, $converted_data ) {
		call_user_func( $this->savePost, $post_id, $original_post_id, $converted_data );
	}
}
