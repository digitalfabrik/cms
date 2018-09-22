<?php
class EM_People extends EM_Object {
	
	public static function init(){
		add_action('delete_user', 'EM_People::delete_user', 10, 1);
		add_filter( 'user_contactmethods', 'EM_People::user_contactmethods', 10, 1);
		add_filter('pre_option_dbem_bookings_registration_user', 'EM_People::dbem_bookings_registration_user');
	}
	
	/**
	 * Handles the action of someone being deleted on WordPress
	 * @param int $id
	 */
	public static function delete_user( $id ){
		global $wpdb;
		//if events are set to be deleted, we hook in correctly already, if they're meant to be reassigned, we only need to update our tables as WP updated theirs
		if( $_REQUEST['delete_option'] == 'reassign' && is_numeric($_REQUEST['reassign_user']) ){
			$wpdb->update(EM_EVENTS_TABLE, array('event_owner'=>$_REQUEST['reassign_user']), array('event_owner'=>$id));
			$wpdb->update(EM_LOCATIONS_TABLE, array('location_owner'=>$_REQUEST['reassign_user']), array('location_owner'=>$id));
		}else{
			//We delete all the events and locations owned by this user
			foreach( EM_Events::get( array('owner'=>$id, 'status'=>'everything') ) as $EM_Event ) $EM_Event->delete();
			foreach( EM_Locations::get( array('owner'=>$id, 'status'=>'everything', 'ids_only'=>true) ) as $EM_Location ) $EM_Location->delete();
		}
		//delete their bookings completely
		//@TODO allow option to reassign bookings in a sensible way (i.e. handle personal data being transferred)
	    $EM_Person = new EM_Person();
	    $EM_Person->ID = $EM_Person->person_id = $id;
	    foreach( $EM_Person->get_bookings() as $EM_Booking){
	        $EM_Booking->manage_override = true;
	        $EM_Booking->delete();
	    }
	}
	
	/**
	 * Adds phone number to contact info of users, compatible with previous phone field method
	 * @param $array
	 * @return array
	 */
	public static function user_contactmethods($array){
		$array['dbem_phone'] = __('Phone','events-manager') . ' <span class="description">('. __('Events Manager','events-manager') .')</span>';
		return $array;
	}
	
	/**
	 * Workaround function for any legacy code requesting the dbem_bookings_registration_user option which should always be 0
	 * @return int
	 */
	public static function dbem_bookings_registration_user(){
		return 0;		
	}
}
EM_People::init();
?>