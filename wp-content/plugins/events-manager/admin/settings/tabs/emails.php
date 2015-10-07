<?php if( !function_exists('current_user_can') || !current_user_can('list_users') ) return; ?>
<!-- EMAIL OPTIONS -->
<div class="em-menu-emails em-menu-group" <?php if( !defined('EM_SETTINGS_TABS') || !EM_SETTINGS_TABS) : ?>style="display:none;"<?php endif; ?>>
	
	<?php if ( !is_multisite() ) { em_admin_option_box_email(); } ?>

	<?php if( get_option('dbem_rsvp_enabled') ): ?>
	<div  class="postbox "  id="em-opt-booking-emails">
	<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php _e ( 'Booking Email Templates', 'dbem' ); ?> </span></h3>
	<div class="inside">
	    <?php do_action('em_options_page_booking_email_templates_options_top'); ?>
		<table class='form-table'>
			<?php
			$email_subject_tip = __('You can disable this email by leaving the subject blank.','dbem');
			em_options_input_text ( __( 'Email events admin?', 'dbem' ), 'dbem_bookings_notify_admin', __( "If you would like every event booking confirmation email sent to an administrator write their email here (leave blank to not send an email).", 'dbem' ).' '.__('For multiple emails, seperate by commas (e.g. email1@test.com,email2@test.com,etc.)','dbem') );
			em_options_radio_binary ( __( 'Email event owner?', 'dbem' ), 'dbem_bookings_contact_email', __( 'Check this option if you want the event contact to receive an email when someone books places. An email will be sent when a booking is first made (regardless if confirmed or pending)', 'dbem' ) );
			?>
			<tr class="em-header"><td colspan='2'><h4><?php _e('Event Admin/Owner Emails', 'dbem'); ?></h4></td></tr>
			<tbody class="em-subsection">
			<tr class="em-subheader"><td colspan='2'>
				<h5><?php _e('Confirmed booking email','dbem') ?></h5>
				<em><?php echo __('This is sent when a person\'s booking is confirmed. This will be sent automatically if approvals are required and the booking is approved. If approvals are disabled, this is sent out when a user first submits their booking.','dbem').$bookings_placeholder_tip ?></em>
			</td></tr>
			<?php
			em_options_input_text ( __( 'Booking confirmed email subject', 'dbem' ), 'dbem_bookings_contact_email_confirmed_subject', $email_subject_tip );
			em_options_textarea ( __( 'Booking confirmed email', 'dbem' ), 'dbem_bookings_contact_email_confirmed_body', '' );
			?>
			<tr class="em-subheader"><td colspan='2'>
				<h5><?php _e('Pending booking email','dbem') ?></h5>
				<em><?php echo __('This is sent when a person\'s booking is pending. If approvals are enabled, this is sent out when a user first submits their booking.','dbem').$bookings_placeholder_tip ?></em>
			</td></tr>
			<?php
			em_options_input_text ( __( 'Booking pending email subject', 'dbem' ), 'dbem_bookings_contact_email_pending_subject', $email_subject_tip );
			em_options_textarea ( __( 'Booking pending email', 'dbem' ), 'dbem_bookings_contact_email_pending_body', '' );
			?>
			<tr class="em-subheader"><td colspan='2'>
				<h5><?php _e('Booking cancelled','dbem') ?></h5>
				<em><?php echo __('An email will be sent to the event contact if someone cancels their booking.','dbem').$bookings_placeholder_tip ?></em>
			</td></tr>
			<?php
			em_options_input_text ( __( 'Booking cancelled email subject', 'dbem' ), 'dbem_bookings_contact_email_cancelled_subject', $email_subject_tip );
			em_options_textarea ( __( 'Booking cancelled email', 'dbem' ), 'dbem_bookings_contact_email_cancelled_body', '' );
			?>
			<tr class="em-subheader"><td colspan='2'>
				<h5><?php _e('Rejected booking email','dbem') ?></h5>
				<em><?php echo __( 'This will be sent to event admins when a booking is rejected.', 'dbem' ).$bookings_placeholder_tip ?></em>
			</td></tr>
			<?php
			em_options_input_text ( __( 'Booking rejected email subject', 'dbem' ), 'dbem_bookings_contact_email_rejected_subject', $email_subject_tip );
			em_options_textarea ( __( 'Booking rejected email', 'dbem' ), 'dbem_bookings_contact_email_rejected_body', '' );
			?>
			</tbody>
			<tr class="em-header"><td colspan='2'><h4><?php _e('Booked User Emails', 'dbem'); ?></h4></td></tr>
			<tbody class="em-subsection">
			<tr class="em-subheader"><td colspan='2'>
				<h5><?php _e('Confirmed booking email','dbem') ?></h5>
				<em><?php echo __('This is sent when a person\'s booking is confirmed. This will be sent automatically if approvals are required and the booking is approved. If approvals are disabled, this is sent out when a user first submits their booking.','dbem').$bookings_placeholder_tip ?></em>
			</td></tr>
			<?php
			em_options_input_text ( __( 'Booking confirmed email subject', 'dbem' ), 'dbem_bookings_email_confirmed_subject', $email_subject_tip );
			em_options_textarea ( __( 'Booking confirmed email', 'dbem' ), 'dbem_bookings_email_confirmed_body', '' );
			?>
			<tr class="em-subheader"><td colspan='2'>
				<h5><?php _e('Pending booking email','dbem') ?></h5>
				<em><?php echo __( 'This will be sent to the person when they first submit their booking. Not relevant if bookings don\'t require approval.', 'dbem' ).$bookings_placeholder_tip ?></em>
			</td></tr>
			<?php
			em_options_input_text ( __( 'Booking pending email subject', 'dbem' ), 'dbem_bookings_email_pending_subject', $email_subject_tip);
			em_options_textarea ( __( 'Booking pending email', 'dbem' ), 'dbem_bookings_email_pending_body','') ;
			?>
			<tr class="em-subheader"><td colspan='2'>
				<h5><?php _e('Rejected booking email','dbem') ?></h5>
				<em><?php echo __( 'This will be sent automatically when a booking is rejected. Not relevant if bookings don\'t require approval.', 'dbem' ).$bookings_placeholder_tip ?></em>
			</td></tr>
			<?php
			em_options_input_text ( __( 'Booking rejected email subject', 'dbem' ), 'dbem_bookings_email_rejected_subject', $email_subject_tip );
			em_options_textarea ( __( 'Booking rejected email', 'dbem' ), 'dbem_bookings_email_rejected_body', '' );
			?>
			<tr class="em-subheader"><td colspan='2'>
				<h5><?php _e('Booking cancelled','dbem') ?></h5>
				<em><?php echo __('This will be sent when a user cancels their booking.','dbem').$bookings_placeholder_tip ?></em>
			</td></tr>
			<?php
			em_options_input_text ( __( 'Booking cancelled email subject', 'dbem' ), 'dbem_bookings_email_cancelled_subject', $email_subject_tip );
			em_options_textarea ( __( 'Booking cancelled email', 'dbem' ), 'dbem_bookings_email_cancelled_body', '' );
			?>
			</tbody>
	        <?php do_action('em_options_page_booking_email_templates_options_bottom'); ?>
			<?php echo $save_button; ?>
		</table>
	</div> <!-- . inside -->
	</div> <!-- .postbox -->
	<?php endif; ?>
			  		
	<?php if( get_option('dbem_rsvp_enabled') ): ?>
	<div  class="postbox "  id="em-opt-registration-emails">
	<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php _e ( 'Registration Email Templates', 'dbem' ); ?> </span></h3>
	<div class="inside">
		<p class="em-boxheader">
			<?php echo sprintf(__('This is only applicable when %s is not active.','dbem'), '<em>'.__('No-User Booking Mode','dbem').'</em>'); ?>
			<?php _e('When a guest user makes a booking for the first time in Events Manager, a new user account is created for them and they are sent their credentials in a seperate email, which can be modified below.','dbem'); ?>
		</p>
		<table class='form-table'>
			<?php
			em_options_radio_binary ( __( 'Disable new registration email?', 'dbem' ), 'dbem_email_disable_registration', __( 'Check this option if you want to prevent the WordPress registration email from going out when a user anonymously books an event.', 'dbem' ) );
			
			em_options_input_text ( __( 'Registration email subject', 'dbem' ), 'dbem_bookings_email_registration_subject' );
			em_options_textarea ( __( 'Registration email', 'dbem' ), 'dbem_bookings_email_registration_body', sprintf(__('%s is replaced by username and %s is replaced by the user password.','dbem'),'<code>%username%</code>','<code>%password%</code>') );
			echo $save_button;
			?>
		</table>
	</div> <!-- . inside -->
	</div> <!-- .postbox -->
	<?php endif; ?>
	
	<div  class="postbox " id="em-opt-event-submission-emails" >
	<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php _e ( 'Event Submission Templates', 'dbem' ); ?> </span></h3>
	<div class="inside">
		<table class='form-table'>
			<tr class="em-header"><td colspan='2'><h4><?php _e('Event Admin Emails', 'dbem'); ?></h4></td></tr>
			<?php 
			em_options_input_text ( __( 'Administrator Email', 'dbem' ), 'dbem_event_submitted_email_admin', __('Event submission notifications will be sent to emails added here.','dbem').' '.__('If left blank, no emails will be sent. Seperate emails with commas for more than one email.','dbem') );
			?>
			<tbody class="em-subsection">
			<tr class="em-subheader"><td colspan='2'>
				<h5><?php _e('Event Submitted','dbem') ?></h5>
				<em><?php echo __('An email will be sent to your administrator emails when an event is submitted and pending approval.','dbem').$bookings_placeholder_tip ?></em>
			</td></tr>
			<?php
			em_options_input_text ( __( 'Event submitted subject', 'dbem' ), 'dbem_event_submitted_email_subject', __('If left blank, this email will not be sent.','dbem') );
			em_options_textarea ( __( 'Event submitted email', 'dbem' ), 'dbem_event_submitted_email_body', '' );
			?>
			<tr class="em-subheader"><td colspan='2'>
				<h5><?php _e('Event Re-Submitted','dbem') ?></h5>
				<em><?php echo __('When a user modifies a previously published event, it will be put back into pending review status and will not be publisehd until you re-approve it.','dbem').$bookings_placeholder_tip ?></em>
			</td></tr>
			<?php
			em_options_input_text ( __( 'Event resubmitted subject', 'dbem' ), 'dbem_event_resubmitted_email_subject', __('If left blank, this email will not be sent.','dbem') );
			em_options_textarea ( __( 'Event resubmitted email', 'dbem' ), 'dbem_event_resubmitted_email_body', '' );
			?>
			<tr class="em-subheader"><td colspan='2'>
				<h5><?php _e('Event Published','dbem') ?></h5>
				<em><?php echo __('An email will be sent to an administrator of your choice when an event is published by users who are not administrators.','dbem').$bookings_placeholder_tip ?>
			</td></tr>
			<?php
			em_options_input_text ( __( 'Event published subject', 'dbem' ), 'dbem_event_published_email_subject', __('If left blank, this email will not be sent.','dbem') );
			em_options_textarea ( __( 'Event published email', 'dbem' ), 'dbem_event_published_email_body', '' );
			?>
			</tbody>
			<tr class="em-header"><td colspan='2'><h4><?php _e('Event Submitter Emails', 'dbem'); ?></h4></td></tr>
			<tbody class="em-subsection">
			<tr class="em-subheader"><td colspan='2'>
				<h5><?php _e('Event Approved','dbem') ?></h5>
				<em><?php echo __('An email will be sent to the event owner when their event is approved. Users requiring event approval do not have the <code>publish_events</code> capability.','dbem').$bookings_placeholder_tip ?>
			</td></tr>
			<?php
			em_options_input_text ( __( 'Event approved subject', 'dbem' ), 'dbem_event_approved_email_subject', __('If left blank, this email will not be sent.','dbem') );
			em_options_textarea ( __( 'Event approved email', 'dbem' ), 'dbem_event_approved_email_body', '' );
			?>
			<tr class="em-subheader"><td colspan='2'>
				<h5><?php _e('Event Reapproved','dbem') ?></h5>
			    <?php echo __('When a user modifies a previously published event, it will be put back into pending review status and will not be publisehd until you re-approve it.','dbem').$bookings_placeholder_tip ?>
			</td></tr>
			<?php
			em_options_input_text ( __( 'Event reapproved subject', 'dbem' ), 'dbem_event_reapproved_email_subject', __('If left blank, this email will not be sent.','dbem') );
			em_options_textarea ( __( 'Event reapproved email', 'dbem' ), 'dbem_event_reapproved_email_body', '' );
			?>
			</tbody>
			<?php echo $save_button; ?>
		</table>
	</div> <!-- . inside -->
	</div> <!-- .postbox -->
	
	<?php do_action('em_options_page_footer_emails'); ?>
	
</div><!-- .em-group-emails --> 