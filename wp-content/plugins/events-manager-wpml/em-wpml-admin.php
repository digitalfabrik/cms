<?php
class EM_WPML_Admin {
    
	public static function init(){
		global $pagenow;
		add_filter('em_ml_admin_original_event_link','EM_WPML_Admin::em_ml_admin_original_event_link',10,1);
		//recurring events notice
		if( !get_option('em_wpml_disable_recurrence_notice') && is_admin() && current_user_can('activate_plugins') ){
		    global $pagenow;
		    if( !empty($_REQUEST['em_wpml_disable_recurrence_notice']) ){
		        update_option('em_wpml_disable_recurrence_notice',true);
		    }else{
			    add_action('admin_notices','EM_WPML_Admin::disable_recurrence_notice');
		    }
		}
		if( $pagenow == 'edit.php' && !empty($_REQUEST['page']) && $_REQUEST['page'] == 'events-manager-options' ){
		    add_filter('admin_init', 'EM_WPML_Admin::options_redirect');
		}
	}
	
	public static function options_redirect(){
	    global $sitepress;
	    //check if we've redirected already
	    if( !empty($_REQUEST['wpmlredirect']) ){
	        global $EM_Notices; /* @var $EM_Notices EM_Notices */
	        $EM_Notices->add_info(__('You have been redirected to the main language of your site for this settings page. All translatable settings can be translated here.', 'events-manager-wpml'));
	    }
	    //redirect users if this isn't the main language of the blog
	    if( EM_ML::$current_language == EM_ML::$wplang ) return;
	    $sitepress_langs = $sitepress->get_active_languages();
	    foreach( $sitepress_langs as $lang => $lang_info ){
	        if( $lang_info['default_locale'] == EM_ML::$wplang ){
                wp_redirect(admin_url('edit.php?post_type=event&page=events-manager-options&wpmlredirect=1&lang='.$lang));
                exit();
	        }
	    }
	}
    
    /**
     * Notifies user of the fact that recurrences are disabled by default with this plugin activated 
     */
    public static function disable_recurrence_notice(){
		?>
		<div id="message" class="updated">
			<p><?php echo sprintf(__('Since you are using WPML, we have automatically disabled recurring events, your recurrences already created will act as normal single events. This is because recurrences are not compatible with WPML at the moment. If you really still want recurrences enabled, then you should add %s to your wp-config.php file. <a href="%s">Dismiss</a>','events-manager-wpml'),'<code>define(\'EM_WMPL_FORCE_RECURRENCES\',true);</code>', add_query_arg(array('em_wpml_disable_recurrence_notice'=>1))); ?></p>
		</div>
		<?php
    }
	
	public static function em_ml_admin_original_event_link( $link ){
	    global $EM_Event;
    	if( empty($EM_Event->event_id) && !empty($_REQUEST['trid']) ){
			$post_id = SitePress::get_original_element_id_by_trid($_REQUEST['trid']);
			$original_event_link = em_get_event($post_id,'post_id')->get_edit_url();
		}
		return $link;
	}
}
EM_WPML_Admin::init();