<?php
/**
 * Static class which will help bulk add/edit/retrieve/manipulate arrays of EM_Location objects. 
 * Optimized for specifically retreiving locations (whether eventful or not). If you want event data AND location information for each event, use EM_Events
 * 
 */
class EM_Locations extends EM_Object {
	
	/**
	 * Like WPDB->num_rows it holds the number of results found on the last query.
	 * @var int
	 */
	public static $num_rows;
	
	/**
	 * If $args['pagination'] is true or $args['offset'] or $args['page'] is greater than one, and a limit is imposed when using a get() query, 
	 * this will contain the total records found without a limit for the last query.
	 * If no limit was used or pagination was not enabled, this will be the same as self::$num_rows
	 * @var int
	 */
	public static $num_rows_found;
	
	/**
	 * Returns an array of EM_Location objects
	 * @param boolean $eventful
	 * @param boolean $return_objects
	 * @return array
	 */
	public static function get( $args = array(), $count=false ){
		global $wpdb;
		$events_table = EM_EVENTS_TABLE;
		$locations_table = EM_LOCATIONS_TABLE;
		$locations = array();
		
		//Quick version, we can accept an array of IDs, which is easy to retrieve
		if( self::array_is_numeric($args) ){ //Array of numbers, assume they are event IDs to retreive
			//We can just get all the events here and return them
			$locations = array();
			foreach($args as $location_id){
				$locations[$location_id] = em_get_location($location_id);
			}
			return apply_filters('em_locations_get', $locations, $args); //We return all the events matched as an EM_Event array. 
		}elseif( is_numeric($args) ){
			//return an event in the usual array format
			return apply_filters('em_locations_get', array(em_get_location($args)), $args);
		}elseif( is_array($args) && is_object(current($args)) && get_class((current($args))) == 'EM_Location' ){
		    //we were passed an array of EM_Location classes, so we just give it back
		    /* @todo do we really need this condition in EM_Locations::get()? */
			return apply_filters('em_locations_get', $args, $args);
		}	

		//We assume it's either an empty array or array of search arguments to merge with defaults			
		$args = self::get_default_search($args);
		$limit = ( $args['limit'] && is_numeric($args['limit'])) ? "LIMIT {$args['limit']}" : '';
		$offset = ( $limit != "" && is_numeric($args['offset']) ) ? "OFFSET {$args['offset']}" : '';
		
		//Get fields that we can use in ordering and grouping, which can be event and location (excluding ambiguous) fields
		$EM_Event = new EM_Event(); //blank event for below
		$EM_Location = new EM_Location(0); //blank location for below
		$location_fields = array_keys($EM_Location->fields);
		$event_fields = array(); //will contain event-specific fields, not ambiguous ones
		foreach( array_keys($EM_Event->fields) as $field_name ){
			if( !in_array($field_name, $location_fields) ) $event_fields[] = $field_name;
		}
		$accepted_fields = array_merge($event_fields, $location_fields);

		//add selectors
		$calc_found_rows = $limit && ( $args['pagination'] || $args['offset'] > 0 || $args['page'] > 0 );
		if( $count ){
			$selectors = 'COUNT(DISTINCT '.$locations_table . '.location_id)'; //works in MS Global mode since location_id is always unique, post_id is not
			$limit = 'LIMIT 1';
			$offset = 'OFFSET 0';
		}else{
			if( $args['array'] ){
				//get all fields from table, add events table prefix to avoid ambiguous fields from location
				$selectors = $locations_table . '.*';
			}elseif( EM_MS_GLOBAL ){
				$selectors = $locations_table.'.post_id, '.$locations_table.'.blog_id';
			}else{
				$selectors = $locations_table.'.post_id';
			}
			if( $calc_found_rows ) $selectors = 'SQL_CALC_FOUND_ROWS ' . $selectors; //for storing total rows found
			$selectors = 'DISTINCT ' . $selectors; //duplicate avoidance
		}
		
		//check if we need to join a location table for this search, which is necessary if any location-specific are supplied, or if certain arguments such as orderby contain location fields
		$join_events_table = false;
		//for we only will check optional joining by default for groupby searches, and for the original searches if EM_DISABLE_OPTIONAL_JOINS is set to true in wp-config.php
		if( !empty($args['groupby']) || (defined('EM_DISABLE_OPTIONAL_JOINS') && EM_DISABLE_OPTIONAL_JOINS) ){
			$event_specific_args = array('eventful', 'eventless', 'tag', 'category', 'event', 'recurrence', 'month', 'year', 'rsvp', 'bookings');
			$join_events_table = $args['scope'] != 'all'; //only value where false is not default so we check that first
			foreach( $event_specific_args as $arg ) if( !empty($args[$arg]) ) $join_events_table = true;
			//if set to false the following would provide a false negative in the line above
			if( $args['recurrences'] !== null ) $join_events_table = true;
			if( $args['recurring'] !== null ) $join_events_table = true;
			if( $args['event_status'] !== false ){ $join_events_table = true; }
			//check ordering and grouping arguments for precense of event fields requiring a join
			if( !$join_events_table ){
				foreach( array('groupby', 'orderby', 'groupby_orderby') as $arg ){
					if( !is_array($args[$arg]) ) continue; //ignore this argument if set to false
					//we assume all these arguments are now array thanks to self::get_search_defaults() cleaning it up
					foreach( $args[$arg] as $field_name ){
						if( in_array($field_name, $event_fields) ){
							$join_events_table = true;
							break; //we join, no need to keep searching
						}
					}
				}
			}
			//EM_Events has a special argument for recurring events (the template), where it automatically omits recurring event templates. If we are searching events, and recurring was not explicitly set, we set it to the same as in EM_Events default 
			if( $join_events_table && $args['recurring'] === null ) $args['recurring'] = false;
		}else{ $join_events_table = true; }//end temporary if( !empty($args['groupby']).... wrapper 
		//plugins can override this optional joining behaviour here in case they add custom WHERE conditions or something like that
		$join_events_table = apply_filters('em_locations_get_join_events_table', $join_events_table, $args, $count);
		//depending on whether to join we do certain things like add a join SQL, change specific values like status search
		$event_optional_join = $join_events_table ? "LEFT JOIN $events_table ON {$locations_table}.location_id={$events_table}.location_id" : '';
		
		//Build ORDER BY and WHERE SQL statements here, after we've done all the pre-processing necessary
		$conditions = self::build_sql_conditions($args);
		$where = ( count($conditions) > 0 ) ? " WHERE " . implode ( " AND ", $conditions ):'';
		$orderby = self::build_sql_orderby($args, $accepted_fields, get_option('dbem_events_default_order'));
		$orderby_sql = ( count($orderby) > 0 ) ? 'ORDER BY '. implode(', ', $orderby) : '';
		
		//Build GROUP BY SQL statement, which will be very different if we group things due to how we need to filter out by event date
		if( !empty($args['groupby']) ){
			//get groupby field(s)
			$groupby_fields = self::build_sql_groupby($args, $accepted_fields);
			if( !empty($groupby_fields[0]) ){
				//we can safely assume we've been passed at least one array item with index of 0 containing a valid field due to build_sql_groupby()
				$groupby_field = $groupby_fields[0]; //we only support one field for events
				$groupby_orderby = self::build_sql_groupby_orderby($args, $accepted_fields);
				$groupby_orderby_sql = !empty($groupby_orderby) ? ', '. implode(', ', $groupby_orderby) : '';
				//get minimum required selectors within the inner query to shorten query length as much as possible
				$inner_selectors = $locations_table . '.*';
				if( $event_optional_join ){
					//we're selecting all fields from events table so add only location fields required in the outer ORDER BY statement
					if( in_array($groupby_field, $event_fields) && !in_array($groupby_field, $args['orderby']) ){
						//we may not have included the grouped field if it's not in the outer ORDER BY clause, so we add it for this specific query
						$inner_selectors .= ', '. $events_table .'.'. $groupby_field;
					}
					foreach( $args['orderby'] as $orderby_field ){
						if( in_array($orderby_field, $event_fields) ){
							$inner_selectors .= ', '. $events_table .'.'. $orderby_field;
						}
					}
				}
				//THE Query - Grouped
				$sql = "
SELECT DISTINCT $selectors
FROM (
	SELECT *,
		@cur := IF($groupby_field = @id, @cur+1, 1) AS RowNumber,
		@id := $groupby_field AS IdCache
	FROM (
		SELECT {$inner_selectors} FROM {$locations_table}
		$event_optional_join
		$where
		ORDER BY {$groupby_field} $groupby_orderby_sql
	) DataSet
	INNER JOIN (
		SELECT @id:='', @cur:=0
	) AS lookup
) {$locations_table}
WHERE RowNumber = 1
$orderby_sql
$limit $offset";
			}
		}

		//build the SQL statement if not already built for group
		if( empty($sql) ){
			//THE query
			$sql = "
SELECT DISTINCT $selectors FROM $locations_table
$event_optional_join
$where
$orderby_sql
$limit $offset
			";
		}
		
		//the query filter
		$sql = apply_filters('em_locations_get_sql', $sql, $args); 
		//if( em_wp_is_super_admin() && WP_DEBUG_DISPLAY ){ echo "<pre>"; print_r($sql); echo '</pre>'; }
		
		//If we're only counting results, return the number of results
		if( $count ){
			self::$num_rows_found = self::$num_rows = $wpdb->get_var($sql);
			return apply_filters('em_locations_get_count', self::$num_rows, $args);	
		}
		
		//get the result and count results
		$results = $wpdb->get_results( $sql, ARRAY_A);
		self::$num_rows = $wpdb->num_rows;
		if( $calc_found_rows ){
			self::$num_rows_found = $wpdb->get_var('SELECT FOUND_ROWS()');
		}else{
			self::$num_rows_found = self::$num_rows;
		}

		//If we want results directly in an array, why not have a shortcut here?
		if( $args['array'] == true ){
			return apply_filters('em_locations_get_array', $results, $args);
		}
		
		if( EM_MS_GLOBAL ){
			foreach ( $results as $location ){
			    if( empty($location['blog_id']) ) $location['blog_id'] = get_current_site()->blog_id;
				$locations[] = em_get_location($location['post_id'], $location['blog_id']);
			}
		}else{
			foreach ( $results as $location ){
				$locations[] = em_get_location($location['post_id'], 'post_id');
			}
		}
		return apply_filters('em_locations_get', $locations, $args);
	}	
	
