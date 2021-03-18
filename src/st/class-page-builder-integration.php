<?php

use WPML\PB\Integration\Integration;
use WPML\PB\Integration\IRegisterStrings;
use WPML\PB\Integration\IUpdateTranslation;

/**
 * Class WPML_Page_Builders_Integration
 */
class WPML_Page_Builders_Integration extends Integration {

	/** @var IWPML_Page_Builders_Data_Settings */
	private $data_settings;

	public function __construct(
		IRegisterStrings $register_strings,
		IUpdateTranslation $update_translation,
		IWPML_Page_Builders_Data_Settings $data_settings
	) {
		parent::__construct(
			$register_strings,
			$update_translation,
			[ $data_settings, 'get_pb_name' ]
		);

		$this->data_settings = $data_settings;
	}

	public function add_hooks() {
		parent::add_hooks();

		$this->data_settings->add_hooks();
	}
}
