<?php

function wpml_get_authenticated_action() {

	$action = filter_input( INPUT_POST, 'icl_ajx_action' );
	$action = $action ? $action : filter_input( INPUT_POST, 'action' );
	$nonce  = $action ? filter_input( INPUT_POST, '_icl_nonce' ) : null;
	if ( $nonce === null || $action === null ) {
		$action = filter_input( INPUT_GET, 'icl_ajx_action' );
		$nonce  = $action ? filter_input( INPUT_GET, '_icl_nonce' ) : null;
	}

	$authenticated_action = $action && wp_verify_nonce( (string) $nonce, $action . '_nonce' ) ? $action : null;

	return $authenticated_action;
}

function wpml_is_action_authenticated( $action ) {
	$nonce = isset( $_POST[ '_icl_nonce' ] ) ? $_POST[ '_icl_nonce' ] : "";

	return wp_verify_nonce( $nonce, $action . '_nonce' );
}