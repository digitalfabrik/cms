<?php
/**
 * Abstract layer allowing for certain aspects of Events Manager to be translateable. Useful for translation plugins to hook into.
 */
class EM_ML{
    /**
     * @var boolean Flag confirming whether this class has been initialized yet.
     */
    static public $init;
	/**
	 * @var array Array of available languages, where keys are the locales and the values are the displayable names of the language e.g. array('fr_FR' => 'French');
	 */
	static public $langs = array();
	/**
	 * @var string The main/default language of this blog, meaning the language used should no multilingual plugin be installed. Example: 'en_US' for American English.
	 */
	static public $wplang;
	/**
	 * The currently active language of this site, meaning the language being manipulated by WordPress. This may be different to the global $locale value, which
	 * especially in the WP Dashboard could be a different display language to the actual language being worked on, e.g. translating a event into Spanish with an English UI.
	 * @var string Example: 'en_US' for American English.
	 */
	static public $current_language;
	/**
	 * If switching languages temporarly, original current language stored here, null if we haven't switched.
	 * @var string
	 */
	static public $current_language_restore;
	/**
	 * @var boolean Flag for whether EM is multilingual ready, false by default, set after init() has been executed first time.
	 */
	static public $is_ml = false;
	/**
	 * Temporary cache of original translations, to avoid repeated hits on the DB
	 * Array is organized by blog_id, then post_id => original_post_id
	 * @var array
	 */
	static public $originals_cache = array();

	public static function init(){
	    if( !empty(self::$init) ) return;
		
		//Determine the available languages and the currently displayed locale for this site. 3rd party plugins need to override these filters.
		self::$langs = apply_filters('em_ml_langs', array());
		self::$wplang = apply_filters('em_ml_wplang', get_locale());
		self::$current_language = !empty($_REQUEST['em_lang']) && array_key_exists($_REQUEST['em_lang'], self::$langs) ? $_REQUEST['em_lang'] : get_locale();
		self::$current_language = apply_filters('em_ml_current_language', self::$current_language);
		
		//proceed with loading the plugin, we don't need to deal with the rest of this if no languages were defined by an extending class
		if( count(self::$langs) > 0 ) {
		    //set flag to prevent unecessary counts
		    self::$is_ml = true;
		    do_action('em_ml_pre_init'); //only initialize when this is a MultiLingual instance 
    		//make sure options are being translated immediately if needed
    		include(EM_DIR.'/multilingual/em-ml-options.php');
    		//load all the extra ML helper classes
			include(EM_DIR.'/multilingual/em-ml-io.php');
			include(EM_DIR.'/multilingual/em-ml-placeholders.php');
			include(EM_DIR.'/multilingual/em-ml-search.php');
			if( is_admin() ){
				include(EM_DIR.'/multilingual/em-ml-admin.php');
			}
			if( get_option('dbem_rsvp_enabled') ){
				include(EM_DIR.'/multilingual/em-ml-bookings.php');
			}
			if( get_option('dbem_locations_enabled') ){
				include(EM_DIR.'/multilingual/em-ml-io-locations.php');
			}
			//change some localized script vars
    		add_filter('em_wp_localize_script', 'EM_ML::em_wp_localize_script');
		}
		self::$init = true;
		if( self::$is_ml ) do_action('em_ml_init'); //only initialize when this is a MultiLingual instance
		add_action('switch_locale', 'EM_ML::wp_switch_locale', 10, 1);
		add_action('restore_previous_locale', 'EM_ML::wp_switch_locale', 10, 1);
	}
	
	/**
     * Localizes the script variables
     * @param array $em_localized_js
     * @return array
     */
    public static function em_wp_localize_script($em_localized_js){
        $em_localized_js['ajaxurl'] = admin_url('admin-ajax.php?em_lang='.self::$current_language);
        $em_localized_js['locationajaxurl'] = admin_url('admin-ajax.php?action=locations_search&em_lang='.self::$current_language);
		if( get_option('dbem_rsvp_enabled') ){
		    $em_localized_js['bookingajaxurl'] = admin_url('admin-ajax.php?em_lang='.self::$current_language);
		}
	    if( !empty($em_localized_js['event_detach_warning']) ){
	    	$em_localized_js['event_detach_warning'] .= "\n\n". __('All translations of this event will be detached from the recurring event.', 'events-manager');
	    }
        return $em_localized_js;
    }
	
