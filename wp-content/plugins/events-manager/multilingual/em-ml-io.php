<?php
/**
 * Model-related functions. Each ML plugin will do its own thing so must be accounted for accordingly. Below is a description of things that should happen so everything 'works'.
 *
 * When an event or location is saved, we need to perform certain options depending whether saved on the front-end editor,
 * or if saved/translated in the backend, since events share information across translations.
 *
 * Event translations should assign one event to be the 'original' event, meaning bookings and event times will be managed by the 'orignal' event.
 * Since ML Plugins can change default languages and you can potentially create events in non-default languages first, the first language will be the 'orignal' event.
 * If an event is deleted and is the original event, but there are still other translations, the original event is reassigned to the default language translation, or whichever other event is found first
 */
class EM_ML_IO {
	
	public static $detaching = false;
    
    public static function init(){
        //Saving/Editing
	    add_action('em_event_save_meta_pre', 'EM_ML_IO::event_save_meta_pre', 10, 1);
	    add_filter('em_event_save_meta','EM_ML_IO::event_save_meta',1000000000000,2); //let other add-ons hook in first
	    add_filter('em_event_get_post_meta','EM_ML_IO::event_get_post_meta',10,2);
	    add_filter('em_tickets_save', 'EM_ML_IO::tickets_save', 10, 2);
	    //Recurring Events
	    add_filter('em_event_save_events_pre', 'EM_ML_IO::event_save_events_pre', 10, 1);
	    add_filter('em_event_save_events','EM_ML_IO::event_save_events',10,4);
	    add_filter('em_event_delete_events', 'EM_ML_IO::event_delete_events', 10, 2);
        add_action('em_event_detach', 'EM_ML_IO::event_detach', 10, 2);
        //Deletion
        add_action('em_event_delete_meta_event_pre', 'EM_ML_IO::event_delete_meta_event_pre', 10, 1);
	    add_filter('em_tickets_delete', 'EM_ML_IO::tickets_delete', 10, 3);
	    add_filter('em_ticket_delete', 'EM_ML_IO::ticket_delete', 10, 2);
        //Loading
        add_filter('em_event_get_location','EM_ML_IO::event_get_location',10,2);
        //Duplication link
        add_filter('em_event_duplicate_url','EM_ML_IO::event_duplicate_url',10, 2);
    }
    
    /**
     * Changes necessary event location to same language as event if different
     * @param EM_Location $EM_Location
     * @param EM_Event $EM_Event
     * @return EM_Location
     */
    public static function event_get_location( $EM_Location, $EM_Event ){
        if( $EM_Location->location_id ){
            $event_lang = EM_ML::get_the_language($EM_Event);
            $location_lang = EM_ML::get_the_language($EM_Location);
            if( $event_lang != $location_lang ){
                $EM_Location = EM_ML::get_translation($EM_Location, $event_lang);
	            $EM_Event->location = $EM_Location;
            }
        }
        return $EM_Location;
    }
	
	/**
	 * @param EM_Event $EM_Event The event to merge original meta into
	 * @param EM_Event $event The original event
	 */
    public static function event_merge_original_meta( $EM_Event, $event ){
        $EM_Event->event_parent = $event->event_id;
        $EM_Event->event_translation = 1;
        //set values over from original event
        $EM_Event->event_start_date  = $event->event_start_date ;
		$EM_Event->event_end_date  = $event->event_end_date ;
		$EM_Event->recurrence  = $event->recurrence ;
		$EM_Event->post_type  = $event->post_type ;
		$EM_Event->location_id  = $event->location_id ;
		$EM_Event->location = false;
		$EM_Event->event_all_day  = $event->event_all_day ;
		$EM_Event->event_start_time  = $event->event_start_time ;
		$EM_Event->event_end_time  = $event->event_end_time ;
		$EM_Event->event_timezone = $event->event_timezone;
		$EM_Event->start(); //reset the start/end internal timestamps and private values
		$EM_Event->end();
		
		$EM_Event->event_rsvp  = $event->event_rsvp ;
		$EM_Event->event_rsvp_date  = $event->event_rsvp_date ;
		$EM_Event->event_rsvp_time  = $event->event_rsvp_time ;
		
		$EM_Event->blog_id  = $event->blog_id ;
		$EM_Event->group_id  = $event->group_id ;
		$EM_Event->recurrence  = $event->recurrence ;
		$EM_Event->recurrence_freq  = $event->recurrence_freq ;
		$EM_Event->recurrence_byday  = $event->recurrence_byday ;
		$EM_Event->recurrence_interval  = $event->recurrence_interval ;
		$EM_Event->recurrence_byweekno  = $event->recurrence_byweekno ;
		$EM_Event->recurrence_days  = $event->recurrence_days ;
		self::event_merge_original_attributes($EM_Event, $event);
    }
	