	public static function count( $args = array() ){
		return apply_filters('em_locations_count', self::get($args, true), $args);
	}
	
	/**
	 * Output a set of matched of events
	 * @param array $args
	 * @return string
	 */
	public static function output( $args ){
		global $EM_Location;
		$EM_Location_old = $EM_Location; //When looping, we can replace EM_Location global with the current event in the loop
		//Can be either an array for the get search or an array of EM_Location objects
		$page_queryvar = !empty($args['page_queryvar']) ? $args['page_queryvar'] : 'pno';
		if( !empty($args['pagination']) && !array_key_exists('page',$args) && !empty($_REQUEST[$page_queryvar]) && is_numeric($_REQUEST[$page_queryvar]) ){
			$args['page'] = $_REQUEST[$page_queryvar];
		}
		if( is_object(current($args)) && get_class((current($args))) == 'EM_Location' ){
			$func_args = func_get_args();
			$locations = $func_args[0];
			$args = (!empty($func_args[1])) ? $func_args[1] : array();
			$args = apply_filters('em_locations_output_args', self::get_default_search($args), $locations);
			$limit = ( !empty($args['limit']) && is_numeric($args['limit']) ) ? $args['limit']:false;
			$locations_count = count($locations);
		}else{
			$args = apply_filters('em_locations_output_args', self::get_default_search($args) );
			$limit = ( !empty($args['limit']) && is_numeric($args['limit']) ) ? $args['limit']:false;
			$locations = self::get( $args );
			$locations_count = self::$num_rows_found;
		}
		//What format shall we output this to, or use default
		$format = empty($args['format']) ? get_option( 'dbem_location_list_item_format' ) : $args['format'] ;
		
		$output = "";
		$locations = apply_filters('em_locations_output_locations', $locations);	
		if ( count($locations) > 0 ) {
			foreach ( $locations as $EM_Location ) {
				$output .= $EM_Location->output($format);
			}
			//Add headers and footers to output
			if( $format == get_option( 'dbem_location_list_item_format' ) ){
			    //we're using the default format, so if a custom format header or footer is supplied, we can override it, if not use the default
			    $format_header = empty($args['format_header']) ? get_option('dbem_location_list_item_format_header') : $args['format_header'];
			    $format_footer = empty($args['format_footer']) ? get_option('dbem_location_list_item_format_footer') : $args['format_footer'];
			}else{
			    //we're using a custom format, so if a header or footer isn't specifically supplied we assume it's blank
			    $format_header = !empty($args['format_header']) ? $args['format_header'] : '' ;
			    $format_footer = !empty($args['format_footer']) ? $args['format_footer'] : '' ;
			}
			$output =  $format_header .  $output . $format_footer;
			
			//Pagination (if needed/requested)
			if( !empty($args['pagination']) && !empty($limit) && $locations_count > $limit ){
				//output pagination links
				$output .= self::get_pagination_links($args, $locations_count);
			}
		}elseif( $args['no_results_msg'] !== false ){
			$output = !empty($args['no_results_msg']) ? $args['no_results_msg'] : get_option('dbem_no_locations_message');
		}
		//FIXME check if reference is ok when restoring object, due to changes in php5 v 4
		$EM_Location_old= $EM_Location;
		return apply_filters('em_locations_output', $output, $locations, $args);		
	}
	