	/**
	 * Gets the available languages this site can display.
	 * @return array EM_ML::$langs;
	 */
	public static function get_langs(){
		return self::$langs;
	}
	
	/**
	 * Handles setting and restoring of WP Locale
	 * @param string $locale
	 */
	public static function wp_switch_locale( $locale ){
		self::$current_language = $locale;
	}
	
	public static function switch_locale( $locale ){
		self::$current_language_restore = self::$current_language;
		switch_to_locale($locale);
	}
	
	public static function restore_locale(){
		if( !self::$current_language_restore ) return;
		restore_current_locale();
		self::$current_language = self::$current_language_restore; // this may not mean the same as the locale currently restored which is the 'display' language, e.g. in admins you could be in french mode whilst viewing an english dashboard
		self::$current_language_restore = null;
		// restoring a locale unloads text domains which prevents them from reloading, EM textdomains and any correctly prefixed add-ons should be reloaded
		global $l10n_unloaded;
		if( !empty($l10n_unloaded['events-manager']) ){
			foreach( $l10n_unloaded as $k => $v ){
				if( preg_match('/^events\-manager/', $k) ){
					unset( $l10n_unloaded[$k] );
				}
			}
		}
	}

    /**
	 * Gets translated option. Shortcut for EM_ML_Options::get_option() and should be used instead outside of the EM_ML namespace.
	 * @uses EM_ML_Options::get_option()
	 * @param string $option
	 * @param string $lang
	 * @param boolean $return_original
	 * @return mixed
	 */
	public static function get_option($option, $lang = null, $return_original = true){
	    if( !self::$is_ml ) return get_option($option);
		return EM_ML_Options::get_option($option, $lang, $return_original);
	}

	/**
	 * Returns whether or not this option name is translatable.
	 * @uses EM_ML_Options::is_option_translatable()
	 * @param string $option Option Name
	 * @return boolean
	 */
	public static function is_option_translatable($option){
	    if( !self::$is_ml ) return false;
		return EM_ML_Options::is_option_translatable($option);
	}
	
	/* From here on you see options that can/should be filtered to provide the relevant translation conversions. The above functions handle the rest... */
    
    /**
     * Takes a post ID for a CPT of $post_type, returns the post ID of a translation of the language currently being viewed.
     * If post ID is already in the same language being viewed, it is returned. If no translation is available, returns false.
     * @param int $post_id          The post ID you want to find the translation for currently viewed language.
     * @param string $post_type     Optional. The post type of the post ID supplied. Avoids extra DB calls if provided.
     * @param string $blog_id       Optional. The blog id this post ID will belong to.
     * @return int|bool
     */
    public static function get_translated_post_id($post_id, $post_type = null, $blog_id = null){
    	global $wpdb;
    	// multisite support
	    if( EM_MS_GLOBAL && $blog_id ) switch_to_blog( $blog_id );
    	// clean post_id and determine $post_type if not supplied
    	$translated_post_id = null;
    	$post_id = absint($post_id);
        if( empty($post_type) ) $post_type = get_post_type($post_id);
        // find the translation via the event or location tables
        if( em_is_event($post_type) || em_is_location($post_type) ){
	        // account for multisite global events
	        $ms_cond = '';
	        if( EM_MS_GLOBAL ){
		        $ms_cond = $blog_id ? ' AND blog_id='.absint($blog_id) : ' AND blog_id='.get_current_blog_id();
	        }
	        // search based on post type
	        if( em_is_event($post_type) ){
		        $post_meta = $wpdb->get_row('SELECT event_id AS parent, event_language AS language, event_parent AS real_parent FROM '.EM_EVENTS_TABLE.' WHERE post_id='.$post_id . $ms_cond);
		        $sql = 'SELECT post_id FROM '.EM_EVENTS_TABLE.' WHERE event_translation=1 AND event_parent=%d AND event_language=%s';
	        }elseif( em_is_location($post_type) ){
		        $post_meta = $wpdb->get_row('SELECT location_id AS parent, location_language AS language, location_parent AS real_parent FROM '.EM_LOCATIONS_TABLE.' WHERE post_id='.$post_id . $ms_cond);
	            $sql = 'SELECT post_id FROM '.EM_LOCATIONS_TABLE.' WHERE location_translation=1 AND location_parent=%d AND location_language=%s';
	        }
	        // get post id from search reults
		    if( !empty($post_meta) && !empty($sql) ){
			    if( $post_meta->language === EM_ML::$current_language ){
				    $translated_post_id = $post_id;
			    }else{
			    	$parent_id = !empty($post_meta->real_parent) ? $post_meta->real_parent : $post_meta->parent;
				    $current_language_post_id = $wpdb->get_var( $wpdb->prepare($sql, $parent_id, EM_ML::$current_language ));
				    if( $current_language_post_id !== null ){
					    $translated_post_id = $current_language_post_id;
				    }
			    }
		    }
        }
        // pass on $translated_post_id to translation plugin
	    $translated_post_id = apply_filters('em_ml_get_translated_post_id', $translated_post_id, $post_id, $post_type, $blog_id);
        // restore multisite blog if necessary
	    if( EM_MS_GLOBAL && $blog_id ) restore_current_blog();
        // return translated post id
        return ( $translated_post_id === null ) ? $post_id : absint($translated_post_id);
    }
	
