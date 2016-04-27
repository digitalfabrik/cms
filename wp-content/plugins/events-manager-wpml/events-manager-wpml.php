<?php
/*
Plugin Name: Events Manager and WPML Compatibility
Version: 1.0.1
Plugin URI: http://wp-events-plugin.com
Description: Integrates the Events Manager and WPML plugins together to provide a smoother multilingual experience (EM and WPML also needed)
Author: Marcus Sykes
Author URI: http://wp-events-plugin.com
*/

/*
Copyright (c) 2015, Marcus Sykes

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

/*
NUANCES

Certain things can't happen without twaks or just can't happen at all
- Recurring events
	- Recurring Events can't be translated when editing the recurrence template, they must be done one by one i.e. at single event level
	- Recurring events are disabled by default due to the above
- Bookings
	- customn booking forms aren't translated
- Location Searching
	- currently autocompleter forces searches for current languages, we may want to change this in the future to search all languages but give precedence to showing the translated version if available
- Slugs
	- not translated, but this might not be something we should control
- More unforseen issues
	- Both Events Manager and WPML are very complex plugin which hook into loads of places within WordPress to make things happen. That means a big combination of things to test, therefore many combinations may have been missed which result in unexpected behaviour. Please let us know of any other nuances you come across and we'll do our best to fix them as time permits.

EXTRA INSTALLATION STEPS

To close some gaps, extra steps are needed
- Pages
	- You should translate pages that EM overrides into different languages, meaning the pages you choose from our Events > Settings > Pages tab in the various panels, such as:
		- events page
		- locations page
		- categories page
		- edit events page
		- edit locations page
		- edit bookings page
		- my bookings page
 */

//TODO booking form override for translated languages
//TODO remove meta boxes on translations, times, locations, ect. remain the same across events
//TODO think about what to do with booking form content r.e. translations

//TODO better way of linking translated events to translations of locations

//TODO what happens if you create a language first in a second languge?

define('EM_WPML_VERSION','0.3.3');

//stores all master event info within a script run, to save repetitive db calls e.g. within an event format output operation.
$em_wpml_translation_index = array();
$em_wpml_original_event_ids_cache = array();
$em_wpml_original_cache = array();

class EM_WPML{
    public static function init(){
	    if( !class_exists('SitePress') || !defined('EM_VERSION') ) return; //only continue of both EM and WPML are activated
	    
	    //check installation
	    if( version_compare(EM_WPML_VERSION, get_option('em_wpml_version')) ){
	        em_wpml_activate();
	    }
		
		//EM_ML filters - register translateable languages and currently displayed language
		add_filter('em_ml_langs','EM_WPML::em_ml_langs');
		add_filter('em_ml_wplang','EM_WPML::em_ml_wplang');
		add_filter('em_ml_current_language','EM_WPML::em_ml_current_language');
		
		//original event/location filters
		add_filter('em_ml_get_original','EM_WPML::get_original',10,1);
		add_filter('em_ml_is_original','EM_WPML::is_original',10,2);

		//other functions
		add_filter('em_ml_get_translated_post_id','EM_WPML::get_translated_post_id',10,2);
		add_filter('em_ml_get_the_language','EM_WPML::get_the_language',10,2);
		add_filter('em_ml_get_translation_id','EM_WPML::get_translation_id',10,2);
	    
	    //continue initialization
	    if( is_admin() ){
	        include('em-wpml-admin.php');
	        include('em-wpml-permalinks.php'); //don't think we need this outside of admin, only when permalinks are rewritten
	    }
	    include('em-wpml-io.php');
	    include('em-wpml-search.php');
	    if( get_option('dbem_categories_enabled') || get_option('dbem_tags_enabled') ) include('em-wpml-taxonomies.php');
		
		//force disable recurring events
		if( !defined('EM_WMPL_FORCE_RECURRENCES') || !EM_WMPL_FORCE_RECURRENCES ){
			add_filter('option_dbem_recurrence_enabled', create_function('$value', 'return false;'));
		}		
		//rewrite language switcher links for our taxonomies if overriden with formats, because EM forces use of a page template by modifying the WP_Query object
		//this confuses WPML since it checks whether WP_Query is structured to show a taxonomy page
		add_filter('icl_ls_languages','EM_WPML::icl_ls_languages');
		
		//add js vars that override EM's 
		add_filter('em_wp_localize_script', 'EM_WPML::em_wp_localize_script', 100);
    }
    
    /**
     * Localizes the script variables
     * @param array $em_localized_js
     * @return array
     */
    public static function em_wp_localize_script($em_localized_js){
        global $sitepress;
        $em_localized_js['ajaxurl'] = add_query_arg(array('lang'=>$sitepress->get_current_language()), $em_localized_js['ajaxurl']);
        $em_localized_js['locationajaxurl'] = add_query_arg(array('lang'=>$sitepress->get_current_language()), $em_localized_js['locationajaxurl']);;
		if( get_option('dbem_rsvp_enabled') ){
		    $em_localized_js['bookingajaxurl'] = add_query_arg(array('lang'=>$sitepress->get_current_language()), $em_localized_js['bookingajaxurl']);
		}
        return $em_localized_js;
    }
    
