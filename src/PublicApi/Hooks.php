<?php

namespace WPML\PB\PublicApi;

use WPML_PB_Last_Translation_Edit_Mode;

class Hooks {

	/** @var WPML_PB_Last_Translation_Edit_Mode $lastEditMode */
	private $lastEditMode;

	public function __construct( WPML_PB_Last_Translation_Edit_Mode $lastEditMode ) {
		$this->lastEditMode = $lastEditMode;
	}

	public function addHooks() {
		add_filter( 'wpml_pb_post_use_translation_editor', [ $this, 'postUseTranslationEditor' ], 10, 2 );
	}

	/**
	 * @param bool $incomingValue
	 * @param int  $postId
	 *
	 * @return bool
	 */
	public function postUseTranslationEditor( $incomingValue, $postId ) {
		return $this->lastEditMode->is_translation_editor( $postId );
	}
}

class Factory {

	public static function create() {
		return new Hooks( new WPML_PB_Last_Translation_Edit_Mode() );
	}
}