	/**
	 * Returns the language of the object, in a WPLANG compatible format
	 * @param EM_Event|EM_Location $object
	 * @return mixed
	 */
	public static function get_the_language($object){
	    $language = null;
	    if( !empty($object->language) ){
	    	$language = $object->language;
	    }
	    $language = apply_filters('em_ml_get_the_language', $language, $object);
	    return ( $language === null ) ? self::$current_language : $language;
	}
	
	
	/**
	 * Gets the post ID of an EM_Event or EM_Location $EM_Object of the desired language $language, returns same object post id if already in requested language or a translation in that $language doesn't exist.
	 * @param EM_Event|EM_Location $EM_Object
	 * @param string $language Optional. WPLANG accepted value
	 * @return EM_Event|EM_Location
	 */
	public static function get_translation_id( $EM_Object, $language ){
		global $wpdb;
		$post_id = null;
		if( $EM_Object->language == $language ){
			$post_id = $EM_Object->post_id;
		}else{
			if( em_is_event($EM_Object) ){
				$items = $wpdb->get_results( $wpdb->prepare('SELECT event_language AS language, post_id FROM '.EM_EVENTS_TABLE.' WHERE event_id=%s OR (event_translation=1 AND event_parent=%s)', $EM_Object->event_id, $EM_Object->event_id), OBJECT_K );
			}elseif( em_is_location($EM_Object) ){
				$items = $wpdb->get_results( $wpdb->prepare('SELECT location_language as language,  post_id FROM '.EM_LOCATIONS_TABLE.' WHERE location_id=%s OR (location_translation=1 AND location_parent=%s)', $EM_Object->location_id, $EM_Object->location_id), OBJECT_K );
			}
			if( !empty($items[$language]) ){
				$post_id = $items[$language]->post_id;
			}
		}
	    $post_id = apply_filters('em_ml_get_translation_id', $post_id, $EM_Object, $language);
		return $post_id === null ? $EM_Object->post_id : $post_id;
	}
	
	/**
	 * Gets and EM_Event or EM_Location object equivalent of the desired language $language, or returns the same object if not available.
	 * This function ouptut does not need to be filtered by a ML plugin if em_ml_get_tralsation_id is already filtered and efficiently provides an ID without loading the object too (otherwise object may be loaded twice unecessarily).
	 * @uses EM_ML::get_translation_id()  
	 * @param EM_Event|EM_Location $object
	 * @param string $language
	 * @return EM_Event|EM_Location
	 */
	public static function get_translation( $object, $language ){
	    $translated_id = self::get_translation_id($object, $language);
	    $translated_object = $object; //return $object if the condition below isn't met
	    if( $object->post_id != $translated_id ){
	    	if( EM_MS_GLOBAL ){
	            if( em_is_event($object) ) $translated_object = em_get_event($translated_id, $object->blog_id);
	            if( em_is_location( $object ) ) $translated_object = em_get_location($translated_id, $object->blog_id);
		    }else{
			    if( em_is_event($object) ) $translated_object = em_get_event($translated_id,'post_id');
			    if( em_is_location( $object ) ) $translated_object = em_get_location($translated_id,'post_id');
		    }
	    }
	    return apply_filters('em_ml_get_translation', $translated_object, $object, $language);
	}
	
