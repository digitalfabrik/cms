<?php
class EM_ML_Bookings {
	
	public static $ignore_post_ids = array();
	
	public static $displaying_locale = false;
    
    public static function init(){
        add_action('em_booking_get_post_pre','EM_ML_Bookings::em_booking_get_post_pre', 1, 1);
		add_filter('em_event_get_bookings', 'EM_ML_Bookings::override_bookings',100,2);
		add_action('em_booking_form_footer','EM_ML_Bookings::em_booking_form_footer',10,1);
		add_filter('em_booking_get_event', 'EM_ML_Bookings::em_booking_get_event',10,2);
		add_filter('em_booking_email_messages', 'EM_ML_Bookings::em_booking_email_messages',10,2);
		add_action('em_bookings_admin_page', 'EM_ML_Bookings::em_bookings_admin_page',10,2);
		add_filter('em_bookings_table_rows_col', 'EM_ML_Bookings::em_bookings_table_rows_col',1,6);
		add_filter('em_bookings_table_cols_template', 'EM_ML_Bookings::em_bookings_table_cols_template',1,2);
		// email language context
	    add_action('em_booking_email_before_send', 'EM_ML_Bookings::em_booking_email_before_send');
	    add_action('em_booking_email_after_send', 'EM_ML_Bookings::em_booking_email_after_send');
	    //add_action('em_booking_output_pre', 'EM_ML_Bookings::em_booking_output_pre', 1, 1);
	    //add_filter('em_booking_output', 'EM_ML_Bookings::em_booking_output', 10, 2);
		// prevent overrides from happening during certain operations
		add_action('before_delete_post', function($post_id){
			EM_ML_Bookings::$ignore_post_ids[] = $post_id;
		});
    }
    
    public static function em_booking_email_before_send( $EM_Booking ){
    	if( $EM_Booking->language  && get_locale() !== $EM_Booking->language ){
            static::$displaying_locale = EM_ML::$current_language;
		    EM_ML::switch_locale($EM_Booking->language);
	    }
    }
	
	/**
	 * Sets the current language to the booking language so that email placeholders are translated correctly.
	 * @param EM_Booking $EM_Booking
	 */
    public static function em_booking_output_pre( $EM_Booking ){
	    if( !static::$displaying_locale ){
	    	if( $EM_Booking->language && get_locale() !== $EM_Booking->language ){
		        EM_ML::switch_locale($EM_Booking->language);
		    }
	    }
    }
	
	
	/**
	 * Reverts the current language in case it was changed temporarily during email sending.
	 * @param string $output
	 * @param EM_Booking $EM_Booking
	 * @return boolean
	 */
    public static function em_booking_output( $output, $EM_Booking ){
	    if( !static::$displaying_locale && $EM_Booking->language ){
	    	// we can run this knowing that if locale wasnt switched previously it won't proceed with switching anything
		    EM_ML::restore_locale();
	    }
	    return $output;
    }
	
	public static function em_booking_email_after_send( $EM_Booking ){
    	if( static::$displaying_locale ){
		    EM_ML::restore_locale();
		    static::$displaying_locale = false;
	    }
	}
    
    /**
     * @param EM_Booking $EM_Booking
     */
    public static function em_booking_get_post_pre( $EM_Booking ){
        if( empty($EM_Booking->booking_id) ){
            $EM_Booking->language = EM_ML::$current_language;
        }
    }
	
	/**
	 * Checks to see if an event is a translation and therefore references booking data from the original event. If so, EM_Bookings object
	 * is replaced with the one belonging to the original event.
	 * @param $EM_Bookings
	 * @param $EM_Event
	 * @return EM_Bookings
	 */
	public static function override_bookings($EM_Bookings, $EM_Event){
		if( !empty($EM_Event->post_id) && !in_array($EM_Event->post_id, static::$ignore_post_ids) && !EM_ML::is_original($EM_Event) ){
		    $event = EM_ML::get_original_event($EM_Event);
		    if( !empty($EM_Bookings->translated) ){
		        //we've already done this before, so we just need to make sure the event id isn't being reset to the translated event id
		        $EM_Bookings->event_id = $event->event_id;
		    }else{
		        //bookings hasn't been 'translated' yet, so we get the original event, get the EM_Bookings object and replace the current event with it. 
    			$EM_Bookings = new EM_Bookings($event);
    			$EM_Bookings->event_id = $event->event_id;
    			$EM_Bookings->translated = true;
		    }
		}
		return $EM_Bookings;
	}
	
	/**
	 * @param $EM_Event
	 */
	public static function em_booking_form_footer($EM_Event){
	    if( EM_ML::$current_language != EM_ML::$wplang || EM_ML::$current_language != EM_ML::get_the_language($EM_Event) ){
	        echo '<input type="hidden" name="em_lang" value="'.EM_ML::$current_language.'" />';
	    }
	}
	
