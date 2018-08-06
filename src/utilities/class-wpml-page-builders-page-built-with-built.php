<?php

class WPML_Page_Builders_Page_Built {

	private $config;

	public function __construct( array $config ) {
		$this->config = $config;
	}

	/**
	 * @param WP_Post $post
	 *
	 * @return bool
	 */
	public function is_page_builder_page( WP_Post $post ) {
		return isset( $this->config['wpml-config']['built_with_page_builder'] )
		       && preg_match( '/' . $this->config['wpml-config']['built_with_page_builder'] . '/', $post->post_content );
	}
}