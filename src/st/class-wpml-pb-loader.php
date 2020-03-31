<?php

class WPML_PB_Loader {

	public function __construct(
		SitePress $sitepress,
		WPDB $wpdb,
		WPML_ST_Settings $st_settings,
		$pb_integration = null // Only needed for testing
	) {
		do_action( 'wpml_load_page_builders_integration' );

		$page_builder_strategies = array();

		/**
		 * This filter hook provide the API page builders names that need to be supported.
		 *
		 * For each PB name, we will create a dedicated strategy and a proper string package namespace.
		 *
		 * It's called in 2 places:
		 * - `WPML_Page_Builders_Integration` for external plugins
		 * - `WPML_Gutenberg_Integration` for WordPress Core block editor
		 *
		 * @param string[] Required plugin names (e.g. `Beaver Builder`, `Gutenberg`)
		 */
		$required = apply_filters( 'wpml_page_builder_support_required', array() );
		foreach ( $required as $plugin ) {
			$page_builder_strategies[] = new WPML_PB_API_Hooks_Strategy( $plugin );
		}

		$page_builder_config_import = new WPML_PB_Config_Import_Shortcode( $st_settings );
		$page_builder_config_import->add_hooks();
		if ( $page_builder_config_import->has_settings() ) {
			$strategy = new WPML_PB_Shortcode_Strategy( new WPML_Page_Builder_Settings() );
			$strategy->add_shortcodes( $page_builder_config_import->get_settings() );
			$page_builder_strategies[] = $strategy;

			if ( defined( 'WPML_MEDIA_VERSION' ) && $page_builder_config_import->get_media_settings() ) {
				$shortcodes_media_hooks = new WPML_Page_Builders_Media_Hooks(
					new WPML_Page_Builders_Media_Shortcodes_Update_Factory( $page_builder_config_import ),
					'shortcodes'
				);
				$shortcodes_media_hooks->add_hooks();
			}
		}

		if ( class_exists( 'WPML_Config_Built_With_Page_Builders' ) ) {
			$post_body_handler = new WPML_PB_Handle_Post_Body(
				new WPML_Page_Builders_Page_Built(
					new WPML_Config_Built_With_Page_Builders()
				)
			);

			$post_body_handler->add_hooks();
		}

		self::load_hooks();

		if ( $page_builder_strategies ) {
			if ( $pb_integration ) {
				$factory = $pb_integration->get_factory();
			} else {
				$factory        = new WPML_PB_Factory( $wpdb, $sitepress );
				$pb_integration = new WPML_PB_Integration( $sitepress, $factory );
			}
			$pb_integration->add_hooks();
			foreach ( $page_builder_strategies as $strategy ) {
				$strategy->set_factory( $factory );
				$pb_integration->add_strategy( $strategy );
			}
		}

	}

	private static function load_hooks() {
		$hooks = [
			WPML\PB\Compatibility\Toolset\Layouts\HooksFactory::class,
		];

		$hooks_loader = new WPML_Action_Filter_Loader();
		$hooks_loader->load( $hooks );
	}
}