	public static function get_pagination_links($args, $count, $search_action = 'search_locations', $default_args = array()){
		//get default args if we're in a search, supply to parent since we can't depend on late static binding until WP requires PHP 5.3 or later
		if( empty($default_args) && (!empty($args['ajax']) || !empty($_REQUEST['action']) && $_REQUEST['action'] == $search_action) ){
			$default_args = self::get_default_search();
			$default_args['limit'] = get_option('dbem_locations_default_limit'); //since we're paginating, get the default limit, which isn't obtained from get_default_search()
		}
		return parent::get_pagination_links($args, $count, $search_action, $default_args);
	}
	
	public static function delete( $args = array() ){
	    $locations = array();
		if( !is_object(current($args)) ){
		    //we've been given an array or search arguments to find the relevant locations to delete
			$locations = self::get($args);
		}elseif( get_class(current($args)) == 'EM_Location' ){
		    //we're deleting an array of locations
			$locations = $args;
		}
		$results = array();
		foreach ( $locations as $EM_Location ){
			$results[] = $EM_Location->delete();
		}		
		return apply_filters('em_locations_delete', in_array(false, $results), $locations);
	}
	
	public static function get_post_search($args = array(), $filter = false, $request = array(), $accepted_args = array()){
		//supply $accepted_args to parent argument since we can't depend on late static binding until WP requires PHP 5.3 or later
		$accepted_args = !empty($accepted_args) ? $accepted_args : array_keys(self::get_default_search());
		$return = parent::get_post_search($args, $filter, $request, $accepted_args);
		//remove unwanted arguments or if not explicitly requested
		if( empty($_REQUEST['scope']) && empty($request['scope']) && !empty($return['scope']) ){
			unset($return['scope']);
		}
		return apply_filters('em_locations_get_post_search', $return);
	}
	
