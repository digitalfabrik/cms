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
class EM_ML_IO_Locations {
	
	public static function init(){
		//Saving/Editing
		add_filter('em_location_get_post_meta','EM_ML_IO_Locations::location_get_post_meta',10,2);
		add_action('em_location_save_meta_pre', 'EM_ML_IO_Locations::location_save_meta_pre', 10, 1);
		//Deletion
		add_action('em_location_delete_meta_pre', 'EM_ML_IO_Locations::location_delete_meta_pre', 10, 1);
	}
	
	/**
	 * Merge shared translation meta from original $location into $EM_Location.
	 * @param EM_Location $EM_Location The location to merge original meta into.
	 * @param EM_Location $location The original location language.
	 */
	public static function location_merge_original_meta( $EM_Location, $location ){
		$EM_Location->location_parent = $location->location_id;
		$EM_Location->location_translation = 1;
		//set values over from original location
		if( empty($EM_Location->location_address) ) $EM_Location->location_address = $location->location_address;
		if( empty($EM_Location->location_town) ) $EM_Location->location_town = $location->location_town;
		if( empty($EM_Location->location_state) ) $EM_Location->location_state = $location->location_state;
		if( empty($EM_Location->location_region) ) $EM_Location->location_region = $location->location_region;
		$EM_Location->location_postcode = $location->location_postcode;
		$EM_Location->location_country = $location->location_country;
		$EM_Location->location_latitude = $location->location_latitude;
		$EM_Location->location_longitude = $location->location_longitude;
		self::location_merge_original_attributes($EM_Location, $location);
	}
	
	public static function location_merge_original_attributes($EM_Location, $location){
		//merge attributes
		$location->location_attributes = maybe_unserialize($location->location_attributes);
		$EM_Location->location_attributes = maybe_unserialize($EM_Location->location_attributes);
		foreach($location->location_attributes as $attribute_key => $attribute){
			if( !empty($attribute) && empty($EM_Location->location_attributes[$attribute_key]) ){
				$EM_Location->location_attributes[$attribute_key] = $attribute;
			}
		}
	}
	
	/**
	 * Hooks into em_location_get_post_meta and assigns location info from original translation so other translations don't manage location-specific info.
	 * @param boolean $result
	 * @param EM_Location $EM_Location
	 * @param boolean $validate
	 * @return boolean
	 */
	public static function location_get_post_meta($result, $EM_Location, $validate = true){
		//check if this is a master location, if not then we need to get the relevant master location info and populate this object with it so it passes validation and saves correctly.
		if( !EM_ML::is_original($EM_Location) ){
			//get original location object
			$location = EM_ML::get_original_location($EM_Location);
			self::location_merge_original_meta($EM_Location, $location);
			if ($validate) $result = $EM_Location->validate();
		}
		return $result;
	}
	
	/**
	 * Merges original information back from original or synchronizes address changes to translations where the address lines aren't overriden.
	 * Unlike with events, we do this all in the _pre action because we need access to address data before it's saved to the DB so we can detect address changes.
	 * @param EM_Location $EM_Location
	 */
	public static function location_save_meta_pre( $EM_Location ){
		$EM_Location->location_language = EM_ML::get_the_language( $EM_Location ); //do this early on so we know the language we're dealing with
		// save parent info about this location
		if( !EM_ML::is_original( $EM_Location ) ){
			$location = EM_ML::get_original( $EM_Location );
			static::location_merge_original_meta($EM_Location, $location); //in case it didnt' go through get_post
		}else{
			// we need to search translations of this location and update untranslated address fields which are still stored in the translation record
			global $wpdb;
			$location_data = $wpdb->get_row( $wpdb->prepare('SELECT location_address, location_town, location_state, location_region FROM '.EM_LOCATIONS_TABLE.' WHERE location_id=%d', $EM_Location->location_id), ARRAY_A );
			//loop through translations, check for original non-translated strings and replace with updated ones
			if( is_array($location_data) ){
				foreach( EM_ML::get_location_translations( $EM_Location ) as $language => $location ){ /* @var EM_Location $location */
					if( $language == $EM_Location->location_language ) continue;
					foreach( $location_data as $k => $v ){
						if( $EM_Location->$k != $v && $location->$k == $v ){
							$location->$k = $EM_Location->$k;
						}
					}
					$location->save_meta();
				}
			}
		}
	}
	
	/**
	 * When a master location is deleted, translations are not necessarily deleted so things like event-location linkage must be transferred to a translation and that must now be the master event.
	 * @param EM_Location $EM_Location
	 */
	public static function location_delete_meta_pre($EM_Location){
		global $wpdb;
		if( EM_ML::is_original($EM_Location) ){
			//check to see if there's any translations of this location
			$location_translations = EM_ML::get_translations($EM_Location);
			foreach( $location_translations as $language => $location_translation ){
				if( $language != $EM_Location->location_language ){
					if( empty($location) ) $location = $location_translation; // first available translation
					// provide the default language if not the original language
					if( $language == EM_ML::$wplang ){
						$location = $location_translation;
						break;
					}
				}
			}
			//if so check if the default language still exists
			if( !empty($location->location_id) && $EM_Location->location_id != $location->location_id ){
				//make that translation the master event by changing event ids of bookings, tickets etc. to the new master event
				$wpdb->update(EM_EVENTS_TABLE, array('location_id'=>$location->location_id), array('location_id'=>$EM_Location->location_id));
				//also change wp_postmeta
				$EM_Location->ms_global_switch();
				$wpdb->update($wpdb->postmeta, array('meta_value'=>$location->location_id), array('meta_key'=>'_location_id', 'meta_value'=>$EM_Location->location_id));
				// update the location parents of any translations
				$wpdb->update(EM_LOCATIONS_TABLE, array('location_parent'=>$location->location_id), array('location_parent'=>$EM_Location->location_id));
				$wpdb->update(EM_LOCATIONS_TABLE, array('location_parent'=>null, 'location_translation'=>0), array('location_id'=>$location->location_id));
				$wpdb->update($wpdb->postmeta, array('meta_value'=>$location->location_id), array('meta_key'=>'_location_parent', 'meta_value'=>$EM_Location->location_id));
				delete_post_meta($location->post_id, '_location_parent');
				delete_post_meta($location->post_id, '_location_translation');
				$EM_Location->ms_global_switch_back();
				do_action('em_ml_transfer_original_location', $location, $EM_Location); //other add-ons with tables with location_id foreign keys should hook here and change
			}
		}
	}
}
EM_ML_IO_Locations::init();