    /**
     * Hooks into icl_ls_languages and fixes links for when viewing an events list page specific to a calendar day.
     * @param array $langs
     * @return array
     */
    public static function icl_ls_languages($langs){
        global $wp_rewrite;
        //modify the URL if we're dealing with calendar day URLs
        if ( !empty($_REQUEST['calendar_day']) && preg_match('/\d{4}-\d{2}-\d{2}/', $_REQUEST['calendar_day']) ) { 
            $query_args = EM_Calendar::get_query_args( array_intersect_key(EM_Calendar::get_default_search($_GET), EM_Events::get_post_search($_GET, true) ));
            if( $wp_rewrite->using_permalinks() ){
                //if using rewrites, add as a slug
                foreach( $langs as $lang => $lang_array ){
                    $lang_url_parts = explode('?', $lang_array['url']);
                    $lang_url_parts[0] = trailingslashit($lang_url_parts[0]). $_REQUEST['calendar_day'].'/';
                    $langs[$lang]['url'] = esc_url_raw(add_query_arg($query_args, implode('?', $lang_url_parts)));
                }
            }else{
                $query_args['calendar_day'] = $_REQUEST['calendar_day'];
                foreach( $langs as $lang => $lang_array ){
                    $langs[$lang]['url'] = esc_url_raw(add_query_arg($query_args, $lang_array['url']));
                }
            }   
        }
        return $langs;
    }
    
    /**
     * Takes a post id, checks if the current language isn't the default language and returns a translated post id if it exists, used to switch our overriding pages or post types
     * @param int $post_id
     * @return int
     */    
    public static function get_translated_post_id($post_id, $post_type){
        if( function_exists('wpml_object_id_filter') ) return wpml_object_id_filter($post_id, $post_type); //3.2 compatible
        return icl_object_id($post_id, $post_type); // <3.2 compatible
    }
	
	/**
	 * Filters the language and provides the language used in $object in the WPLANG compatible format
	 * @param string $lang language code passed by filter (default_locale)
	 * @param EM_Event|EM_Location $object
	 * @return string language code
	 */
	public static function get_the_language($lang, $object){
	    global $sitepress;
	    if( !empty($object) ){
	        $sitepress_langs = $sitepress->get_active_languages();
	        $sitepress_lang = $sitepress->get_language_for_element($object->post_id, 'post_'.$object->post_type);
	        $lang = !empty($sitepress_lang) ? $sitepress_langs[$sitepress_lang]['default_locale'] : EM_ML::$wplang ;
	    }
	    return $lang;
	}
	
    /**
     * Gets the post id of the default translation for this event, location or post. It'll return the same post_id if no translation is available.
     * If $language is false, we return a different translation in precedence of the original id, default language id, or next available. 
     * @param EM_Event|EM_Location|WP_Post $object
     * @param string $lang
     * @return int
     * 
     */
    public static function get_translation_id( $object, $lang = false ){
        global $sitepress, $wpdb;
        if( !empty($object->post_id) && !empty($object->post_type) ){
            //clean $type to include post_ prefix for WPML
            $wpml_post_type = in_array( $object->post_type, array(EM_POST_TYPE_EVENT, EM_POST_TYPE_LOCATION) ) ? 'post_'.$object->post_type:$object->post_type;
    	    //get WPML code from locale we have been provided and then find the relevant translation
    	    $lang_code = $lang  ? $wpdb->get_var( $wpdb->prepare( "SELECT code FROM {$wpdb->prefix}icl_languages WHERE default_locale=%s", $lang ) ):false;
    	    //get translations for this object and loop through them
    	    $default_language = $sitepress->get_default_language();
    	    $trid = $sitepress->get_element_trid($object->post_id, $wpml_post_type);
    	    $translations = $sitepress->get_element_translations($trid, $wpml_post_type);
    	    //return translation
    	    if( !empty($lang_code) && !empty($translations[$lang_code]->element_id) ){
    	        //if we were supplied a language to search for, check if it's available, if not continue searching
    	        return $translations[$lang_code]->element_id;
    	    }elseif( empty($lang) ){
    	        //if no language was supplied we just get the next available translation
    	        //try and find the original language if there is one that's not this object we're requesting
        	    foreach( $translations as $translation ){
    				if( $translation->element_id != $object->post_id && $translation->original ){
    					//if not, use the first available translation we find
    					return $translation->element_id;
    				}
        	    }
        	    //otherwise, give preference to default language, then any other language
    	        if( !empty($translations[$default_language]) && $translations[$default_language]->element_id != $object->post_id ){
        	        //default language 
        	        return $translations[$default_language]->element_id;
        	    }else{
        	        //find the next translation we can find
            	    foreach( $translations as $translation ){
        				if( $translation->element_id != $object->post_id ){
        					//if not, use the first available translation we find
        					return $translation->element_id;
        				}
            	    }
        	    }
    	    }
    	    //if we reached here there's no translation
    	    return $object->post_id;
        }
        return 0;
    }
	