	/**
	 * @param EM_Event $EM_Event
	 * @param EM_Event $event
	 */
	public static function event_merge_original_attributes($EM_Event, $event){
		//merge attributes
		$event->event_attributes = maybe_unserialize($event->event_attributes);
		$EM_Event->event_attributes = maybe_unserialize($EM_Event->event_attributes);
		foreach($event->event_attributes as $event_attribute_key => $event_attribute){
			if( !empty($event_attribute) && empty($EM_Event->event_attributes[$event_attribute_key]) ){
				$EM_Event->event_attributes[$event_attribute_key] = $event_attribute;
			}
		}
    }
    
    /**
     * Hooks into em_event_get_post and writes the original event translation data into the current event, to avoid validation errors and correct data saving.
     * @param boolean $result
     * @param EM_Event $EM_Event
     * @return boolean
     */
    public static function event_get_post_meta($result, $EM_Event){
        //check if this is a master event, if not then we need to get the relevant master event info and populate this object with it so it passes validation and saves correctly.
        if( !EM_ML::is_original($EM_Event) ){
            //get original event object
            $event = EM_ML::get_original_event($EM_Event);
            EM_ML_IO::event_merge_original_meta($EM_Event, $event);
			
			if( $EM_Event->location_id == 0 ) $_POST['no_location'] = 1;
			// We need to save ticket translations here as well to the ticket objects
			foreach( $EM_Event->get_tickets()->tickets as $EM_Ticket ){ /* @var $EM_Ticket EM_Ticket */
			    $ticket_translation = array();
			    if( !empty($_REQUEST['ticket_translations'][$EM_Ticket->ticket_id]['ticket_name'] ) ) $ticket_translation['ticket_name'] = wp_kses_data(wp_unslash($_REQUEST['ticket_translations'][$EM_Ticket->ticket_id]['ticket_name']));
			    if( !empty($_REQUEST['ticket_translations'][$EM_Ticket->ticket_id]['ticket_description'] ) ) $ticket_translation['ticket_description'] = wp_kses_post(wp_unslash($_REQUEST['ticket_translations'][$EM_Ticket->ticket_id]['ticket_description']));
			    if( !empty($ticket_translation) ) $EM_Ticket->ticket_meta['langs'][EM_ML::$current_language] = $ticket_translation;
			}
        }elseif( !empty($EM_Event->location_id) ){
            //we need to make sure the location is the original location
            $EM_Location = $EM_Event->get_location();
            if( !EM_ML::is_original($EM_Location) ){
                $EM_Event->location_id = EM_ML::get_original_location($EM_Location)->location_id;
            }
        }
        return $result;
    }
	
