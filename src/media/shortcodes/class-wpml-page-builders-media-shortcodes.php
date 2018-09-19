<?php

class WPML_Page_Builders_Media_Shortcodes {

	const ALL_TAGS = '\w+';
	const TYPE_URL = 'url';
	const TYPE_IDS = 'ids';

	/** @var WPML_Page_Builders_Media_Translate $media_translate */
	private $media_translate;

	/** @var string $target_lang */
	private $target_lang;

	/** @var string $source_lang */
	private $source_lang;

	/** @var array $config */
	private $config;

	public function __construct( WPML_Page_Builders_Media_Translate $media_translate, array $config ) {
		$this->media_translate = $media_translate;
		$this->config          = $config;
	}

	public function translate( $content )  {
		foreach ( $this->config as $shortcode ) {
			$shortcode = $this->sanitize_shortcode( $shortcode );
			$tag_name  = $this->get_tag_name( $shortcode );

			if ( ! empty( $shortcode['attributes'] ) ) {
				$content = $this->translate_attributes( $content, $tag_name, $shortcode['attributes'] );
			}
		}

		return $content;
	}

	/**
	 * @param array $shortcode
	 *
	 * @return array
	 */
	private function sanitize_shortcode( array $shortcode ) {
		$defaults = array(
			'attributes' => null,
		);

		return array_merge( $defaults, $shortcode );
	}

	/**
	 * @param array $shortcode
	 *
	 * @return string
	 */
	private function get_tag_name( $shortcode ) {
		$tag_name = isset( $shortcode['tag']['name'] ) ? $shortcode['tag']['name'] : '';
		return str_replace( '*', self::ALL_TAGS, $tag_name );
	}

	private function translate_attributes( $content, $tag, $attributes ) {
		foreach ( $attributes as $attribute => $data ) {
			$pattern = '/(\[(?:' . $tag . ')(?: [^\]]* | )' . $attribute . '=(?:"|\'))([^"\']*)/';
			$type    = isset( $data['type'] ) ? $data['type'] : '';
			$content = preg_replace_callback( $pattern, array( $this, $this->get_callback( $type ) ), $content );
		}

		return $content;
	}

	private function get_callback( $type ) {
		if ( self::TYPE_URL === $type ) {
			return 'replace_url_callback';
		}

		return 'replace_ids_callback';
	}

	private function replace_url_callback( array $matches ) {
		$translated_url = $this->media_translate->translate_image_url( $matches[2], $this->target_lang, $this->source_lang );

		return $matches[1] . $translated_url;
	}

	private function replace_ids_callback( array $matches ) {
		$ids = explode( ',', $matches[2] );

		foreach ( $ids as &$id ) {
			$id = $this->media_translate->translate_id( (int) $id, $this->target_lang );
		}

		return $matches[1] . implode( ',', $ids );
	}

	/**
	 * @param string $target_lang
	 *
	 * @return self
	 */
	public function set_target_lang( $target_lang ) {
		$this->target_lang = $target_lang;
		return $this;
	}

	/**
	 * @param string $source_lang
	 *
	 * @return self
	 */
	public function set_source_lang( $source_lang ) {
		$this->source_lang = $source_lang;
		return $this;
	}
}
