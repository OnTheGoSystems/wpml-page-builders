<?php

namespace WPML\PB\Compatibility\Toolset\Layouts;

class HooksFactory implements \IWPML_Backend_Action_Loader, \IWPML_Frontend_Action_Loader {

	public function create() 	{
		if ( defined( 'WPDDL_VERSION' ) ) {
			return new Hooks();
		}

		return null;
	}
}
