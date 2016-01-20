<?php
/**
 * @package wpml-core
 * @used-by Sitepress::ajax_setup
 */
global $wpdb, $sitepress, $sitepress_settings;

$request = filter_input( INPUT_POST, 'icl_ajx_action' );
$request = $request ? $request : filter_input( INPUT_GET, 'icl_ajx_action' );
switch ( $request ) {
	case 'health_check':
		icl_set_setting( 'ajx_health_checked', true, true );
		exit;
	case 'get_browser_language':
		$http_accept_language            = filter_var( $_SERVER[ 'HTTP_ACCEPT_LANGUAGE' ], FILTER_SANITIZE_SPECIAL_CHARS );
		$accepted_languages              = explode( ';', $http_accept_language );
		$default_accepted_language       = $accepted_languages[ 0 ];
		$default_accepted_language_codes = explode( ',', $default_accepted_language );
		wp_send_json_success($default_accepted_language_codes);
}

$request = wpml_get_authenticated_action();

$iclsettings = $this->get_settings();
$default_language = $this->get_default_language();

switch($request){
    case 'set_active_languages':
        $resp = array();
        $old_active_languages_count = count($this->get_active_languages());
	    $lang_codes = filter_input( INPUT_POST, 'langs', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE );
        $lang_codes = explode(',',$lang_codes);
        $setup_instance = wpml_get_setup_instance();
        if ( $setup_instance->set_active_languages ( $lang_codes ) ) {
            $resp[0] = 1;
            $iclsettings['active_languages'] = wpml_reload_active_languages_setting();
            $active_langs = $this->get_active_languages();
            $iclresponse ='';
            $default_categories = $sitepress->get_setting( 'default_categories', array() );
            $default_category_main = $wpdb->get_var(
                $wpdb->prepare("SELECT name FROM {$wpdb->terms} t
                                JOIN {$wpdb->term_taxonomy} tx ON t.term_id=tx.term_id
                                WHERE term_taxonomy_id = %d
                                  AND taxonomy='category'",
                               $default_categories[$default_language] ));
            $default_category_trid = $wpdb->get_var(
                $wpdb->prepare("SELECT trid FROM {$wpdb->prefix}icl_translations
                                WHERE element_id = %d
                                  AND element_type='tax_category'",
                               $default_categories[$default_language]));
            foreach($active_langs as $lang){
                $is_default = ( $default_language ==$lang['code']);
                $iclresponse .= '<li ';
                if($is_default) $iclresponse .= 'class="default_language"';
                $iclresponse .= '><label><input type="radio" name="default_language" value="' . $lang['code'] .'" ';
                if($is_default) $iclresponse .= 'checked="checked"';
                $iclresponse .= '>' . $lang['display_name'];
                if($is_default) $iclresponse .= ' ('. __('default','sitepress') . ')';
                $iclresponse .= '</label></li>';
            }

            $resp[1] = $iclresponse;
            // response 1 - blog got more than 2 languages; -1 blog reduced to 1 language; 0 - no change
            if(count($lang_codes) > 1){
                $resp[2] =$this->get_setting('setup_complete') ? 1 : -2;
            }elseif($old_active_languages_count > 1 && count($lang_codes) < 2){
                $resp[2] = $this->get_setting('setup_complete') ? -1 : -3;
            }else{
                $resp[2] = $this->get_setting('setup_complete') ? 0 : -3;
            }
        }else{
            $resp[0] = 0;
        }

        echo join('|',$resp);
        do_action('icl_update_active_languages');
        break;
    case 'set_default_language':
        $previous_default = $default_language;
	    $new_default_language = filter_input( INPUT_POST, 'lang', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE );
        if($response = $this->set_default_language( $new_default_language )){
            echo '1|'.$previous_default.'|';
        }else{
            echo'0||' ;
        }
        if(1 === $response){
            echo __('WordPress language file (.mo) is missing. Keeping existing display language.', 'sitepress');
        }
        break;
    case 'set_languages_order':
	    $languages_order = filter_input( INPUT_POST, 'order', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE );
        $iclsettings['languages_order'] = explode(';', $languages_order );
        $this->save_settings($iclsettings);
        echo json_encode(array('message' => __('Languages order updated', 'sitepress')));
        break;
    case 'icl_tdo_options':
        $iclsettings['translated_document_status']      = filter_input( INPUT_POST, 'icl_translated_document_status', FILTER_SANITIZE_NUMBER_INT, FILTER_NULL_ON_FAILURE );
        $iclsettings['translated_document_page_url']    = filter_input( INPUT_POST, 'icl_translated_document_page_url', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE );
        $this->save_settings($iclsettings);
        echo '1|';
       break;
    case 'icl_save_language_switcher_options':
        $_POST   = stripslashes_deep( $_POST );

	    $ls_options = filter_input_array (
		    INPUT_POST,
		    array_fill_keys (
			    array(
				    'icl_lso_link_empty',
				    'icl_lso_flags',
				    'icl_lso_native_lang',
				    'icl_lso_display_lang'
			    ),
			    array( 'filter' => FILTER_SANITIZE_NUMBER_INT, 'flags' => FILTER_NULL_ON_FAILURE )
		    )
	    );

        if ( ! $this->get_setting( 'setup_complete' ) ) {
            if ( isset( $iclsettings['setup_reset'] ) ) {
                unset( $iclsettings['setup_reset'] );
            }
            /** @var WPML_Language_Resolution $wpml_language_resolution */
            global $wpml_language_resolution;
            $active_languages        = $wpml_language_resolution->get_active_language_codes();
            $language_domains_helper = new WPML_Language_Domains( $this );
            foreach ( $active_languages as $language_code ) {
                if ( $language_code !== $default_language ) {
                    if ( $language_domains_helper->validate_language_per_directory( $language_code ) ) {
                        $iclsettings['language_negotiation_type'] = 1;
                    }
                    break;
                }
            }
        }

        if(isset($_POST['icl_lang_sel_config'])){
            $iclsettings['icl_lang_sel_config'] = $_POST['icl_lang_sel_config'];
        }

        if(isset($_POST['icl_lang_sel_footer_config'])){
            $iclsettings['icl_lang_sel_footer_config'] = $_POST['icl_lang_sel_footer_config'];
        }

        if (isset($_POST['icl_lang_sel_type']))
            $iclsettings['icl_lang_sel_type'] = $_POST['icl_lang_sel_type'];
        if (isset($_POST['icl_lang_sel_stype']))
            $iclsettings['icl_lang_sel_stype'] = $_POST['icl_lang_sel_stype'];

			$iclsettings['icl_lang_sel_orientation'] = $_POST['icl_lang_sel_orientation'];

        if (isset($_POST['icl_lang_sel_footer']))
            $iclsettings['icl_lang_sel_footer'] = 1;
        else $iclsettings['icl_lang_sel_footer'] = 0;

        if (isset($_POST['icl_post_availability']))
            $iclsettings['icl_post_availability'] = 1;
        else $iclsettings['icl_post_availability'] = 0;

        if (isset($_POST['icl_post_availability_position']))
            $iclsettings['icl_post_availability_position'] = $_POST['icl_post_availability_position'];

        if (isset($_POST['icl_post_availability_text']))
            $iclsettings['icl_post_availability_text'] = $_POST['icl_post_availability_text'];

        $iclsettings['icl_widget_title_show'] = (isset($_POST['icl_widget_title_show'])) ? 1 : 0;
        $iclsettings['icl_additional_css'] = $_POST['icl_additional_css'];

        $iclsettings['display_ls_in_menu'] = @intval($_POST['display_ls_in_menu']);
        $iclsettings['menu_for_ls'] = @intval($_POST['menu_for_ls']);

        $iclsettings['icl_lang_sel_copy_parameters'] = join(', ', array_map('trim', explode(',', $_POST['copy_parameters'])));

        if(!$ls_options['icl_lso_flags'] && !$ls_options['icl_lso_native_lang'] && !$ls_options['icl_lso_display_lang']){
            echo '0|';
            echo __('At least one of the language switcher style options needs to be checked', 'sitepress');
        }else{
            $this->save_settings($iclsettings);
            echo 1;
        }
	    $ls_sidebars = isset($_POST[ 'icl_language_switcher_sidebars' ]) ? $_POST[ 'icl_language_switcher_sidebars' ] : false;
	    $setup_instance = wpml_get_setup_instance();
	    $setup_instance->finish_step3($ls_sidebars, $ls_options);
	    break;
    case 'registration_form_submit':
        
        $ret['error'] = '';
		$setup_instance = wpml_get_setup_instance();

        if($_POST['button_action'] == 'later'){
            //success
            $ret['success'] = sprintf(__('WPML will work on your site, but you will not receive updates. WPML updates are essential for keeping your site running smoothly and secure. To receive automated updates, you need to complete the registration, in the %splugins admin%s page.', 'sitepress'),
                '<a href="' . admin_url('plugin-install.php?tab=commercial') . '">', '</a>');
        }elseif($_POST['button_action'] == 'finish'){
	        $setup_instance->finish_installation();
        }else{
            if(empty($_POST['installer_site_key'])){
                $ret['error'] = __('Missing site key.');
            }else{
                $site_key = $_POST['installer_site_key'];
                if(class_exists('WP_Installer')){
                    $args['repository_id'] = 'wpml';
                    $args['nonce'] = wp_create_nonce('save_site_key_' . $args['repository_id']) ;
                    $args['site_key'] = $site_key;
                    $args['return']   = 1;
                    $r = WP_Installer()->save_site_key($args);
                    if(!empty($r['error'])){
                        $ret['error'] = $r['error'];
                    }else{
                        //success
                        $ret['success'] = __('Thank you for registering WPML on this site. You will receive automatic updates when new versions are available.', 'sitepress');
                    }
                }
	            $setup_instance->finish_installation($site_key);
            }
        }

        echo json_encode($ret);
        break;
    case 'icl_admin_language_options':
        $iclsettings['admin_default_language'] = $_POST['icl_admin_default_language'];
        $this->save_settings($iclsettings);
        echo 1;
        break;
    case 'icl_blog_posts':
        $iclsettings['show_untranslated_blog_posts'] = $_POST['icl_untranslated_blog_posts'];
        $this->save_settings($iclsettings);
        echo 1;
        break;
    case 'icl_page_sync_options':
        $iclsettings['sync_page_ordering'] = @intval($_POST['icl_sync_page_ordering']);
        $iclsettings['sync_page_parent'] = @intval($_POST['icl_sync_page_parent']);
        $iclsettings['sync_page_template'] = @intval($_POST['icl_sync_page_template']);
        $iclsettings['sync_comment_status'] = @intval($_POST['icl_sync_comment_status']);
        $iclsettings['sync_ping_status'] = @intval($_POST['icl_sync_ping_status']);
        $iclsettings['sync_sticky_flag'] = @intval($_POST['icl_sync_sticky_flag']);
        $iclsettings['sync_password'] = @intval($_POST['icl_sync_password']);
        $iclsettings['sync_private_flag'] = @intval($_POST['icl_sync_private_flag']);
        $iclsettings['sync_post_format'] = @intval($_POST['icl_sync_post_format']);
        $iclsettings['sync_delete'] = @intval($_POST['icl_sync_delete']);
        $iclsettings['sync_delete_tax'] = @intval($_POST['icl_sync_delete_tax']);
        $iclsettings['sync_post_taxonomies'] = @intval($_POST['icl_sync_post_taxonomies']);
        $iclsettings['sync_post_date'] = @intval($_POST['icl_sync_post_date']);
        $iclsettings['sync_comments_on_duplicates'] = @intval($_POST['icl_sync_comments_on_duplicates']);
        $this->save_settings($iclsettings);
        echo 1;
        break;
    case 'language_domains':
        $language_domains_helper = new WPML_Language_Domains( $this );
        echo $language_domains_helper->render_domains_options();
        break;
    case 'icl_theme_localization_type':
        $icl_tl_type = @intval($_POST['icl_theme_localization_type']);
        $iclsettings['theme_localization_type'] = $icl_tl_type;
        $iclsettings['theme_localization_load_textdomain'] = @intval($_POST['icl_theme_localization_load_td']);
	    $filtered_textdomain_value = filter_input( INPUT_POST, 'textdomain_value', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE );
        $iclsettings['gettext_theme_domain_name'] = $filtered_textdomain_value;
        if($icl_tl_type == 1) {
			do_action ( 'wpml_scan_theme_for_strings', true );
        }elseif($icl_tl_type==2){
            $parent_theme = get_template_directory();
            $child_theme = get_stylesheet_directory();
            $languages_folders = array();

            if($found_folder = icl_tf_determine_mo_folder($parent_theme)){
                $languages_folders['parent'] = $found_folder;
            }
            if($parent_theme != $child_theme && $found_folder = icl_tf_determine_mo_folder($child_theme)){
                $languages_folders['child'] = $found_folder;
            }
            $iclsettings['theme_language_folders'] = $languages_folders;

        }
        $this->save_settings($iclsettings);
        echo '1|'.$icl_tl_type;
        break;
    case 'dismiss_help':
        icl_set_setting('dont_show_help_admin_notice', true);
        icl_save_settings();
        break;
    case 'dismiss_page_estimate_hint':
        icl_set_setting('dismiss_page_estimate_hint', !icl_get_setting('dismiss_page_estimate_hint'));
        icl_save_settings();
        break;
    case 'dismiss_upgrade_notice':
        icl_set_setting('hide_upgrade_notice', implode('.', array_slice(explode('.', ICL_SITEPRESS_VERSION), 0, 3)));
        icl_save_settings();
        break;
    case 'setup_got_to_step1':
        $setup_instance = wpml_get_setup_instance();
		$setup_instance->go_to_setup1();
        break;
    case 'setup_got_to_step2':
        icl_set_setting('setup_wizard_step', 2);
        icl_save_settings();
        break;
    case 'toggle_show_translations':
        icl_set_setting('show_translations_flag', intval(!icl_get_setting('show_translations_flag', false)));
        icl_save_settings();
        break;
    case 'icl_messages':
        //TODO: handle with Translation Proxy
        if ( ! icl_get_setting( 'icl_disable_reminders' ) ) {
            break;
        }
        exit;
    case 'icl_help_links':
        if ( isset( $iclq ) && $iclq ) {
        $links = $iclq->get_help_links();
            $lang  = icl_get_setting( 'admin_default_language' );
        if (!isset($links['resources'][$lang])) {
            $lang = 'en';
        }

        if (isset($links['resources'][$lang])) {
            $output = '<ul>';
            foreach( $links['resources'][$lang]['resource'] as $resource) {
                if (isset($resource['attr'])) {
                    $title = $resource['attr']['title'];
                    $url = $resource['attr']['url'];
                    $icon = $resource['attr']['icon'];
                    $icon_width = $resource['attr']['icon_width'];
                    $icon_height = $resource['attr']['icon_height'];
                } else {
                    $title = $resource['title'];
                    $url = $resource['url'];
                    $icon = $resource['icon'];
                    $icon_width = $resource['icon_width'];
                    $icon_height = $resource['icon_height'];
                }
                $output .= '<li>';
                if ($icon) {
                    $output .= '<img style="vertical-align: bottom; padding-right: 5px;" src="' . $icon . '"';
                    if ($icon_width) {
                        $output .= ' width="' . $icon_width . '"';
                    }
                    if ($icon_height) {
                        $output .= ' height="' . $icon_height . '"';
                    }
                    $output .= '>';
                }
                $output .= '<a href="' . $url . '">' . $title . '</a></li>';

            }
            $output .= '</ul>';
            echo '1|' . $output;
        } else {
            echo '0|';
        }
        }
        break;
    case 'icl_show_sidebar':
        icl_set_setting('icl_sidebar_minimized', $_POST['state']=='hide'?1:0);
        icl_save_settings();
        break;
    case 'icl_promote_form':
        icl_set_setting('promote_wpml', @intval($_POST['icl_promote']));
        icl_save_settings();
        echo '1|';
        break;
    case 'save_translator_note':
        update_post_meta($_POST['post_id'], '_icl_translator_note', $_POST['note']);
        break;
    case 'icl_st_track_strings':
        foreach($_POST['icl_st'] as $k=>$v){
            $iclsettings['st'][$k] = $v;
        }
		if(isset($iclsettings)) {
        	$this->save_settings($iclsettings);
		}
        echo 1;
        break;
    case 'icl_st_more_options':
        $iclsettings['st']['translated-users'] = !empty($_POST['users']) ? array_keys($_POST['users']) : array();
        $this->save_settings($iclsettings);
        if(!empty($iclsettings['st']['translated-users'])){
            $sitepress_settings['st']['translated-users'] = $iclsettings['st']['translated-users'];
            icl_st_register_user_strings_all();
        }
        echo 1;
        break;
    case 'icl_st_ar_form':
        // Auto register string settings.
        $iclsettings['st']['icl_st_auto_reg'] = $_POST['icl_auto_reg_type'];
        $this->save_settings($iclsettings);
        echo 1;
        break;
    case 'icl_hide_languages':
        $iclsettings['hidden_languages'] = empty($_POST['icl_hidden_languages']) ? array() : $_POST['icl_hidden_languages'];
        $this->set_setting('hidden_languages', array()); //reset current value
        $active_languages = $this->get_active_languages();
        if(!empty($iclsettings['hidden_languages'])){
             if(1 == count($iclsettings['hidden_languages'])){
                 $out = sprintf(__('%s is currently hidden to visitors.', 'sitepress'),
                    $active_languages[$iclsettings['hidden_languages'][0]]['display_name']);
             }else{
                 foreach($iclsettings['hidden_languages'] as $l){
                     $_hlngs[] = $active_languages[$l]['display_name'];
                 }
                 $hlangs = join(', ', $_hlngs);
                 $out = sprintf(__('%s are currently hidden to visitors.', 'sitepress'), $hlangs);
             }
             $out .= ' ' . sprintf(__('You can enable its/their display for yourself, in your <a href="%s">profile page</a>.', 'sitepress'),
                                            'profile.php#wpml');
        } else {
            $out = __('All languages are currently displayed.', 'sitepress');
        }
        $this->save_settings($iclsettings);
        echo '1|'.$out;
        break;
    case 'icl_adjust_ids':
        $iclsettings['auto_adjust_ids'] = @intval($_POST['icl_adjust_ids']);
        $this->save_settings($iclsettings);
        echo '1|';
        break;
    case 'icl_automatic_redirect':
		if (!isset($_POST['icl_remember_language']) || $_POST['icl_remember_language'] < 24) {
			$_POST['icl_remember_language'] = 24;
		}
        $iclsettings['automatic_redirect'] = @intval($_POST['icl_automatic_redirect']);
        $iclsettings['remember_language'] = @intval($_POST['icl_remember_language']);
        $this->save_settings($iclsettings);
        echo '1|';
        break;
    case 'icl_troubleshooting_more_options':
        $iclsettings['troubleshooting_options'] = $_POST['troubleshooting_options'];
        $this->save_settings($iclsettings);
        echo '1|';
        break;
    case 'reset_languages':
        $setup_instance = wpml_get_setup_instance();
        $setup_instance->reset_language_data();
        break;
    case 'icl_support_update_ticket':
        if (isset($_POST['ticket'])) {
            $temp = str_replace('icl_support_ticket_', '', $_POST['ticket']);
            $temp = explode('_', $temp);
            $id = (int)$temp[0];
            $num = (int)$temp[1];
            if ($id && $num) {
                if (isset($iclsettings['icl_support']['tickets'][$id])) {
                    $iclsettings['icl_support']['tickets'][$id]['messages'] = $num;
                    $this->save_settings($iclsettings);
                }
            }
        }
        break;
    case 'icl_custom_tax_sync_options':
        if(!empty($_POST['icl_sync_tax'])){
            foreach($_POST['icl_sync_tax'] as $k=>$v){
                $iclsettings['taxonomies_sync_option'][$k] = $v;
                if($v){
                    $this->verify_taxonomy_translations($k);
                }
            }
			if ( isset( $iclsettings ) ) {
				$this->save_settings($iclsettings);
			}
        }
        echo '1|';
        break;
	case 'icl_custom_posts_sync_options':
		$new_options = ! empty( $_POST['icl_sync_custom_posts'] ) ? $_POST['icl_sync_custom_posts'] : array();
		/** @var WPML_Settings_Helper $settings_helper */
		$settings_helper = wpml_load_settings_helper();
		$settings_helper->update_cpt_sync_settings( $new_options );
		echo '1|';
		break;
	case 'copy_from_original':
		/*
		 * apply filtering as to add further elements
		 * filters will have to like as such
		 * add_filter('wpml_copy_from_original_fields', 'my_copy_from_original_fields');
		 *
		 * function my_copy_from_original_fields( $elements ) {
		 *  $custom_field = 'editor1';
		 *  $elements[ 'customfields' ][ $custom_fields ] = array(
		 *    'editor_name' => 'custom_editor_1',
		 *    'editor_type' => 'editor',
		 *    'value'       => 'test'
		 *  );
		 *
		 *  $custom_field = 'editor2';
		 *  $elements[ 'customfields' ][ $custom_fields ] = array(
		 *    'editor_name' => 'textbox1',
		 *    'editor_type' => 'text',
		 *    'value'       => 'testtext'
		 *  );
		 *
		 *  return $elements;
		 * }
		 * This filter would result in custom_editor_1 being populated with the value "test"
		 * and the textfield with id #textbox1 to be populated with "testtext".
		 * editor type is always either text when populating general fields or editor when populating
		 * a wp editor. The editor id can be either judged from the arguments used in the wp_editor() call
		 * or from looking at the tinyMCE.Editors object that the custom post type's editor sends to the browser.
		 */
        $content_type = filter_input( INPUT_POST, 'content_type' );
        $excerpt_type = filter_input( INPUT_POST, 'excerpt_type' );
        $trid = filter_input( INPUT_POST, 'trid' );
        $lang = filter_input( INPUT_POST, 'lang' );
        echo wp_json_encode( WPML_Post_Edit_Ajax::copy_from_original_fields( $content_type, $excerpt_type, $trid, $lang ) );
		break;
    case 'save_user_preferences':
	    $user_preferences = $this->get_user_preferences();
	    $this->set_user_preferences( array_merge_recursive( $user_preferences, $_POST[ 'user_preferences' ] ) );
	    $this->save_user_preferences();
	    break;
    case 'wpml_cf_translation_preferences':
        if (empty($_POST['custom_field'])) {
            echo '<span style="color:#FF0000;">'
            . __('Error: No custom field', 'sitepress') . '</span>';
            die();
        }
        $_POST['custom_field'] = @strval($_POST['custom_field']);
        if (!isset($_POST['translate_action'])) {
            echo '<span style="color:#FF0000;">'
            . __('Error: Please provide translation action', 'sitepress') . '</span>';
            die();
        }
        $_POST['translate_action'] = @intval($_POST['translate_action']);
        if (defined('WPML_TM_VERSION')) {
            global $iclTranslationManagement;
            if (!empty($iclTranslationManagement)) {
                $iclTranslationManagement->settings['custom_fields_translation'][$_POST['custom_field']] = $_POST['translate_action'];
                $iclTranslationManagement->save_settings();
                echo '<strong><em>' . __('Settings updated', 'sitepress') . '</em></strong>';
            } else {
                echo '<span style="color:#FF0000;">'
                . __('Error: WPML Translation Management plugin not initiated', 'sitepress')
                . '</span>';
            }
        } else {
            echo '<span style="color:#FF0000;">'
            . __('Error: Please activate WPML Translation Management plugin', 'sitepress')
                    . '</span>';
        }
        break;
    case 'icl_seo_options':
        $iclsettings['seo']['head_langs'] = isset($_POST['icl_seo_head_langs']) ? intval($_POST['icl_seo_head_langs']) : 0;
        $iclsettings['seo']['canonicalization_duplicates'] = isset($_POST['icl_seo_canonicalization_duplicates']) ? intval($_POST['icl_seo_canonicalization_duplicates']) : 0;
        $this->save_settings($iclsettings);
        echo '1|';
        break;
    case 'dismiss_object_cache_warning':
        $iclsettings['dismiss_object_cache_warning'] = true;
        $this->save_settings($iclsettings);
        echo '1|';
        break;
    case 'update_option':
        $iclsettings[$_REQUEST['option']] = $_REQUEST['value'];
        $this->save_settings($iclsettings);
        break;
	case 'connect_translations':
		$new_trid = $_POST['new_trid'];
		$post_type = $_POST['post_type'];
		$post_id = $_POST['post_id'];
		$set_as_source = $_POST['set_as_source'];

		$language_details = $sitepress->get_element_language_details($post_id, 'post_' . $post_type);

		if ( $set_as_source ) {
			$wpdb->update( $wpdb->prefix . 'icl_translations', array( 'source_language_code' => $language_details->language_code ), array( 'trid' => $new_trid, 'element_type' => 'post_' . $post_type ) );
			$wpdb->update( $wpdb->prefix . 'icl_translations', array( 'source_language_code' => null, 'trid' => $new_trid ), array( 'element_id' => $post_id, 'element_type' => 'post_' . $post_type ) );
		} else {
			$original_element_language = $sitepress->get_default_language();
			$trid_elements             = $sitepress->get_element_translations( $new_trid, 'post_' . $post_type );
			if($trid_elements) {
				foreach ( $trid_elements as $trid_element ) {
					if ( $trid_element->original ) {
						$original_element_language = $trid_element->language_code;
						break;
					}
				}
			}
			$wpdb->update( $wpdb->prefix . 'icl_translations', array( 'source_language_code' => $original_element_language, 'trid' => $new_trid ), array( 'element_id' => $post_id, 'element_type' => 'post_' . $post_type ) );
		}
		echo wp_json_encode(true);
		break;
	case 'get_posts_from_trid':
		$trid = $_POST['trid'];
		$post_type = $_POST['post_type'];

		$translations = $sitepress->get_element_translations($trid, 'post_' . $post_type);

		$results = array();
		foreach($translations as $language_code => $translation) {
			$post = get_post($translation->element_id);
			$title = $post->post_title ? $post->post_title : strip_shortcodes(wp_trim_words( $post->post_content, 50 ));
			$source_language_code = $translation->source_language_code;
			$results[] = (object) array('language' => $language_code, 'title' => $title, 'source_language' => $source_language_code);
		}
		echo wp_json_encode($results);
		break;
	case 'get_orphan_posts':
		$trid = $_POST['trid'];
		$post_type = $_POST['post_type'];
		$source_language = $_POST['source_language'];
		$results = $sitepress->get_orphan_translations($trid, $post_type, $source_language);

		echo wp_json_encode($results);

		break;
    default:
	    if(function_exists('ajax_' . $request)) {
		    $function_name = 'ajax_' . $request;
		    $function_name();
	    } else {
		    do_action('icl_ajx_custom_call', $request, $_REQUEST);
	    }
}
exit;
