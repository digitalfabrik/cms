<?php
	global $sitepress, $sitepress_settings, $wpdb;

    if(!is_plugin_active(basename(dirname(dirname(__FILE__))) . "/sitepress.php")){
        ?>
        <h2><?php echo __('Setup WPML', 'sitepress') ?></h2>
        <div class="updated fade">
            <p style="line-height:1.5"><?php _e('The WPML Multilingual CMS plugin is not currently enabled.', 'sitepress');?></p>
            <p style="line-height:1.5"><?php printf(__('Please go to the <a href="%s">Plugins</a> page and enable the WPML Multilingual CMS plugin before trying to configure the plugin.', 'sitepress'), 'plugins.php');?></p>
        </div>
        <?php
        return;
    }

	if ( isset( $_GET[ 'trop' ] ) ) {
		require_once dirname( __FILE__ ) . '/edit-languages.php';

		return;
	}

	$sitepress_settings                 = get_option( 'icl_sitepress_settings' );
	$setup_complete                     = $sitepress->get_setting( 'setup_complete' );
	$active_languages                   = $sitepress->get_active_languages();
	$hidden_languages                   = $sitepress->get_setting( 'hidden_languages' );
	$display_ls_in_menu                 = $sitepress->get_setting( 'display_ls_in_menu' );
	$menu_for_ls                        = $sitepress->get_setting( 'menu_for_ls' );
	$show_untranslated_blog_posts       = $sitepress->get_setting( 'show_untranslated_blog_posts' );
	$automatic_redirect                 = $sitepress->get_setting( 'automatic_redirect' );
	$setting_urls                       = $sitepress->get_setting( 'urls' );
	$existing_content_language_verified = $sitepress->get_setting( 'existing_content_language_verified' );
	$setup_wizard_step                  = $sitepress->get_setting( 'setup_wizard_step' );
	$language_negotiation_type          = $sitepress->get_setting( 'language_negotiation_type' );
	$language_domains                   = $sitepress->get_setting( 'language_domains' );
	$icl_widget_title_show              = $sitepress->get_setting( 'icl_widget_title_show' );
	$icl_lang_sel_type                  = $sitepress->get_setting( 'icl_lang_sel_type' );
	$icl_lang_sel_stype                 = $sitepress->get_setting( 'icl_lang_sel_stype' );
	$seo                                = $sitepress->get_setting( 'seo' );
	$default_language                   = $sitepress->get_default_language();
	$all_languages                      = $sitepress->get_languages( $sitepress->get_admin_language() );
	$sample_lang                        = false;
	$default_language_details           = false;

	if(!$existing_content_language_verified ){
        // try to determine the blog language
        $blog_current_lang = 0;
        if($blog_lang = get_option('WPLANG')){
            $exp = explode('_',$blog_lang);
            $blog_current_lang = $wpdb->get_var(
                                    $wpdb->prepare("SELECT code FROM {$wpdb->prefix}icl_languages WHERE code= %s",
                                                   $exp[0]));
        }
        if(!$blog_current_lang && defined('WPLANG') && WPLANG != ''){
            $blog_current_lang = $wpdb->get_var($wpdb->prepare("SELECT code FROM {$wpdb->prefix}icl_languages WHERE default_locale=%s", WPLANG));
            if(!$blog_current_lang){
                $blog_lang = WPLANG;
                $exp = explode('_',$blog_lang);
                $blog_current_lang = $wpdb->get_var(
                    $wpdb->prepare("SELECT code FROM {$wpdb->prefix}icl_languages WHERE code= %s",
                                   $exp[0]));
            }
        }
        if(!$blog_current_lang){
            $blog_current_lang = 'en';
        }
        $languages = $sitepress->get_languages($blog_current_lang);
    }else{
        $languages = $sitepress->get_languages($sitepress->get_admin_language());
        foreach($active_languages as $lang){
            if($lang['code'] != $default_language ){
                $sample_lang = $lang;
                break;
            }
        }
        $default_language_details = $sitepress->get_language_details( $default_language );
        $inactive_content = $sitepress->get_inactive_content();
    }

global $language_switcher_defaults, $language_switcher_defaults_alt;

$theme_wpml_config_file = WPML_Config::get_theme_wpml_config_file();


?>
<?php $sitepress->noscript_notice() ?>

