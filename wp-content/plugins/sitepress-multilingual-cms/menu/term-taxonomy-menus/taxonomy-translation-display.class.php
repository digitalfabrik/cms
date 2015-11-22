<?php

require 'wpml-taxonomy-translation-sync-display.class.php';
new WPML_Taxonomy_Translation_Sync_Display();

class WPML_Taxonomy_Translation_Table_Display {

	private static function get_strings_translation_array() {

		$labels = array(
			"Show"                                  => __ ( "Show", "sitepress" ),
			"untranslated"                          => __ ( "untranslated", "sitepress" ),
			"all"                                   => __ ( "all", "sitepress" ),
			"in"                                    => __ ( "in", "sitepress" ),
			"to"                                    => __ ( "to", "sitepress" ),
			"of"                                    => __ ( "of", "sitepress" ),
			"taxonomy"                              => __ ( "Taxonomy", "sitepress" ),
			"anyLang"                               => __ ( "any language", "sitepress" ),
			"apply"                                 => __ ( "Refresh", "sitepress" ),
			"synchronizeBtn"                        => __ ( "Update Taxonomy Hierarchy", "sitepress" ),
			"searchPlaceHolder"                     => __ ( "search", "sitepress" ),
			"selectParent"                          => __ ( "select parent", "sitepress" ),
			"taxToTranslate"                        => __ ( "Select the taxonomy to translate: ", "sitepress" ),
			"translate"                             => __ ( "Translate", "sitepress" ),
			"Synchronize"                           => __ ( "Synchronize Hierarchy", "sitepress" ),
			"lowercaseTranslate"                    => __ ( "translate", "sitepress" ),
			"willBeRemoved"                         => __ ( "Will be removed.", "sitepress" ),
			"willBeAdded"                           => __ ( "Will be added.", "sitepress" ),
			"refLang"                               => __ ( "Synchronize taxonomy hierarchy according to this language", "sitepress" ),
			"targetLang"                            => __ ( "Target Language", "sitepress" ),
			"Name"                                  => __ ( "Name", "sitepress" ),
			"Slug"                                  => __ ( "Slug", "sitepress" ),
			"Description"                           => __ ( "Description", "sitepress" ),
			"Ok"                                    => __ ( "Ok", "sitepress" ),
			"Singular"                              => __ ( "Singular", "sitepress" ),
			"Plural"                                => __ ( "Plural", "sitepress" ),
			"cancel"                                => __ ( "cancel", "sitepress" ),
			"loading"                               => __ ( "loading", "sitepress" ),
			"Save"                                  => __ ( "Save", "sitepress" ),
			"currentPage"                           => __ ( "Current page", "sitepress" ),
			"goToPreviousPage"                      => __ ( "Go to previous page", "sitepress" ),
			"goToNextPage"                          => __ ( "Go to the next page", "sitepress" ),
			"goToFirstPage"                         => __ ( "Go to the first page", "sitepress" ),
			"goToLastPage"                          => __ ( "Go to the last page", "sitepress" ),
			"hieraSynced"                           => __ ( "Taxonomy hierarchy sync is now complete.", "sitepress" ),
			"hieraAlreadySynced"                    => __ ( "The taxonomy hierarchy is already synchronized.", "sitepress" ),
			"items"                                 => __ ( "items", "sitepress" ),
			"item"                                  => __ ( "item", "sitepress" ),
			"summaryTerms"                          => __ (
				"This table summarizes all the terms for the taxonomy %taxonomy% and their translations. Click on any cell to translate.",
				"sitepress"
			),
			"summaryLabels"                         => __ (
				"This table lets you translate the labels for the taxonomy %taxonomy%. These translations will appear in the WordPress admin menus.",
				"sitepress"
			),
			"preparingTermsData"                    => __( "Loading ...", "sitepress" ),
			"wpml_save_term_nonce"                  => wp_create_nonce( 'wpml_save_term_nonce' ),
			"wpml_tt_save_labels_translation_nonce" => wp_create_nonce( 'wpml_tt_save_labels_translation_nonce' ),
			"wpml_tt_sync_hierarchy_nonce"          => wp_create_nonce( 'wpml_tt_sync_hierarchy_nonce' )
		);

		if ( defined( 'WPML_ST_FOLDER' ) ) {
			$changeLabelLanguage_url = admin_url( 'admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php&context=WordPress' );
			$changeLabelLanguage     = __( 'You can change the language of this label from the <a href="%s">string translation page</a>.', "sitepress" );
		} else {
			$changeLabelLanguage_url = 'https://wpml.org/account/downloads/#wpml-string-translation';
			$changeLabelLanguage     = __( 'You can change the language of this label if you install and activate <a href="%s">WPML String Translation</a>.', "sitepress" );
		}
		$labels['changeLabelLanguage'] = sprintf( $changeLabelLanguage, $changeLabelLanguage_url );

		return $labels;
	}