	/**
	 * Builds an array of SQL query conditions based on regularly used arguments
	 * @param array $args
	 * @return array
	 */
	public static function build_sql_conditions( $args = array(), $count=false ){
	    self::$context = EM_POST_TYPE_LOCATION;
		global $wpdb;
		$events_table = EM_EVENTS_TABLE;
		$locations_table = EM_LOCATIONS_TABLE;
		
		$conditions = parent::build_sql_conditions($args);
		//search locations
		if( !empty($args['search']) ){
			$like_search = array($locations_table.'.post_content','location_name','location_address','location_town','location_postcode','location_state','location_region','location_country');
			$like_search_string = '%'.$wpdb->esc_like($args['search']).'%';
			$like_search_strings = array();
			foreach( $like_search as $v ) $like_search_strings[] = $like_search_string;
			$like_search_sql = "(".implode(" LIKE %s OR ", $like_search). "  LIKE %s)";
			$conditions['search'] = $wpdb->prepare($like_search_sql, $like_search_strings);
		}
		//eventful locations
		if( true == $args['eventful'] ){
			$conditions['eventful'] = "{$events_table}.event_id IS NOT NULL";
		}elseif( true == $args['eventless'] ){
			$conditions['eventless'] = "{$events_table}.event_id IS NULL";
			if( !empty($conditions['scope']) ) unset($conditions['scope']); //scope condition would render all queries return no results
		}
		//owner lookup
		if( !empty($args['owner']) && is_numeric($args['owner'])){
			$conditions['owner'] = "location_owner=".$args['owner'];
		}elseif( !empty($args['owner']) && $args['owner'] == 'me' && is_user_logged_in() ){
			$conditions['owner'] = 'location_owner='.get_current_user_id();
		}elseif( self::array_is_numeric($args['owner']) ){
			$conditions['owner'] = 'location_owner IN ('.implode(',',$args['owner']).')';
		}
		//blog id in events table
		if( EM_MS_GLOBAL && !empty($args['blog']) ){
		    if( is_numeric($args['blog']) ){
				if( is_main_site($args['blog']) ){
					$conditions['blog'] = "(".$locations_table.".blog_id={$args['blog']} OR ".$locations_table.".blog_id IS NULL)";
				}else{
					$conditions['blog'] = "(".$locations_table.".blog_id={$args['blog']})";
				}
		    }else{
		        if( !is_array($args['blog']) && preg_match('/^([\-0-9],?)+$/', $args['blog']) ){
		            $conditions['blog'] = "(".$locations_table.".blog_id IN ({$args['blog']}) )";
			    }elseif( is_array($args['blog']) && self::array_is_numeric($args['blog']) ){
			        $conditions['blog'] = "(".$locations_table.".blog_id IN (".implode(',',$args['blog']).") )";
			    }
		    }
		}
		//private locations
		if( empty($args['private']) ){
			$conditions['private'] = "(`location_private`=0)";
		}elseif( !empty($args['private_only']) ){
			$conditions['private_only'] = "(`location_private`=1)";
		}
		//post search
		if( !empty($args['post_id'])){
			if( self::array_is_numeric($args['post_id']) ){
				$conditions['post_id'] = "($locations_table.post_id IN (".implode(',',$args['post_id'])."))";
			}else{
				$conditions['post_id'] = "($locations_table.post_id={$args['post_id']})";
			}
		}
		return apply_filters('em_locations_build_sql_conditions', $conditions, $args);
	}
	
