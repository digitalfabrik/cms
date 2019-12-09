<?php
/*
 * This file deals with new privacy tools included in WP 4.9.6 and in line with aiding users with conforming to the GPDR rules.
 * Note that consent mechanisms are not included here and will be baked directly into the templates or Pro booking forms.
 */
class EM_Data_Privacy {

	public static function init(){
		add_action( 'admin_init', 'EM_Data_Privacy::privacy_policy_content' );
		add_filter( 'wp_privacy_personal_data_erasers', 'EM_Data_Privacy::register_eraser', 10 );
		add_filter( 'wp_privacy_personal_data_exporters', 'EM_Data_Privacy::register_exporter', 10 );
		add_action( 'wp_privacy_personal_data_export_file_created', 'EM_Data_Privacy::export_cleanup');
	}

	public static function privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = array();
		$content[] = sprintf(
			__('We use Google services to generate maps and provide autocompletion when searching for events by location, which may collect data via your browser in accordance to Google\'s <a href="%s">privacy policy</a>.', 'events-manager' ),
			'https://policies.google.com/privacy'
		);
		$content[] = __('We collect and store information you submit to us when making a booking, for the purpose of reserving your requested spaces at our event and maintaining a record of attendance.', 'events-manager' );
		$content[] = __('We collect and store information you submit to us about events (and corresponding locations) you would like to publish on our site.', 'events-manager' );
		$content[] = __('We may use cookies to temporarily store information about a booking in progress as well as any error/confirmation messages whilst submitting or managing your events and locations.', 'events-manager' );

