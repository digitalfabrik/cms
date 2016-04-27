<?php
/**
 * Model-related functions. Each ML plugin will do its own thing so must be accounted for accordingly. Below is a description of things that should happen so everything 'works'.
 * 
 * When an event or location is saved, we need to perform certain options depending whether saved on the front-end editor, 
 * or if saved/translated in the backend, since events share information across translations.
 * 
 * Event translations should assign one event to be the 'original' event, meaning bookings and event times will be managed by the 'orignal' event.
 * Since WPML can change default languages and you can potentially create events in non-default languages first, the first language will be the 'orignal' event.
 * If an event is deleted and is the original event, but there are still other translations, the original event is reassigned to the default language translation, or whichever other event is found first
 */
class EM_WPML_IO {
    
    public static function init(){
        //Saving/Editing
        add_filter('em_location_save','EM_WPML_IO::location_save',10,2);
        add_filter('em_event_duplicate','EM_WPML_IO::event_duplicate',10,2);
        //Recurring events - WIP
	    add_filter('em_event_save_events','EM_WPML_IO::em_event_save_events',10,4);
	    add_filter('delete_events','EM_WPML_IO::delete_events', 10,3);
	    //EM duplication
        add_filter('em_event_duplicate','EM_WPML_IO::event_duplicate',10,2);
        add_filter('em_event_duplicate_url','EM_WPML_IO::event_duplicate_url',100,2);
	    //WPML duplication
	    add_action( 'icl_make_duplicate', 'EM_WPML_IO::wpml_duplicate', 10, 4);
	    //WPML deletion
	    add_action('em_ml_transfer_original_event', 'EM_WPML_IO::transfer_original',10,2);
	    add_action('em_ml_transfer_original_location', 'EM_WPML_IO::transfer_original',10,2);
    }
    
    /**
     * Writes a record into the WPML translation tables if non-existent when a location has been added via an event
     * @param boolean $result
     * @param EM_Location $EM_Location
     * @return boolean
    */
    public static function location_save($result, $EM_Location){
    	global $wpdb, $sitepress;
    	$trid = $sitepress->get_element_trid($EM_Location->post_id, 'post_'.EM_POST_TYPE_LOCATION);
		if( empty($trid) ){
    		//save a value into WPML table
    		$wpdb->insert($wpdb->prefix.'icl_translations', array('element_type'=>"post_".EM_POST_TYPE_LOCATION, 'trid'=>$EM_Location->post_id, 'element_id'=>$EM_Location->post_id, 'language_code'=>ICL_LANGUAGE_CODE));
    	}
    	return $result;
    }
    
    /**
     * If we delete an original CPT, WPML doesn't assign a new 'original' CPT. Therefore we just assign the one we've chosen for transfer via EM_ML_IO functions and make that the original.
     * 
     * @param EM_Object $object
     * @param EM_Object $original_object
     */
    public static function transfer_original($object, $original_object){
        global $wpdb;
        if( !empty($object->post_id) ){
            $sql = $wpdb->prepare("UPDATE {$wpdb->prefix}icl_translations SET source_language_code = NULL WHERE element_id = %d", $object->post_id); 
            $wpdb->query($sql);
        }
    }
    
    /**
     * When an event is duplicated, we need to get the original event's translations and copy them as duplicates of the current event.
     * An assumption is made here, which is that the event that was duplicated already is the original, since in ML mode we should be only duplicating the original language.
     * 
     * @param boolean $result
     * @param EM_Event $event
     * @return boolean
     */
    public static function event_duplicate($result, $EM_Event){
    	global $wpdb, $sitepress, $EM_WPML_DUPLICATING;
    	if( $result && empty($EM_WPML_DUPLICATING) ){
    	    $wpml_post_type = 'post_'.EM_POST_TYPE_EVENT;
    	    //get the translation info of the duplicated event, for use later on
    	    $event = $result; /* @var $EM_Event EM_Event */
    	    $duplicated_trid = $sitepress->get_element_trid($event->post_id, $wpml_post_type);
    	    //first we must change it to be the translation of an original event if this isn't
    	    $trid = $sitepress->get_element_trid($EM_Event->post_id, $wpml_post_type);
    	    $translations = $sitepress->get_element_translations($trid, $wpml_post_type);
    	    foreach( $translations as $lang_code => $translation ){
    	        //check that we're not in the original language, as that has been duplicated already
    	        if( $translations[$lang_code]->element_id != $EM_Event->post_id ){
    	            //get the translation of original event that was duplicated if exists and duplicate it
    	            $event = em_get_event($translations[$lang_code]->element_id, 'post_id');
    	            $EM_WPML_DUPLICATING = true;
    	            $event = $event->duplicate();
    	            $EM_WPML_DUPLICATING = false;
    	            //once saved, we modify the WPML DB slightly so it matches the translation info of the newly translated event
    	            //$original_properties = $wpdb->get_row('SELECT language_code, source_language_code')
    	            $array_set = array('language_code'=>$lang_code, 'trid'=>$duplicated_trid);
    	            $array_set_format = array('%s','%d');
    	            if( !empty($translation->source_language_code) ){
    	                $array_set['source_language_code'] = $translation->source_language_code;
    	                $array_set_format[] = '%s';
    	            }
    	            $wpdb->update( $wpdb->prefix.'icl_translations', $array_set, array('element_type'=>$wpml_post_type, 'element_id'=>$event->post_id), $array_set_format, array('%s','%d'));
    	        }
    	    }
    	}
    	return $result;
    }
    