	/**
	 * Returns an array of translations for the supplied event, location or taxonomy. Array keys are the language code for this translation, in WPLANG format e.g. en_EN
	 * @param EM_Event|EM_Location|EM_Taxonomy_Term $EM_Object
	 * @return EM_Event[]|EM_Location[]|EM_Taxonomy_Term[]
	 */
	public static function get_translations( $EM_Object ){
		global $wpdb;
		$translated_objects = array();
		if( !empty($EM_Object->event_language) ){
			if( !$EM_Object->translation ){ // original
				$items = $wpdb->get_results( $wpdb->prepare('SELECT event_id, event_language FROM '.EM_EVENTS_TABLE.' WHERE event_id=%s OR (event_translation=1 AND event_parent=%s)', $EM_Object->parent, $EM_Object->parent) );
			}else{
				$items = $wpdb->get_results( $wpdb->prepare('SELECT event_id, event_language FROM '.EM_EVENTS_TABLE.' WHERE event_id=%s OR (event_translation=1 AND event_parent=%s)', $EM_Object->event_id, $EM_Object->event_id) );
			}
			foreach( $items as $item ){
				$translated_objects[$item->event_language] = em_get_event($item->event_id);
			}
		}elseif( !empty($EM_Object->location_language) ){
			if( !$EM_Object->translation ){ // original
				$items = $wpdb->get_results( $wpdb->prepare('SELECT location_id, location_language FROM '.EM_LOCATIONS_TABLE.' WHERE location_id=%s OR (location_translation=1 AND location_parent=%s)', $EM_Object->parent, $EM_Object->parent) );
			}else{
				$items = $wpdb->get_results( $wpdb->prepare('SELECT location_id, location_language FROM '.EM_LOCATIONS_TABLE.' WHERE location_id=%s OR (location_translation=1 AND location_parent=%s)', $EM_Object->location_id, $EM_Object->location_id) );
			}
			foreach( $items as $item ){
				$translated_objects[$item->location_language] = em_get_location($item->location_id);
			}
		}
		$translated_objects = apply_filters('em_ml_get_translations', $translated_objects, $EM_Object);
		if( $translated_objects === null ){
			return !empty($EM_Object->language) ? array($EM_Object->language => $EM_Object) : array(EM_ML::$current_language => $EM_Object);
		}else{
			return $translated_objects;
		}
	}
	
	/**
	 * @param EM_Event $EM_Event
	 * @return array[EM_Event]
	 * @see EM_ML::get_translations()
	 */
	public static function get_event_translations( $EM_Event ){
		return static::get_translations( $EM_Event );
	}
	/**
	 * @param EM_Location $EM_Location
	 * @return array[EM_Location]
	 * @see EM_ML::get_translations()
	 */
	public static function get_location_translations( $EM_Location ){
		return static::get_translations( $EM_Location );
	}

	
	/* START original object determining functions - Whilst not crucial to override, this is a good fallback mechanism for any event/location that may not have had the correct language/parent saved yet */
	