	/**
	 * Overrides EM_Object method to clean ambiguous fields and apply a filter to result.
	 * @see EM_Object::build_sql_orderby()
	 */
	 public static function build_sql_orderby( $args, $accepted_fields, $default_order = 'ASC' ){
		$orderby = parent::build_sql_orderby($args, $accepted_fields, get_option('dbem_events_default_order'));
		$orderby = self::build_sql_ambiguous_fields_helper($orderby); //fix ambiguous fields
		return apply_filters( 'em_locations_build_sql_orderby', $orderby, $args, $accepted_fields, $default_order );
	}
	
	/**
	 * Overrides EM_Object method to clean ambiguous fields and apply a filter to result.
	 * @see EM_Object::build_sql_groupby()
	 */
	public static function build_sql_groupby( $args, $accepted_fields, $groupby_order = false, $default_order = 'ASC' ){
		$groupby = parent::build_sql_groupby($args, $accepted_fields);
		//fix ambiguous fields and give them scope of events table
		$groupby = self::build_sql_ambiguous_fields_helper($groupby);
		return apply_filters( 'em_locations_build_sql_groupby', $groupby, $args, $accepted_fields );
	}
	
	/**
	 * Overrides EM_Object method to clean ambiguous fields and apply a filter to result.
	 * @see EM_Object::build_sql_groupby_orderby()
	 */
	 public static function build_sql_groupby_orderby($args, $accepted_fields, $default_order = 'ASC' ){
	    $group_orderby = parent::build_sql_groupby_orderby($args, $accepted_fields, get_option('dbem_events_default_order'));
		//fix ambiguous fields and give them scope of events table
		$group_orderby = self::build_sql_ambiguous_fields_helper($group_orderby);
		return apply_filters( 'em_locations_build_sql_groupby_orderby', $group_orderby, $args, $accepted_fields, $default_order );
	}
	