	public static function enqueue_taxonomy_table_js() {

		$core_dependencies = array( "underscore", "jquery", "backbone" );
		wp_register_script( "templates",
		                    ICL_PLUGIN_URL . '/res/js/taxonomy-translation/templates.js',
		                    $core_dependencies );
		$core_dependencies[ ] = "templates";
		wp_register_script( "main-util", ICL_PLUGIN_URL . '/res/js/taxonomy-translation/util.js', $core_dependencies );

		wp_register_script( "main-model", ICL_PLUGIN_URL . '/res/js/taxonomy-translation/main.js', $core_dependencies );
		$core_dependencies[ ] = "main-model";

		$dependencies = $core_dependencies;
		wp_register_script( "term-rows-collection",
		                    ICL_PLUGIN_URL . '/res/js/taxonomy-translation/collections/term-rows.js',
		                    array_merge( $core_dependencies, array( "term-row-model" ) ) );
		$dependencies[ ] = "term-rows-collection";
		wp_register_script( "term-model",
		                    ICL_PLUGIN_URL . '/res/js/taxonomy-translation/models/term.js',
		                    $core_dependencies );
		$dependencies[ ] = "term-model";
		wp_register_script( "taxonomy-model",
		                    ICL_PLUGIN_URL . '/res/js/taxonomy-translation/models/taxonomy.js',
		                    $core_dependencies );
		$dependencies[ ] = "taxonomy-model";
		wp_register_script( "term-row-model",
		                    ICL_PLUGIN_URL . '/res/js/taxonomy-translation/models/term-row.js',
		                    $core_dependencies );
		$dependencies[ ] = "term-row-model";
		wp_register_script( "filter-view",
		                    ICL_PLUGIN_URL . '/res/js/taxonomy-translation/views/filter-view.js',
		                    $core_dependencies );
		$dependencies[ ] = "filter-view";
		wp_register_script( "nav-view",
		                    ICL_PLUGIN_URL . '/res/js/taxonomy-translation/views/nav-view.js',
		                    $core_dependencies );
		$dependencies[ ] = "nav-view";
		wp_register_script( "table-view",
		                    ICL_PLUGIN_URL . '/res/js/taxonomy-translation/views/table-view.js',
		                    $core_dependencies );
		$dependencies[ ] = "table-view";
		wp_register_script( "taxonomy-view",
		                    ICL_PLUGIN_URL . '/res/js/taxonomy-translation/views/taxonomy-view.js',
		                    $core_dependencies );
		$dependencies[ ] = "taxonomy-view";
		wp_register_script( "term-popup-view",
		                    ICL_PLUGIN_URL . '/res/js/taxonomy-translation/views/term-popup-view.js',
		                    $core_dependencies );
		$dependencies[ ] = "term-popup-view";
		wp_register_script( "label-popup-view",
		                    ICL_PLUGIN_URL . '/res/js/taxonomy-translation/views/label-popup-view.js',
		                    $core_dependencies );
		$dependencies[ ] = "label-popup-view";
		wp_register_script( "term-row-view",
		                    ICL_PLUGIN_URL . '/res/js/taxonomy-translation/views/term-row-view.js',
		                    $core_dependencies );
		$dependencies[ ] = "term-row-view";
		wp_register_script( "label-row-view",
		                    ICL_PLUGIN_URL . '/res/js/taxonomy-translation/views/label-row-view.js',
		                    $core_dependencies );
		$dependencies[ ] = "label-row-view";
		wp_register_script( "term-rows-view",
		                    ICL_PLUGIN_URL . '/res/js/taxonomy-translation/views/term-rows-view.js',
		                    $core_dependencies );
		$dependencies[ ] = "term-rows-view";
		wp_register_script( "term-view",
		                    ICL_PLUGIN_URL . '/res/js/taxonomy-translation/views/term-view.js',
		                    $core_dependencies );
		$dependencies[ ] = "term-view";

		foreach ( $dependencies as $dependency ) {
			if ( $dependency != "templates" ) {
				wp_localize_script( $dependency, "labels", self::get_strings_translation_array() );
			}
		}

		$need_enqueue    = $dependencies;
		$need_enqueue[ ] = "main-model";
		$need_enqueue[ ] = "main-util";
		$need_enqueue[ ] = "templates";

		foreach ( $need_enqueue as $handle ) {
			wp_enqueue_script( $handle );
		}
	}

