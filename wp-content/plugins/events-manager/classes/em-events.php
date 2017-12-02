<?php
//TODO EM_Events is currently static, better we make this non-static so we can loop sets of events, and standardize with other objects.
/**
 * Use this class to query and manipulate sets of events. If dealing with more than one event, you probably want to use this class in some way.
 *
 */
class EM_Events extends EM_Object {
	
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
	 * Returns an array of EM_Events that match the given specs in the argument, or returns a list of future evetnts in future 
	 * (see EM_Events::get_default_search() ) for explanation of possible search array values. You can also supply a numeric array
	 * containing the ids of the events you'd like to obtain 
	 * 
	 * @param array $args
	 * @return EM_Event array()
	 */
	public static function get( $args = array(), $count=false ) {
		global $wpdb;	 
		$events_table = EM_EVENTS_TABLE;
		$locations_table = EM_LOCATIONS_TABLE;
		
		//Quick version, we can accept an array of IDs, which is easy to retrieve
		if( self::array_is_numeric($args) ){ //Array of numbers, assume they are event IDs to retreive
			//We can just get all the events here and return them
			$events = array();
			foreach($args as $event_id){
				$events[$event_id] = em_get_event($event_id);
			}
			return apply_filters('em_events_get', $events, $args);
		}
		
		//We assume it's either an empty array or array of search arguments to merge with defaults			
		$args = self::get_default_search($args);
		$limit = ( $args['limit'] && is_numeric($args['limit'])) ? "LIMIT {$args['limit']}" : '';
		$offset = ( $limit != "" && is_numeric($args['offset']) ) ? "OFFSET {$args['offset']}" : '';
		
		//Get fields that we can use in ordering and grouping, which can be event and location (excluding ambiguous) fields
		$EM_Event = new EM_Event();
		$EM_Location = new EM_Location();
		$event_fields = array_keys($EM_Event->fields);
		$location_fields = array(); //will contain location-specific fields, not ambiguous ones
		foreach( array_keys($EM_Location->fields) as $field_name ){
			if( !in_array($field_name, $event_fields) ) $location_fields[] = $field_name;
		}
		if( get_option('dbem_locations_enabled') ){
			$accepted_fields = array_merge($event_fields, $location_fields);
		}else{
			//if locations disabled then we don't accept location-specific fields
			$accepted_fields = $event_fields;
		}
		
		//Start SQL statement
		
		//Create the SQL statement selectors
		$calc_found_rows = $limit && ( $args['pagination'] || $args['offset'] > 0 || $args['page'] > 0 );
		if( $count ){
			$selectors = 'COUNT(DISTINCT '.$events_table . '.event_id)';
			$limit = 'LIMIT 1';
			$offset = 'OFFSET 0';
		}else{
			if( $args['array'] ){
				//get all fields from table, add events table prefix to avoid ambiguous fields from location
				$selectors = $events_table . '.*';
			}elseif( EM_MS_GLOBAL ){
				$selectors = $events_table.'.post_id, '.$events_table.'.blog_id';
			}else{
				$selectors = $events_table.'.post_id';
			}
			if( $calc_found_rows ) $selectors = 'SQL_CALC_FOUND_ROWS ' . $selectors; //for storing total rows found
			$selectors = 'DISTINCT ' . $selectors; //duplicate avoidance
		}
		
		//check if we need to join a location table for this search, which is necessary if any location-specific are supplied, or if certain arguments such as orderby contain location fields
		if( !empty($args['groupby']) || (defined('EM_DISABLE_OPTIONAL_JOINS') && EM_DISABLE_OPTIONAL_JOINS) ){
			$location_specific_args = array('town', 'state', 'country', 'region', 'near', 'geo', 'search');
			$join_locations = false;
			foreach( $location_specific_args as $arg ) if( !empty($args[$arg]) ) $join_locations = true;
			//if set to false the following would provide a false negative in the line above
			if( $args['location_status'] !== false ){ $join_locations = true; }
			//check ordering and grouping arguments for precense of location fields requiring a join
			if( !$join_locations ){
				foreach( array('groupby', 'orderby', 'groupby_orderby') as $arg ){
					if( !is_array($args[$arg]) ) continue; //ignore this argument if set to false
					//we assume all these arguments are now array thanks to self::get_search_defaults() cleaning it up
					foreach( $args[$arg] as $field_name ){
						if( in_array($field_name, $location_fields) ){
							$join_locations = true;
							break; //we join, no need to keep searching
						}
					}
				}
			}
		}else{ $join_locations = true; }//end temporary if( !empty($args['groupby']).... wrapper
		//plugins can override this optional joining behaviour here in case they add custom WHERE conditions or something like that
		$join_locations = apply_filters('em_events_get_join_locations_table', $join_locations, $args, $count);
		//depending on whether to join we do certain things like add a join SQL, change specific values like status search
		$location_optional_join = $join_locations ? "LEFT JOIN $locations_table ON {$locations_table}.location_id={$events_table}.location_id" : '';
		
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
				$inner_selectors = $events_table . '.*';
				if( $location_optional_join ){
					//we're selecting all fields from events table so add only location fields required in the outer ORDER BY statement
					foreach( $args['orderby'] as $orderby_field ){
						if( in_array($orderby_field, $location_fields) ){
							$inner_selectors .= ', '. $locations_table .'.'. $orderby_field;
						}
					}
				}
				
				//THE Query - Grouped
				if( in_array($groupby_field, $location_fields) || count(array_intersect($location_fields, $args['groupby_orderby'])) || defined('EM_FORCE_GROUPED_DATASET_SQL') ){
					//if we're grouping by any fields in the locations table, we run a different (slightly slower) query to provide reliable results
					if( in_array($groupby_field, $location_fields) && !in_array($groupby_field, $args['orderby']) ){
						//we may not have included the grouped field if it's not in the outer ORDER BY clause, so we add it for this specific query
						$inner_selectors .= ', '. $locations_table .'.'. $groupby_field;
					}
					$sql = "
SELECT $selectors
FROM (
	SELECT *,
		@cur := IF($groupby_field = @id, @cur+1, 1) AS RowNumber,
		@id := $groupby_field AS IdCache
	FROM (
		SELECT {$inner_selectors} FROM {$events_table}
		$location_optional_join
		$where
		ORDER BY $groupby_field $groupby_orderby_sql
	) {$events_table}
	INNER JOIN (
		SELECT @id:=0, @cur:=0
	) AS lookup
) {$events_table}
WHERE RowNumber = 1
$orderby_sql
$limit $offset";
				}else{
					//we'll keep this query simply because it's a little faster and still seems reliable when not grouping or group-sorting any fields in the locations table
					$sql = "
SELECT $selectors
FROM (
	SELECT {$inner_selectors},
		@cur := IF($groupby_field = @id, @cur+1, 1) AS RowNumber,
		@id := $groupby_field AS IdCache
	FROM {$events_table}
	INNER JOIN (
		SELECT @id:=0, @cur:=0
	) AS lookup
	$location_optional_join
	$where
	ORDER BY {$groupby_field} $groupby_orderby_sql
) {$events_table}
WHERE RowNumber = 1
$orderby_sql
$limit $offset";
				}
			}
		}
		
		//Build THE Query SQL statement if not already built for a grouped query
		if( empty($sql) ){
			//THE Query
			$sql = "
SELECT $selectors FROM $events_table
$location_optional_join
$where
$orderby_sql
$limit $offset";
		}
	
		//THE Query filter
		$sql = apply_filters('em_events_get_sql', $sql, $args);
		//if( em_wp_is_super_admin() && WP_DEBUG_DISPLAY ){ echo "<pre>"; print_r($sql); echo '</pre>'; }
				
		//If we're only counting results, return the number of results and go no further
		if( $count ){
			self::$num_rows_found = self::$num_rows = $wpdb->get_var($sql);
			return apply_filters('em_events_get_count', self::$num_rows, $args);		
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
			return apply_filters('em_events_get_array',$results, $args);
		}
		
		//Make returned results EM_Event objects
		$results = (is_array($results)) ? $results:array();
		$events = array();
		
		if( EM_MS_GLOBAL ){
			foreach ( $results as $event ){
				$events[] = em_get_event($event['post_id'], $event['blog_id']);
			}
		}else{
			foreach ( $results as $event ){
				$events[] = em_get_event($event['post_id'], 'post_id');
			}
		}
		
		return apply_filters('em_events_get', $events, $args);
	}
	
	/**
	 * Returns the number of events on a given date
	 * @param $date
	 * @return int
	 */
	public static function count_date($date){
		global $wpdb;
		$table_name = EM_EVENTS_TABLE;
		$sql = "SELECT COUNT(*) FROM  $table_name WHERE (event_start_date  like '$date') OR (event_start_date <= '$date' AND event_end_date >= '$date');";
		return apply_filters('em_events_count_date', $wpdb->get_var($sql));
	}
	
	public static function count( $args = array() ){
		return apply_filters('em_events_count', self::get($args, true), $args);
	}
	
	/**
	 * Will delete given an array of event_ids or EM_Event objects
	 * @param unknown_type $id_array
	 */
	public static function delete( $array ){
		global $wpdb;
		//Detect array type and generate SQL for event IDs
		$results = array();
		if( !empty($array) && @get_class(current($array)) != 'EM_Event' ){
			$events = self::get($array);
		}else{
			$events = $array;
		}
		$event_ids = array();
		foreach ($events as $EM_Event){
		    $event_ids[] = $EM_Event->event_id;
			$results[] = $EM_Event->delete();
		}
		//TODO add better error feedback on events delete fails
		return apply_filters('em_events_delete',  in_array(false, $results), $event_ids);
	}
	
	
	/**
	 * Output a set of matched of events. You can pass on an array of EM_Events as well, in this event you can pass args in second param.
	 * Note that you can pass a 'pagination' boolean attribute to enable pagination, default is enabled (true). 
	 * @param array $args
	 * @param array $secondary_args
	 * @return string
	 */
	public static function output( $args ){
		global $EM_Event;
		$EM_Event_old = $EM_Event; //When looping, we can replace EM_Event global with the current event in the loop
		//get page number if passed on by request (still needs pagination enabled to have effect)
		$page_queryvar = !empty($args['page_queryvar']) ? $args['page_queryvar'] : 'pno';
		if( !empty($args['pagination']) && !array_key_exists('page',$args) && !empty($_REQUEST[$page_queryvar]) && is_numeric($_REQUEST[$page_queryvar]) ){
			$args['page'] = $_REQUEST[$page_queryvar];
		}
		//Can be either an array for the get search or an array of EM_Event objects
		if( is_object(current($args)) && get_class((current($args))) == 'EM_Event' ){
			$func_args = func_get_args();
			$events = $func_args[0];
			$args = (!empty($func_args[1]) && is_array($func_args[1])) ? $func_args[1] : array();
			$args = apply_filters('em_events_output_args', self::get_default_search($args), $events);
			$limit = ( !empty($args['limit']) && is_numeric($args['limit']) ) ? $args['limit']:false;
			$events_count = count($events);
		}else{
			//Firstly, let's check for a limit/offset here, because if there is we need to remove it and manually do this
			$args = apply_filters('em_events_output_args', self::get_default_search($args));
			$limit = ( !empty($args['limit']) && is_numeric($args['limit']) ) ? $args['limit']:false;
			$events = self::get( $args );
			$events_count = self::$num_rows_found;
		}
		//What format shall we output this to, or use default
		$format = ( empty($args['format']) ) ? get_option( 'dbem_event_list_item_format' ) : $args['format'] ;
		
		$output = "";
		
		if ( $events_count > 0 ) {
			$events = apply_filters('em_events_output_events', $events);
			foreach ( $events as $EM_Event ) {
				$output .= $EM_Event->output($format);
			} 
			//Add headers and footers to output
			if( $format == get_option( 'dbem_event_list_item_format' ) ){
			    //we're using the default format, so if a custom format header or footer is supplied, we can override it, if not use the default
			    $format_header = empty($args['format_header']) ? get_option('dbem_event_list_item_format_header') : $args['format_header'];
			    $format_footer = empty($args['format_footer']) ? get_option('dbem_event_list_item_format_footer') : $args['format_footer'];
			}else{
			    //we're using a custom format, so if a header or footer isn't specifically supplied we assume it's blank
			    $format_header = !empty($args['format_header']) ? $args['format_header'] : '' ;
			    $format_footer = !empty($args['format_footer']) ? $args['format_footer'] : '' ;
			}
			$output = $format_header .  $output . $format_footer;
			//Pagination (if needed/requested)
			if( !empty($args['pagination']) && !empty($limit) && $events_count > $limit ){
				$output .= self::get_pagination_links($args, $events_count);
			}
		}elseif( $args['no_results_msg'] !== false ){
			$output = !empty($args['no_results_msg']) ? $args['no_results_msg'] : get_option('dbem_no_events_message');
		}
		
		//TODO check if reference is ok when restoring object, due to changes in php5 v 4
		$EM_Event = $EM_Event_old;
		$output = apply_filters('em_events_output', $output, $events, $args);
		return $output;		
	}
	
	/**
	 * Generate a grouped list of events by year, month, week or day.
	 * 
	 * There is a nuance with this function, long_events won't work unless you add a limit of 0. The reason is because this won't work with pagination, due to the fact
	 * that you need to alter the event total count to reflect each time an event is displayed in a time range. e.g. if an event lasts 2 days and it's daily grouping,
	 * then that event would count as 2 events for pagination purposes. For that you need to count every single event and calculate date range etc. which is too resource
	 * heavy and not scalabale, therefore we've added this limitation.
	 * 
	 * @since 5.4.4.2
	 * @param array $args
	 * @return string
	 */
	public static function output_grouped( $args = array() ){
		//Reset some args to include pagination for if pagination is requested.
		$args['limit'] = isset($args['limit']) ? $args['limit'] : get_option('dbem_events_default_limit');
		$args['page'] = (!empty($args['page']) && is_numeric($args['page']) )? $args['page'] : 1;
		$args['page'] = (!empty($args['pagination']) && !empty($_REQUEST['pno']) && is_numeric($_REQUEST['pno']) )? $_REQUEST['pno'] : $args['page'];
		$args['offset'] = ($args['page']-1) * $args['limit'];
		$args['orderby'] = 'event_start_date,event_start_time,event_name'; // must override this to display events in right cronology.

		$args['mode'] = !empty($args['mode']) ? $args['mode'] : get_option('dbem_event_list_groupby');
		$args['header_format'] = !empty($args['header_format']) ? $args['header_format'] :  get_option('dbem_event_list_groupby_header_format', '<h2>#s</h2>');
		$args['date_format'] = !empty($args['date_format']) ? $args['date_format'] :  get_option('dbem_event_list_groupby_format','');
		$args = apply_filters('em_events_output_grouped_args', self::get_default_search($args));
		//Reset some vars for counting events and displaying set arrays of events
		$atts = (array) $args;
		$atts['pagination'] = $atts['limit'] = $atts['page'] = $atts['offset'] = false;
		//decide what form of dates to show
		$EM_Events = self::get( $args );
		$events_count = self::$num_rows_found;
		ob_start();
		if( $events_count > 0 ){
			switch ( $args['mode'] ){
				case 'yearly':
					//go through the events and put them into a monthly array
					$format = (!empty($args['date_format'])) ? $args['date_format']:'Y';
					$events_dates = array();
					foreach($EM_Events as $EM_Event){ /* @var $EM_Event EM_Event */
						$year = date('Y',$EM_Event->start);
						$events_dates[$year][] = $EM_Event;
						//if long events requested, add event to other dates too
						if( empty($args['limit']) && !empty($args['long_events']) && $EM_Event->event_end_date != $EM_Event->event_start_date ) {
							$next_year = $year + 1;
							$year_end = date('Y', $EM_Event->end);
							while( $next_year <= $year_end ){
								$events_dates[$next_year][] = $EM_Event;
								$next_year = $next_year + 1;
							}
						}
					}
					foreach ($events_dates as $year => $events){
						echo str_replace('#s', date_i18n($format,strtotime($year.'-01-01', current_time('timestamp'))), $args['header_format']);
						echo self::output($events, $atts);
					}
					break;
				case 'monthly':
					//go through the events and put them into a monthly array
					$format = (!empty($args['date_format'])) ? $args['date_format']:'M Y';
					$events_dates = array();
					foreach($EM_Events as $EM_Event){
						$events_dates[date('Y-m-'.'01', $EM_Event->start)][] = $EM_Event;
						//if long events requested, add event to other dates too
						if( empty($args['limit']) && !empty($args['long_events']) && $EM_Event->event_end_date != $EM_Event->event_start_date ) {
							$next_month = strtotime("+1 Month", $EM_Event->start);
							while( $next_month <= $EM_Event->end ){
								$events_dates[date('Y-m-'.'01',$next_month)][] = $EM_Event;
								$next_month = strtotime("+1 Month", $next_month);
							}
						}
					}
					foreach ($events_dates as $month => $events){
						echo str_replace('#s', date_i18n($format, strtotime($month, current_time('timestamp'))), $args['header_format']);
						echo self::output($events, $atts);
					}
					break;
				case 'weekly':
					$format = (!empty($args['date_format'])) ? $args['date_format']:get_option('date_format');
					$events_dates = array();
					foreach($EM_Events as $EM_Event){
			   			$start_of_week = get_option('start_of_week');
						$day_of_week = date('w',$EM_Event->start);
						$day_of_week = date('w',$EM_Event->start);
						$offset = $day_of_week - $start_of_week;
						if($offset<0){ $offset += 7; }
						$offset = $offset * 60*60*24; //days in seconds
						$start_day = strtotime($EM_Event->start_date);
						$events_dates[$start_day - $offset][] = $EM_Event;
						//if long events requested, add event to other dates too
						if( empty($args['limit']) && !empty($args['long_events']) && $EM_Event->event_end_date != $EM_Event->event_start_date ) {
							$next_week = $start_day - $offset + (86400 * 7);
							while( $next_week <= $EM_Event->end ){
								$events_dates[$next_week][] = $EM_Event;
								$next_week = $next_week + (86400 * 7);
							}
						}
					}
					foreach ($events_dates as $event_day_ts => $events){
						echo str_replace('#s', date_i18n($format,$event_day_ts). get_option('dbem_dates_separator') .date_i18n($format,$event_day_ts+(60*60*24*6)), $args['header_format']);
						echo self::output($events, $atts);
					}
					break;
				default: //daily
					//go through the events and put them into a daily array
					$format = (!empty($args['date_format'])) ? $args['date_format']:get_option('date_format');
					$events_dates = array();
					foreach($EM_Events as $EM_Event){
						$event_start_date = strtotime($EM_Event->start_date);
						$events_dates[$event_start_date][] = $EM_Event;
						//if long events requested, add event to other dates too
						if( empty($args['limit']) && !empty($args['long_events']) && $EM_Event->event_end_date != $EM_Event->event_start_date ) {
							$tomorrow = $event_start_date + 86400;
							while( $tomorrow <= $EM_Event->end ){
								$events_dates[$tomorrow][] = $EM_Event;
								$tomorrow = $tomorrow + 86400;
							}
						}
					}
					foreach ($events_dates as $event_day_ts => $events){
						echo str_replace('#s', date_i18n($format,$event_day_ts), $args['header_format']);
						echo self::output($events, $atts);
					}
					break;
			}
			//Show the pagination links (unless there's less than $limit events)
			if( !empty($args['pagination']) && !empty($args['limit']) && $events_count > $args['limit'] ){
				echo self::get_pagination_links($args, $events_count, 'search_events_grouped');
			}
		}elseif( $args['no_results_msg'] !== false ){
			echo !empty($args['no_results_msg']) ? $args['no_results_msg'] : get_option('dbem_no_events_message');
		}
		return ob_get_clean();
	}
	
	public static function get_pagination_links($args, $count, $search_action = 'search_events', $default_args = array()){
		//get default args if we're in a search, supply to parent since we can't depend on late static binding until WP requires PHP 5.3 or later
		if( empty($default_args) && (!empty($args['ajax']) || !empty($_REQUEST['action']) && $_REQUEST['action'] == $search_action) ){
			$default_args = self::get_default_search();
			$default_args['limit'] = get_option('dbem_events_default_limit');
		}
		return parent::get_pagination_links($args, $count, $search_action, $default_args);
	}
	
	/* (non-PHPdoc)
	 * DEPRECATED - this class should just contain static classes,
	 * @see EM_Object::can_manage()
	 */
	function can_manage($event_ids = false , $admin_capability = false, $user_to_check = false ){
		global $wpdb;
		if( current_user_can('edit_others_events') ){
			return apply_filters('em_events_can_manage', true, $event_ids);
		}
		if( EM_Object::array_is_numeric($event_ids) ){
			$condition = implode(" OR event_id=", $event_ids);
			//we try to find any of these events that don't belong to this user
			$results = $wpdb->get_var("SELECT COUNT(*) FROM ". EM_EVENTS_TABLE ." WHERE event_owner != '". get_current_user_id() ."' event_id=$condition;");
			return apply_filters('em_events_can_manage', ($results == 0), $event_ids);
		}
		return apply_filters('em_events_can_manage', false, $event_ids);
	}
	
	public static function get_post_search($args = array(), $filter = false, $request = array(), $accepted_args = array()){
		//supply $accepted_args to parent argument since we can't depend on late static binding until WP requires PHP 5.3 or later
		$accepted_args = !empty($accepted_args) ? $accepted_args : array_keys(self::get_default_search());
		return apply_filters('em_events_get_post_search', parent::get_post_search($args, $filter, $request, $accepted_args));
	}

	/* Overrides EM_Object method to apply a filter to result
	 * @see wp-content/plugins/events-manager/classes/EM_Object#build_sql_conditions()
	 */
	public static function build_sql_conditions( $args = array() ){
	    self::$context = EM_POST_TYPE_EVENT;
		global $wpdb;
		//continue with conditions
		$conditions = parent::build_sql_conditions($args);
		//specific location query conditions if locations are enabled
		if( get_option('dbem_locations_enabled') ){
			//events with or without locations
			if( !empty($args['has_location']) ){
				$conditions['has_location'] = '('.EM_EVENTS_TABLE.'.location_id IS NOT NULL AND '.EM_EVENTS_TABLE.'.location_id != 0)';
			}elseif( !empty($args['no_location']) ){
				$conditions['no_location'] = '('.EM_EVENTS_TABLE.'.location_id IS NULL OR '.EM_EVENTS_TABLE.'.location_id = 0)';			
			}elseif( !empty($conditions['location_status']) ){
				$location_specific_args = array('town', 'state', 'country', 'region', 'near', 'geo', 'search');
				foreach( $location_specific_args as $location_arg ){
					if( !empty($args[$location_arg]) ) $skip_location_null_condition = true;
				}
				if( empty($skip_location_null_condition) ){
					$conditions['location_status'] = '('.$conditions['location_status'].' OR '.EM_LOCATIONS_TABLE.'.location_id IS NULL)';
				}
			}
		}
		//search conditions
		if( !empty($args['search']) ){
			if( get_option('dbem_locations_enabled') ){
				$like_search = array('event_name',EM_EVENTS_TABLE.'.post_content','location_name','location_address','location_town','location_postcode','location_state','location_country','location_region');
			}else{
				$like_search = array('event_name',EM_EVENTS_TABLE.'.post_content');
			}
			$like_search_string = '%'.$wpdb->esc_like($args['search']).'%';
			$like_search_strings = array();
			foreach( $like_search as $v ) $like_search_strings[] = $like_search_string;
			$like_search_sql = "(".implode(" LIKE %s OR ", $like_search). "  LIKE %s)";
			$conditions['search'] = $wpdb->prepare($like_search_sql, $like_search_strings);
		}
		//private events
		if( empty($args['private']) ){
			$conditions['private'] = "(`event_private`=0)";			
		}elseif( !empty($args['private_only']) ){
			$conditions['private_only'] = "(`event_private`=1)";
		}
		if( EM_MS_GLOBAL && !empty($args['blog']) ){
		    if( is_numeric($args['blog']) ){
				if( is_main_site($args['blog']) ){
					$conditions['blog'] = "(".EM_EVENTS_TABLE.".blog_id={$args['blog']} OR ".EM_EVENTS_TABLE.".blog_id IS NULL)";
				}else{
					$conditions['blog'] = "(".EM_EVENTS_TABLE.".blog_id={$args['blog']})";
				}
		    }else{
		        if( !is_array($args['blog']) && preg_match('/^([\-0-9],?)+$/', $args['blog']) ){
		            $conditions['blog'] = "(".EM_EVENTS_TABLE.".blog_id IN ({$args['blog']}) )";
			    }elseif( is_array($args['blog']) && self::array_is_numeric($args['blog']) ){
			        $conditions['blog'] = "(".EM_EVENTS_TABLE.".blog_id IN (".implode(',',$args['blog']).") )";
			    }
		    }
		}
		//post search
		if( !empty($args['post_id'])){
			if( is_array($args['post_id']) ){
				$conditions['post_id'] = "(".EM_EVENTS_TABLE.".post_id IN (".implode(',',$args['post_id'])."))";
			}else{
				$conditions['post_id'] = "(".EM_EVENTS_TABLE.".post_id={$args['post_id']})";
			}
		}
		return apply_filters( 'em_events_build_sql_conditions', $conditions, $args );
	}
	
	/**
	 * Overrides EM_Object method to clean ambiguous fields and apply a filter to result.
	 * @see EM_Object::build_sql_orderby()
	 */
	public static function build_sql_orderby( $args, $accepted_fields, $default_order = 'ASC' ){
	    $accepted_fields[] = 'event_date_modified';
	    $accepted_fields[] = 'event_date_created';
	    $orderby = parent::build_sql_orderby($args, $accepted_fields, get_option('dbem_events_default_order'));
		$orderby = self::build_sql_ambiguous_fields_helper($orderby); //fix ambiguous fields
		return apply_filters( 'em_events_build_sql_orderby', $orderby, $args, $accepted_fields, $default_order );
	}
	
	/**
	 * Overrides EM_Object method to clean ambiguous fields and apply a filter to result.
	 * @see EM_Object::build_sql_groupby()
	 */
	public static function build_sql_groupby( $args, $accepted_fields, $groupby_order = false, $default_order = 'ASC' ){
	    $accepted_fields[] = 'event_date_modified';
	    $accepted_fields[] = 'event_date_created';
		$groupby = parent::build_sql_groupby($args, $accepted_fields);
		//fix ambiguous fields and give them scope of events table
		$groupby = self::build_sql_ambiguous_fields_helper($groupby);
		return apply_filters( 'em_events_build_sql_groupby', $groupby, $args, $accepted_fields );
	}
	
	/**
	 * Overrides EM_Object method to clean ambiguous fields and apply a filter to result.
	 * @see EM_Object::build_sql_groupby_orderby()
	 */
	public static function build_sql_groupby_orderby($args, $accepted_fields, $default_order = 'ASC' ){
	    $accepted_fields[] = 'event_date_modified';
	    $accepted_fields[] = 'event_date_created';
	    $group_orderby = parent::build_sql_groupby_orderby($args, $accepted_fields, get_option('dbem_events_default_order'));
		//fix ambiguous fields and give them scope of events table
		$group_orderby = self::build_sql_ambiguous_fields_helper($group_orderby);
		return apply_filters( 'em_events_build_sql_groupby_orderby', $group_orderby, $args, $accepted_fields, $default_order );
	}
	
	/**
	 * Overrides EM_Object method to provide specific reserved fields and events table.
	 * @see EM_Object::build_sql_ambiguous_fields_helper()
	 */
	protected static function build_sql_ambiguous_fields_helper( $fields, $reserved_fields = array(), $prefix = 'table_name' ){
		//This will likely be removed when PHP 5.3 is the minimum and LSB is a given
		return parent::build_sql_ambiguous_fields_helper($fields, array('post_id', 'location_id', 'blog_id'), EM_EVENTS_TABLE);
	}
	
	/* 
	 * Adds custom Events search defaults
	 * @param array $array_or_defaults may be the array to override defaults
	 * @param array $array
	 * @return array
	 * @uses EM_Object#get_default_search()
	 */
	public static function get_default_search( $array_or_defaults = array(), $array = array() ){
	    self::$context = EM_POST_TYPE_EVENT;
		$defaults = array(
			'recurring' => false, //we don't initially look for recurring events only events and recurrences of recurring events
			'orderby' => get_option('dbem_events_default_orderby'),
			'order' => get_option('dbem_events_default_order'),
			'groupby' => false,
			'groupby_orderby' => 'event_start_date, event_start_time', //groups according to event start time, i.e. by default shows earliest event in a scope
			'groupby_order' => 'ASC', //groups according to event start time, i.e. by default shows earliest event in a scope
			'status' => 1, //approved events only
			'town' => false,
			'state' => false,
			'country' => false,
			'region' => false,
			'blog' => get_current_blog_id(),
			'private' => current_user_can('read_private_events'),
			'private_only' => false,
			'post_id' => false,
			//ouput_grouped specific arguments
			'mode' => false,
			'header_format' => false,
			'date_format' => false,
			//event-specific search attributes
			'has_location' => false, //search events with a location
			'no_location' => false, //search events without a location
			'location_status' => false //search events with locations of a specific publish status
		);
		//sort out whether defaults were supplied or just the array of search values
		if( empty($array) ){
			$array = $array_or_defaults;
		}else{
			$defaults = array_merge($defaults, $array_or_defaults);
		}
		//specific functionality
		if( EM_MS_GLOBAL && (!is_admin() || defined('DOING_AJAX')) ){
			if( empty($array['blog']) && is_main_site() && get_site_option('dbem_ms_global_events') ){
			    $array['blog'] = false;
			}
		}
		//admin-area specific modifiers
		if( is_admin() && !defined('DOING_AJAX') ){
			//figure out default owning permissions
			$defaults['owner'] = !current_user_can('edit_others_events') ? get_current_user_id() : false;
			if( !array_key_exists('status', $array) && current_user_can('edit_others_events') ){
				$defaults['status'] = false; //by default, admins see pending and live events
			}
		}
		//check if we're doing any location-specific searching, if so then we (by default) want to match the status of events
		if( !empty($array['has_location']) ){
			//we're looking for events with locations, so we match the status we're searching for events unless there's an argument passed on for something different
			$defaults['location_status'] = true;
		}elseif( !empty($array['no_location']) ){
			//if no location is being searched for, we should ignore any status searches for location
			$defaults['location_status'] = $array['location_status'] = false;
		}else{
			$location_specific_args = array('town', 'state', 'country', 'region', 'near', 'geo');
			foreach( $location_specific_args as $location_arg ){
				if( !empty($array[$location_arg]) ) $defaults['location_status'] = true;
			}
		}
		$args = parent::get_default_search($defaults,$array);
		//do some post-parnet cleaning up here if locations are enabled or disabled
		if( !get_option('dbem_locations_enabled') ){
			//locations disabled, wipe any args to do with locations so they're ignored
			$location_args = array('town', 'state', 'country', 'region', 'has_location', 'no_location', 'location_status', 'location', 'geo', 'near', 'location_id');
			foreach( $location_args as $arg ) $args[$arg] = false;
		}
		return apply_filters('em_events_get_default_search', $args, $array, $defaults);
	}
}
?>