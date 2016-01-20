<?php
/**
 * Main SitePress Class
 *
 * @package wpml-core
 */
class SitePress extends WPML_WPDB_User{
	/** @var  WPML_Meta_Boxes_Post_Edit_HTML $post_edit_metabox */
	private $post_edit_metabox;
	/** @var WPML_Post_Translation $post_translation */
	private $post_translation;
    private $post_duplication;
	/** @var WPML_Term_Actions $term_actions */
	private $term_actions;
	/** @var WPML_Admin_Scripts_Setup $scripts_handler */
	private $scripts_handler;
	/** @var WPML_Set_Language $language_setter */
	private $language_setter;
	/** @var array $settings */
	private $settings;
	private $active_languages = array();
	private $_admin_notices = array();
	private $this_lang;
	private $wp_query;
	private $admin_language = null;
	private $user_preferences = array();
	private $is_mobile;
    private $is_tablet;
	private $always_translatable_post_types;
	private $always_translatable_taxonomies;
	/** @var  WPML_WP_API $wp_api */
	private $wp_api;
	/** @var WPML_Records $records */
	private $records;
	/** @var WPML_Post_Status_Display $post_status_display */
	private $post_status_display;
	/** @var  @var int $loaded_blog_id */
	private $loaded_blog_id;

	/** @var  WPML_Locale $locale_utils */
	public $locale_utils;

	public $footer_preview = false;

	/**
	 * @var icl_cache
	 */
	public $icl_translations_cache;
	/**
	 * @var WPML_Flags
	 */
	private $flags;
	/**
	 * @var icl_cache
	 */
	public $icl_language_name_cache;
	/**
	 * @var icl_cache
	 */
	public $icl_term_taxonomy_cache;
	private $wpml_helper;

	function __construct() {
		do_action('wpml_before_startup');


		/** @var array $sitepress_settings */
		global $pagenow, $sitepress_settings, $wpdb, $wpml_post_translations, $locale, $wpml_term_translations;
		$this->wpml_helper = new WPML_Helper( $wpdb );

		parent::__construct( $wpdb );
		$this->locale_utils = new WPML_Locale( $wpdb, $this, $locale );
        $sitepress_settings = get_option('icl_sitepress_settings');
		$this->settings = &$sitepress_settings;
		$this->always_translatable_post_types = array( 'post', 'page' );
		$this->always_translatable_taxonomies = array( 'category', 'post_tag' );

		//TODO: [WPML 3.5] To remove in WPML 3.5
		//@since 3.1
		if(is_admin() && !$this->get_setting('icl_capabilities_verified')) {
			icl_enable_capabilities();
			$sitepress_settings = get_option('icl_sitepress_settings');
		}

		if ( is_null( $pagenow ) && is_multisite() ) {
			include ICL_PLUGIN_PATH . '/inc/hacks/vars-php-multisite.php';
		}

		if ( false != $this->settings ) {
			$this->verify_settings();
		}

		if ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == ICL_PLUGIN_FOLDER . '/menu/troubleshooting.php' && isset( $_GET[ 'debug_action' ] ) ) {
			ob_start();
		}

		if ( isset( $_REQUEST[ 'icl_ajx_action' ] ) ) {
			add_action( 'init', array( $this, 'ajax_setup' ), 15 );
		}
		add_action( 'admin_footer', array( $this, 'icl_nonces' ) );

		// Process post requests
		if ( !empty( $_POST ) ) {
			add_action( 'init', array( $this, 'process_forms' ) );
		}

		$this->initialize_cache( );

		$this->flags = new WPML_Flags( $wpdb );

		add_action( 'plugins_loaded', array( $this, 'init' ), 1 );
		add_action( 'wp_loaded', array( $this, 'maybe_set_this_lang' ) );
		add_action( 'init', array( $this, 'on_wp_init' ), 1 );
		add_action( 'switch_blog', array( $this, 'init_settings' ), 10, 1 );
		// Administration menus
		add_action( 'admin_menu', array( $this, 'administration_menu' ) );
		add_action( 'admin_menu', array( $this, 'administration_menu2' ), 30 );

		add_action( 'init', array( $this, 'plugin_localization' ) );

		if ( $this->get_setting('existing_content_language_verified') && ( $this->get_setting('setup_complete') || ( !empty($_GET[ 'page' ]) && $this->get_setting('setup_wizard_step')==3 && $_GET[ 'page' ] == ICL_PLUGIN_FOLDER . '/menu/languages.php'  ) ) ) {

		// Post/page language box

			add_filter( 'comment_feed_join', array( $this, 'comment_feed_join' ) );

			add_filter( 'comments_clauses', array( $this, 'comments_clauses' ), 10, 2 );

			// Allow us to filter the Query vars before the posts query is being built and executed
			add_filter( 'pre_get_posts', array( $this, 'pre_get_posts' ) );

			add_filter( 'the_posts', array( $this, 'the_posts' ), 10 );

			if ( $pagenow == 'edit.php' ) {
				add_action( 'quick_edit_custom_box', array( 'WPML_Terms_Translations', 'quick_edit_terms_removal' ), 10, 2 );
			}

			add_filter( 'get_pages', array( $this, 'exclude_other_language_pages2' ) );
			add_filter( 'wp_dropdown_pages', array( $this, 'wp_dropdown_pages' ) );

			add_filter( 'get_comment_link', array( $this, 'get_comment_link_filter' ) );

			// filter the saving of terms so that the taxonomy_ids of translated terms are correctly adjusted across taxonomies
			add_action('created_term_translation', array( 'WPML_Terms_Translations', 'sync_ttid_action' ), 10, 3 );
			// filters terms by language for the term/tag-box autoselect
			if ( ( isset( $_GET['action'] ) && 'ajax-tag-search' === $_GET['action'] ) || ( isset( $_POST['action'] ) && 'get-tagcloud' === $_POST['action'] ) ) {
				add_filter( 'get_terms', array( 'WPML_Terms_Translations', 'get_terms_filter' ), 10, 2 );
			}

			$this->set_term_filters_and_hooks();

			add_action( 'parse_query', array( $this, 'parse_query' ) );

			// The delete filter only ensures the synchronizing of delete actions between translations of a term.
			add_action( 'delete_term', array( $this, 'delete_term' ), 1, 3 );
			add_action( 'set_object_terms', array( 'WPML_Terms_Translations', 'set_object_terms_action' ), 10, 6 );

			// AJAX Actions for the post edit screen
			add_action( 'wp_ajax_wpml_save_term', array( 'WPML_Post_Edit_Ajax', 'wpml_save_term' ) );
			add_action( 'wp_ajax_wpml_switch_post_language', array( 'WPML_Post_Edit_Ajax', 'wpml_switch_post_language' ) );
			add_action( 'wp_ajax_wpml_set_post_edit_lang', array( 'WPML_Post_Edit_Ajax', 'wpml_set_post_edit_lang' ) );
			add_action( 'wp_ajax_wpml_get_default_lang', array( 'WPML_Post_Edit_Ajax', 'wpml_get_default_lang' ) );

			//AJAX Actions for the taxonomy translation screen
			add_action( 'wp_ajax_wpml_get_table_taxonomies', array( 'WPML_Taxonomy_Translation_Table_Display', 'wpml_get_table_taxonomies' ) );
			add_action( 'wp_ajax_wpml_get_terms_and_labels_for_taxonomy_table', array( 'WPML_Taxonomy_Translation_Table_Display', 'wpml_get_terms_and_labels_for_taxonomy_table' ) );

			// Ajax Action for the updating of term names on the troubleshooting page
			add_action( 'wp_ajax_wpml_update_term_names_troubleshoot', array( 'WPML_Troubleshooting_Terms_Menu', 'wpml_update_term_names_troubleshoot' ) );

			// short circuit get default category
			add_filter( 'pre_option_default_category', array( $this, 'pre_option_default_category' ) );
			add_filter( 'update_option_default_category', array( $this, 'update_option_default_category' ), 1, 2 );

			// custom hook for adding the language selector to the template
			//@deprecated see 'wpml_add_language_selector' action in $this->api_hooks();)
			add_action( 'icl_language_selector', array( $this, 'language_selector' ) );

			// front end js
			add_action( 'wp_head', array( $this, 'front_end_js' ) );

			add_action( 'wp_head', array( $this, 'rtl_fix' ) );
			add_action( 'admin_print_styles', array( $this, 'rtl_fix' ) );

			add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );

			// adjacent posts links
			add_filter( 'get_previous_post_join', array( $this, 'get_adjacent_post_join' ) );
			add_filter( 'get_next_post_join', array( $this, 'get_adjacent_post_join' ) );
			add_filter( 'get_previous_post_where', array( $this, 'get_adjacent_post_where' ) );
			add_filter( 'get_next_post_where', array( $this, 'get_adjacent_post_where' ) );

			// feeds links
			add_filter( 'feed_link', array( $this, 'feed_link' ) );

			// commenting links
			add_filter( 'post_comments_feed_link', array( $this, 'post_comments_feed_link' ) );
			add_filter( 'trackback_url', array( $this, 'trackback_url' ) );
			add_filter( 'user_trailingslashit', array( $this, 'user_trailingslashit' ), 1, 2 );

			// date based archives
			add_filter( 'year_link', array( $this, 'archives_link' ) );
			add_filter( 'month_link', array( $this, 'archives_link' ) );
			add_filter( 'day_link', array( $this, 'archives_link' ) );
			add_filter( 'getarchives_join', array( $this, 'getarchives_join' ) );
			add_filter( 'getarchives_where', array( $this, 'getarchives_where' ) );
			add_filter( 'pre_option_home', array( $this, 'pre_option_home' ) );

			if ( !is_admin() ) {
				add_filter( 'attachment_link', array( $this, 'attachment_link_filter' ), 10, 2 );
			}

			// Filter custom type archive link (since WP 3.1)
			add_filter( 'post_type_archive_link', array( $this, 'post_type_archive_link_filter' ), 10, 2 );

			add_filter( 'author_link', array( $this, 'author_link' ) );

			// language negotiation
			add_action( 'query_vars', array( $this, 'query_vars' ) );
			add_filter( 'language_attributes', array( $this, 'language_attributes' ) );
			add_filter( 'locale', array( $this, 'locale' ), 10, 1 );
			add_filter( 'pre_option_page_on_front', array( $this, 'pre_option_page_on_front' ) );
			add_filter( 'pre_option_page_for_posts', array( $this, 'pre_option_page_for_posts' ) );
			add_filter( 'pre_option_sticky_posts', array( $this, 'option_sticky_posts' ), 10, 2 );

			add_action( 'wp_head', array( $this, 'set_wp_query' ) );
			add_action( 'personal_options_update', array( $this, 'save_user_options' ) );

			// column with links to translations (or add translation) - low priority
			add_action( 'init', array( $this, 'configure_custom_column' ), 1010 ); // accommodate Types init@999

			if ( !is_admin() ) {
				add_action( 'wp_head', array( $this, 'meta_generator_tag' ) );
			}

			require_once ICL_PLUGIN_PATH . '/inc/wp-nav-menus/iclNavMenu.class.php';
			new iclNavMenu( $this, $wpdb, $wpml_post_translations, $wpml_term_translations );

			if ( is_admin() || defined( 'XMLRPC_REQUEST' ) || preg_match( '#wp-comments-post\.php$#', $_SERVER[ 'REQUEST_URI' ] ) ) {
				global $iclTranslationManagement;

				$iclTranslationManagement = wpml_load_core_tm ();
			}

			add_action( 'wp_login', array( $this, 'reset_admin_language_cookie' ) );

			if ( $this->settings[ 'seo' ][ 'head_langs' ] ) {
				add_action( 'wp_head', array( $this, 'head_langs' ) );
			}