	/**
	 * When a master event is deleted, translations are not necessarily deleted so things like bookings must be transferred to a translation and that must now be the master event.
	 * @param EM_Event $EM_Event
	 */
	public static function event_delete_meta_event_pre( $EM_Event ){
		global $wpdb;
		if( EM_ML::is_original($EM_Event) ){
			//check to see if there's any translations of this event, provide first available one otherwise the default language should the original not be in that language
		    $event_translations = EM_ML::get_translations($EM_Event);
		    foreach( $event_translations as $language => $event_translation ){
		    	if( $language != $EM_Event->event_language ){
				    if( empty($event) ) $event = $event_translation; // first available translation
				    // provide the default language if not the original language
		    		if( $language == EM_ML::$wplang ){
					    $event = $event_translation;
		    			break;
				    }
			    }
		    }
			//if so check if the default language still exists
			if( !empty($event->event_id) && $EM_Event->event_id != $event->event_id ){
				//make that translation the master event by changing event ids of bookings, tickets etc. to the new master event
				$wpdb->update(EM_TICKETS_TABLE, array('event_id'=>$event->event_id), array('event_id'=>$EM_Event->event_id));
				$wpdb->update(EM_BOOKINGS_TABLE, array('event_id'=>$event->event_id), array('event_id'=>$EM_Event->event_id));
				//adjust the event_parent pointer
				$wpdb->update(EM_EVENTS_TABLE, array('event_parent'=>$event->event_id), array('event_parent'=>$EM_Event->event_id));
				$EM_Event->ms_global_switch();
				$wpdb->update(EM_EVENTS_TABLE, array('event_parent'=>null, 'event_translation'=>0), array('event_id'=>$event->event_id));
				$wpdb->update($wpdb->postmeta, array('meta_value'=>$event->event_id), array('meta_key'=>'_event_parent', 'meta_value'=>$EM_Event->event_id));
				delete_post_meta( $event->post_id, '_event_parent');
				delete_post_meta( $event->post_id, '_event_translation');
				$EM_Event->ms_global_switch_back();
				do_action('em_ml_transfer_original_event', $event, $EM_Event); //other add-ons with tables with event_id foreign keys should hook here and change
			}
		}
	}
 
	/**
	 * Changes the event id of the link for duplication so that it duplicates the original event instead of a translation.
	 * Translation plugins should hook into em_event_duplicate, checking to make sure it is the original translation and then duplicating the translations of the original event.
	 * @param string $url
	 * @param EM_Event $EM_Event
	 * @return string
	 */
	public static function event_duplicate_url($url, $EM_Event){
	    if( !EM_ML::is_original($EM_Event) ){
	        $EM_Event = EM_ML::get_original($EM_Event);
    	    $url = add_query_arg(array('action'=>'event_duplicate', 'event_id'=>$EM_Event->event_id, '_wpnonce'=> wp_create_nonce('event_duplicate_'.$EM_Event->event_id)));
    	    //this gets escaped later
	    }
	    return $url;
	}
	
	/**
	 * Saves the original event recurring ID into translated recurring events, for easy reference when finding translations to reschedule.
	 * @param EM_Event $EM_Event
	 */
	public static function event_save_meta_pre( $EM_Event ){
		$EM_Event->event_language = EM_ML::get_the_language( $EM_Event ); //do this early on so we know the language we're dealing with
		// save parent info about this event
		if( !EM_ML::is_original( $EM_Event ) ){
			$event = EM_ML::get_original( $EM_Event );
			if( $EM_Event->is_recurring() ){
				// make this a recurrence of the original language, even though this is a recurring event
				$EM_Event->recurrence_id = $event->event_id;
			}
			static::event_merge_original_meta( $EM_Event, $event );
		}
	}
	
	/**
	 * Saves translations of an original translation when the original has been saved.
	 * @param bool $result
	 * @param EM_Event $EM_Event
	 * @return bool
	 */
	public static function event_save_meta($result, $EM_Event){
		if( $result && EM_ML::is_original($EM_Event) ){
			//save post meta for all others as well
			foreach( EM_ML::get_translations( $EM_Event ) as $lang_code => $event ){
				if( $event->event_id != $EM_Event->event_id ){
					self::event_merge_original_meta($event, $EM_Event);
					$event->save_meta();
				}
			}
		}
		return $result;
	}
	
	/**
	 * Additional cleanup in case a recurrence was translated before translating a recurring event, which results in the recurrence having the recurrence_id of the original translated event.
	 * This function deletes those recurrences, as if it had previously been assigned the correct recurrence_id first time around.
	 * @param bool $result
	 * @param EM_Event $EM_Event
	 * @return bool
	 */
	public static function event_delete_events( $result, EM_Event $EM_Event ){
		global $wpdb;
		if( $result && !EM_ML::is_original($EM_Event) ){
			$event = EM_ML::get_original_event($EM_Event);
			$sql = $wpdb->prepare('SELECT event_id FROM '.EM_EVENTS_TABLE.' WHERE (recurrence!=1 OR recurrence IS NULL)  AND recurrence_id=%d AND event_language=%s', $event->event_id, $EM_Event->event_language);
			// copied from EM_Event->delete_events function
			$event_ids = $wpdb->get_col( $sql );
			foreach($event_ids as $event_id){
				$EM_Event = em_get_event( $event_id );
				$EM_Event->delete(true);
				$events_array[] = $EM_Event;
			}
		}
		return $result;
	}
	
