<?php

namespace WPML\PB\Integration;

interface IRegisterStrings {

	/**
	 * @param \WP_Post $post
	 * @param array $package
	 */
	public function register_strings( \WP_Post $post, array $package );
}