    /**
     * Modifies the duplication URL so that it contains the lang query parameter of the original event that is being duplicated.
     * 
     * @param string $url
     * @param EM_Event $EM_Event
     * @return string
     */
    public static function event_duplicate_url( $url, $EM_Event ){
        global $sitepress;
        if( !EM_ML::is_original($EM_Event) ){
            $EM_Event = EM_ML::get_original_event($EM_Event);
	        $sitepress_lang = $sitepress->get_language_for_element($EM_Event->post_id, 'post_'.$EM_Event->post_type);
    	    $url = add_query_arg(array('lang'=>$sitepress_lang), $url);
    	    //gets escaped later
        }
        return $url;
    }

    
    /**
     * When an event is duplicated via WPML, we need to save the event meta via the EM_Event and EM_Location objects.
     * This way, it grabs the original translation meta and saves it into the duplicate via other hooks in EM_WPML_IO.
     * 
     * @param int $master_post_id
     * @param string $lang
     * @param array $post_array
     * @param int $id
     */
    public static function wpml_duplicate( $master_post_id, $lang, $post_array, $id ){
        //check this is an event
        switch( get_post_type($id) ){
            case EM_POST_TYPE_EVENT:
                //WPML just duplicated an event into another language, so we simply need to load the event and resave it
                $EM_Event = em_get_event($id, 'post_id');
                $EM_Event->event_id = null;
                $EM_Event->save();
                break;
            case EM_POST_TYPE_LOCATION:
                $EM_Location = em_get_location($id, 'post_id');
                $EM_Location->location_id = null;
                $EM_Location->save();
                break;
        }
    }
    
	/*
	 * RECURRING EVENTS
	* WARNING - Given that recurrences create seperate individual events from a different post type, it's pretty much impossible as is to reliably target a translation since WPML requires a new recurring event post to be created. For that reason it's advised you disable recurring events.
	*/
	
	/**
	 * Adds records into WPML translation tables when a recurring event is created.
	 *
	 * @param boolean $result
	 * @param unknown_type $EM_Event
	 * @param unknown_type $event_ids
	 * @param unknown_type $post_ids
	 * @return unknown
	 */
	public static function em_event_save_events($result, $EM_Event, $event_ids, $post_ids){
		global $wpdb;
		if($result){
			$inserts = array();
			$sitepress_options = get_option('icl_sitepress_settings');
			$lang = $wpdb->get_var("SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE element_id={$EM_Event->post_id}");
			if( empty($lang) ) $lang = $sitepress_options['default_language'];
			foreach($post_ids as $post_id){
				if( !$wpdb->get_var("SELECT translation_id FROM {$wpdb->prefix}icl_translations WHERE (element_id={$post_id} OR trid={$post_id}) AND language_code='{$lang}'") ){
					//save a value into WPML table
					$inserts[] = $wpdb->prepare("('post_".EM_POST_TYPE_EVENT."', %d, %d, '%s')", array($post_id, $post_id, $lang));
				}
			}
			if( count($inserts) > 0 ){
				//$wpdb->insert($wpdb->prefix.'icl_translations', array('element_type'=>"post_".EM_POST_TYPE_EVENT, 'trid'=>$post_id, 'element_id'=>$post_id, 'language_code'=>$lang));
				$wpdb->query("INSERT INTO ".$wpdb->prefix."icl_translations (element_type, trid, element_id, language_code) VALUES ".implode(',', $inserts));
			}
		}
		return $result;
	}
	
	/**
	 * Deletes translation info from WPML Tables
	 * 
	 * @param boolean $result
	 * @param EM_Event $EM_Event
	 * @param array $events
	 * @return boolean
	 * 
	 * @todo use a WPML-specific function and pass on post IDs that way, given there may be other traces of meta in other tables
	 */
	public static function delete_events($result, $EM_Event, $events){
		global $wpdb;
		if($result){
			$post_ids = array();
			foreach($events as $event){
				$post_ids[] = $event->post_id;
			}
			if( count($post_ids) > 0 ){
				//$wpdb->insert($wpdb->prefix.'icl_translations', array('element_type'=>"post_".EM_POST_TYPE_EVENT, 'trid'=>$post_id, 'element_id'=>$post_id, 'language_code'=>$lang));
				$wpdb->query("DELETE FROM ".$wpdb->prefix."icl_translations WHERE element_id IN (".implode(',',$post_ids).")");
			}
		}
		return $result;
	}
}
EM_WPML_IO::init();