	/**
	 * Checks if this EM_Event or EM_Location object is the 'original'.
	 * @param bool                             Value passed by filter, will always be true.
	 * @param EM_Event|EM_Location $object     Must be EM_Event or EM_Location
	 * @return boolean
	 */
	public static function is_original( $return, $object ){
		global $em_wpml_original_cache, $pagenow;
		//if we're in admin, we need to check if we're adding an new WPML translation
    	if( is_admin() ){
    	    //check we are adding a new translation belonging to a trid set
    	    if( $pagenow == 'post-new.php' && !empty($_REQUEST['trid']) ) return false;
            //check if a translation is being submitted
            if( $pagenow == 'post.php' && !empty($_REQUEST['icl_translation_of']) ) return false;
		}
		//if we got this far, check that $object has a post_id as EM_Event and EM_Location would have, and get the original translation via WPML
		if( !empty($object->post_id) ){
		    if( !empty($em_wpml_original_cache[$object->blog_id][$object->post_id]) ){
		        $original_post_id = $em_wpml_original_cache[$object->blog_id][$object->post_id];
		    }else{
		        $original_post_id = SitePress::get_original_element_id($object->post_id, 'post_'.$object->post_type);   
		    }
		    //save original ID if not a false value
		    if( $original_post_id !== false ) $em_wpml_original_cache[$object->blog_id][$object->post_id] = $original_post_id;
		    //return whether this is the original post or not
		    return $original_post_id == $object->post_id || $original_post_id === false;
		}
		return true;
	}
	
	/**
	 * Returns the original EM_Location object from the provided EM_Location object
	 * @param EM_Location|EM_Event $object
	 * @return EM_Location|EM_Event
	 */
	public static function get_original( $object ){
	    global $em_wpml_original_cache, $pagenow;
        if( !empty($em_wpml_original_cache[$object->blog_id][$object->post_id]) ){
            //we have done this before....
            $original_post_id = $em_wpml_original_cache[$object->blog_id][$object->post_id]; //retrieve cached ID
        }else{
            //find the original post id via WPML
            $original_post_id = SitePress::get_original_element_id($object->post_id, 'post_'.$object->post_type);
            //check a few admin specific stuff if a standard check didn't work, in case we're in the admin area translating via WPML
            if( empty($original_post_id) && is_admin() ){
                if( !empty($_REQUEST['trid']) ){
        			//we are adding a new translation belonging to a trid set
        			$original_post_id = SitePress::get_original_element_id_by_trid($_REQUEST['trid']);
                }elseif( !empty($_REQUEST['icl_translation_of']) ){
                    //a new translation has just been submitted
                    $translation_of = $_REQUEST['icl_translation_of']; //could be a translation from another translation, e.g. try adding a translation from a second language
                    $original_post_id = SitePress::get_original_element_id($translation_of, 'post_'.$object->post_type);
                }
    		}
        }
        //save to the cache (whether already saved or not)
        $em_wpml_original_cache[$object->blog_id][$object->post_id] = $original_post_id;
        //if the post_ids don't match then the original translation is different to the one passed on, so switch the $object to that translation
        if( $original_post_id != $object->post_id ){
            //get the EM_Event or EM_Location object
            if( $object->post_type == EM_POST_TYPE_EVENT ){
                $object = em_get_event($original_post_id, 'post_id');   
            }elseif( $object->post_type == EM_POST_TYPE_LOCATION ){
                $object = em_get_location($original_post_id, 'post_id');
            }
        }
	    return $object;
	}
	
	/*
	 * EM_ML hooks - register available languages and current language displayed
	 */
	
	/**
	 * Provides an array of languages that are translateable by WPML
	 * @return array In the form of locale => language name e.g. array('fr_FR'=>'French');
	 */
	public static function em_ml_langs(){
		global $sitepress, $wpdb;
		$sitepress_langs = $sitepress->get_active_languages();
		$langs = array();
		foreach($sitepress_langs as $lang){
			$langs[$lang['default_locale']] = $lang['display_name'];
		}
		return $langs;
	}
	
	/**
	 * Returns the default language locale
	 * @return string
	 */
	public static function em_ml_wplang(){
		global $sitepress,$wpdb;
		$sitepress_langs = $sitepress->get_active_languages();
		$sitepress_lang = $sitepress->get_default_language();
		if( !empty($sitepress_langs[$sitepress_lang]) ){
		    return $sitepress_langs[$sitepress_lang]['default_locale'];
		}else{
		    return get_locale();
		}
	}
	
	/**
	 * Returns the current language locale
	 * @return string
	 */
	public static function em_ml_current_language(){
	    global $sitepress;
		$sitepress_langs = $sitepress->get_active_languages();
		$sitepress_lang = $sitepress->get_current_language();
		return $sitepress_langs[$sitepress_lang]['default_locale'];
	}
}
add_action('wpml_after_init', 'EM_WPML::init'); //should be before init priority 10 which is when EM_ML loads

function em_wpml_activate() {
	include_once(dirname( __FILE__ ).'/em-wpml-install.php');
}
register_activation_hook( __FILE__,'em_wpml_activate');