	/**
	 * Gets the original event/location. The 'original' means the language the event/location was first created in, irrespective of the default/main language of the currennt blog, subsequent languages of the same are 'translations'.
	 * @param EM_Event|EM_Location $EM_Object
	 * @return EM_Event|EM_Location
	 */
	public static function get_original( $EM_Object ){
		$object = null;
		if( static::is_original($EM_Object) ){
			$object = $EM_Object;
		}else{
			if( !empty($EM_Object->post_id) && !empty(static::$originals_cache[$EM_Object->blog_id][$EM_Object->post_id]) ){
				$original_post_id = static::$originals_cache[$EM_Object->blog_id][$EM_Object->post_id];
			}
			if( !empty($original_post_id) ){
				if( em_is_event($EM_Object) ){
					$object = em_get_event( $original_post_id, 'post_id' );
				}elseif( em_is_location($EM_Object) ){
					$object = em_get_location( $original_post_id, 'post_id' );
				}
			}elseif( em_is_event($EM_Object) || em_is_location($EM_Object) ){
				$object = $EM_Object->get_parent();
			}
		}
		$object = apply_filters('em_ml_get_original', $object, $EM_Object );
		// return result
		if( $object ){
			// save to cache first
			if( !empty($object->post_id) ){
				static::$originals_cache[$EM_Object->blog_id][$EM_Object->post_id] = $object->post_id;
			}
			return $object;
		}else{
			// if we still don't have an original, return the same object
			return $EM_Object;
		}
	}
	
	/**
	 * @param $object
	 * @return bool
	 */
	public static function is_original( $object ){
		$result = null; // assumed original since all non-multilingual content is original
		if( em_is_event( $object ) || em_is_location( $object ) ){
			if( !empty( static::$originals_cache[$object->blog_id][$object->post_id]) ){
				$result = static::$originals_cache[$object->blog_id][$object->post_id] == $object->post_id;
			}elseif( !empty($object->language) && $object->id ){ // we can only determine if something is original without 3rd party intervention if it has been saved previously
				$result = !$object->translation;
			}
		}
		// if $result is passed as null then it can be assuemd we could not determine originality and multilingual plugins should decide
		$result = apply_filters('em_ml_is_original', $result, $object);
		return $result === null || $result;
	}
	/**
	 * Shortcut for EM_ML::get_original() to aid IDE semantics.
	 * @see EM_ML::get_original()
	 * @param EM_Event $EM_Event
	 * @return EM_Event
	 */
	public static function get_original_event( $EM_Event ){
	    return self::get_original( $EM_Event );
	}
	/**
	 * Shortcut for EM_ML::get_original() to aid IDE semantics.
	 * @see EM_ML::get_original()
	 * @param EM_Location $EM_Location
	 * @return EM_Location
	 */
	public static function get_original_location( $EM_Location ){
	    return self::get_original( $EM_Location );
	}
	
	/* END original object determining functions */
	
	/**
	 * Sets the language of an object, shortcut for EM_ML::set_language_by_post_ids()
	 * @param EM_Event|EM_Location $EM_Object
	 * @param string $locale
	 * @return boolean
	 * @uses EM_ML::set_language_by_post_ids()
	 */
	public static function set_object_language( $EM_Object, $locale ){
		return static::set_language_by_post_ids( $locale, array($EM_Object->post_id), $EM_Object->post_type, $EM_Object->blog_id );
	}
	
	/**
	 * Inserts or updates locale meta for events and locations by post id.
	 * @param string $locale
	 * @param array $post_ids
	 * @param string $post_type
	 * @param int $blog_id
	 * @param bool $update          Determines if these post ids were just added and any extra meta should be bulk added too,
	 *                              or if they are to be updated and therefore some individual post lookups might be necessary by filters.
	 * @return bool
	 */
	public static function set_language_by_post_ids( $locale, $post_ids, $post_type, $blog_id = null, $update = false ){
		global $wpdb;
		if( !preg_match('/^[a-zA-Z_]{2,14}+$/', $locale) ) return false; //valid locale must be supplied
		if( EM_MS_GLOBAL ){
			if( !$blog_id ) $blog_id = get_current_blog_id();
			$blog_id = absint($blog_id);
			switch_to_blog($blog_id);
		}
		foreach( $post_ids as $k => $post_id ) $post_ids[$k] = absint($post_id); //sanitize post ids
		if( em_is_event( $post_type ) || em_is_location( $post_type ) ){
			$key_name = em_is_location( $post_type ) ? 'location_language' : 'event_language';
			$table_name = em_is_location( $post_type ) ? EM_LOCATIONS_TABLE : EM_EVENTS_TABLE;
			//save to events/location table - $update is irrelevant here as we must already have a saved event/location
			$sql = "UPDATE $table_name SET $key_name=%s WHERE post_id IN (". implode(',', $post_ids) .')';
			$sql_vars = array( $locale );
			if( EM_MS_GLOBAL ){
				$sql .= ' AND blog_id=%d';
				$sql_vars[] = $blog_id;
			}
			$wpdb->query( $wpdb->prepare($sql, $sql_vars) );
			//save to meta
			if( $update ){
				$sql = 'UPDATE '.$wpdb->postmeta ." SET meta_value=%s WHERE meta_key=%s AND post_id IN (". implode(',', $post_ids) .')';
				$sql = $wpdb->prepare( $sql, array($locale, $key_name) );
			}else{
				$inserts = array();
				foreach( $post_ids as $post_id ){
					$inserts[] = $wpdb->prepare('(%d, %s, %s)', array($post_id, $key_name, $locale));
				}
				$sql = 'INSERT INTO '.$wpdb->postmeta.' (post_id, meta_key, meta_value) VALUES '. implode(',', $inserts);
			}
			$wpdb->query( $sql );
		}
		if( EM_MS_GLOBAL ){
			restore_current_blog();
		}
		return apply_filters('em_ml_set_language_by_post_ids', true, $locale, $post_ids, $post_type, $blog_id, $update);
	}
	
