<?php
class EM_WPML_Search {
	/*
	* MODIFYING EVENT/LOCATION LIST SEARCHES
	*/
    
    public static function init(){
        add_filter('em_events_build_sql_conditions','EM_WPML_Search::event_searches',10,2);
        add_filter('em_locations_build_sql_conditions','EM_WPML_Search::location_searches',10,2);
		add_filter('em_actions_locations_search_cond','EM_WPML_Search::location_searches_autocompleter'); //will work as of EM 5.3.3
    }
	
	/**
	 * Adds an extra condition to filter out events translated in the current language
	 * @param array $conditions
	 * @param array $args
	 * @return string
	 */
	public static function event_searches($conditions, $args){
		global $wpdb;
		if( defined('ICL_LANGUAGE_CODE') ){
			$conditions['wpml'] = EM_EVENTS_TABLE.'.post_id IN (SELECT element_id FROM '.$wpdb->prefix."icl_translations WHERE language_code ='".ICL_LANGUAGE_CODE."' AND element_type='post_".EM_POST_TYPE_EVENT."')";
		}
		return $conditions;
	}
	
	
	/**
	 * Adds an extra condition to filter out locations translated in the current language
	 * @param array $conditions
	 * @param array $args
	 * @return string
	 */
	public static function location_searches($conditions, $args){
		global $wpdb;
		if( defined('ICL_LANGUAGE_CODE') ){
			$conditions['wpml'] = EM_LOCATIONS_TABLE.'.post_id IN (SELECT element_id FROM '.$wpdb->prefix."icl_translations WHERE language_code ='".ICL_LANGUAGE_CODE."' AND element_type='post_".EM_POST_TYPE_LOCATION."')";
		}
		return $conditions;
	}
	
	/**
	 * Checks location search according  
	 * @param unknown_type $location_conds
	 * @return string
	 */
	public static function location_searches_autocompleter($location_conds){
		global $wpdb;
		if( defined('ICL_LANGUAGE_CODE') ){
			$location_conds .= " AND ".EM_LOCATIONS_TABLE.'.post_id IN (SELECT element_id FROM '.$wpdb->prefix."icl_translations WHERE language_code ='".ICL_LANGUAGE_CODE."' AND element_type='post_".EM_POST_TYPE_LOCATION."')";
		}
		return $location_conds;
	}
    
}
EM_WPML_Search::init();