	/**
	 * Switches the event related to this booking if a translation was booked, so that when outputting information like emails, event info shows in appropriate language.
	 * The event_id booking property should remain the original event id.
	 * @param EM_Event $EM_Event
	 * @param EM_Booking $EM_Booking
	 * @return EM_Event
	 */
	public static function em_booking_get_event($EM_Event, $EM_Booking){
	    if( EM_ML::get_the_language($EM_Event) != get_locale() ){
		    $EM_Event = EM_ML::get_translation($EM_Event, get_locale());
		    $EM_Booking->event = $EM_Event; // so that we always fire this filter each time get_event() is called
		}
	    return $EM_Event;
	}
	
	public static function em_booking_email_messages($msg, $EM_Booking){
	    //only proceed if booking was in another language AND we're not in the current language given the option is translated automatically
	    if( $EM_Booking->language && EM_ML::$current_language != $EM_Booking->language ){
	        $lang = $EM_Booking->language;
		    //get the translated event
	        $EM_Event = EM_ML::get_translation($EM_Booking->get_event(), $lang);
	        //check that we're not already dealing with the translated event
	        if( $EM_Event->post_id != $EM_Booking->get_event()->post_id ){
	            //below is copied script from EM_Booking::email_messages() replacing get_option with EM_ML_Options::get_option() supplying the booking language 
        	    switch( $EM_Booking->booking_status ){
        	    	case 0:
        	    	case 5: //TODO remove offline status from here and move to pro
        	    		$msg['user']['subject'] = EM_ML_Options::get_option('dbem_bookings_email_pending_subject', $lang);
        	    		$msg['user']['body'] = EM_ML_Options::get_option('dbem_bookings_email_pending_body', $lang);
        	    		//admins should get something (if set to)
        	    		$msg['admin']['subject'] = EM_ML_Options::get_option('dbem_bookings_contact_email_pending_subject', $lang);
        	    		$msg['admin']['body'] = EM_ML_Options::get_option('dbem_bookings_contact_email_pending_body', $lang);
        	    		break;
        	    	case 1:
        	    		$msg['user']['subject'] = EM_ML_Options::get_option('dbem_bookings_email_confirmed_subject', $lang);
        	    		$msg['user']['body'] = EM_ML_Options::get_option('dbem_bookings_email_confirmed_body', $lang);
        	    		//admins should get something (if set to)
        	    		$msg['admin']['subject'] = EM_ML_Options::get_option('dbem_bookings_contact_email_confirmed_subject', $lang);
        	    		$msg['admin']['body'] = EM_ML_Options::get_option('dbem_bookings_contact_email_confirmed_body', $lang);
        	    		break;
        	    	case 2:
        	    		$msg['user']['subject'] = EM_ML_Options::get_option('dbem_bookings_email_rejected_subject', $lang);
        	    		$msg['user']['body'] = EM_ML_Options::get_option('dbem_bookings_email_rejected_body', $lang);
        	    		//admins should get something (if set to)
        	    		$msg['admin']['subject'] = EM_ML_Options::get_option('dbem_bookings_contact_email_rejected_subject', $lang);
        	    		$msg['admin']['body'] = EM_ML_Options::get_option('dbem_bookings_contact_email_rejected_body', $lang);
        	    		break;
        	    	case 3:
        	    		$msg['user']['subject'] = EM_ML_Options::get_option('dbem_bookings_email_cancelled_subject', $lang);
        	    		$msg['user']['body'] = EM_ML_Options::get_option('dbem_bookings_email_cancelled_body', $lang);
        	    		//admins should get something (if set to)
        	    		$msg['admin']['subject'] = EM_ML_Options::get_option('dbem_bookings_contact_email_cancelled_subject', $lang);
        	    		$msg['admin']['body'] = EM_ML_Options::get_option('dbem_bookings_contact_email_cancelled_body', $lang);
        	    		break;
        	    }  
        	}
	    }
	    return $msg;
	}
	
	public static function em_bookings_admin_page(){
		global $EM_Booking; /* @var EM_Notices $EM_Notices */
		if( !empty($_REQUEST['booking_id']) && is_object($EM_Booking) ){
			if( $EM_Booking->language && EM_ML::$wplang != $EM_Booking->language ){
				$EM_Notices = new EM_Notices(false);
				require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );
				$languages = EM_ML::get_langs();
				$lang = $EM_Booking->language;
				$language = !empty($languages[$lang]) ? $languages[$lang]:$lang;
				$EM_Notices->add_info(sprintf(esc_html__('The language used to make this booking was %s', 'events-manager'), $language));
				echo $EM_Notices;
			}
		}
	}
	
	public static function em_bookings_table_rows_col($value, $col, $EM_Booking, $EM_Bookings_Table, $format, $object){
		if( $col == 'booking_language' ){
			$languages = EM_ML::get_langs();
			$lang = $EM_Booking->language ? $EM_Booking->language : EM_ML::$wplang;
			$value = !empty($languages[$lang]) ? $languages[$lang]:$lang;
		}
		return $value;
	}
	
	public static function em_bookings_table_cols_template($template, $EM_Bookings_Table){
		$template ['booking_language'] = __('Language Booked', 'events-manager');
		return $template;
	}
}
EM_ML_Bookings::init();