<?php
class EM_WPML_Permalnks {

    public static function init(){
        add_filter('em_rewrite_rules_array_events','EM_WPML_Permalnks::em_rewrite_rules_array_events', 10, 2);
    }
    
    public static function em_rewrite_rules_array_events($em_rules, $events_slug){
        global $sitepress;
		$events_page = get_post( get_option('dbem_events_page') );
		if( is_object($events_page) ){
		    $trid = $sitepress->get_element_trid($events_page->ID);
		    $translations = $sitepress->get_element_translations($trid);
		    $default_lang = $sitepress->get_default_language();
		    foreach( $translations as $lang => $translation ){
		        if( $lang != $default_lang && $translation->post_status == 'publish'){
				    $events_slug = urldecode(preg_replace('/\/$/', '', str_replace( trailingslashit(home_url()), '', get_permalink($translation->element_id)) ));
		            $em_rules[trailingslashit($events_slug).'(\d{4}-\d{2}-\d{2})$'] = 'index.php?page_id='.$translation->element_id.'&calendar_day=$matches[1]'; //event calendar date search
		        }
		    }
		}
        return $em_rules;
    }
}
EM_WPML_Permalnks::init();