			/**
			 * add extra debug information
			 */
			add_filter( 'icl_get_extra_debug_info', array( $this, 'add_extra_debug_info' ) );

		} //end if the initial language is set - existing_content_language_verified

		add_action( 'wp_dashboard_setup', array( $this, 'dashboard_widget_setup' ) );

		add_filter( 'core_version_check_locale', array( $this, 'wp_upgrade_locale' ) );

		if ( $pagenow === 'post.php' && isset( $_REQUEST[ 'action' ] ) && $_REQUEST[ 'action' ] == 'edit' && isset( $_GET[ 'post' ] ) ) {
			add_action( 'init', '_icl_trash_restore_prompt' );
		}

		add_action( 'init', array( $this, 'js_load' ), 2 ); // enqueue scripts - higher priority
		add_filter('url_to_postid', array($this, 'url_to_postid'));
		//cron job to update WPML config index file from CDN
		add_action('update_wpml_config_index', array($this,'update_wpml_config_index_event'));
		//update WPML config files
		add_action('wp_ajax_update_wpml_config_index', array($this,'update_wpml_config_index_event_ajax'));
		add_action( 'after_switch_theme', array( $this, 'update_wpml_config_index_event' ) );
		add_action( 'activated_plugin', array( $this, 'update_wpml_config_index_event' ) );
		add_action('core_upgrade_preamble', array($this, 'update_index_screen'));
		add_shortcode('wpml_language_selector_widget', 'icl_language_selector');
		add_shortcode('wpml_language_selector_footer', 'icl_language_selector_footer');
		add_filter('get_search_form', array($this, 'get_search_form_filter'));
		$this->api_hooks();
		add_action('wpml_loaded', array($this, 'load_dependencies'), 10000);
		do_action('wpml_after_startup');
	}

	/**
	 * @since 3.2
	 */
	public function api_hooks() {
		//TODO: [WPML 3.5] to deprecate in favour of lowercased namespaces
		add_filter( 'WPML_get_setting', array( $this, 'filter_get_setting' ), 10, 2 );
		add_filter( 'WPML_get_current_language', array( $this, 'get_current_language_filter' ), 10, 0 );
		add_filter( 'WPML_get_user_admin_language', array( $this, 'get_user_admin_language_filter' ), 10, 2 );
		add_filter( 'WPML_is_admin_action_from_referer', array( $this, 'check_if_admin_action_from_referer' ), 10, 0 );
		add_filter( 'WPML_current_user', array( $this, 'get_current_user' ), 10, 0 );

		add_filter( 'wpml_get_setting', array( $this, 'filter_get_setting' ), 10, 2 );
		add_action( 'wpml_set_setting', array( $this, 'action_set_setting' ), 10, 3 );
		add_filter( 'wpml_get_language_cookie', array( $this, 'get_language_cookie' ), 10, 0 );
		add_filter( 'wpml_current_language', array( $this, 'get_current_language_filter' ), 10, 0 );
		add_filter( 'wpml_get_user_admin_language', array( $this, 'get_user_admin_language_filter' ), 10, 2 );
		add_filter( 'wpml_is_admin_action_from_referer', array( $this, 'check_if_admin_action_from_referer' ), 10, 0 );
		add_filter( 'wpml_current_user', array( $this, 'get_current_user' ), 10, 0 );

		add_filter( 'wpml_new_post_source_id', array ( $this, 'get_new_post_source_id'), 10, 1);

		/**
		 * @use \SitePress::get_translatable_documents_filter
		 */
		add_filter( 'wpml_translatable_documents', array( $this, 'get_translatable_documents_filter' ), 10, 2 );
		add_filter( 'wpml_is_translated_post_type', array( $this, 'is_translated_post_type_filter' ), 10, 2 );
		/**
		 * @deprecated it has a wrong hook tag
		 * @since 3.2
		 */
		add_filter( 'wpml_get_element_translations_filter', array( $this, 'get_element_translations_filter' ), 10, 6 );
		add_filter( 'wpml_get_element_translations', array( $this, 'get_element_translations_filter' ), 10, 6 );
		add_filter( 'wpml_is_original_content', array( $this, 'is_original_content_filter'), 10, 3 );
		add_filter( 'wpml_original_element_id', array( $this, 'get_original_element_id_filter'), 10, 3 );

		add_filter( 'wpml_is_rtl', array( $this, 'is_rtl' ) );

		add_filter( 'wpml_home_url', 'wpml_get_home_url_filter', 10 );
		add_filter( 'wpml_active_languages', 'wpml_get_active_languages_filter', 10, 2 );
		add_filter( 'wpml_display_language_names', 'wpml_display_language_names_filter', 10, 5 ); 
		add_filter( 'wpml_display_single_language_name', array($this, 'get_display_single_language_name_filter'), 10, 2 );
		add_filter( 'wpml_element_link', 'wpml_link_to_element_filter', 10, 7 );
		add_filter( 'wpml_object_id', 'wpml_object_id_filter', 10, 4 );
		add_filter( 'wpml_translated_language_name', 'wpml_translated_language_name_filter', 10, 3 );
		add_filter( 'wpml_default_language', 'wpml_get_default_language_filter', 10, 1);
		add_filter( 'wpml_post_language_details', 'wpml_get_language_information', 10, 2 );

		add_action( 'wpml_add_language_selector', 'wpml_add_language_selector_action' );
		add_action( 'wpml_footer_language_selector', 'wpml_footer_language_selector_action' );
		add_action( 'wpml_add_language_form_field', 'wpml_add_language_form_field_action' );
		add_shortcode( 'wpml_language_form_field', 'wpml_language_form_field_shortcode' );

		add_filter( 'wpml_element_translation_type', 'wpml_get_element_translation_type_filter', 10, 3 );
		add_filter( 'wpml_element_has_translations', 'wpml_element_has_translations_filter', 10, 3 );
		add_filter( 'wpml_content_translations', 'wpml_get_content_translations_filter', 10, 3 );
		add_filter( 'wpml_master_post_from_duplicate', 'wpml_get_master_post_from_duplicate_filter' );
		add_filter( 'wpml_post_duplicates', 'wpml_get_post_duplicates_filter' );
		add_filter( 'wpml_element_type', 'wpml_element_type_filter' );

		add_filter( 'wpml_setting', 'wpml_get_setting_filter', 10, 3 );
		add_filter( 'wpml_sub_setting', 'wpml_get_sub_setting_filter', 10, 4 );
		add_filter( 'wpml_language_is_active', 'wpml_language_is_active_filter', 10, 2 );

		add_action( 'wpml_admin_make_post_duplicates', 'wpml_admin_make_post_duplicates_action', 10, 1 );
		add_action( 'wpml_make_post_duplicates', 'wpml_make_post_duplicates_action', 10, 1 );

		add_filter( 'wpml_element_language_details', 'wpml_element_language_details_filter', 10, 2 );
		add_action( 'wpml_set_element_language_details', array($this, 'set_element_language_details_action'), 10, 1 );
		add_filter( 'wpml_element_language_code', 'wpml_element_language_code_filter', 10, 2 );
		add_filter( 'wpml_elements_without_translations', 'wpml_elements_without_translations_filter', 10, 2 );

		add_filter( 'wpml_permalink', 'wpml_permalink_filter', 10, 2 );
		add_action( 'wpml_switch_language', 'wpml_switch_language_action', 10, 1 );
	}

	function init() {
		global $wpml_post_translations;

		$this->post_translation = &$wpml_post_translations;

		do_action('wpml_before_init');
		$this->locale_utils->init();
		$this->maybe_set_this_lang();

		if ( function_exists( 'w3tc_add_action' ) ) {
			w3tc_add_action( 'w3tc_object_cache_key', 'w3tc_translate_cache_key_filter' );
		}

		$this->get_user_preferences();
		$this->set_admin_language();

		// default value for theme_localization_type OR
		// reset theme_localization_type if string translation was on (theme_localization_type was set to 2) and then it was deactivated
		if ( !isset( $this->settings[ 'theme_localization_type' ] ) || ( $this->settings[ 'theme_localization_type' ] == 1 && !defined( 'WPML_ST_VERSION' ) && !defined( 'WPML_DOING_UPGRADE' ) ) ) {
			global $sitepress_settings;
			$this->settings[ 'theme_localization_type' ] = $sitepress_settings[ 'theme_localization_type' ] = 2;
		}

		//configure callbacks for plugin menu pages
		if ( defined( 'WP_ADMIN' ) && isset( $_GET[ 'page' ] ) && 0 === strpos( $_GET[ 'page' ], basename( ICL_PLUGIN_PATH ) . '/' ) ) {
			add_action( 'icl_menu_footer', array( $this, 'menu_footer' ) );
		}

		//Run only if existing content language has been verified, and is front-end or settings are not corrupted
		if ( ! empty( $this->settings['existing_content_language_verified'] ) ) {
			add_action( 'wpml_verify_post_translations', array(
				$this,
				'verify_post_translations_action'
			), 10, 1 );

			if ($this->settings[ 'language_negotiation_type' ] == 2) {
				add_filter( 'allowed_redirect_hosts', array( $this, 'allowed_redirect_hosts' ) );
			}

			//reorder active language to put 'this_lang' in front
            $active_languages = $this->get_active_languages();
			foreach ( $active_languages as $k => $active_lang ) {
				if ( $k === $this->this_lang ) {
					unset( $this->active_languages[ $k ] );
					$this->active_languages = array_merge( array( $k => $active_lang ), $this->active_languages );
				}
			}

			add_filter( 'mod_rewrite_rules', array( $this, 'rewrite_rules_filter' ), 10 ,1 );

			if ( is_admin() &&
					$this->get_setting( 'setup_complete' ) &&
					( !isset( $_GET[ 'page' ] ) || !defined( 'WPML_ST_FOLDER' ) || $_GET[ 'page' ] != WPML_ST_FOLDER . '/menu/string-translation.php' ) && ( !isset( $_GET[ 'page' ] ) || !defined( 'WPML_TM_FOLDER' ) || $_GET[ 'page' ] != WPML_TM_FOLDER . '/menu/translations-queue.php' )
			) {
					// Admin language switcher goes to the WP admin bar
					if ( apply_filters( 'wpml_show_admin_language_switcher', true ) ) {
						add_action( 'wp_before_admin_bar_render', array( $this, 'admin_language_switcher' ) );
					} else {
						$this->this_lang = 'all';
					}
			}

			if ( !is_admin() && defined( 'DISQUS_VERSION' ) ) {
				include ICL_PLUGIN_PATH . '/modules/disqus.php';
			}
		}

		if ( $this->is_rtl() ) {
			$GLOBALS[ 'text_direction' ] = 'rtl';
		}

		if ( !wpml_is_ajax() && is_admin() && empty( $this->settings[ 'dont_show_help_admin_notice' ] ) ) {
			WPML_Troubleshooting_Terms_Menu::display_terms_with_suffix_admin_notice();

			if ( !$this->get_setting( 'setup_wizard_step' )
			     && strpos( filter_input( INPUT_GET, 'page', FILTER_SANITIZE_URL ), 'menu/languages.php' ) === false
			) {
				add_action( 'admin_notices', array( $this, 'help_admin_notice' ) );
			}
		}

		$short_v = implode( '.', array_slice( explode( '.', ICL_SITEPRESS_VERSION ), 0, 3 ) );
		if ( is_admin() && ( !isset( $this->settings[ 'hide_upgrade_notice' ] ) || $this->settings[ 'hide_upgrade_notice' ] != $short_v ) ) {
			add_action( 'admin_notices', array( $this, 'upgrade_notice' ) );
		}

		require ICL_PLUGIN_PATH . '/inc/template-constants.php';
		if ( defined( 'WPML_LOAD_API_SUPPORT' ) ) {
			require ICL_PLUGIN_PATH . '/inc/wpml-api.php';
		}

		add_action( 'wp_footer', array( $this, 'display_wpml_footer' ), 20 );

		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			add_action( 'xmlrpc_call', array( $this, 'xmlrpc_call_actions' ) );
			add_filter( 'xmlrpc_methods', array( $this, 'xmlrpc_methods' ) );
		}

		add_action( 'init', array( $this, 'set_up_language_selector' ) );

		global $pagenow;

		// set language to default and remove language switcher when in Taxonomy Translation page
		// If the page uses AJAX and the language must be forced to default, please use the
		// if ( $pagenow == 'admin-ajax.php' )above
		if ( is_admin()
		     && ( isset( $_GET[ 'page' ] )
		          && ( $_GET[ 'page' ] == ICL_PLUGIN_FOLDER . '/menu/taxonomy-translation.php'
		               || $_GET[ 'page' ] == ICL_PLUGIN_FOLDER . '/menu/menu-sync/menus-sync.php'
		               || $_GET[ 'page' ] == ICL_PLUGIN_FOLDER . '/menu/term-taxonomy-menus/taxonomy-translation-display.class.php' )
		          || ( $pagenow == 'admin-ajax.php'
		               && isset( $_POST[ 'action' ] )
		               && $_POST[ 'action' ] == 'wpml_tt_save_labels_translation' ) )
		) {
			$default_language = $this->get_admin_language();
			$this->switch_lang( $default_language, true );
			add_action( 'init', array( $this, 'remove_admin_language_switcher' ) );
		}

		/* Posts and new inline created terms, can only be saved in an active language.
		 * Also the content of the post-new.php should always be filtered for one specific
		 * active language, so to display the correct taxonomy terms for selection.
		 */
		if ( $pagenow == 'post-new.php' ) {
			if ( ! $this->is_active_language( $this->get_current_language() ) ) {
				$default_language = $this->get_admin_language();
				$this->switch_lang( $default_language, true );
			}
		}

		//Code to run when reactivating the plugin
		$recently_activated = $this->get_setting('just_reactivated');
		if($recently_activated) {
			add_action( 'init', array( $this, 'rebuild_language_information' ), 1000 );
		}
		if ( is_admin() ) {
			$this->post_edit_metabox = new WPML_Meta_Boxes_Post_Edit_HTML( $this, $this->post_translation );
		}
		do_action('wpml_after_init');
		do_action('wpml_loaded');
	}

	public function maybe_set_this_lang() {
		global $wpml_request_handler;

		if ( ! defined( 'WP_ADMIN' ) && isset( $_SERVER['HTTP_HOST'] ) && did_action( 'init' ) ) {
			require ICL_PLUGIN_PATH . '/inc/request-handling/redirection/wpml-frontend-redirection.php';
			$redirect_helper = _wpml_get_redirect_helper();
			$redirection     = new WPML_Frontend_Redirection( $this, $wpml_request_handler, $redirect_helper );
			$this->this_lang = $redirection->maybe_redirect();
		} else {
			$this->this_lang = $wpml_request_handler->get_requested_lang();
		}

		$wpml_request_handler->set_language_cookie( $this->this_lang );
	}

	function load_dependencies() {
		do_action('wpml_load_dependencies');
	}

	/**
	 * Sets up all term/taxonomy actions for use outside Translations Management or the Post Edit screen
	 */
	function set_term_filters_and_hooks(){
		add_filter( 'terms_clauses', array( $this, 'terms_clauses' ), 10, 4 );
		add_action( 'create_term', array( $this, 'create_term' ), 1, 3 );
		add_action( 'edit_term', array( $this, 'create_term' ), 1, 3 );
		add_filter( 'get_terms_args', array( $this, 'get_terms_args_filter' ), 10, 2 );
		add_filter( 'get_edit_term_link', array( $this, 'get_edit_term_link' ), 1, 4 );
		add_action( 'deleted_term_relationships', array( $this, 'deleted_term_relationships' ), 10, 2 );
		add_action('wp_ajax_icl_repair_broken_type_and_language_assignments', 'icl_repair_broken_type_and_language_assignments');
		// adjust queried categories and tags ids according to the language
		if ( (bool) $this->get_setting('auto_adjust_ids' ) ) {
			add_action( 'wp_list_pages_excludes', array( $this, 'adjust_wp_list_pages_excludes' ) );
			if ( ! is_admin() ) {
				add_filter( 'get_term', array( $this, 'get_term_adjust_id' ), 1, 1 );
				add_filter( 'category_link', array( $this, 'category_link_adjust_id' ), 1, 2 );
				add_filter( 'get_pages', array( $this, 'get_pages_adjust_ids' ), 1, 2 );
			}
		}
	}

	function remove_admin_language_switcher() {
		remove_action( 'wp_before_admin_bar_render', array( $this, 'admin_language_switcher' ) );
	}

	function rebuild_language_information() {
		$this->set_setting('just_reactivated', 0);
		$this->save_settings();
        /** @var TranslationManagement $iclTranslationManagement */
		global $iclTranslationManagement;
		if ( $iclTranslationManagement ) {
			$iclTranslationManagement->add_missing_language_information();
		}
	}

	function on_wp_init()
	{
		include ICL_PLUGIN_PATH . '/inc/translation-management/taxonomy-translation.php';
	}

	function setup()
	{
		$setup_complete = $this->get_setting('setup_complete');
		if(!$setup_complete) {
			$this->set_setting('setup_complete', false);
		}
		return $setup_complete;
	}

	public function user_lang_by_authcookie() {
		global $current_user;

		if ( !isset( $current_user ) ) {
			$username = '';
			if ( function_exists ( 'wp_parse_auth_cookie' ) ) {
				$cookie_data = wp_parse_auth_cookie ();
				$username    = isset( $cookie_data[ 'username' ] ) ? $cookie_data[ 'username' ] : null;
			}
			$user_obj = new WP_User( null, $username );
		} else {
			$user_obj = $current_user;
		}
		$user_id   = isset( $user_obj->ID ) ? $user_obj->ID : 0;
		$user_lang = $this->get_user_admin_language ( $user_id );
		$user_lang = $user_lang ? $user_lang : $this->get_current_language ();

		return $user_lang;
	}

    function get_current_user() {
        global $current_user;

        return $current_user !== null ? $current_user : new WP_User();
    }

	function ajax_setup()
	{
		require ICL_PLUGIN_PATH . '/ajax.php';
	}

	function check_if_admin_action_from_referer() {
		$referer = isset( $_SERVER[ 'HTTP_REFERER' ] ) ? $_SERVER[ 'HTTP_REFERER' ] : '';

		return strpos ( $referer, strtolower ( admin_url () ) ) === 0;
	}

	function configure_custom_column() {
		global $pagenow, $wp_post_types;

		if ( $pagenow === 'edit.php'
		     || $pagenow === 'edit-pages.php'
		     || ( $pagenow === 'admin-ajax.php'
		          && ( filter_input ( INPUT_POST, 'action' ) === 'inline-save'
		               || filter_input ( INPUT_GET, 'action' ) === 'fetch-list'
		          ) )
		) {
			$post_type = isset( $_REQUEST[ 'post_type' ] ) ? $_REQUEST[ 'post_type' ] : 'post';
			switch ( $post_type ) {
				case 'post':
				case 'page':
					add_filter ( 'manage_' . $post_type . 's_columns', array( $this, 'add_posts_management_column' ) );
					add_filter ( 'manage_' . $post_type . 's_custom_column', array( $this, 'add_content_for_posts_management_column' ) );
					break;
				default:
					if ( in_array ( $post_type, array_keys ( $this->get_translatable_documents () ), true ) ) {
						add_filter (
							'manage_' . $post_type . '_posts_columns',
							array( $this, 'add_posts_management_column' )
						);
						if ( $wp_post_types[ $post_type ]->hierarchical ) {
							add_action (
								'manage_pages_custom_column',
								array( $this, 'add_content_for_posts_management_column' )
							);
							add_action (
								'manage_posts_custom_column',
								array( $this, 'add_content_for_posts_management_column' )
							); // add this too - for more types plugin
						} else {
							add_action (
								'manage_posts_custom_column',
								array( $this, 'add_content_for_posts_management_column' )
							);
						}
					}
			}
		}
	}

	function the_posts( $posts ) {
		global $wpml_post_translations;

		$ids = array();
		foreach ( $posts as $single_post ) {
			$ids[ ] = $single_post->ID;
		}

		$wpml_post_translations->prefetch_ids ( $ids );

		if ( !is_admin() && isset( $this->settings[ 'show_untranslated_blog_posts' ] ) && $this->settings[ 'show_untranslated_blog_posts' ] && $this->get_current_language() != $this->get_default_language() ) {
			// show untranslated posts

			global $wp_query;
			$default_language = $this->get_default_language();
			$current_language = $this->get_current_language();

			$debug_backtrace = $this->get_backtrace(4, true); //Limit to first 4 stack frames, since 3 is the highest index we use

			/** @var $custom_wp_query WP_Query */
			$custom_wp_query = isset( $debug_backtrace[ 3 ][ 'object' ] ) ? $debug_backtrace[ 3 ][ 'object' ] : false;
			//exceptions
			if ( ( $current_language == $default_language )
				 // original language
				 ||
				 ( $wp_query != $custom_wp_query )
				 // called by a custom query
				 ||
				 ( !$custom_wp_query->is_posts_page && !$custom_wp_query->is_home )
				 // not the blog posts page
				 ||
				 $wp_query->is_singular
				 //is singular
				 ||
				 !empty( $custom_wp_query->query_vars[ 'category__not_in' ] )
				 //|| !empty($custom_wp_query->query_vars['category__in'])
				 //|| !empty($custom_wp_query->query_vars['category__and'])
				 ||
				 !empty( $custom_wp_query->query_vars[ 'tag__not_in' ] ) ||
				 !empty( $custom_wp_query->query_vars[ 'post__in' ] ) ||
				 !empty( $custom_wp_query->query_vars[ 'post__not_in' ] ) ||
				 !empty( $custom_wp_query->query_vars[ 'post_parent' ] )
			) {
				return $posts;
			}

			// get the posts in the default language instead
			$this_lang       = $this->this_lang;
			$this->this_lang = $default_language;

			remove_filter( 'the_posts', array( $this, 'the_posts' ) );

			$custom_wp_query->query_vars[ 'suppress_filters' ] = 0;

			if ( isset( $custom_wp_query->query_vars[ 'pagename' ] ) && !empty( $custom_wp_query->query_vars[ 'pagename' ] ) ) {
				if ( isset( $custom_wp_query->queried_object_id ) && !empty( $custom_wp_query->queried_object_id ) ) {
					$page_id = $custom_wp_query->queried_object_id;
				} else {
					// urlencode added for languages that have urlencoded post_name field value
					$custom_wp_query->query_vars[ 'pagename' ] = urlencode( $custom_wp_query->query_vars[ 'pagename' ] );
					$page_id                                   = $this->wpdb->get_var(
						$this->wpdb->prepare("SELECT ID FROM {$this->wpdb->posts}
                                                                                    WHERE post_name = %s
                                                                                      AND post_type='page'",
                                                                                   $custom_wp_query->query_vars['pagename'] ) );
				}
				if ( $page_id ) {
					$tr_page_id = icl_object_id( $page_id, 'page', false, $default_language );
					if ( $tr_page_id ) {
						$custom_wp_query->query_vars[ 'pagename' ] = $this->wpdb->get_var( $this->wpdb->prepare("SELECT post_name
                                                                                                     FROM {$this->wpdb->posts}
                                                                                                     WHERE ID = %d ",
                                                                                                    $tr_page_id ) );
					}
				}
			}

			// look for posts without translations
			if ( $posts ) {
				$pids = false;
				foreach ( $posts as $p ) {
					$pids[ ] = $p->ID;
				}
				if ( $pids ) {
					$trids = $this->wpdb->get_col( $this->wpdb->prepare("
						SELECT trid
						FROM {$this->wpdb->prefix}icl_translations
						WHERE element_type='post_post'
						  AND element_id IN (" . wpml_prepare_in( $pids, '%d' )  . ")
						  AND language_code = %s", $this_lang ) );

					if ( !empty( $trids ) ) {
						$posts_not_translated = $this->wpdb->get_col( "
							SELECT element_id, COUNT(language_code) AS c
							FROM {$this->wpdb->prefix}icl_translations
							WHERE trid IN (" . join( ',', $trids ) . ") GROUP BY trid HAVING c = 1
						" );
						if ( !empty( $posts_not_translated ) ) {
							$GLOBALS[ '__icl_the_posts_posts_not_translated' ] = $posts_not_translated;
							add_filter( 'posts_where', array( $this, '_posts_untranslated_extra_posts_where' ), 99 );
						}
					}
				}
			}

			//fix page for posts
			unset( $custom_wp_query->query_vars[ 'pagename' ] );
			unset( $custom_wp_query->query_vars[ 'page_id' ] );
			unset( $custom_wp_query->query_vars[ 'p' ] );

			$my_query = new WP_Query( $custom_wp_query->query_vars );

			add_filter( 'the_posts', array( $this, 'the_posts' ) );
			$this->this_lang = $this_lang;

			// create a map of the translated posts
			foreach ( $posts as $post ) {
				$trans_posts[ $post->ID ] = $post;
			}

			// loop original posts
			foreach ( $my_query->posts as $k => $post ) { // loop posts in the default language
				$trid         = $this->get_element_trid( $post->ID );
				$translations = $this->get_element_translations( $trid ); // get translations

				if ( isset( $translations[ $current_language ] ) ) { // if there is a translation in the current language
					if ( isset( $trans_posts[ $translations[ $current_language ]->element_id ] ) ) { //check the map of translated posts
						$my_query->posts[ $k ] = $trans_posts[ $translations[ $current_language ]->element_id ];
					} else { // check if the translated post exists in the database still
						$_post = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM {$this->wpdb->posts} WHERE ID = %d AND post_status='publish' LIMIT 1", $translations[ $current_language ]->element_id ) );
						if ( !empty( $_post ) ) {
							$_post                 = sanitize_post( $_post );
							$my_query->posts[ $k ] = $_post;
						} else {
							$my_query->posts[ $k ]->original_language = true;
						}
					}
				} else {
					$my_query->posts[ $k ]->original_language = true;
				}
			}
			if ( $custom_wp_query == $wp_query ) {
				$wp_query->max_num_pages = $my_query->max_num_pages;
			}
			$posts = array_values( array_unique( array_merge( $my_query->posts, $posts ), SORT_REGULAR ) );
			unset( $GLOBALS[ '__icl_the_posts_posts_not_translated' ] );
			remove_filter( 'posts_where', array( $this, '_posts_untranslated_extra_posts_where' ), 99 );
		}

		return $posts;
	}

	function _posts_untranslated_extra_posts_where( $where ) {

		return $where . ' OR ' . $this->wpdb->posts . '.ID IN (' . wpml_prepare_in( $GLOBALS['__icl_the_posts_posts_not_translated'],
		                                                                            '%d' ) . ') ';
	}

	function initialize_cache() {
		require_once ICL_PLUGIN_PATH . '/inc/cache.php';
		$this->icl_translations_cache  = new icl_cache();
		$this->icl_language_name_cache = new icl_cache( 'language_name', true );
	}

	public function set_admin_language( $admin_language = false ) {
		$default_language     = $this->get_default_language ();
		$this->admin_language = $admin_language ? $admin_language : $this->user_lang_by_authcookie ();

		$lang_codes = array_keys ( $this->get_languages () );
		if ( (bool) $this->admin_language === true && !in_array ( $this->admin_language, $lang_codes ) ) {
			delete_user_meta ( $this->get_current_user ()->ID, 'icl_admin_language' );
		}
		if ( empty( $this->settings[ 'admin_default_language' ] ) || !in_array (
				$this->settings[ 'admin_default_language' ],
				$lang_codes
			)
		) {
			$this->settings[ 'admin_default_language' ] = '_default_';
			$this->save_settings ();
		}

		if ( !$this->admin_language ) {
			$this->admin_language = $this->settings[ 'admin_default_language' ];
		}
		if ( $this->admin_language == '_default_' && $default_language ) {
			$this->admin_language = $default_language;
		}
	}

	function get_admin_language() {
		$current_user = $this->get_current_user();
		if ( isset( $current_user->ID ) && get_user_meta( $current_user->ID,
		                                                  'icl_admin_language_for_edit',
		                                                  true ) && $this->is_post_edit_screen()
		) {
			$admin_language = $this->get_current_language();
		} else {
			$admin_language = $this->user_lang_by_authcookie();
		}

		return $admin_language;
	}

	/**
	 * @return bool
	 */
	function is_post_edit_screen() {
		global $pagenow;

		$action = isset( $_GET['action'] ) ? $_GET['action'] : "";

		return $pagenow == 'post-new.php' || ( $pagenow == 'post.php' && ( 0 === strcmp( $action, 'edit' ) ) );
	}

	function get_user_admin_language_filter( $value, $user_id ) {
		$value = $this->get_user_admin_language ( $user_id );

		return $value;
	}

	function get_user_admin_language( $user_id, $reload = false ) {
		static $lang = array();

		$lang = $reload !== false ? array() : $lang;

		if ( !isset( $lang[ $user_id ] ) ) {
			$lang[ $user_id ] = get_user_meta ( $user_id, 'icl_admin_language_for_edit', true )
				? $this->get_current_language() : get_user_meta ( $user_id, 'icl_admin_language', true );
			if ( empty( $lang[ $user_id ] ) ) {
				$admin_default_language = $this->get_setting( 'admin_default_language' );
				if ( $admin_default_language ) {
						$lang[ $user_id ] = $admin_default_language;
				}
				if ( empty( $lang[ $user_id ] ) || '_default_' == $lang[ $user_id ] ) {
					$lang[ $user_id ] = $this->get_default_language();
				}
			}
		}

		return $lang[ $user_id ];
	}

	function administration_menu() {
		ICL_AdminNotifier::removeMessage( 'setup-incomplete' );
		$main_page = apply_filters( 'icl_menu_main_page', basename( ICL_PLUGIN_PATH ) . '/menu/languages.php' );
		$wpml_setup_is_complete = SitePress_Setup::setup_complete();
		if ( $wpml_setup_is_complete ) {
			add_menu_page( __( 'WPML', 'sitepress' ), __( 'WPML', 'sitepress' ), 'wpml_manage_languages', $main_page, null, ICL_PLUGIN_URL . '/res/img/icon16.png' );

			add_submenu_page( $main_page, __( 'Languages', 'sitepress' ), __( 'Languages', 'sitepress' ), 'wpml_manage_languages', basename( ICL_PLUGIN_PATH ) . '/menu/languages.php' );
			//By Gen, moved Translation management after language, because problems with permissions
			do_action( 'icl_wpml_top_menu_added' );

			$wpml_setup_is_complete = $this->get_setting( 'existing_content_language_verified' ) && 2 <= count( $this->get_active_languages() );
			if ( $wpml_setup_is_complete ) {
				add_submenu_page( $main_page, __( 'Theme and plugins localization', 'sitepress' ), __( 'Theme and plugins localization', 'sitepress' ), 'wpml_manage_theme_and_plugin_localization',
				                  basename( ICL_PLUGIN_PATH )
				                  . '/menu/theme-localization.php' );

				if ( ! defined( 'WPML_TM_VERSION' ) ) {
					add_submenu_page( $main_page, __( 'Translation options', 'sitepress' ), __( 'Translation options', 'sitepress' ), 'wpml_manage_translation_options', basename( ICL_PLUGIN_PATH ) . '/menu/translation-options.php' );
				}
			}

			$wpml_admin_menus_args = array(
				'existing_content_language_verified' => $this->get_setting( 'existing_content_language_verified' ),
				'active_languages_count'             => count( $this->get_active_languages() ),
				'wpml_setup_is_ok'                   => $wpml_setup_is_complete
			);
			do_action( 'wpml_admin_menus', $wpml_admin_menus_args );
		} else {
			$main_page = basename( ICL_PLUGIN_PATH ) . '/menu/languages.php';
			add_menu_page( __( 'WPML', 'sitepress' ), __( 'WPML', 'sitepress' ), 'manage_options', $main_page, null, ICL_PLUGIN_URL . '/res/img/icon16.png' );
			add_submenu_page( $main_page, __( 'Languages', 'sitepress' ), __( 'Languages', 'sitepress' ), 'wpml_manage_languages', $main_page );

			if ( ! $this->is_troubleshooting_page() && ! SitePress_Setup::languages_table_is_complete() ) {
				$troubleshooting_url  = admin_url( 'admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/troubleshooting.php' );
				$troubleshooting_link = '<a href="' . $troubleshooting_url . '" title="' . esc_attr( __( 'Troubleshooting', 'sitepress' ) ) . '">' . __( 'Troubleshooting', 'sitepress' ) . '</a>';
				$message = '';
				$message .= __( 'WPML is missing some records in the languages tables and it cannot fully work until this issue is fixed.', 'sitepress' );
				$message .= '<br />';
				$message .= sprintf( __( 'Please go to the %s page and click on %s to fix this problem.', 'sitepress' ), $troubleshooting_link, __( 'Fix languages tables', 'sitepress' ) );
				$message .= '<br />';
				$message .= '<br />';
				$message .= __( 'This warning will disappear once this issue is fixed.', 'sitepress' );
				ICL_AdminNotifier::removeMessage( 'setup-incomplete' );
				ICL_AdminNotifier::addMessage( 'setup-incomplete', $message, 'error', false, false, false, 'setup', true );
				ICL_AdminNotifier::displayMessages( 'setup' );
			}
		}

		add_submenu_page( $main_page, __( 'Support', 'sitepress' ), __( 'Support', 'sitepress' ), 'wpml_manage_support', ICL_PLUGIN_FOLDER . '/menu/support.php' );
		$this->troubleshooting_menu(ICL_PLUGIN_FOLDER . '/menu/support.php');
		$this->debug_information_menu(ICL_PLUGIN_FOLDER . '/menu/support.php');
	}

	private function troubleshooting_menu( $main_page ) {
		$submenu_slug = basename( ICL_PLUGIN_PATH ) . '/menu/troubleshooting.php';
			add_submenu_page( $main_page, __( 'Troubleshooting', 'sitepress' ), __( 'Troubleshooting', 'sitepress' ), 'wpml_manage_troubleshooting', $submenu_slug );

			return $submenu_slug;
	}

	private function debug_information_menu( $main_page ) {
		$submenu_slug = basename( ICL_PLUGIN_PATH ) . '/menu/debug-information.php';
		add_submenu_page( $main_page, __( 'Debug information', 'sitepress' ), __( 'Debug information', 'sitepress' ), 'wpml_manage_troubleshooting', $submenu_slug );
		return $submenu_slug;
	}

	// lower priority
	function administration_menu2() {
		$main_page = apply_filters( 'icl_menu_main_page', ICL_PLUGIN_FOLDER . '/menu/languages.php' );
		if ( $this->setup() ) {
			add_submenu_page( $main_page, __( 'Taxonomy Translation', 'sitepress' ), __( 'Taxonomy Translation', 'sitepress' ), 'wpml_manage_taxonomy_translation', ICL_PLUGIN_FOLDER . '/menu/taxonomy-translation.php' );
		}
	}

	function init_settings( $blog_id ) {
		global $sitepress_settings;

		if ( isset( $this->loaded_blog_id ) && $this->loaded_blog_id != $blog_id ) {
			remove_action( 'switch_blog', array( $this, 'init_settings' ) );
			wp_cache_add( $this->loaded_blog_id . 'icl_sitepress_settings', $sitepress_settings, 'sitepress_ms' );
			add_action( 'switch_blog', array( $this, 'init_settings' ), 10, 1 );
			$sitepress_settings = wp_cache_get( $blog_id . 'icl_sitepress_settings', 'sitepress_ms' );
			$sitepress_settings = (bool) $sitepress_settings === true ? $sitepress_settings : get_option( 'icl_sitepress_settings' );
		}
		$this->loaded_blog_id = $blog_id;

		return $sitepress_settings;
	}

	function save_settings( $settings = null ) {
		if ( ! is_null( $settings ) ) {
			foreach ( $settings as $k => $v ) {
				if ( is_array( $v ) ) {
					foreach ( $v as $k2 => $v2 ) {
						$this->settings[ $k ][ $k2 ] = $v2;
					}
				} else {
					$this->settings[ $k ] = $v;
				}
			}
		}
		if ( ! empty( $this->settings ) ) {
			update_option( 'icl_sitepress_settings', $this->settings );
		}
		do_action( 'icl_save_settings', $settings );
	}

	/**
	 * @since 3.1
	 */
	function get_settings() {
		return $this->settings;
	}

	function filter_get_setting($value, $key) {
		return $this->get_setting($key, $value);
	}

	/**
	 * @param string     $key
	 * @param mixed|bool $default
	 *
	 * @since 3.1
	 *
	 * @return bool|mixed
	 */
	function get_setting( $key, $default = false ) {
		return wpml_get_setting_filter( $default, $key );
	}

	function action_set_setting($key, $value, $save_now) {
		$this->set_setting($key, $value, $save_now);
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @param bool   $save_now	Immediately update the settings record in the DB
	 *
	 * @since 3.1
     */
    function set_setting($key, $value, $save_now = false) {
        icl_set_setting($key, $value, $save_now);
    }

	function get_user_preferences() {
		if ( ! isset( $this->user_preferences ) || ! $this->user_preferences ) {
			$this->user_preferences = get_user_meta( $this->get_current_user()->ID, '_icl_preferences', true );
		}
		if ( (is_array( $this->user_preferences) && $this->user_preferences == array(0 => false)) || !$this->user_preferences ) {
			$this->user_preferences = array();
		}
		if ( ! is_array( $this->user_preferences ) ) {
			$this->user_preferences = (array) $this->user_preferences;
		}

		return $this->user_preferences;
	}

	function set_user_preferences($value) {
		$this->user_preferences = $value;
	}

	function save_user_preferences()
	{
		update_user_meta( $this->get_current_user()->ID, '_icl_preferences', $this->user_preferences );
	}

	function get_option( $option_name )
	{
		return isset( $this->settings[ $option_name ] ) ? $this->settings[ $option_name ] : null;
	}

	function verify_settings() {

		$default_settings = array(
			'interview_translators'              => 1,
			'existing_content_language_verified' => 0,
			'language_negotiation_type'          => 3,
			'theme_localization_type'            => 1,
			'icl_lso_header'                     => 0,
			'icl_lso_link_empty'                 => 0,
			'icl_lso_flags'                      => 0,
			'icl_lso_native_lang'                => 1,
			'icl_lso_display_lang'               => 1,
			'sync_page_ordering'                 => 1,
			'sync_page_parent'                   => 1,
			'sync_page_template'                 => 1,
			'sync_ping_status'                   => 1,
			'sync_comment_status'                => 1,
			'sync_sticky_flag'                   => 1,
			'sync_password'                      => 1,
			'sync_private_flag'                  => 1,
			'sync_post_format'                   => 1,
			'sync_delete'                        => 0,
			'sync_delete_tax'                    => 0,
			'sync_post_taxonomies'               => 1,
			'sync_post_date'                     => 0,
			'sync_taxonomy_parents'              => 0,
			'translation_pickup_method'          => 0,
			'notify_complete'                    => 1,
			'translated_document_status'         => 1,
			'remote_management'                  => 0,
			'auto_adjust_ids'                    => 1,
			'alert_delay'                        => 0,
			'promote_wpml'                       => 0,
			'troubleshooting_options'            => array( 'http_communication' => 1 ),
			'automatic_redirect'                 => 0,
			'remember_language'                  => 24,
			'icl_lang_sel_type'                  => 'dropdown',
			'icl_lang_sel_stype'                 => 'classic',
			'icl_lang_sel_orientation'           => 'vertical',
			'icl_lang_sel_copy_parameters'       => '',
			'icl_widget_title_show'              => 1,
			'translated_document_page_url'       => 'auto-generate',
			'sync_comments_on_duplicates '       => 0,
			'seo'                                => array( 'head_langs' => 1, 'canonicalization_duplicates' => 1 ),
			'posts_slug_translation'             => array( 'on' => 0 ),
			'languages_order'                    => '',
			'urls'                               => array( 'directory_for_default_language' => 0, 'show_on_root' => '', 'root_html_file_path' => '', 'root_page' => 0, 'hide_language_switchers' => 1 ),
			'xdomain_data'						 => WPML_XDOMAIN_DATA_GET
		);

		//configured for three levels
		$update_settings = false;
		foreach ( $default_settings as $key => $value ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $k2 => $v2 ) {
					if ( is_array( $v2 ) ) {
						foreach ( $v2 as $k3 => $v3 ) {
							if ( !isset( $this->settings[ $key ][ $k2 ][ $k3 ] ) ) {
								$this->settings[ $key ][ $k2 ][ $k3 ] = $v3;
								$update_settings                      = true;
							}
						}
					} else {
						if ( !isset( $this->settings[ $key ][ $k2 ] ) ) {
							$this->settings[ $key ][ $k2 ] = $v2;
							$update_settings               = true;
						}
					}
				}
			} else {
				if ( !isset( $this->settings[ $key ] ) ) {
					$this->settings[ $key ] = $value;
					$update_settings        = true;
				}
			}
		}


		if ( $update_settings ) {
			$this->save_settings();
		}
	}

	function save_language_pairs()
	{
		// clear existing languages
		$lang_pairs = $this->settings[ 'language_pairs' ];
		if ( is_array( $lang_pairs ) ) {
			foreach ( $lang_pairs as $from => $to ) {
				$lang_pairs[ $from ] = array();
			}
		}

		// get the from languages
		$from_languages = array();
		foreach ( $_POST as $k => $v ) {
			if ( 0 === strpos( $k, 'icl_lng_from_' ) ) {
				$f                 = str_replace( 'icl_lng_from_', '', $k );
				$from_languages[ ] = $f;
			}
		}

		foreach ( $_POST as $k => $v ) {
			if ( 0 !== strpos( $k, 'icl_lng_' ) ) {
				continue;
			}
			if ( 0 === strpos( $k, 'icl_lng_to' ) ) {
				$t   = str_replace( 'icl_lng_to_', '', $k );
				$exp = explode( '_', $t );
				if ( in_array( $exp[ 0 ], $from_languages ) ) {
					$lang_pairs[ $exp[ 0 ] ][ $exp[ 1 ] ] = 1;
				}
			}
		}

		$iclsettings[ 'language_pairs' ] = $lang_pairs;
		$this->save_settings( $iclsettings );
	}

    function get_active_languages( $refresh = false ) {
	    /** @var WPML_Request  $wpml_request_handler*/
		global $wpml_request_handler;

        $in_language = defined( 'WP_ADMIN' ) && $this->admin_language ? $this->admin_language : null;
        $in_language = $in_language === null ? $this->get_current_language() : $in_language;
        $in_language = $in_language ? $in_language : $this->get_default_language();

        $active_languages       = $this->get_languages( $in_language, true, $refresh );
        $active_languages       = isset($active_languages[$in_language]) ? $active_languages
                                    : $this->get_languages( $in_language, true, true );
        $active_languages       = $active_languages ? $active_languages : array();
	    $this->active_languages = $wpml_request_handler->show_hidden()
		    ? $active_languages
		    : array_diff_key( $active_languages, array_fill_keys($this->get_setting ( 'hidden_languages', array()), 1) );

        return $this->active_languages;
    }

	/**
	 * Returns an input array of languages, that are in the form of associative arrays,
	 * ordered by the user-chosen language order
	 *
	 * @param array[] $languages
	 *
	 * @return array[]
	 */
	function order_languages( $languages ) {

		$ordered_languages = array();
		if ( is_array( $this->settings[ 'languages_order' ] ) ) {
			foreach ( $this->settings[ 'languages_order' ] as $code ) {
				if ( isset( $languages[ $code ] ) ) {
					$ordered_languages[ $code ] = $languages[ $code ];
					unset( $languages[ $code ] );
				}
			}
		} else {
			// initial save
			$iclsettings[ 'languages_order' ] = array_keys( $languages );
			$this->save_settings( $iclsettings );
		}

		if ( ! empty( $languages ) ) {
			foreach ( $languages as $code => $lang ) {
				$ordered_languages[ $code ] = $lang;
			}

		}

		return $ordered_languages;
	}

	/**
	 * @param $lang_code
	 * Checks if a given language code belongs to a currently active language.
	 * @return bool
	 */
	function is_active_language( $lang_code ) {
		$result = false;
		$active_languages = $this->get_active_languages();
		foreach ( $active_languages as $lang ) {
			if ( $lang_code == $lang[ 'code' ] ) {
				$result = true;
				break;
			}
		}

		return $result;
	}

    public function get_languages( $lang = false, $active_only = false, $refresh = false ) {
        if ( !$lang ) {
            $lang = $this->get_default_language();
        }

        if ( $active_only && !$refresh && $res = $this->icl_language_name_cache->get( 'in_language_' . $lang ) ) {
            return $res;
        }

        if ( !$active_only && !$refresh && $res = $this->icl_language_name_cache->get( 'all_language_' . $lang ) ) {
            return $res;
        }

		$setup_instance = wpml_get_setup_instance();

	    return $setup_instance->refresh_active_lang_cache($lang, $active_only);
    }

    function get_language_details( $code ) {
        if ( defined( 'WP_ADMIN' ) ) {
            $dcode = $this->admin_language;
        } else {
            $dcode = $code;
        }
        $details = $this->icl_language_name_cache->get( 'language_details_' . $code . $dcode );

        if ( !$details ) {
            $language_details = $this->get_languages( $dcode );
            $details = isset( $language_details[ $code ] ) ? $language_details[ $code ] : false;
        }

        return $details;
    }

	function get_language_code( $english_name ) {
		$query  = $this->wpdb->prepare( " SELECT code FROM {$this->wpdb->prefix}icl_languages WHERE english_name = %s LIMIT 1", $english_name );
		$code   = $this->wpdb->get_var( $query );

		return $code;
	}

	function get_default_language() {

		return isset( $this->settings[ 'default_language' ] ) ? $this->settings[ 'default_language' ] : false;
	}

	function get_current_language_filter()
	{
		return $this->get_current_language();
	}

	function get_current_language() {
		/** @var WPML_Request $wpml_request_handler */
		global $wpml_request_handler;

		$this->this_lang = $this->this_lang ? $this->this_lang : $wpml_request_handler->get_requested_lang();
		$this->this_lang = $this->this_lang ? $this->this_lang : $this->get_default_language();

		return apply_filters ( 'icl_current_language', $this->this_lang );
	}

	function switch_lang( $code = null, $cookie_lang = false ) {
		/** @var WPML_Request $wpml_request_handler */
        global $wpml_language_resolution, $wpml_request_handler;
		static $original_language, $original_language_cookie;

		$original_language = $original_language === null ? $this->get_current_language() : $original_language;

		if ( is_null( $code ) ) {
			$this->this_lang      = $original_language;

			// restore cookie language if case
			if ( !empty( $original_language_cookie ) ) {
				$this->update_language_cookie($original_language_cookie);
				$original_language_cookie = false;
			}
		} else {
			if ( $code === 'all' || in_array( $code, $wpml_language_resolution->get_active_language_codes(), true ) ) {
				$this->this_lang      = $code;
			}

			// override cookie language
			if ( $cookie_lang ) {
				$original_language_cookie = $wpml_request_handler->get_cookie_lang();
				$this->update_language_cookie($code);
			}
		}
		
		do_action( 'wpml_language_has_switched' );
	}

	function set_default_language( $code ) {
		$previous_default = $this->get_setting('default_language');
		$this->set_setting( 'default_language', $code);
		$this->set_setting('admin_default_language', $code);
		$this->save_settings();

		do_action('icl_after_set_default_language',$code, $previous_default);

		// change WP locale
		$locale = $this->get_locale( $code );
		if ( $locale ) {
			update_option( 'WPLANG', $locale );
		}

		return $code !== 'en' && !file_exists( ABSPATH . LANGDIR . '/' . $locale . '.mo' ) ? 1 : true;
	}

	function js_load()
	{
		global $pagenow, $wpdb, $wpml_post_translations, $wpml_term_translations;
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			$page                  = filter_input( INPUT_GET, 'page' );
			$page                  = $page !== null ? basename( $_GET['page'] ) : null;
			$page_basename         = $page === null ? false : preg_replace( '/[^\w-]/',
			                                                                '',
			                                                                str_replace( '.php', '', $page ) );
			$this->scripts_handler = new WPML_Admin_Scripts_Setup( $wpdb,
			                                                       $this,
			                                                       $wpml_post_translations,
			                                                       $wpml_term_translations,
			                                                       $page_basename );

			if ( isset( $_SERVER[ 'SCRIPT_NAME' ] ) && ( strpos( $_SERVER[ 'SCRIPT_NAME' ], 'post-new.php' ) || strpos( $_SERVER[ 'SCRIPT_NAME' ], 'post.php' ) ) ) {
				wp_register_script( 'sitepress-post-edit-tags', ICL_PLUGIN_URL . '/res/js/post-edit-terms.js', array( 'jquery', 'underscore' ) );
				$post_edit_messages = array(
					'switch_language_title' => __( 'You are about to change the language of {post_name}.', 'sitepress' ),
					'switch_language_alert' => __( 'All categories and tags will be translated if possible.', 'sitepress' ),
					'connection_loss_alert' => __( 'The following terms do not have a translation in the chosen language and will be disconnected from this post:', 'sitepress' ),
					'loading'               => __( 'Loading Language Data for {post_name}', 'sitepress' ),
					'switch_language_message' => __( 'Please make sure that you\'ve saved all the changes. We will have to reload the page.', 'sitepress' ),
					'switch_language_confirm' => __( 'Do you want to continue?', 'sitepress' ),
                    '_nonce'                  => wp_create_nonce('wpml_switch_post_lang_nonce')
				);
				wp_localize_script( 'sitepress-post-edit-tags', 'icl_post_edit_messages', $post_edit_messages );
				wp_enqueue_script( 'sitepress-post-edit-tags' );
			}

			if ( isset( $_SERVER[ 'SCRIPT_NAME' ] ) &&  strpos( $_SERVER[ 'SCRIPT_NAME' ], 'edit.php' ) ) {
				wp_register_script( 'sitepress-post-list-quickedit', ICL_PLUGIN_URL . '/res/js/post-list-quickedit.js', array( 'jquery') );
				wp_enqueue_script( 'sitepress-post-list-quickedit' );
			}

			wp_enqueue_script( 'sitepress-scripts', ICL_PLUGIN_URL . '/res/js/scripts.js', array( 'jquery' ), ICL_SITEPRESS_VERSION );
			if ( isset( $page_basename ) && file_exists( ICL_PLUGIN_PATH . '/res/js/' . $page_basename . '.js' ) ) {
				$dependencies = array();
				$localization = false;
				$color_picker_handler = 'wp-color-picker';
				switch ( $page_basename ) {
					case 'languages':
						$dependencies[ ] = $color_picker_handler;
						$dependencies[ ] = 'sitepress-scripts';
						break;
					case 'troubleshooting':
						$dependencies [ ] = 'jquery-ui-dialog';
						$localization = array(
							'object_name' => 'troubleshooting_strings',
							'strings'     => array(
								'success_1'       => __( "Post type and source language assignment have been fixed for ", 'sitepress' ),
								'success_2'       => __( " elements", 'sitepress' ),
								'no_problems'     => __( "No errors were found in the assignment of post types." ),
								'suffixesRemoved' => __( "Language suffixes were removed from the selected terms." ),
								'done'            => __( 'Done', 'sitepress' ),
								'termNamesNonce'  => wp_create_nonce('update_term_names_nonce'),
								'cacheClearNonce' => wp_create_nonce('cache_clear'),
							)
						);
						wp_enqueue_style("wp-jquery-ui-dialog");
						break;
				}
				$handle = 'sitepress-' . $page_basename;
				wp_register_script( $handle, ICL_PLUGIN_URL . '/res/js/' . $page_basename . '.js', $dependencies, ICL_SITEPRESS_VERSION );
				if ( $localization ) {
					wp_localize_script( $handle, $localization[ 'object_name' ], $localization[ 'strings' ] );
				}
				if(in_array($color_picker_handler, $dependencies)) {
					wp_enqueue_style( $color_picker_handler );
				}
				wp_enqueue_script( $handle );
			}

			if ( $pagenow == 'edit.php' ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'language_filter' ) );
			}

			if ( !wp_style_is( 'toolset-font-awesome', 'registered' ) ) { // check if styles are already registered
				wp_register_style( 'toolset-font-awesome', ICL_PLUGIN_URL . '/res/css/font-awesome.min.css', null, ICL_SITEPRESS_VERSION ); // register if not
			}
			wp_enqueue_style( 'toolset-font-awesome' ); // enqueue styles
			wp_enqueue_style( 'wpml-select-2', ICL_PLUGIN_URL . '/lib/select2/select2.css' );

		}
	}

	function front_end_js()
	{
		if ( defined( 'ICL_DONT_LOAD_LANGUAGES_JS' ) && ICL_DONT_LOAD_LANGUAGES_JS ) {
			return;
		}
		wp_register_script( 'sitepress', ICL_PLUGIN_URL . '/res/js/sitepress.js', false );
		wp_enqueue_script( 'sitepress' );

        $vars = array(
            'current_language'  => $this->this_lang,
            'icl_home'          => $this->language_url(),
            'ajax_url'          => $this->convert_url( admin_url('admin-ajax.php'), $this->this_lang ),
            'url_type'          => $this->settings['language_negotiation_type']
        );

		wp_localize_script( 'sitepress', 'icl_vars', $vars );
	}

	function rtl_fix()
	{
		global $wp_styles;
		if ( !empty( $wp_styles ) && $this->is_rtl() ) {
			$wp_styles->text_direction = 'rtl';
		}
	}

	function process_forms() {

		if ( isset( $_POST[ 'icl_post_action' ] ) ) {
			switch ( $_POST[ 'icl_post_action' ] ) {
				case 'save_theme_localization':
					$locales = array();
					foreach ( $_POST as $k => $v ) {
						if ( 0 !== strpos( $k, 'locale_file_name_' ) || !trim( $v ) ) {
							continue;
						}
						$locales[ str_replace( 'locale_file_name_', '', $k ) ] = $v;
					}
					if ( !empty( $locales ) ) {
						$this->set_locale_file_names( $locales );
					}
					break;
			}
			return;
		}

		if ( wp_verify_nonce(
			(string)filter_input( INPUT_POST, 'icl_initial_languagenonce', FILTER_SANITIZE_STRING ),
			'icl_initial_language'
		) ) {
			$setup_instance = wpml_get_setup_instance ();
			$first_lang = filter_input ( INPUT_POST, 'icl_initial_language_code', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$this->admin_language = $first_lang;
			$setup_instance->finish_step1($first_lang);
		} elseif ( wp_verify_nonce(
			(string)filter_input( INPUT_POST, 'icl_language_pairs_formnounce', FILTER_SANITIZE_STRING ),
			'icl_language_pairs_form'
		) ) {
			$this->save_language_pairs();

			$this->settings[ 'content_translation_languages_setup' ] = 1;
			// Move onto the site description page
			$this->settings[ 'content_translation_setup_wizard_step' ] = 2;

			$this->settings[ 'website_kind' ]          = 2;
			$this->settings[ 'interview_translators' ] = 1;

			$this->save_settings();

		} elseif ( wp_verify_nonce(
			(string)filter_input( INPUT_POST, 'icl_site_description_wizardnounce', FILTER_SANITIZE_STRING ),
			'icl_site_description_wizard'
		) ) {
			if ( isset( $_POST[ 'icl_content_trans_setup_back_2' ] ) ) {
				// back button.
				$this->settings[ 'content_translation_languages_setup' ]   = 0;
				$this->settings[ 'content_translation_setup_wizard_step' ] = 1;
				$this->save_settings();
			} elseif ( isset( $_POST[ 'icl_content_trans_setup_next_2' ] ) || isset( $_POST[ 'icl_content_trans_setup_next_2_enter' ] ) ) {
				// next button.
				$description = $_POST[ 'icl_description' ];
				if ( $description == "" ) {
					$_POST[ 'icl_form_errors' ] = __( 'Please provide a short description of the website so that translators know what background is required from them.', 'sitepress' );
				} else {
					$this->settings[ 'icl_site_description' ]                  = $description;
					$this->settings[ 'content_translation_setup_wizard_step' ] = 3;
					$this->save_settings();
				}
			}
		}
	}

	function post_edit_language_options() {
        /** @var TranslationManagement $iclTranslationManagement */
		global $post, $iclTranslationManagement, $post_new_file, $post_type_object;

		if(!isset($post) || !$this->settings['setup_complete']) return;

		if ( current_user_can( 'manage_options' ) ) {
			add_meta_box( 'icl_div_config', __( 'Multilingual Content Setup', 'sitepress' ), array( $this, 'meta_box_config' ), $post->post_type, 'normal', 'low' );
		}

		if ( filter_input(INPUT_POST, 'icl_action' ) === 'icl_mcs_inline' ) {
			$custom_post_type = filter_input(INPUT_POST, 'custom_post');
			if ( !in_array($custom_post_type, array( 'post', 'page' ) ) ) {
				$translate = (int)filter_input(INPUT_POST, 'translate');
				$iclsettings[ 'custom_posts_sync_option' ][ $custom_post_type ] = $translate;
				if ( $translate ) {
					$this->verify_post_translations( $custom_post_type );
				}
			}

			$custom_taxs_off = (array)filter_input(INPUT_POST, 'custom_taxs_off', FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY);
			$custom_taxs_on = (array)filter_input(INPUT_POST, 'custom_taxs_on', FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY);
			$tax_sync_options = array_merge(array_fill_keys($custom_taxs_on, 1), array_fill_keys($custom_taxs_off, 0));
			foreach ( $tax_sync_options as $key => $setting ) {
				$iclsettings[ 'taxonomies_sync_option' ][ $key ] = $setting;
				if($setting){
					$this->verify_taxonomy_translations( $key );
				}
			}

			$cf_names = (array)filter_input(INPUT_POST, 'cfnames', FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY);
			$cf_vals = (array)filter_input(INPUT_POST, 'cfvals', FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY);
			if ( in_array ( 1, $cf_vals ) === true ) {
				global $wpml_post_translations;
				$original_post_id = $wpml_post_translations->get_original_element ( $post->ID, true );
				$translations     = array_diff($wpml_post_translations->get_element_translations ( $original_post_id ), array($original_post_id));
			}
			foreach ( $cf_names as $k => $v ) {
				$custom_field_name    = base64_decode ( $v );
				$cf_translation_state = isset( $cf_vals[ $k ] ) ? (int) $cf_vals[ $k ] : 0;
				$iclTranslationManagement->settings[ 'custom_fields_translation' ][ $custom_field_name ] = $cf_translation_state;
				$iclTranslationManagement->save_settings();

				// sync the custom fields for the current post
				if ( 1 === $cf_translation_state ) {
					/**
					 * @var int[] $translations
					 * @var int $original_post_id
					 */
					foreach ( $translations as $translated_id ) {
						$this->_sync_custom_field($original_post_id, $translated_id, $custom_field_name);
					}
				}
			}
			if ( !empty( $iclsettings ) ) {
				$this->save_settings( $iclsettings );
			}
		}

		$post_types = array_keys( $this->get_translatable_documents() );
		if ( in_array( $post->post_type, $post_types ) ) {
			add_meta_box( 'icl_div', __( 'Language', 'sitepress' ), array( $this, 'meta_box' ), $post->post_type, 'side', 'high' );
		}

		//Fix the "Add new" button adding the language argument, so to create new content in the same language
		if(isset($post_new_file) && isset($post_type_object) && $this->is_translated_post_type($post_type_object->name)) {
			$post_language = $this->get_language_for_element($post->ID, 'post_' . $post_type_object->name);
			$post_new_file = add_query_arg(array('lang' => $post_language), $post_new_file);
		}
	}

	function set_element_language_details_action( $args ) {
		$element_id           = $args[ 'element_id' ];
		$element_type         = isset( $args[ 'element_type' ] ) ? $args[ 'element_type' ] : 'post_post';
		$trid                 = $args[ 'trid' ];
		$language_code        = $args[ 'language_code' ];
		$source_language_code = isset( $args[ 'source_language_code' ] ) ? $args[ 'source_language_code' ] : null;
		$check_duplicates     = isset( $args[ 'check_duplicates' ] ) ? $args[ 'check_duplicates' ] : true;
		$result = $this->set_element_language_details( $element_id, $element_type, $trid, $language_code, $source_language_code, $check_duplicates );
		$args[ 'result' ] = $result;
	}

	/**
	 * @param int         $el_id
	 * @param string      $el_type
	 * @param int         $trid
	 * @param string      $language_code
	 * @param null|string $src_language_code
	 * @param bool        $check_duplicates
	 *
	 * @return bool|int|null|string
	 */
	function set_element_language_details( $el_id, $el_type = 'post_post', $trid, $language_code, $src_language_code = null, $check_duplicates = true ) {
		$setter = $this->get_language_setter();

		return $setter->set_element_language_details (
			$el_id,
			$el_type,
			$trid,
			$language_code,
			$src_language_code,
			$check_duplicates
		);
	}

	public function get_language_setter() {
		global $wpml_term_translations, $wpml_post_translations;

		if ( ! $this->language_setter ) {
			$this->language_setter = new WPML_Set_Language( $this,
			                                                $this->wpdb,
			                                                $wpml_post_translations,
			                                                $wpml_term_translations );
		}

		return $this->language_setter;
	}

	function delete_element_translation( $trid, $element_type, $language_code = false ) {
		$result = false;

		if ( $trid !== false && is_numeric( $trid ) && $element_type !== false && is_string( $trid ) ) {
			$delete_where   = array( 'trid' => $trid, 'element_type' => $element_type );
			$delete_formats = array( '%d', '%s' );

			if ( $language_code ) {
				$delete_where[ 'language_code' ] = $language_code;
				$delete_formats[ ]               = '%s';
			}

			$result = $this->wpdb->delete( $this->wpdb->prefix . 'icl_translations', $delete_where, $delete_formats );
			$this->icl_translations_cache->clear();
		}

		return $result;
	}

	function get_element_language_details( $el_id, $el_type = 'post_post' ) {
		$details = false;
		if ( $el_id ) {
			if ( strpos( $el_type, 'post_' ) === 0 ) {
				global $wpml_post_translations;
				$details = $wpml_post_translations->get_element_language_details( $el_id, OBJECT );
			}
			if ( strpos( $el_type, 'tax_' ) === 0 ) {
				/** @var WPML_Term_Translation $wpml_term_translations */
				global $wpml_term_translations;
				$details = $wpml_term_translations->get_element_language_details( $el_id, OBJECT );
			}
			if ( ! $details ) {
				$cache_key      = $el_id . ':' . $el_type;
				$cache_group    = 'element_language_details';
				$cached_details = wp_cache_get( $cache_key, $cache_group );
				if ( $cached_details ) {
					return $cached_details;
				}
				if ( isset( $this->icl_translations_cache ) && $this->icl_translations_cache->has_key( $el_id . $el_type ) ) {
					return $this->icl_translations_cache->get( $el_id . $el_type );
				}
				$details_query = "
				SELECT trid, language_code, source_language_code
				FROM {$this->wpdb->prefix}icl_translations
				WHERE element_id=%d AND element_type=%s
				";
				$details_prepare = $this->wpdb->prepare( $details_query, array( $el_id, $el_type ) );
				$details              = $this->wpdb->get_row( $details_prepare );
				if ( isset( $this->icl_translations_cache ) ) {
					$this->icl_translations_cache->set( $el_id . $el_type, $details );
				}

				wp_cache_add( $cache_key, $details, $cache_group );
			}
		}

		return $details;
	}

	public function _sync_custom_field( $post_id_from, $post_id_to, $meta_key ) {
		$sql         = "SELECT meta_value FROM {$this->wpdb->postmeta} WHERE post_id=%d AND meta_key=%s";
		$values_from = $this->wpdb->get_col ( $this->wpdb->prepare ( $sql, array( $post_id_from, $meta_key ) ) );
		$values_to   = $this->wpdb->get_col ( $this->wpdb->prepare ( $sql, array( $post_id_to, $meta_key ) ) );

		$removed = array_diff( $values_to, $values_from );
		foreach ( $removed as $v ) {
			$delete_prepared = $this->wpdb->prepare( "DELETE FROM {$this->wpdb->postmeta}
												WHERE post_id=%d
												AND meta_key=%s
												AND meta_value=%s",
			                                   array($post_id_to, $meta_key, $v) );
			$this->wpdb->query( $delete_prepared );
		}

		$added = array_diff( $values_from, $values_to );
		foreach ( $added as $v ) {
			$insert_prepared = $this->wpdb->prepare( "INSERT INTO {$this->wpdb->postmeta}(post_id, meta_key, meta_value)
												VALUES(%d, %s, %s)",
			                                   array($post_id_to, $meta_key, $v) );
			$this->wpdb->query( $insert_prepared );
		}
	}

	function copy_custom_fields( $post_id_from, $post_id_to ) {
		$cf_copy = array();

		if ( isset( $this->settings[ 'translation-management' ][ 'custom_fields_translation' ] ) ) {
			foreach ( $this->settings[ 'translation-management' ][ 'custom_fields_translation' ] as $meta_key => $option ) {
				if ( $option == 1 ) {
					$cf_copy[ ] = $meta_key;
				}
			}
		}

		foreach ( $cf_copy as $meta_key ) {
			$meta_from = get_post_meta($post_id_from, $meta_key) ;
			$meta_to = get_post_meta($post_id_to, $meta_key) ;
			if($meta_from || $meta_to) {
				$this->_sync_custom_field( $post_id_from, $post_id_to, $meta_key );
			}
		}

	}

	/**
     * @deprecated Since WPML 3.1.9
	 *
	 * @param $meta_id
	 * @param $object_id
	 * @param $meta_key
	 * @param $_meta_value
	 */
	function update_post_meta( $meta_id, $object_id, $meta_key, $_meta_value ) {
		return;
	}

	/**
     * @deprecated Since WPML 3.1.9
     *
     * @param $meta_id
	 */
	function delete_post_meta( $meta_id ) {
		return;
	}

	/* Custom fields synchronization - END */

	function get_element_translations_filter( $value, $trid, $el_type = 'post_post', $skip_empty = false, $all_statuses = false, $skip_cache = false ) {
		return $this->get_element_translations( $trid, $el_type, $skip_empty, $all_statuses, $skip_cache );
	}


	public function get_original_element_id_filter($empty, $element_id, $element_type = 'post_post') {
		$original_element_id = $this->get_original_element_id($element_id, $element_type);

		return $original_element_id;
	}

	function is_original_content_filter($default = false, $element_id, $element_type = 'post_post') {
		$is_original_content = $default;

		$trid = $this->get_element_trid($element_id, $element_type);

		$translations = $this->get_element_translations($trid, $element_type);

		if($translations) {
			foreach($translations as $language_code => $translation) {
				if($translation->element_id == $element_id) {
					$is_original_content = $translation->original;
					break;
				}
			}
		}

		return $is_original_content;
	}

	/**
	 * @param int    $trid
	 * @param string $el_type Use comment, post, page, {custom post time name}, nav_menu, nav_menu_item, category, post_tag, etc. (prefixed with 'post_', 'tax_', or nothing for 'comment')
	 * @param bool   $skip_empty
	 * @param bool   $all_statuses
	 * @param bool   $skip_cache
	 *
	 * @return array|bool|mixed
	 */
	function get_element_translations( $trid, $el_type = 'post_post', $skip_empty = false, $all_statuses = false, $skip_cache = false ) {
		$cache_key_args = array_filter( array( $trid, $el_type, $skip_empty, $all_statuses ) );
		$cache_key = md5(wp_json_encode( $cache_key_args ));
		$cache_group = 'element_translations';
		$cache_found = false;

		$cache = new WPML_WP_Cache( $cache_group );
		$temp_elements = $cache->get( $cache_key, $cache_found );
		if( !$skip_cache && $cache_found ) return $temp_elements;

		$translations = array();
		$sel_add      = '';
		$where_add    = '';
		if ( $trid ) {
			if ( 0 === strpos( $el_type, 'post_' ) ) {
				$sel_add     = ', p.post_title, p.post_status';
				$join_add    = " LEFT JOIN {$this->wpdb->posts} p ON t.element_id=p.ID";
				$groupby_add = "";
				if ( ! is_admin() && empty( $all_statuses ) && $el_type !== 'post_attachment' ) {
					// the current user may not be the admin but may have read private post/page caps!
					if ( current_user_can( 'read_private_pages' ) || current_user_can( 'read_private_posts' ) ) {
						$where_add .= " AND (p.post_status = 'publish' OR p.post_status = 'private' OR p.post_status = 'pending')";
						$where_add .= " AND (p.post_status = 'publish' OR p.post_status = 'private' OR p.post_status = 'pending')";
					} else {
						$where_add .= " AND (";
						$where_add .= "p.post_status = 'publish' OR p.post_status = 'pending' ";
						if ( $uid = $this->get_current_user()->ID ) {
							$where_add .= $this->wpdb->prepare(" OR (post_status in ('draft', 'private', 'pending') AND  post_author = %d)", $uid);
						}
						$where_add .= ") ";
					}
				}

			} elseif ( preg_match( '#^tax_(.+)$#', $el_type ) ) {
				$sel_add     = ', tm.name, tm.term_id, COUNT(tr.object_id) AS instances';
				$join_add    = " LEFT JOIN {$this->wpdb->term_taxonomy} tt ON t.element_id=tt.term_taxonomy_id
							  LEFT JOIN {$this->wpdb->terms} tm ON tt.term_id = tm.term_id
							  LEFT JOIN {$this->wpdb->term_relationships} tr ON tr.term_taxonomy_id=tt.term_taxonomy_id
							  ";
				$groupby_add = "GROUP BY tm.term_id";
			}
            $where_add .= $this->wpdb->prepare( " AND t.trid=%d ", $trid );

			if ( !isset( $join_add ) ) {
				$join_add = "";
			}
			if ( !isset( $groupby_add ) ) {
				$groupby_add = "";
			}

			$query = "
				SELECT t.translation_id, t.language_code, t.element_id, t.source_language_code, NULLIF(t.source_language_code, '') IS NULL AS original {$sel_add}
				FROM {$this->wpdb->prefix}icl_translations t
					 {$join_add}
				WHERE 1 {$where_add}
				{$groupby_add}
			";

			$ret = $this->wpdb->get_results( $query );

			foreach ( $ret as $t ) {
				if ( ( preg_match( '#^tax_(.+)$#', $el_type ) ) && $t->instances == 0 && !_icl_tax_has_objects_recursive( $t->element_id ) && $skip_empty ) {
					continue;
				}


				$cached_object_key = $t->element_id . '#' . $el_type . '#0#' . $t->language_code;
				wp_cache_set( $cached_object_key, $cached_object_key, 'icl_object_id' );

				$translations[ $t->language_code ] = $t;
			}

		}

		if($translations) {
			$cache->set( $cache_key, $translations );
		}
		return $translations;
	}

	static function get_original_element_id($element_id, $element_type = 'post_post', $skip_empty = false, $all_statuses = false, $skip_cache = false) {
		$cache_key_args = array_filter( array( $element_id, $element_type, $skip_empty, $all_statuses ) );
		$cache_key = md5(wp_json_encode( $cache_key_args ));
		$cache_group = 'original_element';

		$temp_elements = $skip_cache ? false : wp_cache_get($cache_key, $cache_group);
		if($temp_elements) return $temp_elements;

		global $sitepress;

		$original_element_id = false;

		$trid = $sitepress->get_element_trid($element_id, $element_type);
		if($trid) {
			$element_translations = $sitepress->get_element_translations($trid, $element_type,$skip_empty,$all_statuses,$skip_cache);

			foreach($element_translations as $element_translation) {
				if($element_translation->original) {
					$original_element_id = $element_translation->element_id;
					break;
				}
			}
		}

		if($original_element_id) {
			wp_cache_set($cache_key, $original_element_id, $cache_group);
		}
		return $original_element_id;
	}

	/**
	 * @param int    $element_id Use term_taxonomy_id for taxonomies, post_id for posts
	 * @param string $el_type    Use comment, post, page, {custom post time name}, nav_menu, nav_menu_item, category, post_tag, etc. (prefixed with 'post_', 'tax_', or nothing for 'comment')
	 *
	 * @return bool|mixed|null|string
	 */
	function get_element_trid( $element_id, $el_type = 'post_post' ) {
		if ( strpos ( $el_type, 'tax_' ) === 0 ) {
			/** @var WPML_Term_Translation $wpml_term_translations */
			global $wpml_term_translations;

			return $wpml_term_translations->get_element_trid ( $element_id );
		} elseif ( strpos ( $el_type, 'post_' ) === 0 ) {
			global $wpml_post_translations;

			return $wpml_post_translations->get_element_trid ( $element_id );
		} else {
			$cache_key = $element_id . ':' . $el_type;
			$cache_group = 'element_trid';
			$temp_trid = wp_cache_get( $cache_key, $cache_group );
			if ( (bool) $temp_trid === true ) {
				return $temp_trid;
			}

			$trid_prepared = $this->wpdb->prepare(
				"SELECT trid FROM {$this->wpdb->prefix}icl_translations WHERE element_id=%d AND element_type=%s",
				array( $element_id, $el_type )
			);

			$trid = $this->wpdb->get_var( $trid_prepared );

			if ( $trid ) {
				wp_cache_add( $cache_key, $trid, $cache_group );
			}
		}

		return $trid;
	}

	/**
	 * @param int $trid
	 *
	 * @return int|bool
	 */
	static function get_original_element_id_by_trid( $trid ) {
		global $wpdb;

		if ( (bool) $trid === true ) {
			$original_element_id_prepared = $wpdb->prepare( "SELECT element_id
															 FROM {$wpdb->prefix}icl_translations
															 WHERE trid=%d
															  AND source_language_code IS NULL
															 LIMIT 1",
															$trid );

			$element_id = $wpdb->get_var( $original_element_id_prepared );
		} else {
			$element_id = false;
		}

		return $element_id;
	}

	static function get_source_language_by_trid( $trid ) {
		global $wpdb;

		$source_language = null;
		if ( (bool) $trid === true ) {
			
			$cache = new WPML_WP_Cache( 'get_source_language_by_trid' );
			$found = false;
			$source_language = $cache->get( $trid, $found );
			if ( ! $found ) {
				$source_language_prepared = $wpdb->prepare( "
																SELECT language_code
																FROM {$wpdb->prefix}icl_translations
																WHERE trid=%d
																	AND source_language_code IS NULL
																LIMIT 1",
					$trid );
	
				$source_language = $wpdb->get_var( $source_language_prepared );
				
				$cache->set( $trid, $source_language );
			}
		}

		return $source_language;
	}

	/**
	 * @param int    $element_id   Use term_taxonomy_id for taxonomies, post_id for posts
	 * @param string $element_type Use comment, post, page, {custom post time name}, nav_menu, nav_menu_item, category,
	 *                             post_tag, etc. (prefixed with 'post_', 'tax_', or nothing for 'comment')
	 *
	 * @return null|string
	 */
	function get_language_for_element( $element_id, $element_type = 'post_post' ) {
		$cache_key_array = array( $element_id, $element_type );
		$cache_key       = md5( serialize( $cache_key_array ) );
		$cache_group     = 'get_language_for_element';
		$cache_found     = false;
		
		$cache           = new WPML_WP_Cache( $cache_group );
		$result          = $cache->get( $cache_key, $cache_found );
		if ( $cache_found ) {
			return $result;
		}

		$language_for_element_prepared = $this->wpdb->prepare( "	SELECT language_code
															FROM {$this->wpdb->prefix}icl_translations
															WHERE element_id=%d
																AND element_type=%s
															LIMIT 1",
														 array( $element_id, $element_type ) );

		$result = $this->wpdb->get_var( $language_for_element_prepared );

		if ( $result ) {
			$cache->set( $cache_key, $result );
		}

		return apply_filters( 'wpml_language_for_element', $result, $element_type );
	}

	/**
	 * @param string $el_type     Use comment, post, page, {custom post time name}, nav_menu, nav_menu_item, category, post_tag, etc. (prefixed with 'post_', 'tax_', or nothing for 'comment')
	 * @param string $target_lang Target language code
	 * @param string $source_lang Source language code
	 *
	 * @return array
	 */
	function get_elements_without_translations( $el_type, $target_lang, $source_lang ) {
        $sql = $this->wpdb->prepare(
            "SELECT trid
             FROM {$this->wpdb->prefix}icl_translations
             WHERE language_code = %s
              AND element_type = %s",
            $target_lang,
            $el_type
        );

		$trids_for_target = $this->wpdb->get_col( $sql );
		if ( sizeof( $trids_for_target ) > 0 ) {
            $trids_for_target = wpml_prepare_in( $trids_for_target, '%d' );
			$not_trids        = 'AND trid NOT IN (' . $trids_for_target . ')';
		} else {
			$not_trids = '';
		}

		$join = $where = '';
		// exclude trashed posts
		if ( 0 === strpos( $el_type, 'post_' ) ) {
			$join .= " JOIN {$this->wpdb->posts} ON {$this->wpdb->posts}.ID = {$this->wpdb->prefix}icl_translations.element_id";
			$where .= " AND {$this->wpdb->posts}.post_status <> 'trash' AND {$this->wpdb->posts}.post_status <> 'auto-draft'";
		}

		// Now get all the elements that are in the source language that
		// are not already translated into the target language.
        $sql = $this->wpdb->prepare(
            "SELECT element_id
				FROM
					{$this->wpdb->prefix}icl_translations
					{$join}
			 WHERE language_code = %s
					{$not_trids}
                AND element_type= %s
					{$where}
				",
            $source_lang,
            $el_type
        );

		return $this->wpdb->get_col( $sql );
	}

	/**
	 * @param string $selected_language
	 * @param string $default_language
	 * @param string $post_type
	 *
	 * @used_by SitePress:meta_box
	 *
	 * @return array
	 */
	function get_posts_without_translations( $selected_language, $default_language, $post_type = 'post_post' ) {
		$untranslated_ids = $this->get_elements_without_translations( $post_type, $selected_language, $default_language );
		$untranslated = array();
		foreach ( $untranslated_ids as $id ) {
            $untranslated[ $id ] = $this->wpdb->get_var(
	            $this->wpdb->prepare( "SELECT post_title FROM {$this->wpdb->prefix}posts WHERE ID = %d", $id )
            );
		}

		return $untranslated;
	}

	public function get_orphan_translations( $trid, $post_type = 'post', $source_language ) {
		$results      = array();
		$translations = $this->get_element_translations( $trid, 'post_' . $post_type );
		if ( count( $translations ) === 1 ) {
			$sql                  = " SELECT trid, ";
			$language_codes       = array_keys( $this->get_active_languages() );
			$sql_languages        = array();
			$sql_languages_having = array();
			foreach ( $language_codes as $language_code ) {
				$sql_languages[] = "SUM(CASE language_code WHEN '" . esc_sql( $language_code ) . "' THEN 1 ELSE 0 END) AS `" . esc_sql( $language_code ) . '`';
				if ( $language_code == $source_language ) {
					$sql_languages_having[] = '`' . esc_sql( $language_code ) . '`= 0';
				}
			}
			$sql .= implode( ',', $sql_languages );
			$sql .= " 	FROM {$this->wpdb->prefix}icl_translations WHERE element_type = %s ";
			$sql .= 'GROUP BY trid ';
			$sql .= 'HAVING ' . implode( ' AND ', $sql_languages_having );
			$sql .= " ORDER BY trid;";
			$sql_prepared = $this->wpdb->prepare( $sql, array( 'post_' . $post_type ) );
			$trid_results = $this->wpdb->get_results( $sql_prepared, 'ARRAY_A' );
			$trid_list    = array_column( $trid_results, 'trid' );
			if ( $trid_list ) {
				$sql          = "SELECT trid AS value, CONCAT('[', t.language_code, '] ', (CASE p.post_title WHEN '' THEN CONCAT(LEFT(p.post_content, 30), '...') ELSE p.post_title END)) AS label
						FROM {$this->wpdb->posts} p
						INNER JOIN {$this->wpdb->prefix}icl_translations t
							ON p.ID = t.element_id
						WHERE t.element_type = %s
							AND t.language_code <> %s
							AND t.trid IN (" . wpml_prepare_in( $trid_list, '%d' ) . ") ";
				$sql_prepared = $this->wpdb->prepare( $sql, array( 'post_' . $post_type, $source_language ) );
				$results      = $this->wpdb->get_results( $sql_prepared );
			}
		}

		return $results;
	}

	/**
	 * @param WP_Post $post
	 */
	function meta_box( $post ) {

		do_action('wpml_post_edit_languages', $post);
	}

	function icl_get_metabox_states() {
		global $icl_meta_box_globals;

		$translation   = false;
		$source_id     = null;
		$translated_id = null;
		if ( sizeof( $icl_meta_box_globals[ 'translations' ] ) > 0 ) {
			if ( !isset( $icl_meta_box_globals[ 'translations' ][ $icl_meta_box_globals[ 'selected_language' ] ] ) ) {
				// We are creating a new translation
				$translation = true;
				// find the original
				foreach ( $icl_meta_box_globals[ 'translations' ] as $trans_data ) {
					if ( $trans_data->original == '1' ) {
						$source_id = $trans_data->element_id;
						break;
					}
				}
			} else {
				$trans_data = $icl_meta_box_globals[ 'translations' ][ $icl_meta_box_globals[ 'selected_language' ] ];
				// see if this is an original or a translation.
				if ( $trans_data->original == '0' ) {
					// double check that it's not the original
					// This is because the source_language_code field in icl_translations is not always being set to null.

					$source_language_code = $this->wpdb->get_var( $this->wpdb->prepare("SELECT source_language_code
                                                                            FROM {$this->wpdb->prefix}icl_translations
                                                                            WHERE translation_id = %d",
                                                                            $trans_data->translation_id ) );
					$translation          = !( $source_language_code == "" || $source_language_code == null );
					if ( $translation ) {
						$source_id     = $icl_meta_box_globals[ 'translations' ][ $source_language_code ]->element_id;
						$translated_id = $trans_data->element_id;
					} else {
						$source_id = $trans_data->element_id;
					}
				} else {
					$source_id = $trans_data->element_id;
				}
			}
		}

		return array( $translation, $source_id, $translated_id );
	}

	function meta_box_config( $post ) {
		global $iclTranslationManagement,$wp_taxonomies, $wp_post_types, $sitepress_settings;
		if ( ! $this->settings[ 'setup_complete' ] ) {
			return false;
		}

		echo '<div class="icl_form_success" style="display:none">' . __( 'Settings saved', 'sitepress' ) . '</div>';

		$cp_editable = false;
		$checked     = false;
		if ( !in_array( $post->post_type, $this->get_always_translatable_post_types() ) ) {
			if ( !isset( $iclTranslationManagement->settings[ 'custom-types_readonly_config' ][ $post->post_type ] ) || $iclTranslationManagement->settings[ 'custom-types_readonly_config' ][ $post->post_type ] !== 0 ) {
				if ( in_array( $post->post_type, array_keys( $this->get_translatable_documents() ) ) ) {
					$checked   = ' checked="checked"';
					$radio_disabled = isset( $iclTranslationManagement->settings[ 'custom-types_readonly_config' ][ $post->post_type ] ) ? 'disabled="disabled"' : '';
				} else {
					$checked = $radio_disabled = '';
				}
				if ( !$radio_disabled ) {
					$cp_editable = true;
				}
				echo '<br style="line-height:8px;" /><label><input id="icl_make_translatable" type="checkbox" value="' . $post->post_type . '"' . $checked . $radio_disabled . '/>&nbsp;' . sprintf( __( "Make '%s' translatable", 'sitepress' ), $wp_post_types[ $post->post_type ]->labels->name ) . '</label><br style="line-height:8px;" />';
			}
		} else {
			echo '<input id="icl_make_translatable" type="checkbox" checked="checked" value="' . $post->post_type . '" style="display:none" />';
			$checked = true;
		}

		echo '<br clear="all" /><span id="icl_mcs_details">';
		if ( $checked ) {
			$custom_taxonomies = array_diff( get_object_taxonomies( $post->post_type ), array( 'post_tag', 'category', 'nav_menu', 'link_category', 'post_format' ) );
			if ( !empty( $custom_taxonomies ) ) {
				?>
				<table class="widefat">
					<thead>
					<tr>
						<th colspan="2"><?php _e( 'Custom taxonomies', 'sitepress' ); ?></th>
					</tr>
					</thead>
					<tbody>
					<?php foreach ( $custom_taxonomies as $ctax ): ?>
						<?php
						$checked1 = ! empty( $sitepress_settings[ 'taxonomies_sync_option' ][ $ctax ] ) ? ' checked="checked"' : '';
						$checked0 = empty( $sitepress_settings[ 'taxonomies_sync_option' ][ $ctax ] ) ? ' checked="checked"' : '';
						$radio_disabled = isset( $iclTranslationManagement->settings[ 'taxonomies_readonly_config' ][ $ctax ] ) ? ' disabled="disabled"' : '';
						?>
						<tr>
							<td><?php echo $wp_taxonomies[ $ctax ]->labels->name ?></td>
							<td align="right">
								<label><input name="icl_mcs_custom_taxs_<?php echo $ctax ?>" class="icl_mcs_custom_taxs" type="radio"
											  value="<?php echo $ctax ?>" <?php echo $checked1; ?><?php echo $radio_disabled ?> />&nbsp;<?php _e( 'Translate', 'sitepress' ) ?></label>
								<label><input name="icl_mcs_custom_taxs_<?php echo $ctax ?>" type="radio" value="0" <?php echo $checked0; ?><?php echo $radio_disabled ?> />&nbsp;<?php _e( 'Do nothing', 'sitepress' ) ?></label>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<br/>
			<?php
			}

			if ( defined( 'WPML_TM_VERSION' ) ) {
				$custom_keys        = (array)get_post_custom_keys( $post->ID );
				$cf_keys_exceptions = array(
					'_edit_last', '_edit_lock', '_wp_page_template', '_wp_attachment_metadata', '_icl_translator_note', '_alp_processed', '_pingme', '_encloseme', '_icl_lang_duplicate_of', '_wpml_media_duplicate', '_wpml_media_featured',
					'_thumbnail_id'
				);
				$custom_keys = array_diff( $custom_keys, $cf_keys_exceptions );
				$cf_settings_read_only = isset( $iclTranslationManagement->settings[ 'custom_fields_readonly_config' ] ) ? (array)$iclTranslationManagement->settings[ 'custom_fields_readonly_config' ] : array();
				$cf_settings = isset( $iclTranslationManagement->settings[ 'custom_fields_translation' ] ) ? $iclTranslationManagement->settings[ 'custom_fields_translation' ] : array();

				if ( !empty( $custom_keys ) ) {
					?>
					<table class="widefat">
						<thead>
						<tr>
							<th colspan="2"><?php _e( 'Custom fields', 'sitepress' ); ?></th>
						</tr>
						</thead>
						<tbody>
						<?php
						foreach ( $custom_keys as $cfield ) {

							if ( empty( $cf_settings[ $cfield ] ) || $cf_settings[ $cfield ] != 3 ) {
								$radio_disabled = in_array( $cfield, $cf_settings_read_only ) ? 'disabled="disabled"' : '';
								$checked0  = empty( $cf_settings[ $cfield ] ) ? ' checked="checked"' : '';
								$checked1  = isset( $cf_settings[ $cfield ] ) && $cf_settings[ $cfield ] == 1 ? ' checked="checked"' : '';
								$checked2  = isset( $cf_settings[ $cfield ] ) && $cf_settings[ $cfield ] == 2 ? ' checked="checked"' : '';
								?>
								<tr>
									<td><?php echo $cfield; ?></td>
									<td align="right">
										<label><input class="icl_mcs_cfs" name="icl_mcs_cf_<?php echo base64_encode( $cfield ); ?> " type="radio"
													  value="0" <?php echo $radio_disabled . $checked0 ?> />&nbsp;<?php _e( "Don't translate", 'sitepress' ) ?></label>
										<label><input class="icl_mcs_cfs" name="icl_mcs_cf_<?php echo base64_encode( $cfield ); ?> " type="radio" value="1" <?php echo $radio_disabled . $checked1 ?> />&nbsp;<?php _e( "Copy", 'sitepress' ) ?>
										</label>
										<label><input class="icl_mcs_cfs" name="icl_mcs_cf_<?php echo base64_encode( $cfield ); ?> " type="radio" value="2" <?php echo $radio_disabled . $checked2 ?> />&nbsp;<?php _e( "Translate", 'sitepress' ) ?>
										</label>
									</td>
								</tr>
							<?php
							}
						}
						?>
						</tbody>
					</table>
					<br/>
				<?php
				}
			}

			if ( !empty( $custom_taxonomies ) || !empty( $custom_keys ) ) {
				echo '<small>' . __( 'Note: Custom taxonomies and custom fields are shared across different post types.', 'sitepress' ) . '</small>';
			}
		}
		echo '</span>';
		if ( $cp_editable || !empty( $custom_taxonomies ) || !empty( $custom_keys ) ) {
			echo '<p class="submit" style="margin:0;padding:0"><input class="button-secondary" id="icl_make_translatable_submit" type="button" value="' . __( 'Apply', 'sitepress' ) . '" /></p><br clear="all" />';
			wp_nonce_field( 'icl_mcs_inline_nonce', '_icl_nonce_imi' );
		} else {
			_e( 'Nothing to configure.', 'sitepress' );
		}
	}

	/**
	 * @param WP_Query $wpq
	 *
	 * @return WP_Query
	 */
	function pre_get_posts( $wpq ) {

        // case of internal links list
        //
        $post_action = filter_input ( INPUT_POST, 'action' );
        if ( 'wp-link-ajax' === $post_action ) {
            global $wpml_language_resolution;
            $lang = $wpml_language_resolution->get_referrer_language_code (true);
            $this->this_lang = $lang;
            $wpq->query_vars[ 'suppress_filters' ] = false;
        }

        return $wpq;
    }

	function comment_feed_join( $join ) {
		global $wp_query;
		$type = $wp_query->query_vars['post_type'] ? esc_sql( $wp_query->query_vars['post_type'] ) : 'post';

		$wp_query->query_vars['is_comment_feed'] = true;
		$join .= $this->wpdb->prepare( " JOIN {$this->wpdb->prefix}icl_translations t
                                    ON {$this->wpdb->comments}.comment_post_ID = t.element_id
                                        AND t.element_type = %s AND t.language_code = %s ",
		                               'post_' . $type,
		                               $this->this_lang );

		return $join;
	}

	/**
	 * @param string[] $clauses
	 * @param WP_Comment_Query $obj
	 *
	 * @return string[]
	 */
	function comments_clauses( $clauses, $obj ) {
		/** @var WPML_Query_Filter $wpml_query_filter */
		global $wpml_query_filter;

		return $wpml_query_filter->comments_clauses_filter ( $clauses, $obj );
	}

	function language_filter() {
		require ICL_PLUGIN_PATH . '/menu/post-menus/wpml-post-language-filter.class.php';
		$post_lang_filter = new WPML_Post_Language_Filter( $this->wpdb, $this );
		$post_lang_filter->register_scripts();

		return $post_lang_filter->post_language_filter();
	}

	function exclude_other_language_pages2( $arr ) {
		$wp_cache_key     = 'wpml_exclude_other_language_pages2';
		$excl_pages       = wp_cache_get( $wp_cache_key );
		$excl_pages       = $excl_pages ? $excl_pages : array();
		$new_arr          = $arr;
		$current_language = $this->get_current_language();

		if ( $current_language != 'all' ) {
			if ( is_array( $new_arr ) && ! empty( $new_arr[ 0 ]->post_type ) ) {
				$post_type = $new_arr[ 0 ]->post_type;
			} else {
				$post_type = 'page';
			}
			$cache_key = serialize( array( $post_type, $current_language ) );

			$filtered_pages = array();
			// grab list of pages NOT in the current language
			if ( ! isset( $excl_pages[ $cache_key ] ) ) {
				$excl_pages_prepare       = $this->wpdb->prepare( "
				SELECT p.ID FROM {$this->wpdb->posts} p
				JOIN {$this->wpdb->prefix}icl_translations t ON p.ID = t.element_id
				WHERE t.element_type=%s AND p.post_type=%s AND t.language_code <> %s
				", 'post_' . $post_type, $post_type, $current_language );
				$excl_pages[ $cache_key ] = $this->wpdb->get_col( $excl_pages_prepare );
				// exclude them from the result set
			}
			if ( ! empty( $new_arr ) ) {
				foreach ( $new_arr as $page ) {
					if ( ! in_array( $page->ID, $excl_pages[ $cache_key ] ) ) {
						$filtered_pages[ ] = $page;
					}
				}
				$new_arr = $filtered_pages;
			}
		}
		wp_cache_set( $wp_cache_key, $excl_pages );

		return $new_arr;
	}

	function wp_dropdown_pages( $output ) {
		if ( isset( $_POST[ 'lang_switch' ] ) ) {
			$post_id = esc_sql( $_POST[ 'lang_switch' ] );
			$lang    = esc_sql( strip_tags( $_GET[ 'lang' ] ) );
			$parent  = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT post_parent FROM {$this->wpdb->posts} WHERE ID=%d", $post_id ) );
			if ( $parent ) {
				global $wpml_post_translations;
				$trid                 = $wpml_post_translations->get_element_trid($parent);
				$translated_parent_id = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT element_id
                                                                         FROM {$this->wpdb->prefix}icl_translations
                                                                         WHERE trid=%d
                                                                          AND element_type='post_page'
                                                                          AND language_code=%s", $trid, $lang) );
				if ( $translated_parent_id ) {
					$output = str_replace( 'selected="selected"', '', $output );
					$output = str_replace( 'value="' . $translated_parent_id . '"', 'value="' . $translated_parent_id . '" selected="selected"', $output );
				}
			}
		} elseif ( isset( $_GET[ 'lang' ] ) && isset( $_GET[ 'trid' ] ) ) {
			$lang                 = esc_sql( strip_tags( $_GET[ 'lang' ] ) );
			$trid                 = esc_sql( $_GET[ 'trid' ] );
			$post_type            = isset( $_GET[ 'post_type' ] ) ? $_GET[ 'post_type' ] : 'page';
			$elements_id          = $this->wpdb->get_col( $this->wpdb->prepare( "SELECT element_id FROM {$this->wpdb->prefix}icl_translations
				 WHERE trid=%d AND element_type=%s AND element_id IS NOT NULL", $trid, 'post_' . $post_type ) );
			$translated_parent_id = 0;
			foreach ( $elements_id as $element_id ) {
				$parent               = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT post_parent FROM {$this->wpdb->posts} WHERE ID=%d", $element_id ) );
				$trid                 = $this->wpdb->get_var( $this->wpdb->prepare( "
					SELECT trid FROM {$this->wpdb->prefix}icl_translations WHERE element_id=%d AND element_type=%s", $parent, 'post_' . $post_type ) );
				$translated_parent_id = $this->wpdb->get_var( $this->wpdb->prepare( "
					SELECT element_id FROM {$this->wpdb->prefix}icl_translations
					WHERE trid=%d AND element_type=%s AND language_code=%s", $trid, 'post_' . $post_type, $lang ) );
				if ( $translated_parent_id ) {
					break;
				}
			}
			if ( $translated_parent_id ) {
				$output = str_replace( 'selected="selected"', '', $output );
				$output = str_replace( 'value="' . $translated_parent_id . '"', 'value="' . $translated_parent_id . '" selected="selected"', $output );
			}
		}
		if ( !$output ) {
			$output = '<select id="parent_id"><option value="">' . __( 'Main Page (no parent)', 'sitepress' ) . '</option></select>';
		}

		return $output;
	}

	function add_translate_options( $trid, $active_languages, $selected_language, $translations, $type ) {
		if ( $trid && isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] === 'edit' ):
			if(!$this->settings['setup_complete']){
				return false;
			}
	?>

		<div id="icl_translate_options">

			<?php
			// count number of translated and un-translated pages.
			$translations_found = 0;
			$untranslated_found = 0;
			foreach ( $active_languages as $lang ) {
				if ( $selected_language == $lang[ 'code' ] ) {
					continue;
				}
				if ( isset( $translations[ $lang[ 'code' ] ]->element_id ) ) {
					$translations_found += 1;
				} else {
					$untranslated_found += 1;
				}
			}
			?>

			<?php if ( $untranslated_found > 0 ): ?>

				<table cellspacing="1" class="icl_translations_table" style="min-width:200px;margin-top:10px;">
					<thead>
					<tr>
						<th colspan="2" style="padding:4px;background-color:#DFDFDF"><b><?php _e( 'Translate', 'sitepress' ); ?></b></th>
					</tr>
					</thead>
					<tbody>
					<?php foreach ( $active_languages as $lang ): if ( $selected_language == $lang[ 'code' ] ) {
						continue;
					} ?>
						<tr>
							<?php if ( !isset( $translations[ $lang[ 'code' ] ]->element_id ) ): ?>
								<td style="padding:4px;line-height:normal;"><?php echo $lang[ 'display_name' ] ?></td>
								<?php
								$taxonomy = $_GET[ 'taxonomy' ];
								$post_type_q = isset( $_GET[ 'post_type' ] ) ? '&amp;post_type=' . esc_html( $_GET[ 'post_type' ] ) : '';
								$add_link = admin_url( "edit-tags.php?taxonomy=" . esc_html( $taxonomy ) . "&amp;trid=" . $trid . "&amp;lang=" . $lang[ 'code' ] . "&amp;source_lang=" . $selected_language . $post_type_q );
								?>
								<td style="padding:4px;line-height:normal;"><a href="<?php echo $add_link ?>"><?php echo __( 'add', 'sitepress' ) ?></a></td>
							<?php endif; ?>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<?php if ( $translations_found > 0 ): ?>
				<p style="clear:both;margin:5px 0 5px 0">
					<b><?php _e( 'Translations', 'sitepress' ) ?></b>
					(<a class="icl_toggle_show_translations" href="#" <?php if (empty( $this->settings[ 'show_translations_flag' ] )): ?>style="display:none;"<?php endif; ?>><?php _e( 'hide', 'sitepress' ) ?></a><a
						class="icl_toggle_show_translations" href="#" <?php if (!empty( $this->settings[ 'show_translations_flag' ] )): ?>style="display:none;"<?php endif; ?>><?php _e( 'show', 'sitepress' ) ?></a>)

					<?php wp_nonce_field( 'toggle_show_translations_nonce', '_icl_nonce_tst' ) ?>
				<table cellspacing="1" width="100%" id="icl_translations_table" style="<?php if ( empty( $this->settings[ 'show_translations_flag' ] ) ): ?>display:none;<?php endif; ?>margin-left:0;">

					<?php foreach ( $active_languages as $lang ): if ( $selected_language === $lang[ 'code' ] )
						continue; ?>
						<tr>
							<?php if ( isset( $translations[ $lang[ 'code' ] ]->element_id ) ): ?>
								<td style="line-height:normal;"><?php echo $lang[ 'display_name' ] ?></td>
								<?php
								$taxonomy = $_GET[ 'taxonomy' ];
								$post_type_q = isset( $_GET[ 'post_type' ] ) ? '&amp;post_type=' . esc_html( $_GET[ 'post_type' ] ) : '';
								$edit_link = admin_url( "edit-tags.php?taxonomy=" . esc_html( $taxonomy ) . "&amp;action=edit&amp;tag_ID=" . $translations[ $lang[ 'code' ] ]->term_id . "&amp;lang=" . $lang[ 'code' ] . $post_type_q );
								?>
								<td align="right" width="30%"
									style="line-height:normal;"><?php echo isset( $translations[ $lang[ 'code' ] ]->name ) ? '<a href="' . $edit_link . '" title="' . __( 'Edit', 'sitepress' ) . '">' . $translations[ $lang[ 'code' ] ]->name . '</a>' : __( 'n/a', 'sitepress' ) ?></td>

							<?php endif; ?>
						</tr>
					<?php endforeach; ?>
				</table>
			<?php endif; ?>
			<br clear="all" style="line-height:1px;"/>
		</div>
	<?php
		endif;
	}

	/**
	 * @param string $name
	 *
	 * @deprecated deprecated since version 3.1.8
	 * @return array|mixed
	 */
	function the_category_name_filter( $name ) {
		if ( is_array( $name ) ) {
			foreach ( $name as $k => $v ) {
				$name[ $k ] = $this->the_category_name_filter( $v );
			}

			return $name;
		}
		if ( false === strpos( $name, '@' ) ) {
			return $name;
		}
		if ( false !== strpos( $name, '<a' ) ) {
			$int = preg_match_all( '|<a([^>]+)>([^<]+)</a>|i', $name, $matches );
			if ( $int && count( $matches[ 0 ] ) > 1 ) {
				$originals = $filtered = array();
				foreach ( $matches[ 0 ] as $m ) {
					$originals[ ] = $m;
					$filtered[ ]  = $this->the_category_name_filter( $m );
				}
				$name = str_replace( $originals, $filtered, $name );
			} else {
				$name_sh = strip_tags( $name );
				$exp     = explode( '@', $name_sh );
				$name    = str_replace( $name_sh, trim( $exp[ 0 ] ), $name );
			}
		} else {
			$name = preg_replace( '#(.*) @(.*)#i', '$1', $name );
		}

		return $name;
	}

	/**
	 * @param $terms
	 *
	 * @deprecated deprecated since version 3.1.8
	 * @return mixed
	 */
	function get_terms_filter( $terms ) {
		if ( is_wp_error( $terms ) ) {
			return $terms;
		}
		foreach ( $terms as $k => $v ) {
			if ( isset( $terms[ $k ]->name ) ) {
				$terms[ $k ]->name = $this->the_category_name_filter( $terms[ $k ]->name );
			}
		}

		return $terms;
	}

	/**
	 * @param $terms
	 * @param $id
	 * @param $taxonomy
	 *
	 * @deprecated deprecated since version 3.1.8
	 * @return mixed
	 */
	function get_the_terms_filter( $terms, $id, $taxonomy ) {
		return $terms;
	}

	/**
	 * Wrapper for \WPML_Term_Actions::save_term_actions
	 *
	 * @param int    $cat_id
	 * @param int    $tt_id term taxonomy id of the new term
	 * @param string $taxonomy
	 *
	 * @uses \WPML_Term_Actions::save_term_actions to handle required actions
	 *                                               when creating a term
	 *
	 * @hook delete_term
	 */
	function create_term( $cat_id, $tt_id, $taxonomy ) {
		$term_actions = $this->get_term_actions_helper();
		$term_actions->save_term_actions( $tt_id, $taxonomy );
	}

	/**
	 * Wrapper for \WPML_Term_Actions::deleted_term_relationships
	 *
	 * @param int   $post_id
	 * @param array $delete_terms
	 *
	 * @uses \WPML_Term_Actions::deleted_term_relationships to handle required actions
	 *                                               when removing a term from a post
	 *
	 * @hook deleted_term_relationships
	 */
	function deleted_term_relationships( $post_id, $delete_terms ) {
		if ( $this->get_setting( 'sync_post_taxonomies' ) ) {
			$term_actions = $this->get_term_actions_helper();
			$term_actions->deleted_term_relationships( $post_id, $delete_terms );
		}
	}

	/**
	 * Wrapper for \WPML_Term_Actions::delete_term_actions
	 *
	 * @param mixed  $cat
	 * @param int    $tt_id term taxonomy id of the deleted term
	 * @param string $taxonomy
	 *
	 * @uses \WPML_Term_Actions::delete_term_actions to handle required actions
	 *                                               when deleting a term
	 *
	 * @hook delete_term
	 */
	function delete_term( $cat, $tt_id, $taxonomy ) {
		$term_actions = $this->get_term_actions_helper();
		$term_actions->delete_term_actions( $tt_id, $taxonomy );
	}

	/**
	 * @return WPML_Term_Actions
	 */
	public function get_term_actions_helper() {
		if ( ! isset( $this->term_actions ) ) {
			global $wpml_term_translations, $wpml_post_translations;
			require ICL_PLUGIN_PATH . '/inc/taxonomy-term-translation/wpml-term-actions.class.php';
			$this->term_actions = new WPML_Term_Actions( $this,
			                                             $this->wpdb,
			                                             $wpml_post_translations,
			                                             $wpml_term_translations );
		}

		return $this->term_actions;
	}

	function get_terms_args_filter( $args, $taxonomies ) {
		$translated = false;
		foreach ( (array) $taxonomies as $tax ) {
			if ( is_taxonomy_translated( $tax ) ) {
				$translated = true;
				break;
			}
		}
		if ( $translated === false ) {
			return $args;
		}
		if ( isset( $args[ 'cache_domain' ] ) ) {
			$args[ 'cache_domain' ] .= '_' . $this->get_current_language();
		}
		$include_arr = $this->adjust_taxonomies_terms_ids( $args[ 'include' ] );
		if ( !empty( $include_arr ) ) {
			$args[ 'include' ] = $include_arr;
		}
		$exclude_arr = $this->adjust_taxonomies_terms_ids( $args[ 'exclude' ] );
		if ( !empty( $exclude_arr ) ) {
			$args[ 'exclude' ] = $exclude_arr;
		}
		$exclude_tree_arr = $this->adjust_taxonomies_terms_ids( $args[ 'exclude_tree' ] );
		if ( !empty( $exclude_tree_arr ) ) {
			$args['exclude_tree'] = $exclude_tree_arr;
		}
		$child_id_arr = isset( $args[ 'child_of' ] ) ? $this->adjust_taxonomies_terms_ids( $args[ 'child_of' ] ) : array();
		if ( !empty( $child_id_arr ) ) {
			$args[ 'child_of' ] = array_pop( $child_id_arr );
		}
		$parent_arr = isset( $args[ 'parent' ] ) ? $this->adjust_taxonomies_terms_ids( $args[ 'parent' ] ) : array();
		if ( !empty( $parent_arr ) ){
			$args[ 'parent' ] = array_pop( $parent_arr );
		}
		// special case for when term hierarchy is cached in wp_options
		$debug_backtrace = $this->get_backtrace( 5 );
		if ( isset( $debug_backtrace[ 4 ] ) && $debug_backtrace[ 4 ][ 'function' ] == '_get_term_hierarchy' ) {
			$args[ '_icl_show_all_langs' ] = true;
		}

		return $args;
	}

	function terms_clauses( $clauses, $taxonomies, $args ) {
		// special case for when term hierarchy is cached in wp_options
		$debug_backtrace = $this->get_backtrace( 6 ); //Limit to first 5 stack frames, since 4 is the highest index we use
        if ( (bool) $taxonomies === false
             || ( isset( $debug_backtrace[ 4 ] ) && $debug_backtrace[ 4 ][ 'function' ] == '_get_term_hierarchy' )
        ) {
            return $clauses;
        }

		$icl_taxonomies = array();
		foreach ( $taxonomies as $tax ) {
			if ( $this->is_translated_taxonomy( $tax ) ) {
				$icl_taxonomies[] = $tax;
			}
		}

		if ( (bool)$icl_taxonomies === false ){
			return $clauses;
		}

        $icl_taxonomies = "'tax_" . join( "','tax_",  esc_sql( $icl_taxonomies ) ) . "'";

		$lang = $this->get_current_language();
		$where_lang = $lang === 'all' ? '' : $this->wpdb->prepare( " AND icl_t.language_code = %s ", $lang );

		$clauses[ 'join' ] .= " LEFT JOIN {$this->wpdb->prefix}icl_translations icl_t
                                    ON icl_t.element_id = tt.term_taxonomy_id
                                        AND icl_t.element_type IN ({$icl_taxonomies})";
		$clauses[ 'where' ] .= " AND ( ( icl_t.element_type IN ({$icl_taxonomies}) {$where_lang} )
                                    OR icl_t.element_type NOT IN ({$icl_taxonomies}) OR icl_t.element_type IS NULL ) ";

		return $clauses;
	}

	/**
	 * Saves the current $wp_query to \SitePress::$wp_query
	 *
	 * @global WP_Query $wp_query
	 */
	public function set_wp_query() {
		global $wp_query;

		if ( $wp_query ) {
			$this->wp_query = clone $wp_query;
		} else {
			$this->wp_query = null;
		}
	}

	/**
	 * Converts WP generated url to language specific based on plugin settings
	 *
	 * @param string      $url
	 * @param null|string $code	(if null, fallback to default language for root page, or current language in all other cases)
	 *
	 * @return bool|string
	 */
	function convert_url( $url, $code = null ) {
		/* @var WPML_URL_Converter $wpml_url_converter */
		global $wpml_url_converter;

		return $wpml_url_converter->convert_url($url, $code);
	}

	function language_url( $code = null ) {
		global $wpml_url_converter;

		if ( is_null( $code ) ) {
			$code = $this->this_lang;
		}

		$abs_home = $wpml_url_converter->get_abs_home();

		if ( $this->settings[ 'language_negotiation_type' ] == 1 || $this->settings[ 'language_negotiation_type' ] == 2 ) {
			$url = trailingslashit( $this->convert_url( $abs_home, $code ) );
		} else {
			$url = $this->convert_url( $abs_home, $code );
		}

		return $url;
	}

	function post_type_archive_link_filter( $link, $post_type ) {
		/* @var WPML_URL_Converter $wpml_url_converter */
		global $wpml_url_converter;

		if ( isset( $this->settings[ 'custom_posts_sync_option' ][ $post_type ] ) && $this->settings[ 'custom_posts_sync_option' ][ $post_type ] ) {
			$link = $wpml_url_converter->convert_url( $link );
			$link = $this->adjust_cpt_in_url( $link, $post_type );
		}

		return $link;
	}

	public function adjust_cpt_in_url( $link, $post_type, $language_code = null ) {

		if ( $this->slug_translation_turned_on( $post_type ) ) {
			/* @var WPML_URL_Converter $wpml_url_converter */
			global $wpml_url_converter;

			$link = $wpml_url_converter->adjust_cpt_in_url( $link, $post_type, $language_code );
		}

		return $link;
	}

	/**
	 * Check if "Translate custom posts slugs (via WPML String Translation)."
	 * and slug translation for given $post_type are both checked
	 *
	 * @param string $post_type
	 * @return boolean
	 */
	private function slug_translation_turned_on($post_type) {
		return isset($this->settings['posts_slug_translation']['types'][$post_type])
					&& $this->settings['posts_slug_translation']['types'][$post_type]
					&& isset($this->settings['posts_slug_translation']['on'])
					&& $this->settings['posts_slug_translation']['on'];
	}

	function home_url($url){

		return $url;
	}

	function get_comment_link_filter( $link )
	{
		// decode html characters since they are already encoded in the template for some reason
		$link = html_entity_decode( $link );

		return $link;
	}

    function attachment_link_filter( $link, $id ) {
        /** @var WPML_Post_Translation $wpml_post_translations */
        global $wpml_post_translations;

	    $convert_url = $this->convert_url( $link, $wpml_post_translations->get_element_lang_code( $id ) );

        return $convert_url;
    }

	/**
	 * @return WPML_Query_Utils
	 */
	public function get_query_utils() {

		return new WPML_Query_Utils( $this->wpdb );
	}

	/**
	 * @return WPML_Root_Page_Actions
	 */
	public function get_root_page_utils() {

		return wpml_get_root_page_actions_obj();
	}

	/**
	 * @return WPML_WP_API
	 */
	public function get_wp_api() {
		$this->wp_api = $this->wp_api ? $this->wp_api : new WPML_WP_API();

		return $this->wp_api;
	}

	/**
	 * @return wpdb
	 */
	public function wpdb() {

		return $this->wpdb;
	}

	/**
	 * @return WPML_Records
	 */
	public function get_records() {
		$this->records = $this->records
			? $this->records : new WPML_Records( $this->wpdb );

		return $this->records;
	}

	/**
	 * @param WPML_WP_API $wp_api
	 */
	public function set_wp_api( $wp_api ) {
		$this->wp_api = $wp_api;
	}

	/**
	 * @return WPML_Helper
	 */
	public function get_wp_helper() {
		return $this->wpml_helper;
	}

	function get_ls_languages( $template_args = array() ) {

		/** @var $wp_query WP_Query */
		global $wp_query, $wpml_post_translations, $wpml_term_translations;

		if ( is_null( $this->wp_query ) ) {
			$this->set_wp_query();
		}

		$current_language = $this->get_current_language();
		$default_language = $this->get_default_language();

		$cache_key_args   = $template_args ? array_filter( $template_args ) : array( 'default' );
		$cache_key_args[] = $current_language;
		$cache_key_args[] = $default_language;
		if ( isset( $this->wp_query->request ) ) {
			$cache_key_args[] = $this->wp_query->request;
		}
		$cache_key_args   = array_filter( $cache_key_args );
		$cache_key        = md5( wp_json_encode( $cache_key_args ) );
		$cache_group      = 'ls_languages';
		$found            = false;
		
		$cache            = new WPML_WP_Cache( $cache_group );
		$ls_languages     = $cache->get( $cache_key, $found );
		if ( $found ) {
			return $ls_languages;
		}

		// use original wp_query for this
		// backup current $wp_query

		if ( ! isset( $wp_query ) ) {
			return $this->get_active_languages();
		}
		$_wp_query_back = clone $wp_query;
		unset( $wp_query );
		global $wp_query; // make it global again after unset
		$wp_query = clone $this->wp_query;

		$w_active_languages = $this->get_active_languages();

		if ( isset( $template_args[ 'skip_missing' ] ) ) {
			//override default setting
			$icl_lso_link_empty = !$template_args[ 'skip_missing' ];
		} else {
			$icl_lso_link_empty = $this->settings[ 'icl_lso_link_empty' ];
		}

		$languages_helper = new WPML_Languages( $wpml_term_translations, $this, $wpml_post_translations );
		list( $translations, $wp_query ) = $languages_helper->get_ls_translations( $wp_query,
		                                                                           $_wp_query_back,
		                                                                           $this->wp_query );

		// 2. determine url
		foreach ( $w_active_languages as $k => $lang ) {
			$skip_lang = false;
			if ( is_singular()
			     || ( isset( $_wp_query_back->query[ 'name' ] ) && isset( $_wp_query_back->query[ 'post_type' ] ) )
			     || ( ! empty( $this->wp_query->queried_object_id ) && $this->wp_query->queried_object_id == get_option( 'page_for_posts' ) )
			) {
				$this_lang_tmp       = $this->this_lang;
				$this->switch_lang($lang[ 'code' ]);
				$lang_page_on_front  = get_option( 'page_on_front' );
				$lang_page_for_posts = get_option( 'page_for_posts' );
				if($lang_page_on_front) {
					$lang_page_on_front = icl_object_id($lang_page_on_front, 'page', false, $lang[ 'code' ]);
				}
				if($lang_page_for_posts) {
					$lang_page_for_posts = icl_object_id($lang_page_for_posts, 'page', false, $lang[ 'code' ]);
				}
				if ( 'page' === get_option( 'show_on_front' ) && !empty( $translations[ $lang[ 'code' ] ] ) && $translations[ $lang[ 'code' ] ]->element_id == $lang_page_on_front ) {
					$lang[ 'translated_url' ] = $this->language_url( $lang[ 'code' ] );
				} elseif ( 'page' == get_option( 'show_on_front' ) && !empty( $translations[ $lang[ 'code' ] ] ) && $translations[ $lang[ 'code' ] ]->element_id && $translations[ $lang[ 'code' ] ]->element_id == $lang_page_for_posts ) {
					if ( $lang_page_for_posts ) {
						$lang[ 'translated_url' ] = get_permalink( $lang_page_for_posts );
					} else {
						$lang[ 'translated_url' ] = $this->language_url( $lang[ 'code' ] );
					}
				} else {
					if ( !empty( $translations[ $lang[ 'code' ] ] ) && isset( $translations[ $lang[ 'code' ] ]->post_title ) ) {
						$this->switch_lang( $lang['code'] );
						$lang[ 'translated_url' ] = get_permalink( $translations[ $lang[ 'code' ] ]->element_id );
						$lang[ 'missing' ]        = 0;
						$this->switch_lang( $current_language );
					} else {
						if ( $icl_lso_link_empty ) {
							if ( !empty( $template_args[ 'link_empty_to' ] ) ) {
								$lang[ 'translated_url' ] = str_replace( '{%lang}', $lang[ 'code' ], $template_args[ 'link_empty_to' ] );
							} else {
								$lang[ 'translated_url' ] = $this->language_url( $lang[ 'code' ] );
							}

						} else {
							$skip_lang = true;
						}
						$lang[ 'missing' ] = 1;
					}
				}
				$this->this_lang = $this_lang_tmp;
			} elseif ( is_category() || is_tax() || is_tag() ) {
				global $icl_adjust_id_url_filter_off;

				$icl_adjust_id_url_filter_off = true;
				list( $lang, $skip_lang ) = $languages_helper->add_tax_url_to_ls_lang( $lang,
				                                                                       $translations,
				                                                                       $icl_lso_link_empty,
				                                                                       $skip_lang );
				$icl_adjust_id_url_filter_off = false;
			} elseif ( is_author() ) {
				global $authordata;
				if ( empty( $authordata ) ) {
					$authordata = get_userdata( get_query_var( 'author' ) );
				}
				remove_filter( 'home_url', array( $this, 'home_url' ), 1, 4 );
				remove_filter( 'author_link', array( $this, 'author_link' ) );
				list( $lang, $skip_lang ) = $languages_helper->add_author_url_to_ls_lang( $lang,
				                                                                          $authordata,
				                                                                          $icl_lso_link_empty,
				                                                                          $skip_lang );
				add_filter( 'home_url', array( $this, 'home_url' ), 1, 4 );
				add_filter( 'author_link', array( $this, 'author_link' ) );
			} elseif ( is_archive() && !is_tag() ) {
				global $icl_archive_url_filter_off;
				$icl_archive_url_filter_off = true;
				remove_filter( 'post_type_archive_link', array( $this, 'post_type_archive_link_filter' ), 10 );
				list( $lang, $skip_lang ) = $languages_helper->add_date_or_cpt_url_to_ls_lang( $lang,
				                                                                               $this->wp_query,
				                                                                               $icl_lso_link_empty,
				                                                                               $skip_lang );
				add_filter( 'post_type_archive_link', array( $this, 'post_type_archive_link_filter' ), 10, 2 );
				$icl_archive_url_filter_off = false;
			} elseif ( is_search() ) {
				$url_glue                 = strpos( $this->language_url( $lang[ 'code' ] ), '?' ) === false ? '?' : '&';
				$lang[ 'translated_url' ] = $this->language_url( $lang[ 'code' ] ) . $url_glue . 's=' . urlencode( $wp_query->query[ 's' ] );
			} else {
				global $icl_language_switcher_preview;
				if ( $icl_lso_link_empty || is_home() || is_404() || ( 'page' === get_option( 'show_on_front' ) && ( $this->wp_query->queried_object_id == get_option( 'page_on_front' ) || $this->wp_query->queried_object_id == get_option( 'page_for_posts' ) ) ) || $icl_language_switcher_preview || WPML_Root_Page::is_current_request_root() ) {
					$lang[ 'translated_url' ] = $this->language_url( $lang[ 'code' ] );
					$skip_lang                = false;
				} else {
					$skip_lang = true;
					unset( $w_active_languages[ $k ] );
				}
			}
			if ( !$skip_lang ) {
				$w_active_languages[ $k ] = $lang;
			} else {
				unset( $w_active_languages[ $k ] );
			}
		}

		// 3.
		foreach ( $w_active_languages as $k => $v ) {
			$w_active_languages[ $k ] = $languages_helper->get_ls_language (
				$k,
				$current_language,
				$w_active_languages[ $k ]
			);
		}

		// 4. pass GET parameters
		$parameters_copied = apply_filters( 'icl_lang_sel_copy_parameters',
											array_map( 'trim',
													   explode( ',',
																wpml_get_setting_filter('',
																						'icl_lang_sel_copy_parameters') ) ) );
		if ( $parameters_copied ) {
			foreach ( $_GET as $k => $v ) {
				if ( in_array( $k, $parameters_copied ) ) {
					$gets_passed[ $k ] = $v;
				}
			}
		}
		if ( !empty( $gets_passed ) ) {
			$gets_passed = http_build_query( $gets_passed );
			foreach ( $w_active_languages as $code => $al ) {
				if ( empty( $al[ 'missing' ] ) ) {
					$glue = false !== strpos( $w_active_languages[ $code ][ 'url' ], '?' ) ? '&' : '?';
					$w_active_languages[ $code ][ 'url' ] .= $glue . $gets_passed;
				}
			}
		}

		// restore current $wp_query
		unset( $wp_query );
		global $wp_query; // make it global again after unset
		$wp_query = clone $_wp_query_back;
		unset( $_wp_query_back );

		$w_active_languages = apply_filters( 'icl_ls_languages', $w_active_languages );

		$w_active_languages = $languages_helper->sort_ls_languages( $w_active_languages, $template_args );

		// Change the url, in case languages in subdomains are set.
		if ( $this->settings[ 'language_negotiation_type' ] == 2 ) {
			foreach ( $w_active_languages as $lang => $element ) {
				$w_active_languages[ $lang ][ 'url' ] = $this->convert_url( $element[ 'url' ], $lang );
			}
		}

		wp_reset_query();

		$cache->set( $cache_key, $w_active_languages );
		return $w_active_languages;
	}

	function get_display_single_language_name_filter( $empty, $args ) {
		$language_code = $args['language_code'];
		$display_code = isset($args['display_code']) ? $args['display_code'] : null;
		return $this->get_display_language_name($language_code, $display_code);
	}

	function get_display_language_name( $lang_code, $display_code = null ) {
		$display_code = $display_code ? $display_code : $this->get_current_language();
		if ( isset( $this->icl_language_name_cache ) ) {
			$translated_name = $this->icl_language_name_cache->get( $lang_code . $display_code );
		} else {
			$translated_name = null;
		}
		if ( !$translated_name ) {
			$display_code    = $display_code == 'all' ? $this->get_admin_language() : $display_code;
			$translated_name = $this->wpdb->get_var(
				$this->wpdb->prepare(
					"  SELECT name
                       FROM {$this->wpdb->prefix}icl_languages_translations
                       WHERE language_code=%s
                        AND display_language_code=%s",
					$lang_code,
					$display_code
				)
			);
			if ( isset( $this->icl_language_name_cache ) ) {
				$this->icl_language_name_cache->set( $lang_code . $display_code, $translated_name );
			}
		}

		return $translated_name;
	}

	function get_flag( $lang_code ) {
		return $this->flags->get_flag( $lang_code );
	}

	function get_flag_url( $code ) {
		return $this->flags->get_flag_url( $code );
	}

	function get_flag_img( $code ) {
		return '<img src="' . $this->flags->get_flag_url( $code ) . '">';
	}

	function clear_flags_cache() {
		$this->flags->clear();
	}

	function set_up_language_selector()
	{
		$this->get_wp_api();
		if(!$this->wp_api->is_ajax() && !$this->wp_api->is_back_end() && !$this->wp_api->is_cron_job() && !$this->wp_api->is_heartbeat()) {
			// language selector
			// load js and style for js language selector
			if ( isset( $this->settings['icl_lang_sel_type'] ) && $this->settings['icl_lang_sel_type'] == 'dropdown' && ( ! is_admin() || ( isset( $_GET['page'] ) && $_GET['page'] == ICL_PLUGIN_FOLDER . '/menu/languages.php' ) ) ) {
				if ( $this->settings['icl_lang_sel_stype'] == 'mobile-auto' ) {
					include ICL_PLUGIN_PATH . '/lib/mobile-detect.php';
					$WPML_Mobile_Detect = new WPML_Mobile_Detect;
					$this->is_mobile    = $WPML_Mobile_Detect->isMobile();
					$this->is_tablet    = $WPML_Mobile_Detect->isTablet();
				}
				if ( ( $this->settings['icl_lang_sel_stype'] == 'mobile-auto' && ( ! empty( $this->is_mobile ) || ! empty( $this->is_tablet ) ) ) || $this->settings['icl_lang_sel_stype'] == 'mobile' ) {
					if ( ! defined( 'ICL_DONT_LOAD_LANGUAGES_JS' ) || ! ICL_DONT_LOAD_LANGUAGES_JS ) {
						wp_enqueue_script( 'language-selector', ICL_PLUGIN_URL . '/res/js/language-selector.js', array(), ICL_SITEPRESS_VERSION, true );
					}
					if ( ! defined( 'ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS' ) || ! ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS ) {
						wp_enqueue_style( 'language-selector', ICL_PLUGIN_URL . '/res/css/language-selector-click.css', ICL_SITEPRESS_VERSION );
					}
				}
			}
		}
	}

	function get_desktop_language_selector() {
		$active_languages = $this->get_ls_languages();
		$active_languages = apply_filters('wpml_active_languages_access', $active_languages, array( 'action'=>'read' ));

		if ( $active_languages ) {
			/**
			 * @var $main_language bool|string
			 * @used_by menu/language-selector.php
			 */
			foreach ( $active_languages as $k => $al ) {
				if ( $al[ 'active' ] == 1 ) {
					unset( $active_languages[ $k ] );
					break;
				}
			}
		} else {
			return '';
		}

		global $icl_language_switcher_preview;

		$main_language = array(
			'native_name'     => __( 'All languages', 'sitepress' ),
			'translated_name' => __( 'All languages', 'sitepress' ),
			'language_code'   => 'all',
			'country_flag_url'   => ICL_PLUGIN_URL . '/res/img/icon16.png',
		);
		if ( 'all' !== $this->this_lang ) {
			$language_details                      = $this->get_language_details( $this->this_lang );
			$main_language['native_name']     = $language_details['display_name'];
			$main_language['translated_name'] = $language_details['display_name'];
			$main_language['language_code']   = $language_details['code'];

			$flag = $this->get_flag($main_language['language_code']);
			if ( isset( $flag->from_template ) && $flag->from_template && isset( $flag->flag ) ) {
				$wp_upload_dir = wp_upload_dir();
				$main_language['country_flag_url'] = $wp_upload_dir['baseurl'] . '/flags/' . $flag->flag;
			}else{
				if(isset($flag->flag)){
					$main_language['country_flag_url'] = ICL_PLUGIN_URL . '/res/flags/'.$flag->flag;
				} else {
					$main_language['country_flag_url'] = ICL_PLUGIN_URL . '/res/img/icon16.png';
				}
			}
		}

		$style_display_none_icl_lang_sel_type = $this->settings['icl_lang_sel_type'] == 'list' ? ' style="display:none;"' : '';
		$class_icl_rtl = $this->is_rtl() ? 'class="icl_rtl"' : '';

		$language_selector = '<div id="lang_sel" '
                             .$style_display_none_icl_lang_sel_type.' '
                             .$class_icl_rtl.' ><ul><li><a href="#" class="lang_sel_sel icl-'.$main_language['language_code'].'">';

		if ( $this->settings[ 'icl_lso_flags' ] || $icl_language_switcher_preview ) {
			$language_selector .= '<img ' . ( !$this->settings['icl_lso_flags'] ? 'style="display:none"' : '' )
						. ' class="iclflag" '
						. 'src="'.$main_language['country_flag_url'].'" '
						. 'alt="'.$main_language['language_code'].'"  '
						. 'title="'. ($this->settings['icl_lso_display_lang'] ? esc_attr($main_language['translated_name']) : esc_attr($main_language['native_name']) ) .'" />
								&nbsp;';
		}

        $ls_settings = $this->get_ls_settings ( $main_language, false, false );
        $language_selector .= icl_disp_language (
            $ls_settings[ 'lang_native' ],
            $ls_settings[ 'lang_translated' ],
            $ls_settings[ 'lang_native_hidden' ],
            $ls_settings[ 'lang_translated_hidden' ]
        );

		$language_selector .= '</a> ';

		if(!empty($active_languages)) {

			$language_selector .= '<ul>';
			$active_languages_ordered = $this->order_languages($active_languages);
			foreach($active_languages_ordered as $lang) {
                $language_selector .= $this->render_ls_li_item (
                    $lang,
                    $ls_settings[ 'lang_native_hidden' ],
                    $ls_settings[ 'lang_translated_hidden' ]
                );
			}
			$language_selector .= '</ul>';
		}
		$language_selector .= '</li></ul></div>';

		return $language_selector;
	}

    public function render_ls_li_item(
        $lang,
        $lang_native_hidden = false,
        $lang_translated_hidden = false,
        $language_selected = ""
    ) {
        global $icl_language_switcher_preview;

        $country_flag_url = $lang[ 'country_flag_url' ];
        $language_url = apply_filters ( 'WPML_filter_link', $lang[ 'url' ], $lang );
        $language_flag_title = $this->settings[ 'icl_lso_display_lang' ] ? esc_attr (
            $lang[ 'translated_name' ]
        ) : esc_attr ( $lang[ 'native_name' ] );
        $ls_settings = $this->get_ls_settings ( $lang, $lang_native_hidden, $lang_translated_hidden );

        $language_selector = '<li class="icl-' . $lang[ 'language_code' ]
                             . '"><a href="' . $language_url . '" ' . $language_selected . '>';

        if ( $this->settings[ 'icl_lso_flags' ] || $icl_language_switcher_preview ):
            $language_selector .= '<img ' . ( !$this->settings[ 'icl_lso_flags' ] ? 'style="display:none"' : '' )
                                  . ' class="iclflag" '
                                  . 'src="' . $country_flag_url . '" '
                                  . 'alt="' . $lang[ 'language_code' ] . '" '
                                  . 'title="' . $language_flag_title . '" />&nbsp;';
        endif;

        $ls_settings = $this->get_ls_settings (
            $lang,
            $ls_settings[ 'lang_native_hidden' ],
            $ls_settings[ 'lang_translated_hidden' ]
        );
        $language_selector .= icl_disp_language (
            $ls_settings[ 'lang_native' ],
            $ls_settings[ 'lang_translated' ],
            $ls_settings[ 'lang_native_hidden' ],
            $ls_settings[ 'lang_translated_hidden' ]
        );

        $language_selector
            .= '</a></li>';

        return $language_selector;
    }

    private function get_ls_settings( $lang, $lang_native_hidden = false, $lang_translated_hidden = false ) {
        global $icl_language_switcher_preview;

        if ( $icl_language_switcher_preview ) {
			// We need to show everything for the preview.
			// We then hide what we don't want using js.
            $lang_native = $lang[ 'native_name' ];
            $lang_native_hidden = 0;
            $lang_translated = $lang[ 'translated_name' ];
            $lang_translated_hidden = 0;
        } else {
            $lang_native = $this->settings[ 'icl_lso_native_lang' ] ? $lang[ 'native_name' ] : false;
            $lang_translated = $this->settings[ 'icl_lso_display_lang' ] ? $lang[ 'translated_name' ] : false;
        }

        return array(
            'lang_native' => $lang_native,
            'lang_native_hidden' => $lang_native_hidden,
            'lang_translated' => $lang_translated,
            'lang_translated_hidden' => $lang_translated_hidden
        );
    }

	function get_mobile_language_selector() {
		$languages = $this->get_ls_languages();
		$current_lang_code = $this->get_current_language();
		if ( ! isset( $languages[ $current_lang_code ] ) ) {
			return '';
		}
		$current_language = $languages[ $current_lang_code ];
		unset( $languages[ $current_lang_code ] );

		$user_agent = isset($_SERVER[ 'HTTP_USER_AGENT' ]) ? $_SERVER[ 'HTTP_USER_AGENT' ] : '';
		if ( preg_match( '#MSIE ([0-9]+)\.[0-9]#', $user_agent, $matches ) ) {
			$ie_ver = $matches[ 1 ];
		}
		$rtl_class_suffix = $this->is_rtl() ? 'icl_rtl' : '';
		$language_selector_mobile = '<div id="lang_sel_click" onclick="wpml_language_selector_click.toggle(this);"
									 class="lang_sel_click' . $rtl_class_suffix . '" ><ul><li>';

		$language_selector_mobile .= '<a href="javascript:;" class="lang_sel_sel icl-' . $current_language[ 'language_code' ] . '">';
		$language_selector_mobile = $this->maybe_add_lso_flag($language_selector_mobile, $current_language);
		$language_selector_mobile .= $this->settings[ 'icl_lso_display_lang' ] || $this->settings[ 'icl_lso_native_lang' ]
			? $current_language[ 'native_name' ] : '';

		$language_selector_mobile .= ! isset( $ie_ver ) || $ie_ver > 6 ? '</a>' : '';
		$old_ie = isset( $ie_ver ) && $ie_ver <= 6;
		$language_selector_mobile .=  $old_ie ? '<table><tr><td>' : '';
		$language_selector_mobile .= '<ul>';

		foreach ( $languages as $code => $language ) {
			$language_selector_mobile .= '<li class="icl-' . $language[ 'language_code' ] . '">'
			                             . '<a rel="alternate" href="' . apply_filters( 'WPML_filter_link',
			                                                                            $language[ 'url' ],
			                                                                            $language ) . '">';

			$language_selector_mobile = $this->maybe_add_lso_flag($language_selector_mobile, $language);
			$language_name = $this->settings[ 'icl_lso_native_lang' ]
				? '<span class="icl_lang_sel_native">' . $language[ 'native_name' ] . '</span>' : '';
			$language_name .= $this->settings[ 'icl_lso_display_lang' ]
				? '<span class="icl_lang_sel_translated"><span class="icl_lang_sel_bracket"> (</span>' . $language[ 'translated_name' ] . '<span class="icl_lang_sel_bracket">)</span></span>' : '';
			$language_selector_mobile .= $language_name . '</a></li>';
		}
		$language_selector_mobile .= '</ul>';
		$language_selector_mobile .= $old_ie ? '</td></tr></table></a>' : '';
		$language_selector_mobile .= '</li></ul></div>';

		return $language_selector_mobile;

	}

	private function maybe_add_lso_flag( $html, $language ) {
		if ( $this->settings[ 'icl_lso_flags' ] ) {
			$name_index = $this->settings[ 'icl_lso_display_lang' ] ? 'translated_name' : 'native_name';
			$html .= '<img class="iclflag" src="' . $language[ 'country_flag_url' ] . '" alt="' . $language[ 'language_code' ]
			         . '" title="' . esc_attr ( $language[ $name_index ] ) . '" />&nbsp;';
		}

		return $html;
	}

	function get_language_selector() {
		if ( !function_exists( 'wpml_home_url_ls_hide_check' ) || !wpml_home_url_ls_hide_check() ) {
			// Mobile or auto
			$type = $this->settings[ 'icl_lang_sel_type' ];
			$mobile = $this->settings[ 'icl_lang_sel_stype' ] === 'mobile'
					  || ( $type === 'mobile'
						   || ( $type === 'mobile-auto'
								&& ( !empty( $this->is_tablet ) || !empty( $this->is_mobile ) ) ) );

			global $icl_language_switcher_preview;
			if ( !$mobile && ( $type === 'list' || $icl_language_switcher_preview ) ) {
				global $icl_language_switcher;
				$icl_language_switcher->widget_list();
			}

			return $mobile === true
				? $this->get_mobile_language_selector ()
				: ( !$icl_language_switcher_preview && $type === 'list' ? ''
					: $this->get_desktop_language_selector () );
		} else {
			return '';
		}
	}

	function language_selector()
	{
		echo $this->get_language_selector();
	}

	public function add_extra_debug_info( $extra_debug ) {
		$extra_debug[ 'WMPL' ] = $this->get_settings();

		return $extra_debug;
	}

	function set_default_categories( $def_cat )
	{
		$this->settings[ 'default_categories' ] = $def_cat;
		$this->save_settings();
	}

	function pre_option_default_category( $setting ) {
		$lang = filter_input ( INPUT_POST, 'icl_post_language', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$lang = $lang ? $lang : filter_input ( INPUT_GET, 'lang', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$lang = $lang ? $lang : $this->get_current_language();

		$lang = $lang === 'all' ? $this->get_default_language () : $lang;
		$ttid = isset( $this->settings[ 'default_categories' ][ $lang ] )
			? intval ( $this->settings[ 'default_categories' ][ $lang ] ) : 0;

		return $ttid === 0
			? null : $this->wpdb->get_var (
				$this->wpdb->prepare (
							"SELECT term_id
		                     FROM {$this->wpdb->term_taxonomy}
		                     WHERE term_taxonomy_id= %d
		                     AND taxonomy='category'",
							$ttid
						)
					);
	}

	function update_option_default_category( $oldvalue, $new_value ) {
		$new_value     = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT term_taxonomy_id FROM {$this->wpdb->term_taxonomy} WHERE taxonomy='category' AND term_id=%d", $new_value ) );
		$translations = $this->get_element_translations( $this->get_element_trid( $new_value, 'tax_category' ) );
		if ( !empty( $translations ) ) {
			foreach ( $translations as $t ) {
				$icl_settings[ 'default_categories' ][ $t->language_code ] = $t->element_id;
			}
			if ( isset( $icl_settings ) ) {
				$this->save_settings( $icl_settings );
			}
		}
	}


	function get_term_adjust_id( $term ) {
		/** @var WPML_Term_Translation $wpml_term_translations */
		global $icl_adjust_id_url_filter_off, $wpml_term_translations;
		if ( $icl_adjust_id_url_filter_off || ! $this->get_setting( 'auto_adjust_ids' ) ) {
			return $term;
		} // special cases when we need the category in a different language

		// exception: don't filter when called from get_permalink. When category parents are determined
		$debug_backtrace = $this->get_backtrace( 7 ); //Limit to first 7 stack frames, since 6 is the highest index we use
		if ( isset( $debug_backtrace[ 5 ][ 'function' ] ) &&
			 $debug_backtrace[ 5 ][ 'function' ] == 'get_category_parents' ||
			 isset( $debug_backtrace[ 6 ][ 'function' ] ) &&
			 $debug_backtrace[ 6 ][ 'function' ] == 'get_permalink' ||
			 isset( $debug_backtrace[ 4 ][ 'function' ] ) &&
			 $debug_backtrace[ 4 ][ 'function' ] == 'get_permalink' // WP 3.5
		) {
			return $term;
		}

		$translated_id = $wpml_term_translations->element_id_in($term->term_taxonomy_id, $this->this_lang);


		return $translated_id && (int)$translated_id !== (int)$term->term_taxonomy_id
			? get_term_by('term_taxonomy_id', $translated_id, $term->taxonomy) : $term;
	}

	function get_pages_adjust_ids( $pages, $args ) {
		if ( $pages && $this->get_current_language () !== $this->get_default_language () ) {
			$args_hash      = md5 ( wp_json_encode ( $args ) );
			$cache_key_args = md5 ( wp_json_encode ( wp_list_pluck ( $pages, 'ID' ) ) );
			$cache_key_args .= ":";
			$cache_key_args .= $args_hash;

			$cache_key     = $cache_key_args;
			$cache_group   = 'get_pages_adjust_ids';
			$found         = false;
			$cached_result = wp_cache_get ( $cache_key, $cache_group, false, $found );

			if ( !$found ) {
				if ( $args[ 'include' ] ) {
					$args = $this->translate_csv_page_ids ( $args, 'include' );
				}
				if ( $args[ 'exclude' ] ) {
					$args = $this->translate_csv_page_ids ( $args, 'exclude' );
				}
				if ( $args[ 'child_of' ] ) {
					$args[ 'child_of' ] = icl_object_id ( $args[ 'child_of' ], 'page', true );
				}
				if ( md5 ( wp_json_encode ( $args ) ) !== $args_hash ) {
					remove_filter ( 'get_pages', array( $this, 'get_pages_adjust_ids' ), 1 );
					$pages = get_pages ( $args );
					add_filter ( 'get_pages', array( $this, 'get_pages_adjust_ids' ), 1, 2 );
				}
				wp_cache_set ( $cache_key, $pages, $cache_group );
			} else {
				$pages = $cached_result;
			}
		}

		return $pages;
	}

	private function translate_csv_page_ids( $args, $index ) {
		$original_ids   = array_map ( 'trim', explode ( ',', $args[ $index ] ) );
		$translated_ids = array();
		foreach ( $original_ids as $i ) {
			$t = icl_object_id ( $i, 'page', true );
			if ( $t ) {
				$translated_ids[ ] = $t;
			}
		}
		$args[ $index ] = join ( ',', $translated_ids );

		return $args;
	}

	function category_link_adjust_id( $catlink, $cat_id ) {
		/** @var WPML_Term_Translation $wpml_term_translations */
		global $icl_adjust_id_url_filter_off, $wpml_term_translations;
		if ( $icl_adjust_id_url_filter_off )
			return $catlink; // special cases when we need the category in a different language

		$translated_id = $wpml_term_translations->term_id_in($cat_id, $this->this_lang);
		if ( $translated_id && $translated_id != $cat_id ) {
			remove_filter( 'category_link', array( $this, 'category_link_adjust_id' ), 1 );
			$catlink = get_category_link( $translated_id );
			add_filter( 'category_link', array( $this, 'category_link_adjust_id' ), 1, 2 );
		}

		return $catlink;
	}

	// adjacent posts links
	function get_adjacent_post_join( $join ) {
		$post_type = get_query_var( 'post_type' );
		$cache_key = md5( wp_json_encode( array( $post_type, $join ) ) );
		$cache_group = 'adjacent_post_join';

		$temp_join = wp_cache_get( $cache_key, $cache_group );
		if ( $temp_join ) {
			return $temp_join;
		}

		if ( !$post_type ) {
			$post_type = 'post';
		}
		if ( $this->is_translated_post_type( $post_type ) ) {
			$join .= $this->wpdb->prepare(" JOIN {$this->wpdb->prefix}icl_translations t
                                        ON t.element_id = p.ID AND t.element_type = %s",
                                    'post_' . $post_type );
		}
		wp_cache_set( $cache_key, $join, $cache_group );

		return $join;
	}

	function get_adjacent_post_where( $where ) {
		$post_type = get_query_var( 'post_type' );
		$cache_key   = md5(wp_json_encode( array( $post_type, $where ) ) );
		$cache_group = 'adjacent_post_where';
		$temp_where = wp_cache_get( $cache_key, $cache_group );
		if ( $temp_where ) {
			return $temp_where;
		}
		if ( !$post_type ) {
			$post_type = 'post';
		}
		if ( $this->is_translated_post_type( $post_type ) ) {
			$where .= " AND language_code = '" . esc_sql( $this->this_lang ) . "'";
		}
		wp_cache_set( $cache_key, $where, $cache_group );

		return $where;

	}

	// feeds links
	function feed_link( $out ) {
		return $this->convert_url( $out );
	}

	// commenting links
	function post_comments_feed_link( $out ) {
		if ( $this->settings[ 'language_negotiation_type' ] == 3 ) {
			$out = preg_replace( '@(\?|&)lang=([^/]+)/feed/@i', 'feed/$1lang=$2', $out );
		}

		return $out;
	}

	function trackback_url( $out ) {
		return $this->convert_url( $out );
	}

	function user_trailingslashit( $string, $type_of_url )
	{
		// fixes comment link for when the comments list pagination is enabled
		if ( $type_of_url == 'comment' ) {
			$string = preg_replace( '@(.*)/\?lang=([a-z-]+)/(.*)@is', '$1/$3?lang=$2', $string );
		}

		return $string;
	}

	// archives links
	function getarchives_join( $join ) {

		return $join . " JOIN {$this->wpdb->prefix}icl_translations t ON t.element_id = {$this->wpdb->posts}.ID AND t.element_type='post_post'";
	}

	function getarchives_where( $where ) {

		return $where . " AND language_code = '" . esc_sql( $this->this_lang ) . "'";
	}

	function archives_link( $out ) {
		global $icl_archive_url_filter_off;
		if ( !$icl_archive_url_filter_off ) {
			$out = $this->convert_url( $out, $this->this_lang );
		}
		$icl_archive_url_filter_off = false;

		return $out;
	}

	/**
	 * Alias for \SitePress::convert_url
	 *
	 * @param string $url
	 * @param string $lang
	 *
	 * @deprecated since WPML 3.2.3 use \SitePress::convert_url
	 *
	 * @return bool|string
	 */
	function archive_url( $url, $lang ) {
		$url = $this->convert_url( $url, $lang );

		return $url;
	}

	/**
	 * Fixes double dashes as well as broken author links that contain repeated question marks as a result of
	 * WPML filtering the home url to contain a question mark already.
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	function author_link( $url ) {
		$url = preg_replace( '#^http://(.+)//(.+)$#', 'http://$1/$2', $this->convert_url( $url ) );

		return preg_replace( '#(\?.*)(\?)#', '$1&', $url );
	}

	function pre_option_home( $setting = false ) {
		if ( !defined( 'TEMPLATEPATH' ) )
			return $setting;

		$template_real_path = realpath( TEMPLATEPATH );
		$debug_backtrace = $this->get_backtrace( 7 ); //Ignore objects and limit to first 7 stack frames, since 6 is the highest index we use
		$function = isset( $debug_backtrace[ 4 ] ) && isset( $debug_backtrace[ 4 ][ 'function' ] ) ? $debug_backtrace[ 4 ][ 'function' ] : null;
		$previous_function = isset( $debug_backtrace[ 5 ] ) && isset( $debug_backtrace[ 5 ][ 'function' ] ) ? $debug_backtrace[ 5 ][ 'function' ] : null;
		$inc_methods = array( 'include', 'include_once', 'require', 'require_once' );

		if ( $function === 'get_bloginfo' && $previous_function === 'bloginfo' ) {
			// case of bloginfo
			$is_template_file = false !== strpos( $debug_backtrace[ 5 ][ 'file' ], $template_real_path );
			$is_direct_call   = in_array( $debug_backtrace[ 6 ][ 'function' ], $inc_methods ) || ( false !== strpos( $debug_backtrace[ 6 ][ 'file' ], $template_real_path ) );
		} elseif ( in_array ( $function, array( 'get_bloginfo', 'get_settings' ), true ) ) {
			// case of get_bloginfo or get_settings
			$is_template_file = false !== strpos( $debug_backtrace[ 4 ][ 'file' ], $template_real_path );
			$is_direct_call   = in_array( $previous_function, $inc_methods ) || ( false !== strpos( $debug_backtrace[ 5 ][ 'file' ], $template_real_path ) );
		} else {
			// case of get_option
			$is_template_file = isset( $debug_backtrace[ 3 ][ 'file' ] ) && ( false !== strpos( $debug_backtrace[ 3 ][ 'file' ], $template_real_path ) );
			$is_direct_call   = in_array( $function, $inc_methods ) || ( isset( $debug_backtrace[ 4 ][ 'file' ] ) && false !== strpos( $debug_backtrace[ 4 ][ 'file' ], $template_real_path ) );
		}

		$home_url = $is_template_file && $is_direct_call ? $this->language_url( $this->this_lang ) : $setting;

		return $home_url;
	}

	/**
	 *
	 *
	 * @param array $public_query_vars
	 *
	 * @return array with added 'lang' index
	 */
	function query_vars( $public_query_vars ) {
		global $wp_query;

		$public_query_vars[] = 'lang';
		$wp_query->query_vars['lang'] = $this->this_lang;

		return $public_query_vars;
	}

	function parse_query( $q ) {
		global $wpml_post_translations, $wpml_term_translations, $wpml_query_filter;

		$query_parser = new WPML_Query_Parser( $this,
		                                       $this->wpdb,
		                                       $wpml_post_translations,
		                                       $wpml_term_translations,
		                                       $wpml_query_filter );

		return $query_parser->parse_query( $q );
	}

	function adjust_wp_list_pages_excludes( $pages ) {
		foreach ( $pages as $k => $v ) {
			$pages[ $k ] = icl_object_id( $v, 'page', true );
		}

		return $pages;
	}

	function language_attributes( $output ) {
		if ( preg_match( '#lang="[a-z-]+"#i', $output ) ) {
			$output = preg_replace( '#lang="([a-z-]+)"#i', 'lang="' . $this->this_lang . '"', $output );
		} else {
			$output .= ' lang="' . $this->this_lang . '"';
		}

		return $output;
	}

	// Localization
	function plugin_localization() {
		load_plugin_textdomain( 'sitepress', false, ICL_PLUGIN_FOLDER . '/locale' );
	}

	function locale() {

		return $this->locale_utils->locale();
	}

	function get_language_tag( $code ) {
		if ( is_null ( $code ) ) {
			return false;
		}
		$found = false;
		$tags  = wp_cache_get ( 'icl_language_tags', '', false, $found );
		if ( $found === true ) {
			if ( isset( $tags[ $code ] ) ) {
				return $tags[ $code ];
			}
		}

		$all_tags = array();
		$all_tags_data = $this->wpdb->get_results( "SELECT code, tag FROM {$this->wpdb->prefix}icl_languages" );
		foreach($all_tags_data as $tag_data) {
			$all_tags[$tag_data->code] = $tag_data->tag;
		}

		$tag = isset($all_tags[$code]) ? $all_tags[$code] : false;
		wp_cache_set( 'icl_language_tags', $all_tags);

		return $tag ? $tag : $this->get_locale( $code );
	}

	function get_locale( $code ) {

		return $this->locale_utils->get_locale( $code );
	}

	function switch_locale( $lang_code = false ) {
		$this->locale_utils->switch_locale( $lang_code );
	}

	function get_locale_file_names() {

		return $this->locale_utils->get_locale_file_names();
	}

	function set_locale_file_names( $locale_file_names_pairs ) {

		return $this->locale_utils->set_locale_file_names( $locale_file_names_pairs );
	}

	function pre_option_page_on_front() {
		global $switched;

		$pre_option_page = new WPML_Pre_Option_Page( $this->wpdb, $this, $switched, $this->this_lang );
		return $pre_option_page->get( 'page_on_front' );
	}

	function pre_option_page_for_posts() {

		global $switched;

		$pre_option_page = new WPML_Pre_Option_Page( $this->wpdb, $this, $switched, $this->this_lang );
		return $pre_option_page->get( 'page_for_posts' );
	}

	// adds the language parameter to the admin post filtering/search
	function restrict_manage_posts() {
		echo '<input type="hidden" name="lang" value="' . $this->this_lang . '" />';
	}

	// adds the language parameter to the admin pages search
	function restrict_manage_pages()
	{
		?>
		<script type="text/javascript">
			addLoadEvent(function () {
				jQuery('p.search-box').append('<input type="hidden" name="lang" value="<?php echo $this->this_lang ?>">');
			});
		</script>
	<?php
	}

	function get_edit_term_link( $link, $term_id, $taxonomy, $object_type ) {
		/** @var WPML_Term_Translation $wpml_term_translations */
		global $wpml_term_translations;
		$default_language = $this->get_default_language();
		$current_language = $this->get_current_language();
		$lang = $wpml_term_translations->lang_code_by_termid($term_id);
		$lang = $lang ? $lang : $default_language;

		if ( $lang !== $default_language || $current_language !== $default_language ) {
			$link .= '&lang=' . $lang;
		}

		return $link;
	}

	/**
	 * Wrapper for \WPML_Post_Translation::pre_option_sticky_posts_filter
	 *
	 * @param int[] $posts
	 *
	 * @return int[]
	 *
	 * @uses \WPML_Post_Translation::pre_option_sticky_posts_filter to filter the sticky posts
	 *
	 * @hook pre_option_sticky_posts
	 */
	function option_sticky_posts( $posts ) {
		/** @var WPML_Post_Translation $wpml_post_translations */
		global $wpml_post_translations;

		remove_filter( 'pre_option_sticky_posts', array( $this, 'option_sticky_posts' ) );
		$posts = $wpml_post_translations->pre_option_sticky_posts_filter($posts, $this);
		add_filter( 'pre_option_sticky_posts', array( $this, 'option_sticky_posts' ), 10, 2 );

		return $posts;
	}

	function noscript_notice()
	{
		?>
		<noscript>
		<div class="error"><?php echo __( 'WPML admin screens require JavaScript in order to display. JavaScript is currently off in your browser.', 'sitepress' ) ?></div></noscript><?php
	}

	function get_inactive_content() {
		$inactive         = array();
		$current_language = $this->get_current_language();
		$res_p_prepared   = $this->wpdb->prepare( "
				   SELECT COUNT(p.ID) AS c, p.post_type, lt.name AS language FROM {$this->wpdb->prefix}icl_translations t
					JOIN {$this->wpdb->posts} p ON t.element_id=p.ID AND t.element_type LIKE %s
					JOIN {$this->wpdb->prefix}icl_languages l ON t.language_code = l.code AND l.active = 0
					JOIN {$this->wpdb->prefix}icl_languages_translations lt ON lt.language_code = l.code  AND lt.display_language_code=%s
					GROUP BY p.post_type, t.language_code
				", array( wpml_like_escape('post_') . '%', $current_language) );
		$res_p            = $this->wpdb->get_results( $res_p_prepared );
		if ($res_p) {
			foreach ( $res_p as $r ) {
				$inactive[ $r->language ][ $r->post_type ] = $r->c;
			}
		}
		$res_t_query = "
		   SELECT COUNT(p.term_taxonomy_id) AS c, p.taxonomy, lt.name AS language FROM {$this->wpdb->prefix}icl_translations t
			JOIN {$this->wpdb->term_taxonomy} p ON t.element_id=p.term_taxonomy_id
			JOIN {$this->wpdb->prefix}icl_languages l ON t.language_code = l.code AND l.active = 0
			JOIN {$this->wpdb->prefix}icl_languages_translations lt ON lt.language_code = l.code  AND lt.display_language_code=%s
			WHERE t.element_type LIKE %s
			GROUP BY p.taxonomy, t.language_code
		";
		$res_t_query_prepared = $this->wpdb->prepare($res_t_query, $current_language, wpml_like_escape('tax_') . '%');
		$res_t = $this->wpdb->get_results( $res_t_query_prepared );
		if ($res_t) {
			foreach ( $res_t as $r ) {
				if ( $r->taxonomy == 'category' && $r->c == 1 ) {
					continue; //ignore the case of just the default category that gets automatically created for a new language
				}
				$inactive[ $r->language ][ $r->taxonomy ] = $r->c;
			}
		}

		return $inactive;
	}

	function menu_footer()
	{
		include ICL_PLUGIN_PATH . '/menu/menu-footer.php';
	}

	function  save_user_options() {
		$user_id = $_POST[ 'user_id' ];
		if ( $user_id ) {
			update_user_meta( $user_id, 'icl_admin_language', $_POST[ 'icl_user_admin_language' ] );
			update_user_meta( $user_id, 'icl_show_hidden_languages', isset( $_POST[ 'icl_show_hidden_languages' ] ) ? intval( $_POST[ 'icl_show_hidden_languages' ] ) : 0 );
			update_user_meta( $user_id, 'icl_admin_language_for_edit', isset( $_POST[ 'icl_admin_language_for_edit' ] ) ? intval( $_POST[ 'icl_admin_language_for_edit' ] ) : 0 );
			$this->reset_admin_language_cookie();
		}
	}

	function help_admin_notice() {
		$args = array(
			'name' => 'wpml-intro',
			'iso'  => defined( 'WPLANG' ) ? WPLANG : ''
		);
		$q = http_build_query( $args );
		?>
		<br clear="all"/>
		<div id="message" class="updated message fade" style="clear:both;margin-top:5px;"><p>
				<?php _e( 'WPML is a powerful plugin with many features. Would you like to see a quick overview?', 'sitepress' ); ?>
			</p>

			<p>
				<a href="<?php echo ICL_API_ENDPOINT ?>/destinations/go?<?php echo $q ?>" target="_blank" class="button-primary"><?php _e( 'Yes', 'sitepress' ) ?></a>&nbsp;
				<input type="hidden" id="icl_dismiss_help_nonce" value="<?php echo $icl_dhn = wp_create_nonce( 'dismiss_help_nonce' ) ?>"/>
				<a href="admin.php?page=<?php echo basename( ICL_PLUGIN_PATH ) . '/menu/languages.php' ?>" class="button"><?php _e( 'No thanks, I will configure myself', 'sitepress' ) ?></a>&nbsp;
				<a title="<?php _e( 'Stop showing this message', 'sitepress' ) ?>" id="icl_dismiss_help" href=""><?php _e( 'Dismiss', 'sitepress' ) ?></a>
			</p>
		</div>
	<?php
	}

	function upgrade_notice() {
		include ICL_PLUGIN_PATH . '/menu/upgrade_notice.php';
	}

	function add_posts_management_column( $columns ) {
		$new_columns = $columns;

		global $posts;
		if ( count( $this->get_active_languages() ) <= 1 || get_query_var( 'post_status' ) == 'trash' ) {
			return $columns;
		}

		if ( isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] == 'inline-save' && $_POST[ 'post_ID' ] ) {
			$p     = new stdClass();
			$p->ID = $_POST[ 'post_ID' ];
			$posts = array( $p );
		} elseif ( empty( $posts ) ) {
			return $columns;
		}
		if ( is_null( $this->post_status_display ) ) {
			$post_ids = array();
			foreach ( $posts as $p ) {
				$post_ids[ ] = $p->ID;
			}
			$this->post_status_display = new WPML_Post_Status_Display( $this->get_active_languages() );
		}
		$active_languages = $this->get_active_languages();
		$active_languages = apply_filters('wpml_active_languages_access', $active_languages, array( 'action'=>'edit' ));
		$languages        = array();
		foreach ( $active_languages as $v ) {
			if ( $v[ 'code' ] == $this->get_current_language() ) {
				continue;
			}
			$languages[ ] = $v[ 'code' ];
		}

		if ( count( $languages ) > 0 ) {
			
			$flags_cache  = new WPML_WP_Cache( 'add_posts_management_column' );
			$key          = md5( serialize( $languages ) . $this->admin_language );
			$found        = false;
			$flags_column = $flags_cache->get( $key, $found );
			if ( !$found ) {
				$res = $this->wpdb->get_results( $this->wpdb->prepare( "
												SELECT f.lang_code, f.flag, f.from_template, l.name
												FROM {$this->wpdb->prefix}icl_flags f
													JOIN {$this->wpdb->prefix}icl_languages_translations l ON f.lang_code = l.language_code
												WHERE l.display_language_code = %s AND f.lang_code IN(" . wpml_prepare_in( $languages ) . ")
												", $this->admin_language ) );
	
				foreach ( $res as $r ) {
					if ( $r->from_template ) {
						$wp_upload_dir = wp_upload_dir();
						$flag_path     = $wp_upload_dir['baseurl'] . '/flags/';
					} else {
						$flag_path = ICL_PLUGIN_URL . '/res/flags/';
					}
					$flags[ $r->lang_code ] = '<img src="' . $flag_path . $r->flag . '" width="18" height="12" alt="' . $r->name . '" title="' . $r->name . '" style="margin:2px" />';
				}
				$flags_column = '';
				foreach ( $active_languages as $v ) {
					if ( isset( $flags[ $v['code'] ] ) ) {
						$flags_column .= $flags[ $v['code'] ];
					}
				}
				
				$flags_cache->set( $key, $flags_column );
			}
			
			$new_columns = array();
			foreach ( $columns as $k => $v ) {
				$new_columns[ $k ] = $v;
				if ( ( $k === 'title' || $k === 'name' ) && ! isset( $new_columns['icl_translations'] ) ) {
					$new_columns['icl_translations'] = $flags_column;
				}
			}
		}

		return $new_columns;
	}

	function add_content_for_posts_management_column( $column_name ) {
		if ( $column_name !== 'icl_translations' ) {
			return;
		}

		global $id;
		$active_languages = $this->get_active_languages ();
		$active_languages = apply_filters('wpml_active_languages_access', $active_languages, array( 'action'=>'edit' ));
		$current_language = $this->get_current_language ();
		foreach ( $active_languages as $v ) {
			if ( $v[ 'code' ] === $current_language ) {
				continue;
			}
			$icon_html = $this->post_status_display->get_status_html (
				$id,
				$v[ 'code' ]
			);
			echo $icon_html;
		}
	}

	function display_wpml_footer() {
		if ( $this->settings[ 'promote_wpml' ] ) {
			$wpml_site_languages = array( 'es', 'de', 'fr', 'pt-br', 'ja', 'ru', 'zh-hans', 'it', 'he', 'ar' );
			$url_language_code   = in_array( ICL_LANGUAGE_CODE, $wpml_site_languages ) ? ICL_LANGUAGE_CODE . '/' : '';

			$part_one = _x( 'Multilingual WordPress', 'Multilingual WordPress with WPML: first part', 'sitepress' );
			$part_two = _x( 'with WPML', 'Multilingual WordPress with WPML: second part', 'sitepress' );

			echo '<p id="wpml_credit_footer"><a href="https://wpml.org/' . $url_language_code . '" rel="nofollow" >' . $part_one . '</a> ' . $part_two . '</p>';
		}
	}

	function xmlrpc_methods( $methods )
	{
		//Translation proxy XMLRPC calls
		$methods[ 'translationproxy.get_languages_list' ] = array( $this, 'xmlrpc_get_languages_list' );

		return $methods;
	}

	function xmlrpc_call_actions( $action ) {
		$params = icl_xml2array ( print_r ( file_get_contents( 'php://input' ), true ) );
		add_filter( 'is_protected_meta', array( $this, 'xml_unprotect_wpml_meta' ), 10, 3 );
		switch ( $action ) {
			case 'wp.getPage':
			case 'blogger.getPost': // yet this doesn't return custom fields
				if ( isset( $params[ 'methodCall' ][ 'params' ][ 'param' ][ 1 ][ 'value' ][ 'int' ][ 'value' ] ) ) {
					$page_id         = $params[ 'methodCall' ][ 'params' ][ 'param' ][ 1 ][ 'value' ][ 'int' ][ 'value' ];
					$lang_details    = $this->get_element_language_details( $page_id, 'post_' . get_post_type( $page_id ) );
					$this->this_lang = $lang_details->language_code; // set the current language to the posts language
					update_post_meta( $page_id, '_wpml_language', $lang_details->language_code );
					update_post_meta( $page_id, '_wpml_trid', $lang_details->trid );
					$active_languages = $this->get_active_languages();
					$res              = $this->get_element_translations( $lang_details->trid );
					$translations     = array();
					foreach ( $active_languages as $k => $v ) {
						if ( $page_id != $res[ $k ]->element_id ) {
							$translations[ $k ] = isset( $res[ $k ]->element_id ) ? $res[ $k ]->element_id : 0;
						}
					}
					update_post_meta( $page_id, '_wpml_translations', wp_json_encode( $translations ) );
				}
				break;
			case 'metaWeblog.getPost':
				if ( isset( $params[ 'methodCall' ][ 'params' ][ 'param' ][ 0 ][ 'value' ][ 'int' ][ 'value' ] ) ) {
					$page_id         = $params[ 'methodCall' ][ 'params' ][ 'param' ][ 0 ][ 'value' ][ 'int' ][ 'value' ];
					$lang_details    = $this->get_element_language_details( $page_id, 'post_' . get_post_type( $page_id ) );
					$this->this_lang = $lang_details->language_code; // set the current language to the posts language
					update_post_meta( $page_id, '_wpml_language', $lang_details->language_code );
					update_post_meta( $page_id, '_wpml_trid', $lang_details->trid );
					$active_languages = $this->get_active_languages();
					$res              = $this->get_element_translations( $lang_details->trid );
					$translations     = array();
					foreach ( $active_languages as $k => $v ) {
						if ( isset( $res[ $k ] ) && $page_id != $res[ $k ]->element_id ) {
							$translations[ $k ] = isset( $res[ $k ]->element_id ) ? $res[ $k ]->element_id : 0;
						}
					}
					update_post_meta( $page_id, '_wpml_translations', wp_json_encode( $translations ) );
				}
				break;
			case 'metaWeblog.getRecentPosts':
				if ( isset( $params[ 'methodCall' ][ 'params' ][ 'param' ][ 3 ][ 'value' ][ 'int' ][ 'value' ] ) ) {
					$num_posts = intval( $params[ 'methodCall' ][ 'params' ][ 'param' ][ 3 ][ 'value' ][ 'int' ][ 'value' ] );
					if ( $num_posts ) {
						$posts = get_posts( 'suppress_filters=false&numberposts=' . $num_posts );
						foreach ( $posts as $p ) {
							$lang_details = $this->get_element_language_details( $p->ID, 'post_post' );
							update_post_meta( $p->ID, '_wpml_language', $lang_details->language_code );
							update_post_meta( $p->ID, '_wpml_trid', $lang_details->trid );
							$active_languages = $this->get_active_languages();
							$res              = $this->get_element_translations( $lang_details->trid );
							$translations     = array();
							foreach ( $active_languages as $k => $v ) {
								if ( $p->ID != $res[ $k ]->element_id ) {
									$translations[ $k ] = isset( $res[ $k ]->element_id ) ? $res[ $k ]->element_id : 0;
								}
							}
							update_post_meta( $p->ID, '_wpml_translations', wp_json_encode( $translations ) );
						}
					}
				}
				break;
			case 'metaWeblog.newPost':
				$custom_fields = false;
				if ( is_array( $params[ 'methodCall' ][ 'params' ][ 'param' ][ 3 ][ 'value' ][ 'struct' ][ 'member' ] ) ) {
					foreach ( $params[ 'methodCall' ][ 'params' ][ 'param' ][ 3 ][ 'value' ][ 'struct' ][ 'member' ] as $m ) {
						if ( $m[ 'name' ][ 'value' ] == 'custom_fields' ) {
							$custom_fields_raw = $m[ 'value' ][ 'array' ][ 'data' ][ 'value' ];
							break;
						}
					}
				}

				if ( !empty( $custom_fields_raw ) ) {
					foreach ( $custom_fields_raw as $cf ) {
						$key = $value = null;
						foreach ( $cf[ 'struct' ][ 'member' ] as $m ) {
							if ( $m[ 'name' ][ 'value' ] == 'key' )
								$key = $m[ 'value' ][ 'string' ][ 'value' ]; elseif ( $m[ 'name' ][ 'value' ] == 'value' )
								$value = $m[ 'value' ][ 'string' ][ 'value' ];
						}
						if ( $key !== null && $value !== null )
							$custom_fields[ $key ] = $value;
					}
				}

				if ( is_array( $custom_fields ) && isset( $custom_fields[ '_wpml_language' ] ) && isset( $custom_fields[ '_wpml_trid' ] ) ) {
					$icl_post_language = $custom_fields[ '_wpml_language' ];
					$icl_trid          = $custom_fields[ '_wpml_trid' ];
					$post_type = $params[ 'methodCall' ][ 'params' ][ 'param' ][ 3 ][ 'value' ][ 'struct' ][ 'member' ][ 2 ][ 'value' ][ 'string' ][ 'value' ];
					if ( !$this->wpdb->get_var( $this->wpdb->prepare("SELECT translation_id
                                                          FROM {$this->wpdb->prefix}icl_translations
                                                          WHERE element_type=%s AND trid=%d AND language_code=%s" ,
                                                         "post_".$post_type, $icl_trid, $icl_post_language ) )
                    ) {
						$_POST[ 'icl_post_language' ] = $icl_post_language;
						$_POST[ 'icl_trid' ]          = $icl_trid;
					} else {
						$IXR_Error = new IXR_Error( 401, __( 'A translation for this post already exists', 'sitepress' ) );
						echo $IXR_Error->getXml();
						exit( 1 );
					}
				}
				break;
			case 'metaWeblog.editPost':
				$post_id = $params[ 'methodCall' ][ 'params' ][ 'param' ][ 0 ][ 'value' ][ 'int' ][ 'value' ];
				if ( !$post_id ) {
					break;
				}
				$custom_fields = $params[ 'methodCall' ][ 'params' ][ 'param' ][ 3 ][ 'value' ][ 'struct' ][ 'member' ][ 3 ][ 'value' ][ 'array' ][ 'data' ][ 'value' ];
				if ( is_array( $custom_fields ) ) {
					$icl_trid = false;
					$icl_post_language = false;
					foreach ( $custom_fields as $cf ) {
						if ( $cf[ 'struct' ][ 'member' ][ 0 ][ 'value' ][ 'string' ][ 'value' ] == '_wpml_language' ) {
							$icl_post_language = $cf[ 'struct' ][ 'member' ][ 1 ][ 'value' ][ 'string' ][ 'value' ];
						} elseif ( $cf[ 'struct' ][ 'member' ][ 0 ][ 'value' ][ 'string' ][ 'value' ] == '_wpml_trid' ) {
							$icl_trid = $cf[ 'struct' ][ 'member' ][ 1 ][ 'value' ][ 'string' ][ 'value' ];
						}
					}

					$epost_id = $this->wpdb->get_var( $this->wpdb->prepare("SELECT element_id FROM {$this->wpdb->prefix}icl_translations
                                                                WHERE element_type='post_post'
                                                                  AND trid=%d AND language_code=%s",
                                                               $icl_trid, $icl_post_language ) );
					if ( $icl_trid && $icl_post_language && ( !$epost_id || $epost_id == $post_id ) ) {
						$_POST[ 'icl_post_language' ] = $icl_post_language;
						$_POST[ 'icl_trid' ]          = $icl_trid;
					} else {
						$IXR_Error = new IXR_Error( 401, __( 'A translation in this language already exists', 'sitepress' ) );
						echo $IXR_Error->getXml();
						exit( 1 );
					}
				}
				break;
		}
	}

	function xmlrpc_get_languages_list( $lang ) {
		if ( !is_null( $lang ) ) {
			if ( !$this->wpdb->get_var( "SELECT code FROM {$this->wpdb->prefix}icl_languages WHERE code='" . esc_sql( $lang ) . "'" ) ) {
				$IXR_Error = new IXR_Error( 401, __( 'Invalid language code', 'sitepress' ) );
				echo $IXR_Error->getXml();
				exit( 1 );
			}
			$this->admin_language = $lang;
		}
		define( 'WP_ADMIN', true ); // hack - allow to force display language
		$active_languages = $this->get_active_languages( true );

		return $active_languages;
	}

	function xml_unprotect_wpml_meta( $protected, $meta_key, $meta_type )
	{
		$metas_list = array( '_wpml_trid', '_wpml_translations', '_wpml_language' );
		if ( in_array( $meta_key, $metas_list, true ) ) {
			$protected = false;
		}

		return $protected;
	}

	function get_current_action_step() {
		$icl_lang_status = $this->settings[ 'icl_lang_status' ];
		$has_translators = false;
		foreach ( (array)$icl_lang_status as $k => $lang ) {
			if ( !is_numeric( $k ) )
				continue;
			if ( !empty( $lang[ 'translators' ] ) ) {
				$has_translators = true;
				break;
			}
		}
		if ( !$has_translators ) {
			return 0;
		}
		$cms_count = $this->wpdb->get_var( "SELECT COUNT(rid) FROM {$this->wpdb->prefix}icl_core_status WHERE status=3" );
		if ( $cms_count > 0 ) {
			return 4;
		}
		$cms_count = $this->wpdb->get_var( "SELECT COUNT(rid) FROM {$this->wpdb->prefix}icl_core_status WHERE 1" );
		if ( $cms_count == 0 ) {
			// No documents sent yet
			return 1;
		}
		if ( $this->settings[ 'icl_balance' ] <= 0 ) {
			return 2;
		}

		return 3;
	}

	function show_action_list() {
		$steps = array(
			__( 'Select translators', 'sitepress' ), __( 'Send documents to translation', 'sitepress' ), __( 'Deposit payment', 'sitepress' ), __( 'Translations will be returned to your site', 'sitepress' )
		);

		$current_step = $this->get_current_action_step();
		if ( $current_step >= sizeof( $steps ) ) {
			// everything is already setup.
			if ( $this->settings[ 'last_action_step_shown' ] ) {
				return '';
			} else {
				$this->save_settings( array( 'last_action_step_shown' => 1 ) );
			}
		}

		$output = '
			<h3>' . __( 'Setup check list', 'sitepress' ) . '</h3>
			<ul id="icl_check_list">';

		foreach ( $steps as $index => $step ) {
			$step_data = $step;

			if ( $index < $current_step || ( $index == 4 && $this->settings[ 'icl_balance' ] > 0 ) ) {
				$attr = ' class="icl_tick"';
			} else {
				$attr = ' class="icl_next_step"';
			}

			if ( $index == $current_step ) {
				$output .= '<li class="icl_info"><b>' . $step_data . '</b></li>';
			} else {
				$output .= '<li' . $attr . '>' . $step_data . '</li>';
			}
			$output .= "\n";
		}

		$output .= '
			</ul>';

		return $output;
	}

	function meta_generator_tag()
	{
		$lids = array();
		$active_languages = $this->get_active_languages();
		if($active_languages) {
			foreach ( $active_languages as $l ) {
				$lids[ ] = $l[ 'id' ];
			}
			$stt = join( ",", $lids );
			$stt .= ";";
			printf( '<meta name="generator" content="WPML ver:%s stt:%s" />' . PHP_EOL, ICL_SITEPRESS_VERSION, $stt );
		}
	}

	function update_language_cookie($language_code) {
		$_COOKIE[ '_icl_current_language' ] = $language_code;
	}

	function get_language_cookie() {
		static $active_languages = false;
		if ( isset( $_COOKIE[ '_icl_current_language' ] ) ) {
			$lang = substr( $_COOKIE[ '_icl_current_language' ], 0, 10 );
			if(!$active_languages) {
				$active_languages = $this->get_active_languages();
			}
			if ( !isset( $active_languages[ $lang ] ) ) {
				$lang = $this->get_default_language();
			}
		} else {
			$lang = '';
		}

		return $lang;
	}

	// _icl_current_language will have to be replaced with _icl_current_language
	function set_admin_language_cookie( $lang = false ) {
		if ( is_admin() ) {
			global $wpml_request_handler;

			$wpml_request_handler->set_language_cookie( $lang ? $lang : $this->get_default_language() );
		}
	}

	function get_admin_language_cookie() {
		global $wpml_request_handler;

		return is_admin() ? $wpml_request_handler->get_cookie_lang() : null;
	}

	function reset_admin_language_cookie() {
		$this->set_admin_language_cookie( $this->get_default_language() );
	}

	function rewrite_rules_filter( $value ) {
		/** @var WPML_URL_Converter $wpml_url_converter */
		global $wpml_url_converter;

		return $wpml_url_converter->rewrite_rules_filter ( $value );
	}

	function is_rtl( $lang = false )
	{
		if ( is_admin() ) {
			if ( empty( $lang ) )
				$lang = $this->get_admin_language();

		} else {
			if ( empty( $lang ) )
				$lang = $this->get_current_language();
		}

		$rtl_languages_codes = apply_filters('wpml_rtl_languages_codes', array( 'ar', 'he', 'fa', 'ku' ));

		return in_array( $lang, $rtl_languages_codes );
	}

	/**
	 * Returns an array of post types that are set to be translatable
	 * @param array $default  Set the default value, in case no posts are set to be translatable (default: array())
	 *
	 * @return array
	 */
	function get_translatable_documents_filter( $default = array() ) {
		$post_types = $this->get_translatable_documents(false);
		if(!$post_types) {
			$post_types = $default;
		}

		return $post_types;
	}

	function get_translatable_documents( $include_not_synced = false )
	{
		global $wp_post_types;
		$icl_post_types = array();
		//TODO: [WPML 3.3] Keep attachments as exception, and handle it from WPML-Media by filtering get_translatable_documents
		$attachment_is_translatable = $this->is_translated_post_type( 'attachment' );
		$exceptions = array( 'revision', 'nav_menu_item' );
		if(!$attachment_is_translatable) {
			$exceptions[] = 'attachment';
		}

		foreach ( $wp_post_types as $k => $v ) {
			if ( !in_array( $k, $exceptions ) ) {
				if ( !$include_not_synced && ( empty( $this->settings[ 'custom_posts_sync_option' ][ $k ] ) || $this->settings[ 'custom_posts_sync_option' ][ $k ] != 1 ) && !in_array( $k, array( 'post', 'page' ) ) )
					continue;
				$icl_post_types[ $k ] = $v;
			}
		}
		$icl_post_types = apply_filters( 'get_translatable_documents', $icl_post_types );

		return $icl_post_types;
	}

	function get_translatable_taxonomies( $include_not_synced = false, $object_type = 'post' )
	{
		global $wp_taxonomies;
		$t_taxonomies = array();
		if ( $include_not_synced ) {
			if ( in_array( $object_type, $wp_taxonomies[ 'post_tag' ]->object_type ) )
				$t_taxonomies[ ] = 'post_tag';
			if ( in_array( $object_type, $wp_taxonomies[ 'category' ]->object_type ) )
				$t_taxonomies[ ] = 'category';
		}
		foreach ( $wp_taxonomies as $taxonomy_name => $taxonomy ) {
			// exceptions
			if ( 'post_format' == $taxonomy_name )
				continue;
			if ( in_array( $object_type, $taxonomy->object_type ) && !empty( $this->settings[ 'taxonomies_sync_option' ][ $taxonomy_name ] ) ) {
				$t_taxonomies[ ] = $taxonomy_name;
			}
		}

		if ( has_filter( 'get_translatable_taxonomies' ) ) {
			$filtered     = apply_filters( 'get_translatable_taxonomies', array( 'taxs' => $t_taxonomies, 'object_type' => $object_type ) );
			$t_taxonomies = $filtered[ 'taxs' ];
			if ( empty( $t_taxonomies ) )
				$t_taxonomies = array();
		}

		return $t_taxonomies;
	}

	function is_translated_taxonomy( $tax ) {
		$option_key          = 'taxonomies_sync_option';
		$readonly_config_key = 'taxonomies_readonly_config';

		return $this->is_translated_element( $tax, $option_key, $readonly_config_key, $this->get_always_translatable_taxonomies() );
	}

	public function is_translated_post_type_filter($value, $post_type ) {
		return $this->is_translated_post_type( $post_type );
	}

	function is_translated_post_type( $type ) {

		$translated = apply_filters( 'pre_wpml_is_translated_post_type', $type === 'attachment' ? false : null, $type );

		return $translated !== null ? $translated : $this->is_translated_element( $type,
		                                                                          'custom_posts_sync_option',
		                                                                          'custom-types_readonly_config',
		                                                                          $this->get_always_translatable_post_types() );
	}

	function dashboard_widget_setup() {
		if ( current_user_can( 'manage_options' ) && (!defined('ICL_HIDE_DASHBOARD_WIDGET') || !ICL_HIDE_DASHBOARD_WIDGET) ) {
			$dashboard_widgets_order = (array)get_user_option( "meta-box-order_dashboard" );
			$icl_dashboard_widget_id = 'icl_dashboard_widget';
			$all_widgets             = array();
			foreach ( $dashboard_widgets_order as $v ) {
				$all_widgets = array_merge( $all_widgets, explode( ',', $v ) );
			}
			wp_add_dashboard_widget (
				$icl_dashboard_widget_id,
				sprintf( __( 'Multi-language | WPML %s', 'sitepress' ), ICL_SITEPRESS_VERSION ),
				array( $this, 'dashboard_widget' ), null
			);
			if ( !in_array( $icl_dashboard_widget_id, $all_widgets )
			     && isset( $dashboard_widgets_order[ 'side' ] ) ) {
				$dashboard_widgets_order[ 'side' ] = $icl_dashboard_widget_id . ',' . strval( $dashboard_widgets_order[ 'side' ] );
				$user                              = wp_get_current_user();
				update_user_option( $user->ID, 'meta-box-order_dashboard', $dashboard_widgets_order, true );
			}
		}
	}

	function dashboard_widget()
	{
		do_action( 'icl_dashboard_widget_notices' );
		include_once ICL_PLUGIN_PATH . '/menu/dashboard-widget.php';
	}

	function verify_post_translations_action( $post_types ) {
		if ( ! is_array( $post_types ) ) {
			$post_types = (array) $post_types;
		}
		foreach ( $post_types as $post_type => $translate ) {
			if ( $translate && ! is_numeric( $post_type ) ) {
				$this->verify_post_translations( $post_type );
			}
		}
	}

	/**
	 * Sets the default language for all posts in a given post type that do not have any language set
	 *
	 * @param string $post_type
	 */
	public function verify_post_translations( $post_type ) {
		$sql          = "
					SELECT p.ID
					FROM {$this->wpdb->posts} p
					LEFT OUTER JOIN {$this->wpdb->prefix}icl_translations t
						ON t.element_id = p.ID AND t.element_type = CONCAT('post_', p.post_type)
					WHERE p.post_type = %s AND t.translation_id IS NULL
				";
		$sql_prepared = $this->wpdb->prepare( $sql, array( $post_type ) );
		$results      = $this->wpdb->get_col( $sql_prepared );

		$def_language = $this->get_default_language();
		foreach ( $results as $id ) {
			$this->set_element_language_details( $id, 'post_' . $post_type, false, $def_language );
		}
	}

	/**
	 * This function is to be used on setting a taxonomy from untranslated to being translated.
	 * It creates potentially missing translations and reassigns posts to the then created terms in the correct language.
	 * This function affects all terms in a taxonomy and therefore, depending on the database size results in
	 * heavy resource demand. It should not be used to fix term and post assignment problems other than those
	 * resulting from the action of turning a translated taxonomy into an untranslated one.
	 *
	 * An exception is being made for the installation process assigning all existing terms the default language,
	 * given no prior language information is saved about them in the database.
	 *
	 * @param $taxonomy string
	 */
	function verify_taxonomy_translations( $taxonomy ) {
		$term_utils = new WPML_Terms_Translations();
		$tax_sync = new WPML_Term_Language_Synchronization( $taxonomy, $this, $this->wpdb, $term_utils );
		if ( $this->get_setting( 'setup_complete' ) ) {
			$tax_sync->set_translated();
		} else {
			$tax_sync->set_initial_term_language( );
		}
		delete_option( $taxonomy . '_children', array() );
	}

	function wp_upgrade_locale( $locale ) {

		return defined( 'WPLANG' ) && WPLANG ? WPLANG : ICL_WP_UPDATE_LOCALE;
	}

	function admin_language_switcher() {
		require_once ICL_PLUGIN_PATH . '/menu/wpml-admin-lang-switcher.class.php';
		$admin_lang_switcher = new WPML_Admin_Language_Switcher();
		$admin_lang_switcher->render();
	}

	function admin_notices( $message, $class = "updated" )
	{
		static $hook_added = 0;
		$this->_admin_notices[ ] = array( 'class' => $class, 'message' => $message );

		if ( !$hook_added )
			add_action( 'admin_notices', array( $this, '_admin_notices_hook' ) );

		$hook_added = 1;
	}

	function _admin_notices_hook()
	{
		if ( !empty( $this->_admin_notices ) )
			foreach ( $this->_admin_notices as $n ) {
				echo '<div class="' . $n[ 'class' ] . '">';
				echo '<p>' . $n[ 'message' ] . '</p>';
				echo '</div>';
			}
	}

	function head_langs()
	{
		$languages = $this->get_ls_languages( array( 'skip_missing' => true ) );
		// If there are translations and is not paged content...

		//Renders head alternate links only on certain conditions
		$the_post = get_post();
		$the_id   = $the_post ? $the_post->ID : false;
		$is_valid = count( $languages ) > 1 && !is_paged() && ( ( ( is_single() || is_page() ) && $the_id && get_post_status( $the_id ) == 'publish' ) || ( is_home() || is_front_page() || is_archive() ) );

		if ( $is_valid ) {
			foreach ( $languages as $code => $lang ) {
				$alternate_hreflang = apply_filters( 'wpml_alternate_hreflang', $lang[ 'url' ], $code );
				printf( '<link rel="alternate" hreflang="%s" href="%s" />' . PHP_EOL,
				        $this->get_language_tag( $code ),
				        str_replace( '&amp;', '&', $alternate_hreflang ) );
			}
		}
	}

	function allowed_redirect_hosts( $hosts ) {
		if ( $this->settings[ 'language_negotiation_type' ] == 2 ) {
			foreach ( $this->settings[ 'language_domains' ] as $code => $url ) {
				if ( !empty( $this->active_languages[ $code ] ) ) {
					$parts = parse_url( $url );
					if ( isset($parts[ 'host' ]) && !in_array( $parts[ 'host' ], $hosts ) ) {
						$hosts[ ] = $parts[ 'host' ];
					}
				}
			}
		}

		return $hosts;
	}

	function icl_nonces()
	{
		//@since 3.1	Calls made only when in Translation Management pages
		$allowed_pages = array();
		if(defined('WPML_TM_FOLDER')) {
			$allowed_pages[] = WPML_TM_FOLDER . '/menu/main.php';
		}
		if(!isset($_REQUEST['page']) || !in_array($_REQUEST['page'], $allowed_pages)) {
			return;
		}
		//messages
		wp_nonce_field( 'icl_messages_nonce', '_icl_nonce_m' );
	}

	public static function get_installed_plugins() {
		if(!function_exists('get_plugins')) {
			require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		}
		$wp_plugins        = get_plugins();
		$wpml_plugins_list = array(
			'WPML Multilingual CMS'       => array( 'installed' => false, 'active' => false, 'file' => false, 'plugin' => false, 'slug' => 'sitepress-multilingual-cms' ),
			'WPML CMS Nav'                => array( 'installed' => false, 'active' => false, 'file' => false, 'plugin' => false, 'slug' => 'wpml-cms-nav' ),
			'WPML String Translation'     => array( 'installed' => false, 'active' => false, 'file' => false, 'plugin' => false, 'slug' => 'wpml-string-translation' ),
			'WPML Sticky Links'           => array( 'installed' => false, 'active' => false, 'file' => false, 'plugin' => false, 'slug' => 'wpml-sticky-links' ),
			'WPML Translation Management' => array( 'installed' => false, 'active' => false, 'file' => false, 'plugin' => false, 'slug' => 'wpml-translation-management' ),
			'WPML Media'                  => array( 'installed' => false, 'active' => false, 'file' => false, 'plugin' => false, 'slug' => 'wpml-media' ),
			'WooCommerce Multilingual'    => array( 'installed' => false, 'active' => false, 'file' => false, 'plugin' => false, 'slug' => 'woocommerce-multilingual' ),
			'Gravity Forms Multilingual'  => array( 'installed' => false, 'active' => false, 'file' => false, 'plugin' => false, 'slug' => 'gravityforms-multilingual' ),
		);

		foreach ( $wpml_plugins_list as $wpml_plugin_name => $v ) {
			foreach ( $wp_plugins as $file => $plugin ) {
				$plugin_name = $plugin[ 'Name' ];
				if ( $plugin_name == $wpml_plugin_name ) {
					$wpml_plugins_list[ $plugin_name ][ 'installed' ] = true;
					$wpml_plugins_list[ $plugin_name ][ 'plugin' ]    = $plugin;
					$wpml_plugins_list[ $plugin_name ][ 'file' ]      = $file;
				}
			}
		}

		return $wpml_plugins_list;
	}

	/**
	 * @param int  $limit
	 * @param bool $provide_object
	 * @param bool $ignore_args
	 *
	 * @return array
	 */
	public function get_backtrace($limit = 0, $provide_object = false, $ignore_args = true) {
		$options = false;

		if ( version_compare( phpversion(), '5.3.6' ) < 0 ) {
			// Before 5.3.6, the only values recognized are TRUE or FALSE,
			// which are the same as setting or not setting the DEBUG_BACKTRACE_PROVIDE_OBJECT option respectively.
			$options = $provide_object;
		} else {
			// As of 5.3.6, 'options' parameter is a bitmask for the following options:
			if ( $provide_object )
				$options |= DEBUG_BACKTRACE_PROVIDE_OBJECT;
			if ( $ignore_args )
				$options |= DEBUG_BACKTRACE_IGNORE_ARGS;
		}
		if ( version_compare( phpversion(), '5.4.0' ) >= 0 ) {
			$actual_limit    = $limit == 0 ? 0 : $limit + 1;
			$debug_backtrace = debug_backtrace( $options, $actual_limit ); //add one item to include the current frame
		} elseif ( version_compare( phpversion(), '5.2.4' ) >= 0 ) {
			//@link https://core.trac.wordpress.org/ticket/20953
			$debug_backtrace = debug_backtrace();
		} else {
			$debug_backtrace = debug_backtrace( $options );
		}

		//Remove the current frame
		if($debug_backtrace) {
			array_shift($debug_backtrace);
		}
		return $debug_backtrace;
	}

	/**
	 * Used as filter for wordpress core function url_to_postid()
	 *
	 * @global AbsoluteLinks $absolute_links_object
	 * @param string $url URL to filter
	 * @return string URL changed into format ...?p={ID} or original
	 */
	function url_to_postid($url) {

		if (strpos($url, 'wp-login.php') !== false
		    || strpos($url, '/wp-admin/') !== false
		    || strpos($url, '/wp-content/') !== false ) {
			return $url;
		}

		$is_language_in_domain = false; // if language negotiation type as lang. in domain
		$is_translated_domain = false; // if this url is in secondary language domain

		// for 'different domain per language' we need to switch_lang according to domain of parsed $url
		if (2 == $this->settings['language_negotiation_type'] && isset($this->settings['language_domains'])) {
			$is_language_in_domain = true;
			// if url domain fits to one of secondary language domains
			// switch sitepress language to this
			// but save current language context in $current_language, we will have to switch to this back
			$domains = array_filter( $this->get_setting( 'language_domains' ) );
			foreach ( $domains as $code => $domain ) {
				if ( strpos( $url, $domain ) === 0 ) {
					$is_translated_domain = true;
					$current_language     = $this->get_current_language();
					$this->switch_lang( $code );
					$url = str_replace( $domain, site_url(), $url );
					break;
				}
			}

			// if it is url in original domain
			// switch sitepress language to default language
			// but save current language context in $current_language, we will have to switch to this back
			if (!$is_translated_domain) {
				$current_language = $this->get_current_language();
				$default_language = $this->get_default_language();
				$this->switch_lang($default_language);
			}
		}

		// we will use AbsoluteLinks::_process_generic_text, so make sure that
		// we have this object here
		global $absolute_links_object;
		if (!isset($absolute_links_object) || !is_a($absolute_links_object, 'AbsoluteLinks') || $is_language_in_domain ) {
			require_once ICL_PLUGIN_PATH . '/inc/absolute-links/absolute-links.class.php';
			$absolute_links_object = new AbsoluteLinks();
		}

		// in next steps we will have to compare processed url with original,
		// so we need to save original
		$original_url = $url;
		// we also need site_url for comparisions
		$site_url = site_url();

		// _process_generic_text will change slug urls into ?p=1 or ?cpt-slug=cpt-title
		// but this function operates not on clean url but on html <a> element
		// we need to change temporary url into html, pass to this function and
		// extract url from returned html
		$html = '<a href="'.$url.'">removeit</a>';
		$alp_broken_links = array();
		remove_filter('url_to_postid', array($this, 'url_to_postid'));
		$html = $absolute_links_object->_process_generic_text($html, $alp_broken_links);
		add_filter('url_to_postid', array($this, 'url_to_postid'));
		$url = str_replace(array('<a href="', '">removeit</a>'), array('', ''), $html);

		// for 'different domain per language', switch language back. now we can do this
		if ($is_language_in_domain && isset($current_language)) {
			$this->switch_lang($current_language);
		}

		// if this is not url to external site
		if ( 0 === strpos($original_url, $site_url)) {

			// if this is url like ...?cpt-rewrite-slug=cpt-title
			// change it into ...?p=11
			$url2 = $this->cpt_url_to_id_url($url, $original_url);

			if ($url2 == $url && $original_url != $url) { // if it was not a case with ?cpt-slug=cpt-title
				// if this is translated post and it has the same slug as original,
				// _process_generic_text returns the same ID for both
				// lets check if it is this case and replace ID in returned url
				$url = $this->maybe_adjust_url($url, $original_url);
			} else { // yes! it was not a case with ?cpt-slug=cpt-title
				$url = $url2;
			}
		}

		return $url;
	}

	/**
	 * Check if $url is in format ...?cpt-slug=cpt-title and change into ...?p={ID}
	 *
	 *
	 * @param string $url URL, probably in format ?cpt-slug=cpt-title
	 * @param string $original_url URL in original format (probably with permalink)
	 * @return string URL, if $url was in expected format ?cpt-slug format, url is now changed into ?p={ID}, otherwise, returns $url as it was passed in parameter
	 */
	function cpt_url_to_id_url($url, $original_url) {

		$parsed_url = parse_url($url);

		if (!isset($parsed_url['query'])) {
			return $url;
		}

		$query = $parsed_url['query'];


		parse_str($query, $vars);


		$args = array(
				'public'   => true,
				'_builtin' => false
		);

		$post_types = get_post_types($args, 'objects');

		foreach ($post_types as $name => $attrs) {
			if ( isset( $vars[ $attrs->rewrite['slug'] ] ) ) {
				$post_type = $name;
				$post_slug = $vars[trim($attrs->rewrite['slug'],'/')];
				break;
			}
		}

		if (!isset($post_type, $post_slug)) {
			return $url;
		}

		$args = array(
				'name' => $post_slug,
				'post_type' => $post_type
		);

		$post = new WP_Query($args);

		if (!isset($post->post)) {
			return $url;
		}

		$id = $post->post->ID;

		$post_language = $this->get_language_for_element($id, 'post_' . $post_type);

		$url_language = $this->get_language_from_url($original_url);

		$new_vars = array();
		if ($post_language != $url_language) {

			$trid         = $this->get_element_trid( $id, 'post_' . $post_type );
			$translations = $this->get_element_translations( $trid, 'post_' . $post_type );

			if (isset($translations[$url_language])) {
				$translation = $translations[$url_language];
				if (isset($translation->element_id)) {
					$new_vars['p'] = $translation->element_id;
				}

			}

		} else {
			$new_vars['p'] = $id;
		}

		$new_query = http_build_query($new_vars);

		$url = str_replace($query, $new_query, $url);

		return $url;
	}

	/**
	 * Fix sticky link url to have ID of translated post (used in case both translations have same slug)
	 *
	 * @param string $url - url in sticky link form
	 * @param string $original_url - url in permalink form
	 * @return string  - url in sticky link form to correct translation
	 */
	private function maybe_adjust_url( $url, $original_url ) {
		$parsed_url = parse_url ( $url );
		$query      = isset( $parsed_url[ 'query' ] ) ? $parsed_url[ 'query' ] : "";

		parse_str ( $query, $vars );
		$inurl   = isset( $vars[ 'page_id' ] ) ? 'page_id' : null;
		$inurl   = $inurl === null && isset( $vars[ 'p' ] ) ? 'p' : null;
		$post_id = $inurl !== null ? $vars[ $inurl ] : null;

		if ( isset( $post_id ) ) {
			$post_type     = get_post_type ( $post_id );
			$post_language = $this->get_language_for_element ( $post_id, 'post_' . $post_type );
			$url_language  = $this->get_language_from_url ( $original_url );
			if ( $post_language !== $url_language ) {
				$trid         = $this->get_element_trid ( $post_id, 'post_' . $post_type );
				$translations = $this->get_element_translations ( $trid, 'post_' . $post_type );
				if ( isset( $translations[ $url_language ] ) ) {
					$translation = $translations[ $url_language ];
					if ( isset( $translation->element_id ) ) {
						$vars[ $inurl ] = $translation->element_id;
						$new_query      = http_build_query ( $vars );
						$url            = str_replace ( $query, $new_query, $url );
					}
				}
			}
		}

		return $url;
	}

	/**
	 * Find language of document based on given permalink
	 *
	 * @param string $url Local url in permalink form
	 * @return string language code
	 */
	function get_language_from_url( $url ) {
		/* @var WPML_URL_Converter $wpml_url_converter */
		global $wpml_url_converter;

		return $wpml_url_converter->get_language_from_url ( $url );
	}

	function update_wpml_config_index_event(){

		require_once ICL_PLUGIN_PATH . '/inc/wpml-config/wpml-config-update.php';

		return update_wpml_config_index_event();
	}


	function update_wpml_config_index_event_ajax(){
		if($this->update_wpml_config_index_event()){
			echo date('F j, Y H:i a', time());
		}

		die;
	}

	function update_index_screen(){
		return include ICL_PLUGIN_PATH . '/menu/theme-plugins-compatibility.php';
	}

	/**
	 * Filter to add language field to WordPress search form
	 *
	 * @param string $form HTML code of search for before filtering
	 * @return string HTML code of search form
	 */
	function get_search_form_filter($form) {
		if ( strpos($form, wpml_get_language_input_field() ) === false ) {
			$form = str_replace("</form>", wpml_get_language_input_field() . "</form>", $form);
		}
		return $form;
	}

	/**
	 * @return bool
	 */
	public function got_string_translation() {
		return $this->get_string_translation_settings() != false && defined('WPML_ST_VERSION');
	}

	/**
	 * @param string $key
	 *
	 * @return bool|mixed
	 */
	public function get_string_translation_settings($key = '') {
		$setting = $this->get_setting( 'st' );

		if( $this->setting_array_is_set_or_has_key( $setting, $key ) ) {
			$setting = $setting[$key];
		}
		return $setting;
	}

	/**
	 * @param $setting
	 * @param $key
	 *
	 * @return bool
	 */
	private function setting_array_is_set_or_has_key( $setting, $key ) {
		return $key != '' && $setting && isset( $setting[ $key ] );
	}

	/**
	 * @param string|array $source
	 *
	 * @return array
	 */
	private function explode_and_trim( $source ) {
		if ( ! is_array( $source ) ) {
			$source = array_map( 'trim', explode( ',', $source ) );
		}

		return $source;
	}

	/**
	 * @param string|array $terms_ids
	 *
	 * @return array
	 */
	private function adjust_taxonomies_terms_ids( $terms_ids ) {
		/** @var  WPML_Term_Translation $wpml_term_translations */
		global $wpml_term_translations;

		$terms_ids = array_filter( array_unique( $this->explode_and_trim( $terms_ids ) ) );

		$translated_ids = array();
		foreach ( $terms_ids as $term_id ) {
			$translated_ids[ ] = $wpml_term_translations->term_id_in( $term_id, $this->this_lang );
		}

		return array_filter( $translated_ids );
	}

	/**
	 * @param $element
	 * @param $option_key
	 * @param $readonly_config_key
	 * @param $always_true_types
	 *
	 * @return bool|mixed
	 */
	private function is_translated_element( $element, $option_key, $readonly_config_key, $always_true_types ) {
		$ret = false;

		if ( is_scalar( $element ) ) {
			if ( in_array( $element, $always_true_types ) ) {
				$ret = true;
			} else {
				$translation_management_options = $this->get_setting( 'translation-management' );
				$element_settings               = icl_get_sub_setting( $option_key, $element );
				if ( isset( $element_settings ) ) {
					$ret = icl_get_sub_setting( $option_key, $element );
				} elseif ( isset( $translation_management_options[ $readonly_config_key ][ $element ] ) && $translation_management_options[ $readonly_config_key ][ $element ] == 1 ) {
					$ret = true;
				} else {
					$ret = false;
				}
			}
		}

		return $ret;
	}

	/**
	 * @return array
	 */
	private function get_always_translatable_post_types() {
		return $this->always_translatable_post_types;
	}

	/**
	 * @return array
	 */
	private function get_always_translatable_taxonomies() {
		return $this->always_translatable_taxonomies;
	}

	/**
     * @param Integer $master_post_id The original post id for which duplicate posts are to be retrieved
     * @return Integer[] An associative array with language codes as indexes and post_ids as values
     */
    function get_duplicates( $master_post_id ) {
        $this->post_duplication = $this->post_duplication === null ? new WPML_Post_Duplication( $this->wpdb, $this )
            : $this->post_duplication;

        return $this->post_duplication->get_duplicates ( $master_post_id );
    }

    /**
     * @param Integer $master_post_id ID of the to be duplicated post
     * @param String $lang Language code to which the post is to be duplicated
     * @return bool|int|WP_Error
     */function make_duplicate($master_post_id, $lang){
        $this->post_duplication = $this->post_duplication === null ? new WPML_Post_Duplication( $this->wpdb, $this )
            : $this->post_duplication;

        return $this->post_duplication->make_duplicate($master_post_id, $lang);
    }

	function get_new_post_source_id ( $post_id ) {
		global $pagenow;

		if ( $pagenow == 'post-new.php' && isset( $_GET['trid'] ) && isset( $_GET['source_lang'] ) ) {
			// Get the template from the source post.
			$translations = $this->get_element_translations( $_GET['trid'] );

			if (isset($translations[$_GET['source_lang']])) {
				$post_id = $translations[$_GET['source_lang']]->element_id;
			}
		}

		return $post_id;
	}

	/**
	 * @param int           $element_id
	 * @param string     $element_type
	 * @param bool|false $return_original_if_missing
	 * @param null       $language_code
	 *
	 * @return int|null
	 */
	function get_object_id( $element_id, $element_type = 'post', $return_original_if_missing = false, $language_code = null ) {
		global $wp_post_types, $wp_taxonomies;

		$ret_element_id = null;

		if ( $element_id ) {
			$language_code = $language_code ? $language_code : $this->get_current_language();

			if ( $element_type == 'any' ) {
				$post_type = get_post_type( $element_id );
				if ( $post_type ) {
					$element_type = $post_type;
				} else {
					$element_type = null;
				}
			}

			if ( $element_type ) {
				if ( isset( $wp_taxonomies[ $element_type ] ) ) {
					/** @var WPML_Term_Translation $wpml_term_translations */
					global $wpml_term_translations;
					$ret_element_id = is_taxonomy_translated( $element_type ) ? $wpml_term_translations->term_id_in( $element_id, $language_code ) : $element_id;
				} elseif ( isset( $wp_post_types[ $element_type ] ) ) {
					/** @var WPML_Post_Translation $wpml_post_translations */
					global $wpml_post_translations;
					$ret_element_id = is_post_type_translated( $element_type ) ? $wpml_post_translations->element_id_in( $element_id, $language_code ) : $element_id;
				} else {
					$ret_element_id = null;
				}

				$ret_element_id = $ret_element_id ? (int) $ret_element_id : ( $return_original_if_missing && ! $ret_element_id ? $element_id : null );
			}
		}

		return $ret_element_id;
	}

	private function is_troubleshooting_page() {
		return isset( $_REQUEST[ 'page' ] ) && $_REQUEST[ 'page' ] == ICL_PLUGIN_FOLDER . '/menu/troubleshooting.php';
	}
}
