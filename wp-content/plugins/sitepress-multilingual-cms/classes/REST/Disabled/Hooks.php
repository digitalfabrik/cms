<?php

namespace WPML\Core\REST\Disabled;

class Hooks implements \IWPML_Backend_Action  {
	public function add_hooks() {
		add_action( 'init', [ $this, 'displayWarning' ] );
	}

	public function displayWarning() {
		if ( wpml_is_rest_enabled() ) {
			wpml_get_admin_notices()->remove_notice( 'default', Notice::NOTICE_ID );

			return;
		}

		wpml_get_admin_notices()->add_notice( new Notice() );
	}
}