	/**
	 * Prevent language searches hindering deletion and manipulation of events via the EM_Events::get search
	 */
	public static function event_save_events_pre(){
		EM_ML_Search::$active = false;
	}
	
	/**
	 * Links translations of events to the original language event recurrences, also triggers a saving/updating or translated recurrences when the original language is updated e.g. things like times, images etc. get saved to translations too.
	 *
	 * @param boolean $result
	 * @param EM_Event $EM_Event
	 * @param array $event_ids
	 * @param array $post_ids
	 * @return boolean
	 * @throws Exception DateTime Exception
	 */
	public static function event_save_events($result, $EM_Event, $event_ids, $post_ids){
		global $wpdb;
		if( $result ){
			$event = EM_ML::get_original_event($EM_Event);
			$is_original = $event->event_id == $EM_Event->event_id;
			if( $EM_Event->recurring_reschedule ){
				// first, we obtain the original event, get all the recurrences, and match the timestamp of each translation with that post ID and set the recurrence. If there is no match (for no explicable reason), we delete the event.
				if( !$is_original ){
					// get original recurrences, sort them by timestamp
					$events = $wpdb->get_results( $wpdb->prepare('SELECT event_start_date, event_start_time, post_id, event_id FROM '.EM_EVENTS_TABLE.' WHERE recurrence=0 AND recurrence_id=%d', $event->event_id), ARRAY_A );
					$original_post_ids = $original_event_ids = array();
					foreach( $events as $recurrence_event ){
						$EM_DateTime = new EM_DateTime($recurrence_event['event_start_date'].' '.$recurrence_event['event_start_time'], $EM_Event->get_timezone());
						$original_post_ids[$EM_DateTime->getTimestamp()] = absint($recurrence_event['post_id']);
						$original_event_ids[$recurrence_event['post_id']] = $recurrence_event['event_id'];
					}
					// join the original post_ids with the new post_ids based on timestamp, delete any posts that have no identical times since we can't link the translation.
					$attach_post_ids = array();
					foreach( $post_ids as $ts => $post_id ){
						if( !empty($original_post_ids[$ts]) ){
							$attach_post_ids[$original_post_ids[$ts]] = $post_id;
						}else{
							// delete the event as there's no match
							wp_delete_post($post_id);
						}
					}
					// attach the events
					EM_ML::attach_translations( $EM_Event->event_language, $attach_post_ids, EM_POST_TYPE_EVENT, $EM_Event->blog_id );
					// correct the wp_postmeta table which will have inherited the parent id of the recurring event
					foreach( $attach_post_ids as $original_post_id => $post_id ){
						update_post_meta( $post_id, '_event_parent', $original_event_ids[$original_post_id] );
						update_post_meta( $post_id, '_event_translation', 1 );
					}
				}else{
					// firstly, we need to save the language of these events, since they were added directly, not via insert_post
					EM_ML::set_language_by_post_ids( $EM_Event->event_language, $post_ids, EM_POST_TYPE_EVENT, $EM_Event->blog_id );
				}
			}
			if( $is_original ){
				//we need to recreate the translations as well, rescheduling them as well. this function will get called again, but it'll hit the earlier if and not become an infinite loop
				global $EM_EVENT_SAVE_POST;
				$save_post_status = $EM_EVENT_SAVE_POST;
				remove_filter('em_event_get_bookings', 'EM_ML_Bookings::override_bookings',100); //prevent overriding of bookings for translations whilst saving
				$event_ids = $wpdb->get_col( $wpdb->prepare('SELECT event_id FROM '.EM_EVENTS_TABLE.' WHERE recurrence=1 AND recurrence_id=%d', $EM_Event->event_id) );
				foreach( $event_ids as $event_id ){ /* @var EM_Event $event */
					$EM_EVENT_SAVE_POST = false;
					EM_ML_Search::$active = false; //just in case
					$event = em_get_event( $event_id );
					EM_ML_IO::event_merge_original_meta( $event, $EM_Event );
					$event->recurring_reschedule = $EM_Event->recurring_reschedule;
					$event->recurring_recreate_bookings = false; //specifically skip creation/recreation of tickets/booking-data for translations, as these are overriden by ML functions
					$event->recurring_delete_bookings = false; //don't even try
					$event->save_meta(); //this will save the current event and call this function again to pass through the top if clause
				}
				add_filter('em_event_get_bookings', 'EM_ML_Bookings::override_bookings',100,2);
				$EM_EVENT_SAVE_POST = $save_post_status;
			}else{
				//we need to save tickets in any case in case they were translated
				//static::tickets_save( true, $EM_Event->get_tickets() );
			}
		}
		EM_ML_Search::$active = true;
		return $result;
	}
	