	public static function attach_translations( $locale, $post_ids_map, $post_type, $blog_id = null ){
		global $wpdb;
		if( EM_MS_GLOBAL && !$blog_id ) $blog_id = get_current_blog_id();
		//set parents and languages for each post
		if( em_is_event( $post_type ) || em_is_location( $post_type ) ){
			//sanitize any sql vars
			$prefix = em_is_location( $post_type ) ? 'location' : 'event';
			$table_name = em_is_location( $post_type ) ? EM_LOCATIONS_TABLE : EM_EVENTS_TABLE;
			$post_ids = array();
			foreach( $post_ids_map as $original_post_id => $post_id ){
				$post_ids[$original_post_id] = absint($post_id);
			}
			//get the event/location ids of both new and old event/locations so we can do a bulk update using their ids
			$sql = "SELECT post_id, {$prefix}_id AS id FROM $table_name WHERE post_id IN (". implode(',', $post_ids + array_keys($post_ids)) .")";
			if( EM_MS_GLOBAL ) $sql .= ' AND blog_id='.absint($blog_id);
			$object_ids = $wpdb->get_results( $sql, OBJECT_K );
			//save to events/location table - $update is irrelevant here as we must already have a saved event/location
			$inserts = array();
			foreach( $post_ids as $original_post_id => $post_id ){
				if( !empty($object_ids[$post_id]) && !empty($object_ids[$original_post_id]) ){
					$inserts[] = $wpdb->prepare('(%d, %d, %s, 1)', $object_ids[$post_id]->id, $object_ids[$original_post_id]->id, $locale);
				}
			}
			$wpdb->query("INSERT INTO $table_name ({$prefix}_id, {$prefix}_parent, {$prefix}_language, {$prefix}_translation) VALUES ".implode(',', $inserts)."
			ON DUPLICATE KEY UPDATE {$prefix}_parent=VALUES({$prefix}_parent), {$prefix}_language=VALUES({$prefix}_language), {$prefix}_translation=1");
		}
		return apply_filters('em_ml_attach_translations', true, $locale, $post_ids_map, $post_type, $blog_id );
	}
	
	public static function toggle_languages_index( $force = null ){
		global $wpdb;
		$tables_keys = array(EM_EVENTS_TABLE => 'event_language', EM_LOCATIONS_TABLE => 'location_language');
		foreach( $tables_keys as $table_name => $key ){
			$language_key = $wpdb->get_row("SHOW KEYS FROM $table_name WHERE Key_name = '$key'");
			if( empty($language_key) && ( (!empty(EM_ML::$langs) && !$force) || $force === 'add' ) ){
				$wpdb->query("ALTER TABLE $table_name ADD INDEX (`$key`)");
			}elseif( !empty($language_key) && ( (empty(EM_ML::$langs) && !$force) ||$force === 'remove' ) ){
				$wpdb->query("ALTER TABLE $table_name DROP INDEX `$key`");
			}
		}
	}
}
add_action('init','EM_ML::init'); //other plugins may want to do this before we do, that's ok!