	public static function wpml_get_table_taxonomies() {
		global $sitepress;
		$taxonomies = get_taxonomies( array(), 'objects' );

		$result = array( "taxonomies" => array(), "activeLanguages" => array() );
		$sitepress->set_admin_language();
		$active_langs = $sitepress->get_active_languages();
		$default_lang = $sitepress->get_default_language();

		foreach ( $active_langs as $code => $lang ) {
			if ( is_array( $lang ) && isset( $lang[ 'display_name' ] ) ) {
				$result[ "activeLanguages" ][ $code ] = array( "label" => $lang[ 'display_name' ] );
			}
		}

		if ( isset( $active_langs[ $default_lang ] ) ) {
			$def_lang                    = $active_langs[ $default_lang ];
			$result[ "activeLanguages" ] = array( $default_lang => array( "label" => $def_lang[ 'display_name' ] ) ) + $result[ "activeLanguages" ];
		}

		foreach ( $taxonomies as $key => $tax ) {
			if ( $sitepress->is_translated_taxonomy( $key ) ) {
				$result[ "taxonomies" ][ $key ] = array(
					"label"         => $tax->label,
					"singularLabel" => $tax->labels->singular_name,
					"hierarchical"  => $tax->hierarchical,
					"name"          => $key
				);
			}
		}

		wp_send_json( $result );
	}

	public static function wpml_get_terms_and_labels_for_taxonomy_table() {
		global $sitepress;
		$args     = array();
		$taxonomy = false;

		$request_post_page = filter_input( INPUT_POST,
										   'page',
										   FILTER_SANITIZE_FULL_SPECIAL_CHARS,
										   FILTER_NULL_ON_FAILURE );
		if ( $request_post_page ) {
			$args[ 'page' ] = $request_post_page;
		}

		$request_post_perPage = filter_input( INPUT_POST,
											  'perPage',
											  FILTER_SANITIZE_FULL_SPECIAL_CHARS,
											  FILTER_NULL_ON_FAILURE );
		if ( $request_post_perPage ) {
			$args[ 'per_page' ] = $request_post_perPage;
		}

		$request_post_taxonomy = filter_input( INPUT_POST,
											   'taxonomy',
											   FILTER_SANITIZE_FULL_SPECIAL_CHARS,
											   FILTER_NULL_ON_FAILURE );
		if ( $request_post_taxonomy ) {
			$taxonomy = html_entity_decode( $request_post_taxonomy );
		}

		do_action( 'wpml_st_load_label_menu' );

		if ( $taxonomy ) {
			$terms    = WPML_Taxonomy_Translation::get_terms_for_taxonomy_translation_screen( $taxonomy );
			$labels   = apply_filters( 'wpml_label_translation_data', false, $taxonomy );
			$def_lang = $sitepress->get_default_language();
			wp_send_json( array(
							  "terms"                => $terms,
							  "taxLabelTranslations" => $labels,
							  "defaultLanguage"      => $def_lang
						  ) );
		} else {
			wp_send_json_error();
		}
	}
}
