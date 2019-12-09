<?php
class EM_ML_Search {
	
	/**
	 * @var bool Flag to easily disable search filtering of languages
	 */
	public static $active = true;
	public static $show_untranslated = array('location' => false, 'event' => false, 'event-recurring' => false);
	public static $untranslated_cache = array();
    
    public static function init(){
        add_filter('em_object_get_default_search','EM_ML_Search::em_object_get_default_search',10);
	    add_filter('em_locations_build_sql_conditions','EM_ML_Search::location_searches',10,2);
	    add_filter('em_events_build_sql_conditions','EM_ML_Search::event_searches',10,2);
	    add_filter('em_actions_locations_search_cond','EM_ML_Search::location_searches_autocompleter'); //will work as of EM 5.3.3
    }
	
	/**
	 * Returns a list of untranslated events or locations by evnet/location id. Used f,
	 * ,or searches that should include untranslated versions of items in a list.
	 * @param string $language
	 * @param string $what
	 * @return array
	 */
    public static function get_untranslated( $language, $what = 'event' ){
    	global $wpdb;
    	if( $what !== EM_POST_TYPE_EVENT && $what !== 'event-recurring' && $what !== EM_POST_TYPE_LOCATION ) return array();
    	$prefix = $what == 'location' ? 'location' : 'event';
    	$table = $what == 'location' ? EM_LOCATIONS_TABLE : EM_EVENTS_TABLE;
	    $blog_id = get_current_blog_id(); // only useful for MS non-global tables mode
	    // return cache if possible
	    if( is_multisite() && !EM_MS_GLOBAL && !empty(static::$untranslated_cache[$blog_id][$language][$table]) ){
	        return static::$untranslated_cache[$blog_id][$language][$table];
	    }elseif( !empty(static::$untranslated_cache[$language][$table]) ){
	        return static::$untranslated_cache[$language][$table];
	    }
	    // query once per script run
    	$sql = "SELECT {$prefix}_id FROM $table WHERE {$prefix}_translation=0 AND {$prefix}_id NOT IN (SELECT DISTINCT {$prefix}_parent FROM $table WHERE {$prefix}_language=%s AND {$prefix}_translation=1 AND {$prefix}_parent IS NOT NULL) AND {$prefix}_language!='%s';";
    	$results = $wpdb->get_col( $wpdb->prepare($sql, $language, $language) );
    	if( is_multisite() && !EM_MS_GLOBAL){
    		//each blog will have its own location/events table
		    static::$untranslated_cache[$blog_id][$language][$table] = $results;
	    }else{
		    static::$untranslated_cache[$language][$table] = $results;
	    }
    	return is_array($results) ? $results : array();
    }
	
	/**
	 * @param array $defaults
	 * @return array
	 */
    public static function em_object_get_default_search( $defaults ){
	    if( !EM_ML_Search::$active ) return $defaults;
        if( !empty($defaults['location']) ){
            //check that this location ID is the original one, given that all events of any language will refer to the location_id of the original
            $EM_Location = em_get_location($defaults['location']);
            if( !EM_ML::is_original($EM_Location) ){
                $defaults['location'] = EM_ML::get_original_location($EM_Location)->location_id;
            }
        }
        // define a language if not already defined
        if( $defaults['language'] === null ){
		    $defaults['language'] = EM_ML::$current_language; // the default will now be to search in the current language
        }
        return $defaults;
    }
 
	/**
	 * Tweaks eventful and eventless search arguments so that these searches are based off the original/parent location which is what events in any language will store as the location_id.
	 * @param array $conditions
	 * @param array $args
	 * @return array
	 */
	public static function location_searches($conditions, $args){
		global $wpdb;
		if( !EM_ML_Search::$active ) return $conditions;
		if( !empty($args['language']) ){
			// if we are to show locations where translated events don't exist, we will need to include those here
			if( static::$show_untranslated['location'] ){
				$untranslated_locations = static::get_untranslated( $args['language'], EM_POST_TYPE_LOCATION );
				if( !empty($untranslated_locations) ){
					$conditions['language'] = $wpdb->prepare('(location_language=%s OR '.EM_LOCATIONS_TABLE.'.location_id IN ('. implode(',', $untranslated_locations) .'))', $args['language']);
				}
			}
			// we add a sub sub query to get any originally translated locations that do/don't have an originally translated event of some kind, past present or whatever, the parent query will do the real filtering
			if( !empty($args['eventless']) ){
				$conditions['eventless'] = '(event_id IS NULL AND (location_translation=0 OR location_parent NOT IN (SELECT DISTINCT location_id FROM '.EM_EVENTS_TABLE.')))';
			}elseif( !empty($args['eventful']) ){
				$conditions['eventful'] = '(event_id IS NOT NULL OR location_parent IN (SELECT DISTINCT location_id FROM '.EM_EVENTS_TABLE.'))';
			}
		}
		return $conditions;
	}
	
	/**
	 *
	 * @param array $conditions
	 * @param array $args
	 * @return array
	 */
	public static function event_searches( $conditions, $args ){
		global $wpdb;
		if( !EM_ML_Search::$active ) return $conditions;
		if( !empty($args['language']) ){
			// if we are to show events where translated events don't exist, we will need to include those here
			if( static::$show_untranslated['event'] ){
				$untranslated_events = static::get_untranslated( $args['language'], EM_POST_TYPE_EVENT );
				if( !empty($untranslated_events) ){
					$conditions['language'] = $wpdb->prepare('(event_language=%s OR '.EM_EVENTS_TABLE.'.event_id IN ('. implode(',', $untranslated_events) .'))', $args['language']);
				}
			}
		}
		return $conditions;
	}
	
	/**
	 * Checks location search according to current language.
	 * @param string $location_conds
	 * @return string
	 */
	public static function location_searches_autocompleter($location_conds){
		global $wpdb;
		if( !EM_ML_Search::$active ) return $location_conds;
		$location_conds .= $wpdb->prepare(' AND '.EM_LOCATIONS_TABLE.'.location_language=%s', EM_ML::$current_language);
		return $location_conds;
	}
    
}
EM_ML_Search::init();