	/**
	 * Overrides EM_Object method to provide specific reserved fields and locations table.
	 * @see EM_Object::build_sql_ambiguous_fields_helper()
	 */
	protected static function build_sql_ambiguous_fields_helper( $fields, $reserved_fields = array(), $prefix = 'table_name' ){
		//This will likely be removed when PHP 5.3 is the minimum and LSB is a given
		return parent::build_sql_ambiguous_fields_helper($fields, array('post_id', 'location_id', 'blog_id'), EM_LOCATIONS_TABLE);
	}
	
	/* 
	 * Generate a search arguments array from defalut and user-defined.
	 * @param array $array_or_defaults may be the array to override defaults
	 * @param array $array
	 * @return array
	 * @uses EM_Object#get_default_search()
	 */
	public static function get_default_search( $array_or_defaults = array(), $array = array() ){
	    self::$context = EM_POST_TYPE_LOCATION;
		$defaults = array(
			'orderby' => 'location_name',
			'groupby' => false,
			'groupby_orderby' => 'location_name', //groups according to event start time, i.e. by default shows earliest event in a scope
			'groupby_order' => 'ASC', //groups according to event start time, i.e. by default shows earliest event in a scope
			'town' => false,
			'state' => false,
			'country' => false,
			'region' => false,
			'status' => 1, //approved locations only
			'scope' => 'all', //we probably want to search all locations by default, not like events
			'blog' => get_current_blog_id(),
			'private' => current_user_can('read_private_locations'),
			'private_only' => false,
			'post_id' => false,
			//location-specific attributes
			'eventful' => false, //Locations that have an event (scope will also play a part here
			'eventless' => false, //Locations WITHOUT events, eventful takes precedence
			'event_status' => false //search locations with events of a specific publish status
		);
		//sort out whether defaults were supplied or just the array of search values
		if( empty($array) ){
			$array = $array_or_defaults;
		}else{
			$defaults = array_merge($defaults, $array_or_defaults);
		}
		//specific functionality
		if( EM_MS_GLOBAL ){
			if( get_site_option('dbem_ms_mainblog_locations') ){
			    //when searching in MS Global mode with all locations being stored on the main blog, blog_id becomes redundant as locations are stored in one blog table set
			    $array['blog'] = false;
			}elseif( (!is_admin() || defined('DOING_AJAX')) && empty($array['blog']) && is_main_site() && get_site_option('dbem_ms_global_locations') ){
				//if enabled, by default we display all blog locations on main site
			    $array['blog'] = false;
			}
		}
		$array['eventful'] = ( !empty($array['eventful']) && $array['eventful'] == true );
		$array['eventless'] = ( !empty($array['eventless']) && $array['eventless'] == true );
		if( is_admin() && !defined('DOING_AJAX') ){
			$defaults['owner'] = !current_user_can('read_others_locations') ? get_current_user_id():false;
		}
		return apply_filters('em_locations_get_default_search', parent::get_default_search($defaults, $array), $array, $defaults);
	}
}
?>