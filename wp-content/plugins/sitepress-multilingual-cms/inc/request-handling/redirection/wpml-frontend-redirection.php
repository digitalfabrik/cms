<?php
require 'wpml-redirection.class.php';
require ICL_PLUGIN_PATH . '/inc/request-handling/redirection/wpml-redirect-by-param.class.php';

/**
 * Redirects to a URL corrected for the language information in it, in case request URI and $_REQUEST['lang'],
 * requested domain or $_SERVER['REQUEST_URI'] do not match and gives precedence to the explicit language parameter if
 * there.
 *
 * @return string The language code of the currently requested URL in case no redirection was necessary.
 */
function wpml_maybe_frontend_redirect() {
	global $wpml_url_converter;

	$language_code = $wpml_url_converter->get_language_from_url( $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	/** @var WPML_Redirection $redirect_helper */
	$redirect_helper = _wpml_get_redirect_helper();
	if ( ( $target = $redirect_helper->get_redirect_target() ) !== false ) {
		wp_safe_redirect( $target );
		exit;
	};

	// allow forcing the current language when it can't be decoded from the URL
	return apply_filters( 'icl_set_current_language', $language_code );
}

/**
 *
 * @return  WPML_Redirection
 *
 */
function _wpml_get_redirect_helper() {
	global $wpml_url_converter, $wpml_request_handler, $wpml_language_resolution;

	$lang_neg_type = wpml_get_setting_filter( false, 'language_negotiation_type' );
	switch ( $lang_neg_type ) {
		case 1:
			global $wpml_url_filters;
			if ( $wpml_url_filters->frontend_uses_root() !== false ) {
				require ICL_PLUGIN_PATH . '/inc/request-handling/redirection/wpml-rootpage-redirect-by-subdir.class.php';
				$redirect_helper = new WPML_RootPage_Redirect_By_Subdir(
						wpml_get_setting_filter( array(), 'urls' ),
						$wpml_request_handler,
						$wpml_url_converter,
						$wpml_language_resolution
				);
			} else {
				require ICL_PLUGIN_PATH . '/inc/request-handling/redirection/wpml-redirect-by-subdir.class.php';
				$redirect_helper = new WPML_Redirect_By_Subdir(
						$wpml_url_converter,
						$wpml_request_handler,
						$wpml_language_resolution
				);
			}
			break;
		case 2:
			require ICL_PLUGIN_PATH . '/inc/request-handling/redirection/wpml-redirect-by-domain.class.php';
			$redirect_helper = new WPML_Redirect_By_Domain(
					icl_get_setting( 'language_domains' ),
					$wpml_request_handler,
					$wpml_url_converter,
					$wpml_language_resolution
			);
			break;
		case 3:
		default:
			$redirect_helper = new WPML_Redirect_By_Param(
					icl_get_setting( 'taxonomies_sync_option', array() ),
					$wpml_url_converter,
					$wpml_request_handler,
					$wpml_language_resolution
			);
	}

	return $redirect_helper;
}