	/**
	 * @param bool $result
	 * @param EM_Event $EM_Event
	 * @return bool
	 */
	public static function event_detach( $result, $EM_Event ){
		if( $result && !static::$detaching ){
			static::$detaching = true; //infinite loop prevention
			$translations = EM_ML::get_translations( $EM_Event );
			foreach( $translations as $event ){ /* @var EM_Event $event */
				if( $event->event_id != $EM_Event->event_id ){
					$event->detach();
				}
			}
			static::$detaching = false;
		}
		return $result;
	}
	
	/**
	 * Saves ticket meta to recurrences if the event is a recurring event.
	 * @param $result
	 * @param EM_Tickets $EM_Tickets
	 */
	public static function tickets_save( $result, $EM_Tickets ){
		global $wpdb;
		if( $result ){
			$EM_Event = $EM_Tickets->get_event();
			//if this is a recurring event, we should save all the ticket meta to the equivalent tickets belonging to the recurrences
			if( $EM_Event->is_recurring() ){
				$EM_Event = EM_ML::get_original($EM_Event); //get original event recurrence, not the translation (even though the bookings object points to the same data) just in case
				//we need to update ticket meta fields so they have translation data
				foreach( $EM_Event->get_bookings()->get_tickets() as $EM_Ticket ){ /* @var EM_Ticket $EM_Ticket */
					$ticket_meta = $EM_Ticket->ticket_meta;
					if( isset($ticket_meta['recurrences']) ) unset($ticket_meta['recurrences']);
					$ticket_ids = $EM_Ticket->get_recurrence_ticket_ids(); //get tickets that are recurrences
					if( !empty($ticket_ids) ){
						$ticket_ids = implode(',', $ticket_ids);
						$sql = $wpdb->prepare('UPDATE '.EM_TICKETS_TABLE." SET ticket_meta=%s, ticket_parent=%s WHERE ticket_id IN ($ticket_ids)", maybe_serialize($ticket_meta), $EM_Ticket->ticket_id);
						$wpdb->query($sql);
					}
				}
			}
		}
	}
	
	
	/**
	 * Runs through deleted tickets and passes them through the single ticket_delete function.
	 * @param boolean $result
	 * @param array[int] $ticket_ids
	 * @param EM_Tickets $EM_Tickets
	 * @return boolean
	 */
	public static function tickets_delete( $result, $ticket_ids, $EM_Tickets ){
		if( $result ){
			if( !empty($EM_Tickets->tickets) ){
				foreach( $EM_Tickets as $EM_Ticket ){
					static::ticket_delete( $result, $EM_Ticket );
				}
			}elseif( !empty($EM_Tickets->event_id) ){
				foreach( $ticket_ids as $ticket_id ){
					$EM_Ticket = new EM_Ticket();
					$EM_Ticket->ticket_id = $ticket_id;
					$EM_Ticket->event_id = $EM_Tickets->event_id;
					static::ticket_delete( $result, $EM_Ticket );
				}
			}
		}
		return $result;
	}
	
	/**
	 * Hooks into em_ticket_delete and allows multilingual plugins ot delete any external ticket string management. Note that
	 * the $EM_Ticket may not be a fully populated object, it may just contain the ticket_id and event_id properties such as in cases where
	 * recurrences and their resulting tickets are deleted in bulk.
	 * @param boolean $result
	 * @param EM_Ticket $EM_Ticket
	 * @return boolean
	 */
	public static function ticket_delete($result, $EM_Ticket ) {
		global $wpdb;
		return apply_filters('em_ml_ticket_delete', $result, $EM_Ticket);
	}
}
EM_ML_IO::init();