<?php

/**
 * Registers scripts so that they can be reused throughout WPML plugins
 */
function wpml_tm_register_js_scripts() {
	wp_register_script(
		'wpml-tm-editor-job',
		WPML_TM_URL . '/res/js/translation-editor/wpml-tm-editor-job.js',
		array( 'underscore', 'backbone' ),
		WPML_TM_VERSION,
		true
	);
	wp_register_script(
		'wpml-tm-editor-job-field-view',
		WPML_TM_URL . '/res/js/translation-editor/wpml-tm-editor-job-field-view.js',
		array( 'wpml-tm-editor-job' ),
		WPML_TM_VERSION,
		true
	);
	wp_register_script(
		'wpml-tm-editor-scripts',
		WPML_TM_URL . '/res/js/translation-editor/translation-editor.js',
		array( 'jquery', 'jquery-ui-dialog', 'wpml-tm-editor-job-field-view' ),
		WPML_TM_VERSION,
		true
	);
	wp_register_script( 'wpml-tp-polling-box-populate',
		WPML_TM_URL . '/res/js/tp-polling/box-populate.js',
		array( 'jquery' ), WPML_TM_VERSION );
	wp_register_script( 'wpml-tp-polling',
		WPML_TM_URL . '/res/js/tp-polling/poll-for-translations.js',
		array( 'wpml-tp-polling-box-populate' ), WPML_TM_VERSION );
	wp_register_script( 'wpml-tp-polling-setup',
		WPML_TM_URL . '/res/js/tp-polling/box-setup.js',
		array( 'wpml-tp-polling' ), WPML_TM_VERSION );
	wp_register_script( 'wpml-tm-mcs',
		WPML_TM_URL . '/res/js/mcs/wpml-tm-mcs.js',
		array( 'wpml-tp-polling' ), WPML_TM_VERSION );
}

if ( is_admin() ) {
	add_action( 'admin_enqueue_scripts', 'wpml_tm_register_js_scripts' );
} else {
	add_action( 'wp_enqueue_scripts', 'wpml_tm_register_js_scripts' );
}