<?php if($setup_complete || SitePress_Setup::languages_table_is_complete()) { ?>
<div class="wrap <?php if( empty( $setup_complete ) ): ?>wpml-wizard<?php endif; ?>">
    <div id="icon-wpml" class="icon32" ><br /></div>
    <h2><?php _e('Setup WPML', 'sitepress') ?></h2>

    <?php
    
	if( empty( $setup_complete ) ){ /* setup wizard */

            if(!$existing_content_language_verified ){
                $sw_width = 15;
            }elseif(count($sitepress->get_active_languages()) < 2 || $setup_wizard_step == 2){
                $sw_width = 35;
            }elseif($setup_wizard_step == 3){
                $sw_width = 60;
            }else{
                $sw_width = 85;
            }

			include ICL_PLUGIN_PATH . '/menu/setup/setup_001.php';

	} /* setup wizard */

    if ( ! $existing_content_language_verified || $setup_wizard_step <= 1 ): ?>
	    <?php
	    $setup_step = new WPML_Setup_Step_One_Menu( $sitepress );
	    echo $setup_step->render();
	    ?>
    <?php else: ?>
        <?php
		if(!empty( $setup_complete ) || $setup_wizard_step == 2): ?>
            <?php if(!empty( $setup_complete ) && (count($active_languages) > 1)): ?>
                <p>
                    <strong><?php _e('This screen contains the language settings for your site.','sitepress'); ?></strong>
                </p>
                <ul class="wpml-navigation-links js-wpml-navigation-links">
                    <li><a href="#lang-sec-1"><?php _e('Site Languages','sitepress'); ?></a></li>
                    <li><a href="#lang-sec-2"><?php _e('Language URL format', 'sitepress'); ?></a></li>
                    <li><a href="#lang-sec-3"><?php _e('Language switcher options', 'sitepress'); ?></a></li>
                    <li><a href="#lang-sec-4"><?php _e('Admin language', 'sitepress'); ?></a></li>
                    <li><a href="#lang-sec-6"><?php _e('Blog posts to display', 'sitepress'); ?></a></li>
                    <li><a href="#lang-sec-7"><?php _e('Hide languages', 'sitepress'); ?></a></li>
                    <li><a href="#lang-sec-8"><?php _e('Make themes work multilingual', 'sitepress'); ?></a></li>
                    <li><a href="#lang-sec-9"><?php _e('Browser language redirect', 'sitepress'); ?></a></li>
                    <li><a href="#lang-sec-9-5"><?php _e('SEO Options', 'sitepress'); ?></a></li>
                    <li><a href="#lang-sec-10"><?php _e('WPML love', 'sitepress'); ?></a></li>
                </ul>
            <?php endif; ?>

            <div id="lang-sec-1" class="wpml-section wpml-section-languages">
                <div class="wpml-section-header">
                    <h3><?php _e('Site Languages', 'sitepress') ?></h3>
                </div>

                <div class="wpml-section-content">
                    <div class="wpml-section-content-inner">
                        <?php if(!empty( $setup_complete )): ?>
                            <h4><?php _e('These languages are enabled for this site:','sitepress'); ?></h4>
                            <ul id="icl_enabled_languages" class="enabled-languages">
                                    <?php foreach($active_languages as $lang): $is_default = ( $default_language ==$lang['code']); ?>
                                    <?php
                                    if(!empty( $hidden_languages ) && in_array($lang['code'], $hidden_languages )){
                                        $hidden = '&nbsp<strong style="color:#f00">('.__('hidden', 'sitepress').')</strong>';
                                    }else{
                                        $hidden = '';
                                    }
                                    ?>

                                <li <?php if($is_default):?>class="selected"<?php endif;?>>
                                    <input id="default_language_<?php echo $lang['code'] ?>" name="default_language" type="radio" value="<?php echo $lang['code'] ?>" <?php if($is_default):?>checked="checked"<?php endif;?> />
                                    <label for="default_language_<?php echo $lang['code'] ?>"><?php echo $lang['display_name'] . $hidden ?> <?php if($is_default):?>(<?php echo __('default', 'sitepress') ?>)<?php endif?></label>
                                </li>
                                <?php endforeach ?>
                            </ul>
                        <?php else: ?>
                            <p><?php _e('Select the languages to enable for your site (you can also add and remove languages later).','sitepress'); ?></p>
                        <?php endif; ?>
                        <?php wp_nonce_field('set_default_language_nonce', 'set_default_language_nonce'); ?>
                        <p class="buttons-wrap">
                            <button id="icl_cancel_default_button" class="button-secondary action"><?php _e('Cancel', 'sitepress') ?></button>
                            <button id="icl_save_default_button" class="button-primary action"><?php _e('Save', 'sitepress') ?></button>
                        </p>
                        <?php if(!empty( $setup_complete )): ?>
                            <p>
                                <button id="icl_change_default_button" class="button-secondary action <?php if(count($active_languages) < 2): ?>hidden<?php endif ?>"><?php _e('Change default language', 'sitepress') ?></button>
                                <button id="icl_add_remove_button" class="button-secondary action"><?php _e('Add / Remove languages', 'sitepress') ?></button>
                            </p>
                            <p class="icl_ajx_response" id="icl_ajx_response"></p>
                        <?php endif; ?>
                        <div id="icl_avail_languages_picker" class="<?php if( !empty( $setup_complete ) ) echo 'hidden'; ?>">
                            <ul class="available-languages">
                                <?php
                                foreach ( $languages as $lang ) {
                                    $checked  = checked( '1', $lang['active'], false );
                                    $disabled = disabled( $default_language, $lang['code'], false );
                                    ?>
                                    <li><label><input type="checkbox" value="<?php echo $lang['code'] ?>"
                                                <?php echo $checked; ?>
                                                <?php echo $disabled; ?>
                                            />
                                            <?php if ( $lang['major'] ): ?><strong><?php endif;
                                                ?><?php echo $lang['display_name'] ?><?php if ( $lang['major'] ): ?></strong><?php endif;
                                        ?></label></li>
                                    <?php
                                }
                                ?>
                            </ul>
                            <?php if(!empty( $setup_complete )): ?>
                            <p class="buttons-wrap">
                                <input id="icl_cancel_language_selection" type="button" class="button-secondary action" value="<?php _e('Cancel', 'sitepress') ?>" />
                                <input id="icl_save_language_selection" type="button" class="button-primary action" value="<?php _e('Save', 'sitepress') ?>" />
                            </p>
                            <?php endif; ?>
                            <?php wp_nonce_field('set_active_languages_nonce', 'set_active_languages_nonce'); ?>
                        </div>

                        <?php if (!empty( $setup_complete )): ?>
                            <p>
                                <a href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/languages.php&amp;trop=1"><?php _e('Edit Languages','sitepress'); ?></a>
                            </p>
                        <?php endif; ?>
                    </div> <!-- wpml-section-content-inner -->


                    <?php if( !empty($inactive_content) ): ?>
                        <div class="wpml-section-content-inner">
                            <?php
                                $t_posts = $t_pages = $t_cats = $t_tags = 0;
                                foreach($inactive_content as $language=>$ic){
                                    $t_posts += @$ic['post'];
                                    $t_pages += @$ic['page'];
                                    $t_cats += @$ic['category'];
                                    $t_tags += @$ic['post_tag'];
                                }
                            ?>
                            <h4><?php _e('Inactive content', 'sitepress') ?></h4>
                            <p class="explanation-text"><?php _e('In order to edit or delete these you need to activate the corresponding language first', 'sitepress') ?></p>
                            <table class="widefat inactive-content-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('Language', 'sitepress') ?></th>
                                        <th><?php _e('Posts', 'sitepress') ?></th>
                                        <th><?php _e('Pages', 'sitepress') ?></th>
                                        <th><?php _e('Categories', 'sitepress') ?></th>
                                        <th><?php _e('Tags', 'sitepress') ?></th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th><?php _e('Total', 'sitepress') ?></th>
                                        <td><?php echo @intval($t_posts) ?></td>
                                        <td><?php echo @intval($t_pages) ?></td>
                                        <td><?php echo @intval($t_cats) ?></td>
                                        <td><?php echo @intval($t_tags) ?></td>
                                    </tr>
                                </tfoot>
                                <tbody>
                                    <?php foreach($inactive_content as $language=>$ic): ?>
                                        <tr>
                                            <th><?php echo $language ?></th>
                                            <td><?php echo @intval($ic['post']); ?></td>
                                            <td><?php echo @intval($ic['page']); ?></td>
                                            <td><?php echo @intval($ic['category']); ?></td>
                                        	<td><?php echo @intval($ic['post_tag']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div> <!-- wpml-section-content-inner -->
                    <?php endif; ?>

                    <?php if( $setup_wizard_step ==2): ?>
                        <div style="text-align:right">
                            <input id="icl_setup_back_1" class="button-primary" name="save" value="<?php echo __('Back', 'sitepress') ?>" type="button" />
                            <?php wp_nonce_field('setup_got_to_step1_nonce', '_icl_nonce_gts1'); ?>
                            <input id="icl_setup_next_1" class="button-primary" name="save" value="<?php echo __('Next', 'sitepress') ?>" type="button" <?php if(count($active_languages) < 2):?>disabled="disabled"<?php endif;?> />
                        </div>
                    <?php endif; ?>

                </div> <!-- .wcml-section-content -->
            </div> <!-- .wpml-section-languages -->
            
        <?php 
        elseif($setup_wizard_step == 4): ?>
        <?php $site_key = WP_Installer()->get_site_key('wpml'); ?>    
        <div class="wpml-section" id="lang-sec-0">
            <div class="wpml-section-header">
                <h3><?php _e('Registration', 'sitepress'); ?></h3>
            </div>
            <div class="wpml-section-content">
                
                <?php if(is_multisite() && !empty($site_key)): ?>
                
                <p><?php _e('WPML is already registered network-wide.', 'sitepress') ?></p>
                <div style="text-align:right">
                    <form id="installer_registration_form">
                        <input type="hidden" name="action" value="installer_save_key" />
                        <input type="hidden" name="button_action" value="installer_save_key" />
                        <input <?php if(empty($site_key)): ?>style="display: none;"<?php endif; ?> class="button-primary" name="finish" value="<?php echo __('Finish', 'sitepress') ?>" type="submit" />
                        <?php wp_nonce_field('registration_form_submit_nonce', '_icl_nonce'); ?>
                    </form>
                </div>
                
                <?php else: ?>        
                
                <p><?php _e('Enter the site key, from your wpml.org account, to receive automatic updates for WPML on this site.', 'sitepress'); ?></p>
                <form id="installer_registration_form">
                    <input type="hidden" name="action" value="installer_save_key" />
                    <input type="hidden" name="button_action" value="installer_save_key" />
                    <label>
                        <?php _e('Site key:', 'sitepress'); ?>
                        <input type="text" name="installer_site_key" value="<?php echo $site_key ?>" <?php if(!empty($site_key)): ?>disabled="disabled"<?php endif; ?> />
                    </label>
                    
                    
                    <div class="status_msg<?php if(!empty($site_key)): ?> icl_valid_text<?php endif; ?>">
                        <?php if($site_key) _e('Thank you for registering WPML on this site. You will receive automatic updates when new versions are available.', 'sitepress'); ?>
                    </div>
                    
                    <div style="text-align:right">
                        <?php if(empty($site_key)): ?>
                        <input class="button-primary" name="register" value="<?php echo __('Register', 'sitepress') ?>" type="submit" />                        
                        <input class="button-secondary" name="later" value="<?php echo __('Remind me later', 'sitepress') ?>" type="submit" />
                        <?php endif; ?>
                        <input <?php if(empty($site_key)): ?>style="display: none;"<?php endif; ?> class="button-primary" name="finish" value="<?php echo __('Finish', 'sitepress') ?>" type="submit" />
                        
                        <?php wp_nonce_field('registration_form_submit_nonce', '_icl_nonce'); ?>
                    </div>
                    
                </form>
                
                <?php endif; ?>
                
                <?php if(empty($site_key)): ?>
                <p><?php printf(__("Don't have a key for this site? %sGenerate a key for this site%s", 'sitepress'), '<a class="button-primary" href="https://wpml.org/my-account/sites/?add='.urlencode(get_site_url()).'" target="_blank">', '</a>') ?></p>
                <p><?php printf(__("If you don't have a WPML.org account or a valid subscription, you can %spurchase%s one and get later upgrades, full support and 30 days money-back guarantee." , 'sitepress'), '<a href="http://wpml.org/purchase/" target="_blank">', '</a>') ?></p>
                <?php endif; ?>
            </div>
        </div>
            
        <?php endif; ?>

        
        <?php if(!empty( $setup_complete )): ?>
            <?php
            if ( !class_exists ( 'WP_Http' ) ) {
                include_once ABSPATH . WPINC . '/class-http.php';
            }
            /**
             * @var WPML_URL_Converter $wpml_url_converter
             * @var WPML_Request $wpml_request_handler
             */
            global $wpml_url_converter, $wpml_request_handler;

            $validator = wpml_get_langs_in_dirs_val ( new WP_Http(), $wpml_url_converter );
            ?>
            <?php if(count($active_languages) > 1): ?>
                <div class="wpml-section wpml-section-url-format" id="lang-sec-2">
                    <div class="wpml-section-header">
                        <h3><?php _e('Language URL format', 'sitepress'); ?></h3>
                    </div>
                    <div class="wpml-section-content">
                        <h4><?php _e('Choose how to determine which language visitors see contents in', 'sitepress'); ?></h4>
                        <form id="icl_save_language_negotiation_type" name="icl_save_language_negotiation_type" action="">
                            <?php wp_nonce_field('save_language_negotiation_type', 'save_language_negotiation_type_nonce') ?>
                            <ul>
                                <?php

                                $abs_home = $wpml_url_converter->get_abs_home();
                                $icl_folder_url_disabled = $validator->validate_langs_in_dirs ( $sample_lang['code'] );
                                ?>
                                <li>
                                    <label>
                                        <input type="radio" name="icl_language_negotiation_type" value="1" <?php if($language_negotiation_type ==1):?>checked<?php endif?> />
                                        <?php _e('Different languages in directories', 'sitepress'); ?>
                                        <span class="explanation-text">
                                        (<?php
	                                        $root = !empty( $setting_urls['directory_for_default_language']);
                                            echo $validator->print_explanation( $sample_lang['code'], $root );?>)
                                        </span>
                                    </label>

                                    <div id="icl_use_directory_wrap" style="<?php if( $language_negotiation_type != 1): ?>display:none;<?php endif; ?>" >
                                        <p class="sub-section">
                                            <label>
                                                <input type="checkbox" name="use_directory" id="icl_use_directory" value="1" <?php if(!empty( $setting_urls['directory_for_default_language'])):?>checked<?php endif; ?> />
                                                <?php _e('Use directory for default language', 'sitepress') ?>
                                            </label>
                                        </p>

                                        <div id="icl_use_directory_details" class="sub-section" <?php if(empty( $setting_urls['directory_for_default_language'])) echo ' style="display:none"'  ?> >

                                            <p><?php _e('What to show for the root url:', 'sitepress') ?></p>

                                            <ul>
                                                <li>
                                                    <label for="wpml_show_on_root_html_file">
                                                        <input id="wpml_show_on_root_html_file" type="radio" name="show_on_root" value="html_file" <?php if($setting_urls['show_on_root'] == 'html_file'):
                                                        ?>checked="checked"<?php endif; ?> />
                                                        <?php _e('HTML file', 'sitepress') ?> &ndash; <span class="explanation-text"><?php _e('please enter path: absolute or relative to the WordPress installation folder','sitepress'); ?></span>
                                                    </label>
                                                    <p>
                                                        <input type="text" id="root_html_file_path" name="root_html_file_path" value="<?php echo $setting_urls['root_html_file_path']?>" />
                                                        <label class="icl_error_text icl_error_1" for="root_html_file_path" style="display: none;"><?php _e('Please select what to show for the root url.', 'sitepress') ?></label>
                                                    </p>
                                                </li>

                                                <li>
                                                    <label>
                                                        <input id="wpml_show_on_root_page" type="radio" name="show_on_root" value="page" <?php if($setting_urls['show_on_root'] == 'page'): ?>checked<?php endif; ?>  <?php if($setting_urls['show_on_root'] == 'page'):?>class="active"<?php endif; ?> />
                                                        <?php _e('A page', 'sitepress') ?>

                                                        <span style="display: none;" id="wpml_show_page_on_root_x"><?php echo esc_js(__("Please save the settings first by clicking Save.", 'sitepress')); ?></span>

                                                        <span id="wpml_show_page_on_root_details" <?php if($setting_urls['show_on_root'] != 'page'):
                                                        ?>style="display:none"<?php endif; ?>>
                                                        <?php
                                                        $rp_exists = false;
                                                        if(!empty( $setting_urls['root_page'])){
                                                            $rp = get_post( $setting_urls['root_page']);
                                                            if($rp && $rp->post_status != 'trash'){
                                                                $rp_exists = true;
                                                            }
                                                        }
                                                        ?>
                                                        <?php if($rp_exists): ?>
                                                            <a href="<?php echo get_edit_post_link( $setting_urls['root_page']) ?>"><?php _e('Edit root page.', 'sitepress') ?></a>
                                                        <?php else: ?>
                                                            <a href="<?php echo admin_url('post-new.php?post_type=page&wpml_root_page=1') ?>"><?php _e('Create root page.', 'sitepress') ?></a>
                                                        <?php endif; ?>
                                                        </span>
														<p id="icl_hide_language_switchers" class="sub-section" <?php if($setting_urls['show_on_root'] != 'page'): ?>style="display:none"<?php endif; ?>>
														  <label>
															  <input type="checkbox" name="hide_language_switchers" id="icl_hide_language_switchers" value="1" <?php checked( $setting_urls['hide_language_switchers']) ?> />
															  <?php _e('Hide language switchers on the root page', 'sitepress') ?>
														  </label>
													  </p>

                                                    </label>
                                                </li>


                                            </ul>

                                        </div>
                                    </div>

	                                <?php if ( $icl_folder_url_disabled ): ?>
                                    <div class="icl_error_text" style="margin:10px;">
                                        <p>
                                            <?php _e('It looks like languages per directories will not function.', 'sitepress'); ?>
                                            <a href="#" onClick="jQuery(this).parent().parent().next().toggle();return false"><?php _e("Details", 'sitepress') ?></a>
                                        </p>
                                    </div>
                                    <div class="icl_error_text" style="display:none;margin:10px;">
                    					<p><?php _e("This can be a result of either:", 'sitepress') ?></p>
                    					<ul>
                        					<li><?php _e("WordPress is installed in a directory (not root) and you're using default links.",'sitepress') ?></li>
                        					<li><?php _e("URL rewriting is not enabled in your web server.",'sitepress') ?></li>
                                            <li><?php _e("The web server cannot write to the .htaccess file",'sitepress') ?></li>
                    					</ul>
                                        <a href="https://wpml.org/?page_id=1010"><?php _e('How to fix','sitepress') ?></a>
                                            <p>
                                                <?php printf(__('When WPML accesses <a target="_blank" href="%s">%s</a> it gets:', 'sitepress'), $__url = $validator->get_validation_url($sample_lang['code']), $__url); ?>
	                                            <br/>
	                                            <?php
	                                            echo $validator->print_error_response ();
	                                            ?>
                                            </p>
                                            <p>
                                                <?php printf(__('The expected value is: %s', 'sitepress'), '<br /><strong>&lt;!--'.get_home_url().'--&gt;</strong>'); ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </li>
                                <?php
                                global $wpmu_version;
                                if(isset($wpmu_version) || function_exists('is_multisite') && is_multisite() && (!defined('WPML_SUNRISE_MULTISITE_DOMAINS') || !WPML_SUNRISE_MULTISITE_DOMAINS)){
                                    $icl_lnt_disabled = 'disabled="disabled" ';
                                }else{
                                    $icl_lnt_disabled = '';
                                }
                                ?>
                                <li>
                                    <label>
                                        <input <?php echo $icl_lnt_disabled ?>id="icl_lnt_domains" type="radio" name="icl_language_negotiation_type" value="2" <?php if($language_negotiation_type ==2):?>checked<?php endif?> />
                                        <?php _e('A different domain per language', 'sitepress') ?>
                                        <?php if($icl_lnt_disabled): ?>
                                            <span class="icl_error_text"><?php _e('This option is not yet available for Multisite installs', 'sitepress')?></span>
                                        <?php endif; ?>
                                        <?php if(defined('WPML_SUNRISE_MULTISITE_DOMAINS') && WPML_SUNRISE_MULTISITE_DOMAINS): ?>
                                            <span class="icl_error_text"><?php _e('Experimental', 'sitepress')?></span>
                                        <?php endif; ?>
                                    </label>
                                    <?php wp_nonce_field('language_domains_nonce', '_icl_nonce_ldom', false); ?>
                                    <?php wp_nonce_field('validate_language_domain', 'validate_language_domain_nonce', false); ?>
                                    <?php if( $language_negotiation_type ==2):?>
                                    <div id="icl_lnt_domains_box">
                                        <table class="language_domains">
                                        <?php foreach($active_languages as $lang) :?>
                                            <tr>
                                                <td>
																									<label for="language_domain_<?php echo $lang['code'] ?>">
																										<?php echo $lang['display_name'] ?>
																									</label>
																								</td>
                                                <?php if($lang['code']== $default_language ): ?>
                                                    <td id="icl_ln_home">
																											<?php echo $sitepress->convert_url( get_home_url(), $sitepress->get_default_language() ) ?>
																										</td>
                                                    <td>&nbsp;</td>
                                                    <td>&nbsp;</td>
                                                <?php else: ?>
                                                    <td>
																												<input
																													type="text"
																													id="language_domain_<?php echo $lang['code'] ?>"
																													name="language_domains[<?php echo $lang['code'] ?>]"
																													value="<?php echo isset($language_domains[$lang['code']]) ? $language_domains[$lang['code']] : ''; ?>"
																													data-language="<?php echo $lang['code']; ?>"
																													size="40" />
																										</td>
                                                    <td>
																												<input
																													class="validate_language_domain"
																													type="checkbox"
																													id="validate_language_domains_<?php echo $lang['code'] ?>"
																													name="validate_language_domains[]"
																													value="<?php echo $lang['code'] ?>"
																													checked="checked" />
																											<label for="validate_language_domains_<?php echo $lang['code'] ?>">
																												<?php _e('Validate on save', 'sitepress') ?>
																											</label>
																										</td>
                                                    <td>
																											<span class="spinner spinner-<?php echo $lang['code'] ?>"></span>
																											<span id="ajx_ld_<?php echo $lang['code'] ?>"></span>
																										</td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                        </table>
                                    </div>
                                    <?php else: ?>
									<div id="icl_lnt_domains_box"></div>
                                    <?php endif; ?>

                                    <div id="language_domain_xdomain_options" class="sub-section" <?php if( $language_negotiation_type != 2 ) echo ' style="display:none"'  ?>>
                                        <p><?php _e('Pass session arguments between domains through the language switcher', 'wpml-translation-management'); ?></p>
                                        <p>
                                            <label>
                                                <input type="radio" name="icl_xdomain_data"
                                                       value="<?php echo WPML_XDOMAIN_DATA_GET ?>"
                                                       <?php if ($sitepress_settings['xdomain_data'] == WPML_XDOMAIN_DATA_GET): ?>checked="checked"<?php endif ?>/>
                                                <?php echo __('Pass arguments via GET (the url)', 'wpml-translation-management'); ?>
                                            </label>
                                        </p>
                                        <p>
                                            <label>
                                                <input type="radio" name="icl_xdomain_data"
                                                       value="<?php echo WPML_XDOMAIN_DATA_POST ?>"
                                                       <?php if ($sitepress_settings['xdomain_data'] == WPML_XDOMAIN_DATA_POST): ?>checked="checked"<?php endif ?>/>
                                                <?php echo __('Pass arguments via POST', 'wpml-translation-management'); ?>
                                            </label>
                                        </p>
                                        <p>
                                            <label>
                                                <input type="radio" name="icl_xdomain_data"
                                                       value="<?php echo WPML_XDOMAIN_DATA_OFF ?>"
                                                       <?php if ($sitepress_settings['xdomain_data'] == WPML_XDOMAIN_DATA_OFF): ?>checked="checked"<?php endif ?>/>
                                                <?php echo __('Disable this feature', 'wpml-translation-management'); ?>
                                            </label>
                                        </p>

                                        <?php if( function_exists( 'mcrypt_encrypt' ) && function_exists( 'mcrypt_decrypt' ) ): ?>
                                            <p><?php _e('The data will be encrypted with the MCRYPT_RIJNDAEL_256 algorithm.'); ?></p>
                                        <?php else: ?>
                                            <p><?php _e('Because encryption is not supported on your host, the data will only have a basic encoding with the bse64 algorithm.'); ?></p>
                                        <?php endif; ?>

                                        <p><a href="https://wpml.org/?page_id=693147" target="_blank"><?php _e('Learn more about passing data between domains'); ?></a></p>


                                    </div>

                                </li>
                                <li>
                                    <label>
                                        <input type="radio" name="icl_language_negotiation_type" value="3" <?php if($language_negotiation_type ==3):?>checked<?php endif?> />
                                        <?php _e('Language name added as a parameter', 'sitepress') ?>
                                        <span class="explanation-text"><?php echo sprintf('(%s?lang=%s - %s)',get_home_url(),$sample_lang['code'],$sample_lang['display_name']) ?></span>
                                    </label>
                                </li>
                            </ul>
	                        <div class="wpml-form-message" style="display:none;"></div>
                            <p class="buttons-wrap">
                                <span class="icl_ajx_response" id="icl_ajx_response2"></span>
                                <input class="button button-primary" name="save" value="<?php _e('Save','sitepress') ?>" type="submit" />
                            </p>
                        </form>
                    </div>
                </div> <!-- .wpml-section-url-format -->
            <?php endif; ?>
        <?php endif; ?>


        <?php if(!empty( $setup_complete ) && count($active_languages) > 1 || $setup_wizard_step ==3): ?>
            <div class="wpml-section wpml-section-language-switcher" id="lang-sec-3">
                <form id="icl_save_language_switcher_options" name="icl_save_language_switcher_options" action="">
                    <?php wp_nonce_field('icl_save_language_switcher_options_nonce', '_icl_nonce'); ?>

                    <div class="wpml-section-header">
                        <h3><?php _e('Language switcher options', 'sitepress') ?></h3>
                    </div>

                    <div class="wpml-section-content">

                        <div class="wpml-section-content-inner">
                            <p class="icl_form_errors" style="display:none"></p>
                            <?php if(isset($_GET['icl_ls_reset']) && $_GET['icl_ls_reset'] == 'default'): ?>
                                <p class="icl_form_success"><?php _e('Default settings have been loaded', 'sitepress')?></p>
                            <?php endif; ?>
                            <h4><?php _e('Language switcher widget', 'sitepress')?></h4>
                            <?php
                            global $wp_registered_widgets, $wp_registered_sidebars;
                            $sidebar_widgets = wp_get_sidebars_widgets();

                            if ( ! $sidebar_widgets ) {
	                            echo __( 'Your theme has no registered sidebars.', 'sitepress' );
                            } elseif(is_array( $sidebars_widgets )) {
	                            ?>
	                            <ul id="icl_language_switcher_sidebars">
		                            <?php
		                            foreach ( $wp_registered_sidebars as $sidebar_id => $sidebar_settings ) {
			                            // Widgets registered in wp_inactive_widgets or orphaned_widgets_* sidebars
			                            if ( in_array( $sidebar_id, array( 'array_version', 'wp_inactive_widgets' ) ) || strpos($sidebar_id, 'orphaned_widgets_')!==false ) {
				                            continue;
			                            }

			                            // Widgets registered in sidebars that does not exists anymore (e.g. just switched theme)
			                            if(!isset($sidebar_widgets[ $sidebar_id ])) {
				                            $widgets = array();
			                            } else {
				                            $widgets = $sidebar_widgets[ $sidebar_id ];
			                            }

			                            $sidebar_data = $wp_registered_sidebars[ $sidebar_id ];
			                            $sidebar_name = $sidebar_data[ 'name' ];
				                            $has_language_switcher = false;
				                            $language_switcher_widgets = array();
				                            if ( count( $widgets ) >= 0 ) {
					                            foreach ( $widgets as $index => $widget ) {
						                            $is_language_switcher_widget = strpos( $widget, 'icl_lang_sel_widget-' ) !== false;
						                            if ( $is_language_switcher_widget ) {
							                            $language_switcher_widgets[ $index ] = $widget;
							                            $has_language_switcher = true;
							                            break;
						                            }
					                            }
				                            }
				                            $element_id   = 'icl_language_switcher_sidebar_' . $sidebar_id;
				                            $element_name = 'icl_language_switcher_sidebars[' . $sidebar_id . ']';

				                            $html_checked = '';
				                            if($has_language_switcher) {
					                            $html_checked = 'checked="checked"';
				                            }
			                            ?>
			                            <li>
				                            <label for="<?php echo $element_id; ?>">
					                            <input <?php echo $html_checked; ?>
					                                   name="toggle_<?php echo $element_name; ?>"
					                                   type="checkbox" class="toggle"
					                                   value="<?php echo $sidebar_id; ?>"
					                                   id="<?php echo $element_id; ?>"
					                                   data-toggle_value_name="<?php echo $element_name; ?>"
					                                   data-toggle_checked_value="1"
					                                   data-toggle_unchecked_value="0">&nbsp;<?php echo $sidebar_name; ?>
				                            </label>
			                            </li>
			                            <?php
			                            ?>
		                            <?php
		                            }
		                            ?>
	                            </ul>
                            <?php
                            }

                            ?>

                            <p class="icl_advanced_feature">
                                <?php printf(__('The drop-down language switcher can be added to your theme by inserting this PHP code: %s or as a widget','sitepress'),'<code class="php">&lt;?php do_action(\'wpml_add_language_selector\'); ?&gt;</code>'); ?>.
                            </p>

                            <p class="icl_advanced_feature"><?php _e('You can also create custom language switchers, such as a list of languages or country flags.','sitepress'); ?>
                                <a href="https://wpml.org/?page_id=989"><?php _e('Custom language switcher creation guide','sitepress')?></a>.
                            </p>
                            <p>
                                <label>
                                    <input type="checkbox" name="icl_widget_title_show" value="1"<?php if ( $icl_widget_title_show ) echo ' checked="checked"'; ?> />
                                    <?php _e('Display \'Languages\' as the widget title', 'sitepress'); ?>
                                </label>
                            </p>

	                        <?php if ( ! empty( $setup_complete ) ): ?>
		                        <button class="button-primary" name="save" type="submit"><?php _e('Save','sitepress') ?></button>
	                        <?php endif; ?>
                        </div> <!-- .wpml-section-content-inner -->

                        <div class="wpml-section-content-inner">
                            <h4><label for="icl_display_ls_in_menu"><?php _e('Language switcher in the WP Menu', 'sitepress')?></label></h4>
                            <p>
                                <label for="menu_for_ls">
                                    <input type="checkbox" id="icl_display_ls_in_menu" name="display_ls_in_menu" value="1"<?php if (!empty( $display_ls_in_menu )) echo ' checked="checked"'; ?> />
                                    <?php _e('Display the language switcher in the WP Menu', 'sitepress'); ?>
                                </label>
                                <span id="icl_ls_menus_list" <?php if (empty( $display_ls_in_menu )):?> style="display: none;"<?php endif;?>>
                                    <?php $nav_menus = wp_get_nav_menus( array('orderby' => 'name') ); ?>
                                    <?php ?>
                                    <select id="menu_for_ls" name="menu_for_ls">
                                        <?php if(empty($nav_menus)): ?>
                                        <option value="">--<?php _e('no menus defined', 'sitepress')?>--</option>
                                        <?php else: ?>
                                        <option value="">--<?php _e('select', 'sitepress')?>--</option>
                                        <?php endif; ?>
                                        <?php foreach($nav_menus as $nav_menu):?>
                                            <option value="<?php echo $nav_menu->term_id ?>"<?php if(isset( $menu_for_ls ) && icl_object_id( $nav_menu->term_id, 'nav_menu', false, $default_language ) ==  icl_object_id( $menu_for_ls, 'nav_menu', false, $default_language ) ):?> selected="selected"<?php endif;?>><?php echo $nav_menu->name ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </span>
                            </p>
                        </div>

                        <div class="wpml-section-content-inner">
                            <h4><?php _e('Languages order', 'sitepress')?></h4>
                            <?php $active_languages_ordered = $sitepress->order_languages($active_languages); ?>
                            <ul id="icl_languages_order" class="languages-order">
                                <?php foreach($active_languages_ordered as $language): ?>
                                    <li class="icl_languages_order_<?php echo $language['code']?>" ><?php echo $language['display_name']?></li>
                                <?php endforeach; ?>
                            </ul>
                            <span style="display:none;" class="icl_languages_order_ajx_resp"></span>
                            <input type="hidden" id="icl_languages_order_nonce" value="<?php echo wp_create_nonce('set_languages_order_nonce') ?>" />
                            <p class="explanation-text"><?php _e('Drag the languages to change their order', 'sitepress') ?></p>
                        </div>

                        <div class="wpml-section-content-inner">

	                        <h4><label for="icl_lang_sel_type"><?php _e( 'Language switcher style', 'sitepress' ) ?>
			                        <span style="display: none; color: red;"> - <strong><?php echo __( 'You must save your settings to see the updated preview', 'sitepress' ); ?></strong></span></label></h4>

                            <ul>
                                <li>
	                                <input type="radio" id="icl_lang_sel_type" name="icl_lang_sel_type" value="dropdown" <?php if ( ! $icl_lang_sel_type || $icl_lang_sel_type == 'dropdown' ): ?>checked="checked"<?php endif ?> />
	                                <label for="icl_lang_sel_stype">
                                        <?php echo __('Drop-down menu', 'sitepress') ?>
                                    </label>
                                    <select id="icl_lang_sel_stype" name="icl_lang_sel_stype" <?php if($icl_lang_sel_type != 'dropdown'): ?>style="display:none<?php endif;?>">
	                                    <?php
	                                    $must_reload = ( 'mobile' === $icl_lang_sel_stype ) ? 1 : 0;
	                                    ?>
	                                    <option <?php selected( $icl_lang_sel_stype, 'classic' ); ?> value="classic" data-reload="<?php echo $must_reload; ?>"><?php _e( 'Classic', 'sitepress' ) ?></option>
	                                    <option <?php selected( $icl_lang_sel_stype, 'mobile-auto' ); ?> value="mobile-auto"
	                                                                                                     data-reload="<?php echo $must_reload; ?>"><?php _e( 'Mobile friendly for mobile agents only', 'sitepress' ) ?></option>
	                                    <?php
	                                    $must_reload = ( 'mobile' == $icl_lang_sel_stype ) ? 0 : 1;
	                                    ?>
	                                    <option <?php selected( $icl_lang_sel_stype, 'mobile' ); ?> value="mobile" data-reload="true" data-reload="<?php echo $must_reload; ?>"><?php _e( 'Mobile friendly always', 'sitepress' ) ?></option>
                                    </select>
                                </li>
                                <li>
                                    <label for="icl_lang_sel_orientation">
                                        <input type="radio" name="icl_lang_sel_type" value="list" <?php if($icl_lang_sel_type == 'list'):?>checked="checked"<?php endif?> />
                                        <?php echo __('List of languages', 'sitepress') ?>
                                    </label>
                                    <select id="icl_lang_sel_orientation" name="icl_lang_sel_orientation" <?php if($icl_lang_sel_type != 'list'): ?>style="display: none;"<?php endif;?>>
                                        <option value="vertical"><?php _e('Vertical', 'sitepress') ?></option>
                                        <option value="horizontal" <?php if($sitepress->get_setting('icl_lang_sel_orientation')=='horizontal'): ?>selected="selected"<?php endif;?>><?php _e('Horizontal', 'sitepress') ?></option>
                                    </select>
                                </li>
                            </ul>

                        </div>

                        <div class="wpml-section-content-inner">
                            <h4><?php _e('What to include in the language switcher', 'sitepress')?></h4>
                            <ul>
                                <li>
                                    <label>
                                        <input class="icl_ls_include" type="checkbox" name="icl_lso_flags" value="1" <?php if($sitepress->get_setting('icl_lso_flags')):?>checked<?php endif?> />
                                        <?php _e('Flag', 'sitepress') ?>
                                    </label>
                                </li>
                                <li>
                                    <p>
                                        <label>
                                            <input class="icl_ls_include" type="checkbox" name="icl_lso_native_lang" value="1" <?php if($sitepress->get_setting('icl_lso_native_lang')):?>checked<?php endif?> />
                                            <?php _e('Native language name', 'sitepress') ?>
                                            <span class="explanation-text"><?php _e("(the language name as it's written in that language)", 'sitepress') ?></span>
                                        </label>
                                    </p>
                                    <p>
                                        <label>
                                            <input class="icl_ls_include" type="checkbox" name="icl_lso_display_lang" value="1" <?php if($sitepress->get_setting('icl_lso_display_lang')):?>checked<?php endif?> />
                                            <?php _e('Language name in display language', 'sitepress') ?>
                                            <span class="explanation-text"><?php _e("(the language name as it's written in the currently displayed language)", 'sitepress') ?></span>
                                        </label>
                                    </p>
                                </li>
                            </ul>

                            <?php if ( !defined('ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS') || !ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS ) : ?>
                                <div id="icl_lang_sel_preview_wrap" class="language-selector-preview" style="min-height:<?php echo 50 + 25 * count($sitepress->get_active_languages())?>px">
                                    <div id="icl_lang_sel_preview">
                                        <p><strong><?php _e('Language switcher widget preview', 'sitepress')?></strong></p>
                                        <?php
                                            global $icl_language_switcher_preview;
                                            $icl_language_switcher_preview = true;
                                            $sitepress->language_selector();
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>

                        <div class="wpml-section-content-inner">
                            <h4><?php _e('How to handle languages without translation', 'sitepress')?></h4>
                            <p><?php _e('Some pages or posts may not be translated to all languages. Select how the language selector should behave in case translation is missing.', 'sitepress') ?></p>
                            <ul>
                                <li>
                                    <label>
                                        <input type="radio" name="icl_lso_link_empty" value="0" <?php if(!$sitepress->get_setting('icl_lso_link_empty')):?>checked="checked"<?php endif?> />
                                        <?php _e('Skip language', 'sitepress') ?>
                                    </label>
                                </li>
                                <li>
                                <label>
                                    <input type="radio" name="icl_lso_link_empty" value="1" <?php if($sitepress->get_setting('icl_lso_link_empty')==1):?>checked="checked"<?php endif?> />
                                    <?php _e('Link to home of language for missing translations', 'sitepress') ?>
                                </label>
                                </li>
                            </ul>
                        </div>

                        <div class="wpml-section-content-inner">
                            <h4><?php _e('Preserve url parameters', 'sitepress')?></h4>
                            <label for="copy_parameters"><?php _e('These url parameters will be copied to the translated urls in the language switcher. Enter parameters separated by commas.', 'sitepress') ?></label><br />
                            <input type="text" size="100" id="copy_parameters" name="copy_parameters" value="<?php echo $sitepress->get_setting('icl_lang_sel_copy_parameters'); ?>" />
                        </div>

                        <?php do_action('icl_language_switcher_options'); ?>

                        <?php if(!empty( $setup_complete )): ?>
                        <div class="wpml-section-content-inner">
                            <p class="buttons-wrap">
                                <span class="icl_ajx_response" id="icl_ajx_response3"></span>
                                <a class="button button-secondary" onclick="if(!confirm('<?php echo esc_js(__('Are you sure you want to reset to the default settings?', 'sitepress')) ?>')) return false;"
                                    href="<?php echo admin_url('admin.php?page='.$_GET['page'].'&amp;restore_ls_settings=1') ?>"><?php _e('Restore default', 'sitepress'); if ( $theme_wpml_config_file ) { echo ' *'; } ?></a>
                                <button class="button-primary" name="save" type="submit"><?php _e('Save','sitepress') ?></button>
                            </p>
							<?php if ( $theme_wpml_config_file ): ?>
								<p>
									<span class="explanation-text"><?php echo sprintf(__('* Your theme has a %s file, which sets the default values for WPML.', 'sitepress'), '<span title="' . esc_attr($theme_wpml_config_file) . '">wpml-config.xml</span>'); ?></span>
								</p>
							<?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php if(empty( $setup_complete )): ?>
                            <div id="icl_setup_nav_3" style="text-align:right">
                                <input id="icl_setup_back_2" class="button-primary" name="save" value="<?php echo __('Back', 'sitepress') ?>" type="button" />
                                <?php wp_nonce_field('setup_got_to_step2_nonce', '_icl_nonce_gts2'); ?>
                                <input class="button-primary" name="save" value="<?php echo __('Next', 'sitepress') ?>" type="submit" />
                            </div>
                            <script type="text/javascript">
                                addLoadEvent(function(){
                                    jQuery('#icl_save_language_switcher_options').submit(function(){
                                        iclSaveForm_success_cb.push(function(){
                                            location.href = location.href.replace(/#.*/,'')
                                        });
                                    });
                                });
                            </script>
                        <?php endif; ?>

                    </div> <!-- .wpml-section-content -->
                </form>

            </div> <!-- .wpml-section -->
        <?php endif; ?>

        <?php if(!empty( $setup_complete ) && count($all_languages) > 1): ?>
            <div class="wpml-section wpml-section-admin-language" id="lang-sec-4">
                <div class="wpml-section-header">
                    <h3><?php _e('Admin language', 'sitepress') ?></h3>
                </div>
                <div class="wpml-section-content">
                    <form id="icl_admin_language_options" name="icl_admin_language_options" action="">
                        <?php wp_nonce_field('icl_admin_language_options_nonce', '_icl_nonce'); ?>
                        <?php if(is_admin()): ?>
                        <p>
                            <label>
                                <?php _e('Default admin language: ', 'sitepress'); ?>
                                <?php $default_language_details = $sitepress->get_language_details( $default_language ); ?>
                                <select name="icl_admin_default_language">
									<option value="_default_"><?php printf(__('Default language (currently %s)', 'sitepress'),  $default_language_details['display_name']); ?></option>
									<?php foreach($all_languages as $al):?>
										<?php if($al['active']): ?>
											<option value="<?php echo $al['code'] ?>"<?php if($sitepress->get_setting('admin_default_language')==$al['code']) echo ' selected="selected"'?>><?php echo $al['display_name']; if($sitepress->get_admin_language() != $al['code']) echo ' ('. $al['native_name'] .')' ?>&nbsp;</option>
										<?php endif; ?>
									<?php endforeach; ?>
									<?php foreach($all_languages as $al):?>
										<?php if(!$al['active']): ?>
											<option value="<?php echo $al['code'] ?>"<?php if($sitepress->get_setting('admin_default_language')==$al['code']) echo ' selected="selected"'?>><?php echo $al['display_name']; if($sitepress->get_admin_language() != $al['code']) echo ' ('. $al['native_name'] .')' ?>&nbsp;</option>
										<?php endif; ?>
									<?php endforeach; ?>
                                </select>
                            </label>
                        </p>
                        <?php endif; ?>
                        <p><?php printf(__('Each user can choose the admin language. You can edit your language preferences by visiting your <a href="%s">profile page</a>.','sitepress'),'profile.php#wpml')?></p>
                        <p class="buttons-wrap">
                            <span class="icl_ajx_response" id="icl_ajx_response_al"></span>
                            <input class="button button-primary" name="save" value="<?php _e('Save','sitepress') ?>" type="submit" />
                        </p>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php if(!empty( $setup_complete ) && count($active_languages) > 1): ?>
            <div class="wpml-section wpml-section-blog-posts" id="lang-sec-6">
                <div class="wpml-section-header">
                    <h3><?php _e('Blog posts to display', 'sitepress') ?></h3>
                </div>
                <div class="wpml-section-content">
	                <form id="icl_blog_posts" name="icl_blog_posts" action="">
		                <?php wp_nonce_field( 'icl_blog_posts_nonce', '_icl_nonce' ); ?>
		                <p>
			                <?php
			                $icl_only_translated_posts_checked = checked( 0, icl_get_setting( 'show_untranslated_blog_posts', 0 ), false )
			                ?>
			                <label>
				                <input type="radio" name="icl_untranslated_blog_posts" <?php echo $icl_only_translated_posts_checked; ?> value="0"/>
				                <?php _e( 'Only translated posts.', 'sitepress' ); ?>
			                </label>
		                </p>

		                <p>
			                <label>
				                <?php
				                $icl_all_posts_checked = checked( 1, icl_get_setting( 'show_untranslated_blog_posts', 0 ), false )
				                ?>
				                <input type="radio" name="icl_untranslated_blog_posts" <?php echo $icl_all_posts_checked; ?> value="1"/>
				                <?php _e( 'All posts (display translation if it exists or posts in default language otherwise).', 'sitepress' ); ?>
			                </label>

		                </p>

		                <div id="icl_untranslated_blog_posts_help" style="display: none">
			                <?php _e( "Please note that this setting affects only blog posts queried by the main loop in a theme's index.php template.", "sitepress" ); ?>
		                </div>
		                <p class="buttons-wrap">
			                <span class="icl_ajx_response" id="icl_ajx_response_bp"></span>
			                <input class="button button-primary" name="save" value="<?php _e( 'Save', 'sitepress' ) ?>" type="submit"/>
		                </p>
	                </form>
                </div>
            </div>

            <div class="wpml-section wpml-section-hide-languages" id="lang-sec-7">
                <div class="wpml-section-header">
                    <h3><?php _e('Hide languages', 'sitepress') ?></h3>
                </div>
                <div class="wpml-section-content">
                    <p><?php _e("You can completely hide content in specific languages from visitors and search engines, but still view it yourself. This allows reviewing translations that are in progress.", 'sitepress') ?></p>
                    <form id="icl_hide_languages" name="icl_hide_languages" action="">
                        <?php wp_nonce_field('icl_hide_languages_nonce', '_icl_nonce') ?>
                        <?php foreach($active_languages as $l): ?>
                        <?php if($l['code'] == $default_language_details['code']) continue; ?>
                            <p>
                                <label>
                                    <input type="checkbox" name="icl_hidden_languages[]" <?php if(!empty( $hidden_languages ) && in_array($l['code'], $hidden_languages )) echo 'checked="checked"' ?> value="<?php echo $l['code']?>" /> <?php echo $l['display_name'] ?>
                                </label>
                            </p>
                        <?php endforeach; ?>
                        <p id="icl_hidden_languages_status">
                            <?php
                                if (!empty( $hidden_languages )){
																		//While checking for hidden languages, it cleans any possible leftover from inactive or deleted languages
																		if ( 1 == count( $hidden_languages ) ) {
																			if ( isset( $active_languages[ $hidden_languages[ 0 ] ] ) ) {
																				printf( __( '%s is currently hidden to visitors.', 'sitepress' ), $active_languages[ $hidden_languages[ 0 ] ][ 'display_name' ] );
																				$hidden_languages[] = $hidden_languages[ 0 ];
																			}
																		} else {
																			$_hlngs = array();
																			foreach ( $hidden_languages as $l ) {
																				if ( isset( $active_languages[ $l ] ) ) {
																					$_hlngs[ ] = $active_languages[ $l ][ 'display_name' ];
																				}
																			}
																			$hlangs = join( ', ', $_hlngs );
																			printf( __( '%s are currently hidden to visitors.', 'sitepress' ), $hlangs );
																		}
																		
																		$hidden_languages = array_unique($hidden_languages);
																		$sitepress->set_setting('hidden_languages', $hidden_languages);

                                     echo '<p>';
                                        printf(__('You can enable its/their display for yourself, in your <a href="%s">profile page</a>.', 'sitepress'),'profile.php#wpml');
                                     echo '</p>';
                                }
                                else {
                                    _e('All languages are currently displayed.', 'sitepress');
                                }
                            ?>
                        </p>
                        <p class="buttons-wrap">
                            <span class="icl_ajx_response" id="icl_ajx_response_hl"></span>
                            <input class="button button-primary" name="save" value="<?php _e('Save','sitepress') ?>" type="submit" />
                        </p>
                    </form>
                </div>
            </div>

            <div class="wpml-section wpml-section-ml-themes" id="lang-sec-8">
                <div class="wpml-section-header">
                    <h3><?php _e('Make themes work multilingual', 'sitepress') ?></h3>
                </div>
                <div class="wpml-section-content">
                        <form id="icl_adjust_ids" name="icl_adjust_ids" action="">
                            <?php wp_nonce_field('icl_adjust_ids_nonce', '_icl_nonce'); ?>
                            <p><?php _e('This feature turns themes into multilingual, without having to edit their PHP files.', 'sitepress')?></p>
                            <p>
                                <label>
                                    <input type="checkbox" value="1" name="icl_adjust_ids" <?php if($sitepress->get_setting('auto_adjust_ids')) echo 'checked="checked"' ?> />
                                    <?php _e('Adjust IDs for multilingual functionality', 'sitepress')?>
                                </label>
                            </p>
                            <p class="explanation-text"><?php _e('Note: auto-adjust IDs will increase the number of database queries for your site.', 'sitepress')?></p>
                            <p class="buttons-wrap">
                                <span class="icl_ajx_response" id="icl_ajx_response_ai"></span>
                                <input class="button button-primary" name="save" value="<?php _e('Save','sitepress') ?>" type="submit" />
                            </p>
                        </form>
                </div>
            </div>

            <div class="wpml-section wpml-section-redirect" id="lang-sec-9">
                <div class="wpml-section-header">
                    <h3><?php _e('Browser language redirect', 'sitepress') ?></h3>
                </div>
                <div class="wpml-section-content">
                    <p><?php _e('WPML can automatically redirect visitors according to browser language.', 'sitepress')?></p>
                    <p class="explanation-text"><?php _e("This feature uses Javascript. Make sure that your site doesn't have JS errors.", 'sitepress'); ?></p>
                    <form id="icl_automatic_redirect" name="icl_automatic_redirect" action="">
                        <?php wp_nonce_field('icl_automatic_redirect_nonce', '_icl_nonce') ?>
                        <ul>
                            <li><label>
                                <input type="radio" value="0" name="icl_automatic_redirect" <?php if(empty( $automatic_redirect )) echo 'checked="checked"' ?> />
                                <?php _e('Disable browser language redirect', 'sitepress')?>
                            </label></li>
                            <li><label>
                                <input type="radio" value="1" name="icl_automatic_redirect" <?php if(@intval( $automatic_redirect ) == 1) echo 'checked="checked"' ?> />
                                <?php _e('Redirect visitors based on browser language only if translations exist', 'sitepress')?>
                            </label></li>
                            <li><label>
                                <input type="radio" value="2" name="icl_automatic_redirect" <?php if(@intval( $automatic_redirect ) == 2) echo 'checked="checked"' ?> />
                                <?php _e('Always redirect visitors based on browser language (redirect to home page if translations are missing)', 'sitepress')?>
                            </label></li>
                        </ul>
                        <ul>
                            <li>
                                <label><?php printf(__("Remember visitors' language preference for %s hours (please enter 24 or multiples of it).", 'sitepress'),
                                    '<input size="2" type="number" min="24" value="'.@intval($sitepress->get_setting('remember_language')).'" name="icl_remember_language" /> ');
                                ?>
                                <?php if(!$wpml_request_handler->get_cookie_lang()): ?>
                                <span class="icl_error_text"><?php _e("Your browser doesn't seem to be allowing cookies to be set.", 'sitepress'); ?></span>
                                <?php endif; ?>
                            </label></li>
                        </ul>
                        <p class="buttons-wrap">
                            <span class="icl_ajx_response" id="icl_ajx_response_ar"></span>
                            <input class="button button-primary" name="save" value="<?php _e('Save','sitepress') ?>" type="submit" />
                        </p>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>


    <?php if(!empty( $setup_complete )): ?>

        <?php
				$request_get_page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE);
				do_action('icl_extra_options_' . $request_get_page); ?>

        <div class="wpml-section wpml-section-seo-options" id="lang-sec-9-5">
            <div class="wpml-section-header">
                <h3><?php _e('SEO Options', 'sitepress') ?></h3>
            </div>
            <div class="wpml-section-content">
                <form id="icl_seo_options" name="icl_seo_options" action="">
                    <?php wp_nonce_field('icl_seo_options_nonce', '_icl_nonce'); ?>
                    <p>
                        <label><input type="checkbox" name="icl_seo_head_langs" <?php if( $seo['head_langs']) echo 'checked="checked"' ?> value="1" />
                        <?php _e("Display alternative languages in the HEAD section.", 'sitepress'); ?></label>
                    </p>
                    <p class="buttons-wrap">
                        <span class="icl_ajx_response" id="icl_ajx_response_seo"></span>
                        <input class="button button-primary" name="save" value="<?php _e('Save','sitepress') ?>" type="submit" />
                    </p>
                </form>
            </div>
        </div>

        <div class="wpml-section wpml-section-wpml-love" id="lang-sec-10">
            <div class="wpml-section-header">
                <h3><?php _e('WPML love', 'sitepress') ?></h3>
            </div>
            <div class="wpml-section-content">
                <form id="icl_promote_form" name="icl_promote_form" action="">
                    <?php wp_nonce_field('icl_promote_form_nonce', '_icl_nonce'); ?>
                    <p>
                        <label><input type="checkbox" name="icl_promote" <?php if($sitepress->get_setting('promote_wpml')) echo 'checked="checked"' ?> value="1" />
                        <?php printf(__("Tell the world your site is running multilingual with WPML (places a message in your site's footer) - <a href=\"%s\">read more</a>", 'sitepress'),'https://wpml.org/?page_id=4560'); ?></label>
                    </p>
                    <p class="buttons-wrap">
                        <span class="icl_ajx_response" id="icl_ajx_response_lv"></span>
                        <input class="button button-primary" name="save" value="<?php _e('Save','sitepress') ?>" type="submit" />
                    </p>
                </form>
            </div>
        </div>

    <?php endif; ?>

    <?php do_action('icl_menu_footer'); ?>

</div> <!-- .wrap -->
<?php
}
//Save any changed setting
$sitepress->save_settings();
