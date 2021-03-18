<?php

/**
 * Class WPML_Page_Builders_Integration
 */
class WPML_Page_Builders_Integration extends WPML_Page_Builders_Integration_Base {

	/** @var IWPML_Page_Builders_Data_Settings */
	private $data_settings;

	public function __construct(
		WPML_Page_Builders_Register_Strings $register_strings,
		WPML_Page_Builders_Update_Translation $update_translation,
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