		wp_add_privacy_policy_content(
			__('Events Manager', 'events-manager'),
			wp_kses_post( '<p>'. implode('</p><p>', $content) .'</p>' )
		);
	}

	public static function register_eraser( $erasers ) {
		if( get_option('dbem_data_privacy_erase_bookings') ){
			$erasers['events-manager-bookings'] = array(
				'eraser_friendly_name' =>  __( 'Events Manager', 'events-manager' ) . ' - ' . __('Bookings', 'events-manager'),
				'callback' => 'EM_Data_Privacy::erase_bookings',
			);
		}
		if( get_option('dbem_data_privacy_erase_events') ){
			$erasers['events-manager-recurring-events'] = array(
				'eraser_friendly_name' =>  __( 'Events Manager', 'events-manager' ) . ' - ' . __('Recurring Events', 'events-manager'),
				'callback' => 'EM_Data_Privacy::erase_recurring_events',
			);
			$erasers['events-manager-events'] = array(
				'eraser_friendly_name' =>  __( 'Events Manager', 'events-manager' ) . ' - ' . __('Events', 'events-manager'),
				'callback' => 'EM_Data_Privacy::erase_events',
			);
		}
		if( get_option('dbem_data_privacy_erase_locations') ){
			$erasers['events-manager-locations'] = array(
				'eraser_friendly_name' =>  __( 'Events Manager', 'events-manager' ) . ' - ' . __('Locations', 'events-manager'),
				'callback' => 'EM_Data_Privacy::erase_locations',
			);
		}
		//in this case we don't register location deletion because we only need to handle anonymous events, locations submitted anonymously shouldn't have any personal data associated with it (unless a user submitted their home address anonymously!)
		return $erasers;
	}

	public static function erase_bookings( $email_address, $page = 1 ) {
		$page = (int) $page;
		$limit = apply_filters('em_data_privacy_export_limit', 100);
		$messages = array();
		$user = get_user_by('email', $email_address); //is user or no-user?

		if( $user !== false && get_option('dbem_data_privacy_erase_bookings') == 2 ){
			//we're only deleting anonymous bookings, and letting WP handle user/booking deletion the traditional way for registered accounts
			$done = true;
		}else{
			//get items to erase
			$booking_ids = self::get_bookings($email_address, $page);

			$items_removed = $items_retained = false;
			foreach ( $booking_ids as $booking_id ) {
				$EM_Booking = em_get_booking($booking_id);
				if( $EM_Booking->delete() ){
					$items_removed = true;
				}else{
					$items_retained = true;
					$messages = array_merge($messages, $EM_Booking->get_errors());
				}
			}
			if( $items_removed ) add_action('em_data_privacy_bookings_deleted', $booking_ids);

			// Tell core if we have more comments to work on still
			$done = count( $booking_ids ) < $limit;
		}
		return array(
			'items_removed' => $items_removed,
			'items_retained' => $items_retained, // always false in this example
			'messages' => $messages, // no messages in this example
			'done' => $done,
		);
	}

	public static function erase_recurring_events( $email_address, $page = 1 ) {
		return self::erase_events( $email_address, $page, 'event-recurring');
	}

	public static function erase_events( $email_address, $page = 1, $post_type = false ) {
		$user = get_user_by('email', $email_address); //is user or no-user?
		if( !$post_type ) $post_type = EM_POST_TYPE_EVENT; //default to event
		$page = (int) $page;
		$limit = apply_filters('em_data_privacy_erase_limit', 100);
		$items_removed = $items_retained = false;
		$messages = array();

		if( $user !== false && get_option('dbem_data_privacy_erase_events') == 2 ){
			//we're only deleting anonymous events, and letting WP handle CPT deletion the traditional way for registered accounts
			$done = true;
		}else{
			//get event IDs submitted by user or "anonymously" by email
			$events = self::get_cpts($email_address, $page, $post_type);

			foreach( $events as $post_id ){
				$EM_Event = em_get_event($post_id, 'post_id');
				//erase the location first
				$EM_Location = $EM_Event->get_location();
				if( $EM_Location->location_id && get_option('dbem_data_privacy_erase_locations', 2) ){
					//prior to 5.9.3 locations submitted alongside anonymous events didn't store email info, so for older locations we can only assume if the location is guest submitted and only linked to this one event
					$user_probably_owns_location = $user === false && empty($EM_Location->owner_email) && $EM_Location->location_owner == get_option('dbem_events_anonymous_user');
					if( $user_probably_owns_location ){
						//depending on settings delete location only if it has no other events (eventually if we're deleting the last event of this user with the same location, it'll only have one event)
						if( get_option('dbem_data_privacy_erase_locations', 2) == 1 ||  EM_Events::count(array('location_id' => $EM_Location->location_id, 'status' => 'all')) <= 1 ){
							if( $EM_Location->delete(true) ){
								$items_removed = true;
							}else{
								$items_retained = true;
							}
						}
					}
				}
				//now erase the event
				if( $EM_Event->delete(true) ){
					$items_removed = true;
				}else{
					$items_retained = true;
					$messages = array_merge($messages, $EM_Event->get_errors());
				}
			}
			// Tell core if we have more comments to work on still
			$done = count( $events ) < $limit;
		}
		return array(
			'items_removed' => $items_removed,
			'items_retained' => $items_retained, // always false in this example
			'messages' => $messages, // no messages in this example
			'done' => $done,
		);
	}

	public static function erase_locations( $email_address, $page = 1 ) {
		$page = (int) $page;
		$limit = apply_filters('em_data_privacy_erase_limit', 100);
		$items_removed = $items_retained = false;
		$messages = array();
		
		$locations_count = 0;
		$locations = self::get_cpts($email_address, $page, EM_POST_TYPE_LOCATION);
		foreach( $locations as $post_id ){
			$EM_Location = em_get_location( $post_id, 'post_id' ); /* @var EM_Location $EM_Location */
			if( $EM_Location->delete(true) ){
				$items_removed = true;
			}else{
				$items_retained = true;
				$messages = array_merge($messages, $EM_Location->get_errors());
			}
			$locations_count++;
		}
		// Tell core if we have more comments to work on still
		$done = $locations_count < $limit;
		return array(
			'items_removed' => $items_removed,
			'items_retained' => $items_retained, // always false in this example
			'messages' => $messages, // no messages in this example
			'done' => $done,
		);
	}

	public static function register_exporter( $exporters ) {
		$exporters['events-manager-user'] = array(
			'exporter_friendly_name' =>  __( 'Events Manager', 'events-manager' ) . ' - ' .__( 'Further Information', 'events-manager' ),
			'callback' => 'EM_Data_Privacy::export_user',
		);
		if( get_option('dbem_data_privacy_export_bookings') ){
			$exporters['events-manager-bookings'] = array(
				'exporter_friendly_name' =>  __( 'Events Manager', 'events-manager' ) . ' - ' . __('Bookings', 'events-manager'),
				'callback' => 'EM_Data_Privacy::export_bookings',
			);
		}
		if( get_option('dbem_data_privacy_export_events') ){
			$exporters['events-manager-recurring-events'] = array(
				'exporter_friendly_name' =>  __( 'Events Manager', 'events-manager' ) . ' - ' . __('Recurring Events', 'events-manager'),
				'callback' => 'EM_Data_Privacy::export_recurring_events',
			);
			$exporters['events-manager-events'] = array(
				'exporter_friendly_name' =>  __( 'Events Manager', 'events-manager' ) . ' - ' . __('Events', 'events-manager'),
				'callback' => 'EM_Data_Privacy::export_events',
			);
		}
		if( get_option('dbem_data_privacy_export_locations') ){
			$exporters['events-manager-locations'] = array(
				'exporter_friendly_name' =>  __( 'Events Manager', 'events-manager' ) . ' - ' . __('Locations', 'events-manager'),
				'callback' => 'EM_Data_Privacy::export_locations',
			);
		}
		return $exporters;
	}

	public static function export_cleanup(){
		delete_post_meta( absint($_REQUEST['id']), '_em_locations_exported');
		delete_post_meta( absint($_REQUEST['id']), '_em_bookings_exported' );
	}

	public static function export_user( $email_address ){
		$user = get_user_by('email', $email_address); //is user or no-user?
		$export_items = array();
		if( $user !== false ){
			//we add to the WP User section
			$data_to_export[] = array(
				'group_id'    => 'user',
				'group_label' => __( 'User' ),
				'item_id'     => "user-{$user->ID}",
				'data'        => array(),
			);
			$dbem_phone = get_user_meta($user->ID, 'dbem_phone', true);
			if( !empty($dbem_phone) ){
				$export_item['data'][] = array( 'name' => __('Phone', 'events-manager'), 'value' => $dbem_phone );
			}
			$export_item = apply_filters('em_data_privacy_export_user', $export_item, $user);
			if( !empty($export_item['data']) ){
				$export_items[] = $export_item;
			}
		}
		return array(
			'data' => $export_items,
			'done' => true,
		);
	}

	public static function export_bookings( $email_address, $page = 1 ) {
		$page = (int) $page;
		$limit = apply_filters('em_data_privacy_export_limit', 100);

		$export_items = array();
		$items_count = 0;

		//check if we're only exporting bookings to those who made anonymous bookings
		$user = get_user_by('email', $email_address); //is user or no-user?
		if( $user !== false && get_option('dbem_data_privacy_export_bookings') == 2 ) return array( 'data' => $export_items, 'done' => true ); //return if user is registered and we're only exporting anon bookings

		$bookings = self::get_bookings($email_address, $page);

		$booking_export_default = array(
			'group_id' => 'events-manager-bookings',
			'group_label' => __('Bookings', 'events-manager'),
			'item_id' => 'booking-ID', //replace ID with booking ID
			'data' => array() // replace this with assoc array of name/value key arrays
		);
		$booking_price_adjustments = array(
			'discounts_pre_tax' => __('Discounts Before Taxes','events-manager'),
			'surcharges_pre_tax' => __('Surcharges Before Taxes','events-manager'),
			'discounts_post_tax' => __('Discounts (After Taxes)','events-manager'),
			'surcharges_post_tax' => __('Surcharges (After Taxes)','events-manager')
		);

		foreach ( $bookings as $booking_id ) {
			$EM_Booking = em_get_booking($booking_id);
			$export_item = $booking_export_default;
			$export_item['item_id'] = 'booking-'.$EM_Booking->booking_id;
			$export_item['data']['status'] = array('name' => __('Status','events-manager'), 'value' => $EM_Booking->get_status() );
			$export_item['data']['date'] = array('name' => __('Date','events-manager'), 'value' => $EM_Booking->date()->getDateTime() . ' ' . $EM_Booking->date()->getTimezone()->getName() );
			$export_item['data']['event'] = array('name' => __('Event','events-manager'), 'value' => $EM_Booking->get_event()->output('#_EVENTLINK - #_EVENTDATES @ #_EVENTTIMES') );
			if( $EM_Booking->person_id == 0 ){
				foreach( $EM_Booking->get_person()->get_summary() as $key => $info ){
					$export_item['data']['field-'.$key] = $info;
				}
			}
			$booking_tickets = array();
			foreach($EM_Booking->get_tickets_bookings()->tickets_bookings as $EM_Ticket_Booking){ /* @var EM_Ticket_Booking $EM_Ticket_Booking */
				$booking_tickets[] = $EM_Ticket_Booking->get_ticket()->ticket_name . ' x '. $EM_Ticket_Booking->get_spaces() . ' @ ' . $EM_Ticket_Booking->get_price(true);
			}
			$export_item['data']['tickets'] = array('name' => __('Tickets', 'events-manager-pro'), 'value' => implode('<br>', $booking_tickets));
			$export_item['data']['sub-total'] = array('name' => __('Sub Total','events-manager'), 'value' => $EM_Booking->get_price_base(true));
			$price_summary = $EM_Booking->get_price_summary_array();
			foreach( $booking_price_adjustments as $adjustment_key => $adjustment_title ){
				if( count($price_summary[$adjustment_key]) > 0 ){
					$adjustments = array();
					foreach( $price_summary[$adjustment_key] as $adjustment ){
						$adjustments[] = "{$adjustment['name']} @ {$adjustment['amount']}";
					}
					$export_item['data'][$adjustment_key] = array('name' => $adjustment_title, 'value' => implode('<br>', $adjustments));
				}
			}
			if( !empty($price_summary['taxes']['amount']) ){
				$export_item['data']['taxes'] = array('name' => __('Taxes','events-manager'), 'value' => "({$price_summary['taxes']['rate']}) {$price_summary['taxes']['amount']}");
			}
			$export_item['data']['total'] = array('name' => __('Total Price','events-manager'), 'value' => $price_summary['total']);
			//booking notes - can be exempt with a filter since maybe notes have private info
			if( apply_filters('em_data_privacy_export_bookings_include_notes', true, $EM_Booking) ){
				$booking_notes = $EM_Booking->get_notes();
				if( !empty($booking_notes) ){
					$booking_notes_data = array();
					foreach( $booking_notes as $booking_note ){
						$booking_notes_data[] = date(get_option('date_format'), $booking_note['timestamp']) .' - '. $booking_note['note'];
					}
					$export_item['data']['notes'] = array('name' => __( 'Booking Notes', 'events-manager'), 'value' => implode('<br><br>', $booking_notes_data));
				}
			}
			$export_item = apply_filters('em_data_privacy_export_bookings_item', $export_item, $EM_Booking);
			$export_items[] = $export_item;
			$export_items = apply_filters('em_data_privacy_export_bookings_items_after_item', $export_items, $export_item, $EM_Booking); //could be used for cross-referencing and add-ing other groups e.g. Multiple Bookings in Pro
			$items_count++;
			if( $items_count == $limit ) break;
		}

		$done = $items_count < $limit; //if we didn't reach limit of bookings then we must be done
		return array(
			'data' => $export_items,
			'done' => $done,
		);
	}

	public static function export_recurring_events( $email_address, $page = 1){
		return self::export_events( $email_address, $page, 'event-recurring' );
	}

	public static function export_events( $email_address, $page = 1, $post_type = false ) {
		if( !$post_type ) $post_type = EM_POST_TYPE_EVENT; //default to event
		$page = (int) $page;
		$limit = apply_filters('em_data_privacy_export_limit', 100);
		$user = get_user_by('email', $email_address); //is user or no-user?
		$export_items = array();
		$items_count = 0;

		//check if we're only exporting events to those who submitted anonymously
		if( $user !== false && get_option('dbem_data_privacy_export_events') == 2 ) return array( 'data' => $export_items, 'done' => true ); //return if user is registered and we're only exporting anon events

		//prepare some location stuff for use within events
		$locations_export_default = array(
			'group_id' => 'events-manager-'.EM_POST_TYPE_LOCATION,
			'group_label' => __('Locations', 'events-manager'),
			'item_id' => 'location-post-ID', //replace ID with booking ID
			'data' => array() // replace this with assoc array of name/value key arrays
		);
		$locations_exported = get_post_meta( absint($_REQUEST['id']), '_em_locations_exported', true);
		if( empty($locations_exported) ) $locations_exported = array();

		//EVENTS
		$event_export_default = array(
			'group_id' => 'events-manager-'.$post_type,
			'item_id' => 'event-post-ID', //replace ID with booking ID
			'data' => array() // replace this with assoc array of name/value key arrays
		);
		$event_export_default['group_label'] = $post_type == 'event-recurring' ? __('Recurring Events', 'events-manager'):__('Events', 'events-manager');

		//get event IDs submitted by user or "anonymously" by email
		$events = self::get_cpts($email_address, $page, $post_type);

		foreach( $events as $post_id ){
			$EM_Event = em_get_event($post_id, 'post_id');
			$export_item = $event_export_default;
			$export_item['item_id'] = 'event-post-'.$EM_Event->post_id;
			$export_item['data'][] = array('name' => __('Event Name','events-manager'), 'value' => $EM_Event->event_name );
			$export_item['data'][] = array('name' => sprintf(__('%s Status','events-manager'), __('Event','events-manager')), 'value' => $EM_Event->post_status );
			if( $post_type == EM_POST_TYPE_EVENT && $EM_Event->event_status == 1 ){
				$export_item['data'][] = array('name' => sprintf(__('%s URL','events-manager'), __('Event','events-manager')), 'value' => $EM_Event->get_permalink() );
			}
			if( $post_type == 'event-recurring' ){
				$export_item['data'][] = array('name' => __('When','events-manager'), 'value' => $EM_Event->output('#_EVENTDATES @ #_EVENTTIMES').'<br>'.$EM_Event->get_recurrence_description() );
			}else{
				$export_item['data'][] = array('name' => __('When','events-manager'), 'value' => $EM_Event->output('#_EVENTDATES @ #_EVENTTIMES') );
			}
			$export_item['data'][] = array('name' => __('Timezone','events-manager'), 'value' => $EM_Event->start()->getTimezone()->getName() );
			if( !empty($EM_Event->event_owner_name) ) $export_item['data'][] = array('name' => __('Name','events-manager'), 'value' => $EM_Event->event_owner_name );
			if( $EM_Event->get_location()->location_id ){
				$EM_Location = $EM_Event->get_location();
				$export_item['data'][] = array('name' => __('Location','events-manager'), 'value' => $EM_Location->location_name . ', '. $EM_Location->get_full_address() .', '. $EM_Location->location_country);
				//put this location as a new export item
				$already_exported = in_array($EM_Location->location_id, $locations_exported);
				$user_probably_owns_location = $user === false && empty($EM_Location->owner_email) && $EM_Location->location_owner == get_option('dbem_events_anonymous_user');
				$user_submitted_location = $user === false && $EM_Location->owner_email == $email_address;
				$user_owns_location = $user !== false && $EM_Location->location_owner == $user->ID;
				if( !$already_exported && ($user_owns_location || $user_submitted_location || $user_probably_owns_location) ){
					$location_export_item = $locations_export_default;
					$location_export_item['item_id'] = 'location-post-'.$EM_Location->post_id;
					$location_export_item['data'][] = array('name' => __('Name','events-manager'), 'value' => $EM_Location->location_name );
					$location_export_item['data'][] = array('name' => __('Address','events-manager'), 'value' => $EM_Location->get_full_address() .', '. $EM_Location->location_country );
					$location_export_item['data'][] = array('name' => __('Coordinates','events-manager'), 'value' => $EM_Location->location_latitude .', '. $EM_Location->location_longitude );
					$location_export_item['data'][] = array('name' => sprintf(__('%s Status','events-manager'), __('Location','events-manager')), 'value' => $EM_Location->post_status );
					if( $EM_Location->post_status == 'publish' ){
						$location_export_item['data'][] = array('name' => sprintf(__('%s URL','events-manager'), __('Location','events-manager')), 'value' => $EM_Location->get_permalink() );
					}
					foreach( $EM_Location->location_attributes as $k => $v ){
						$location_export_item['data'][] = array('name' => $k, 'value' => $v);
					}
					$location_export_item = apply_filters('em_data_privacy_export_locations_item', $location_export_item, $EM_Location);
					$export_items[] = $location_export_item;
					$export_items = apply_filters('em_data_privacy_export_locations_items_after_item', $export_items, $location_export_item, $EM_Location); //could be used for cross-referencing and add-ing other groups e.g. Multiple Bookings in Pro
					$items_count++;
					$locations_exported[] = $EM_Location->location_id;
				}
			}
			if( $EM_Event->event_rsvp ){
				$tickets = array();
				foreach( $EM_Event->get_tickets() as $EM_Ticket ){ /* @var EM_Ticket $EM_Ticket */
					$ticket = array($EM_Ticket->ticket_name, $EM_Ticket->ticket_description, $EM_Ticket->get_price(true));
					if( empty($EM_Ticket->ticket_description) ) unset($ticket[1]);
					$tickets[] = implode( ' - ',  $ticket);
				}
				$export_item['data'][] = array('name' => __('Tickets','events-manager'), 'value' => implode('<br>', $tickets) );
			}
			foreach( $EM_Event->event_attributes as $k => $v ){
				$export_item['data'][] = array('name' => $k, 'value' => $v);
			}
			$export_item = apply_filters('em_data_privacy_export_events_item', $export_item, $EM_Event);
			$export_items[] = $export_item;
			$export_items = apply_filters('em_data_privacy_export_events_items_after_item', $export_items, $export_item, $EM_Event); //could be used for cross-referencing and add-ing other groups e.g. Multiple Bookings in Pro
			$items_count++;
			if( $items_count >= $limit ) break;
		}

		$done = $items_count < $limit; //if we didn't reach limit of bookings then we must be done
		update_post_meta( absint($_REQUEST['id']), '_em_locations_exported', $locations_exported);
		return array(
			'data' => $export_items,
			'done' => $done,
		);
	}

	public static function export_locations( $email_address, $page = 1 ) {
		$page = (int) $page;
		$limit = apply_filters('em_data_privacy_export_limit', 100);
		$offset = ($page -1) * $limit;
		$user = get_user_by('email', $email_address); //is user or no-user?
		$export_items = array();
		$items_count = 0;

		$locations_export_default = array(
			'group_id' => 'events-manager-'.EM_POST_TYPE_LOCATION,
			'group_label' => __('Locations', 'events-manager'),
			'item_id' => 'location-post-ID', //replace ID with booking ID
			'data' => array() // replace this with assoc array of name/value key arrays
		);

		//Locations - previous to 5.9.4 locations submitted anonymously did nint include
		$locations_exported = get_post_meta( absint($_REQUEST['id']), '_em_locations_exported', true );
		if( empty($locations_exported) ) $locations_exported = array();
		
		$locations = self::get_cpts($email_address, $page, EM_POST_TYPE_LOCATION);
		foreach( $locations as $post_id ){
			$EM_Location = em_get_location( $post_id, 'post_id' ); /* @var EM_Location $EM_Location */
			if( !in_array($EM_Location->location_id, $locations_exported) ){
				$location_export_item = $locations_export_default;
				$location_export_item['item_id'] = 'event-post-'.$EM_Location->post_id;
				$location_export_item['data'][] = array('name' => __('Name','events-manager'), 'value' => $EM_Location->location_name );
				$location_export_item['data'][] = array('name' => sprintf(__('%s Status','events-manager'), __('Location','events-manager')), 'value' => $EM_Location->post_status );
				if( $EM_Location->post_status == 'publish' ){
					$location_export_item['data'][] = array('name' => sprintf(__('%s URL','events-manager'), __('Location','events-manager')), 'value' => $EM_Location->get_permalink() );
				}
				$location_export_item['data'][] = array('name' => __('Address','events-manager'), 'value' => $EM_Location->get_full_address() .', '. $EM_Location->location_country );
				$location_export_item['data'][] = array('name' => __('Coordinates','events-manager'), 'value' => $EM_Location->location_latitude .', '. $EM_Location->location_longitude );
				foreach( $EM_Location->location_attributes as $k => $v ){
					$location_export_item['data'][] = array('name' => $k, 'value' => $v);
				}
				$location_export_item = apply_filters('em_data_privacy_export_locations_item', $location_export_item, $EM_Location);
				$export_items[] = $location_export_item;
				$export_items = apply_filters('em_data_privacy_export_locations_items_after_item', $export_items, $location_export_item, $EM_Location); //could be used for cross-referencing and add-ing other groups e.g. Multiple Bookings in Pro
				$locations_exported[] = $EM_Location->location_id;
				$items_count++;
				if( $items_count == $limit ) break;
			}
		}
		update_post_meta( absint($_REQUEST['id']), '_em_locations_exported', $locations_exported );
		$done = $items_count < $limit; //if we didn't reach limit of bookings then we must be done
		return array(
			'data' => $export_items,
			'done' => $done,
		);
	}

	public static function get_cpts($email_address, $page, $post_type ){
		global $wpdb;
		$page = (int) $page;
		$limit = apply_filters('em_data_privacy_export_limit', 100);
		$offset = ($page -1) * $limit;
		$user = get_user_by('email', $email_address); //is user or no-user?
		$anon_email_key = $post_type == EM_POST_TYPE_LOCATION ? '_owner_email':'_event_owner_email';
		//get event IDs submitted by user or "anonymously" by email
		$events = array();
		if( $user !== false ){
			$sql = $wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_author = %d AND post_type = %s LIMIT %d OFFSET %d", $user->ID, $post_type, $limit, $offset);
			$events = $wpdb->get_col($sql);
		}
		//if user ever submitted anonymous events with same email, we also process these
		$sql = $wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value=%s) AND post_type = %s LIMIT %d OFFSET %d", $anon_email_key, $email_address, $post_type, $limit, $offset);
		$events = array_merge($events, $wpdb->get_col($sql));
		return $events;
	}

	public static function get_bookings( $email_address, $page ){
		global $wpdb;
		$page = (int) $page;
		$limit = apply_filters('em_data_privacy_export_limit', 100);
		$offset = ($page -1) * $limit;
		$user = get_user_by('email', $email_address); //is user or no-user?

		$conditions = array();
		if( $user !== false ){
			$conditions[] = $wpdb->prepare('person_id = %d', $user->ID);
		}
		$conditions[] = $wpdb->prepare('person_id=0 AND booking_meta LIKE %s', "%\"user_email\";s:".strlen($email_address).":\"$email_address\"%"); //find any booking that may have their email, anonymous or previous email address.
		$bookings = $wpdb->get_col('SELECT booking_id FROM '.EM_BOOKINGS_TABLE.' WHERE '. implode(' OR ', $conditions) .' LIMIT '.$limit . ' OFFSET '.$offset);

		return $bookings;

	}
}
EM_Data_Privacy::init();
/*
add_action('admin_init', function(){
	$data = EM_Data_Privacy::exporter('subscriber@netweblogic.com');
	echo "<table>";
	foreach( $data['data'] as $items ){
		foreach($items['data'] as $item) echo "<tr><th>{$item['name']}</th><td>{$item['value']}</td>";
	}
	echo "</table>";
	die();
}); //*/