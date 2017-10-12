<?php
/* @var $EM_Event EM_Event */
$people = array();
$EM_Bookings = $EM_Event->get_bookings();
if( count($EM_Bookings->bookings) > 0 ){
	?>
	<ul class="event-attendees">
	<?php
	foreach( $EM_Bookings as $EM_Booking){ /* @var $EM_Booking EM_Booking */
		if( $EM_Booking->is_pending() && !in_array($EM_Booking->get_person()->ID, $people) ){
			$people[] = $EM_Booking->get_person()->ID;
			echo '<li>'. $EM_Booking->get_person()->get_name() .'</li>';
		}elseif( $EM_Booking->is_pending() && $EM_Booking->is_no_user() ){
			echo '<li>'. $EM_Booking->get_person()->get_name() .'</li>';
		}
	}
	?>
	</ul>
	<?php
}