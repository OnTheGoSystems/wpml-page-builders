<?php

interface IWPML_Page_Builders_Register_Strings {

	/**
	 * @param WP_Post $post
	 * @param array $package
	 */
	public function register_strings( WP_Post $post, array $package );
}
