<?php if( !function_exists('current_user_can') || !current_user_can('list_users') ) return; ?>
<!-- BOOKING OPTIONS -->
<div class="em-menu-bookings em-menu-group"  <?php if( !defined('EM_SETTINGS_TABS') || !EM_SETTINGS_TABS) : ?>style="display:none;"<?php endif; ?>>	
	
	<div  class="postbox " id="em-opt-bookings-general" >
	<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php echo sprintf(__( '%s Options', 'dbem' ),__('General','dbem')); ?> </span></h3>
	<div class="inside">
		<table class='form-table'> 
			<?php 
			em_options_radio_binary ( __( 'Allow guest bookings?', 'dbem' ), 'dbem_bookings_anonymous', __( 'If enabled, guest visitors can supply an email address and a user account will automatically be created for them along with their booking. They will be also be able to log back in with that newly created account.', 'dbem' ) );
			em_options_radio_binary ( __( 'Approval Required?', 'dbem' ), 'dbem_bookings_approval', __( 'Bookings will not be confirmed until the event administrator approves it.', 'dbem' ).' '.__( 'This setting is not applicable when using payment gateways, see individual gateways for approval settings.', 'dbem' ));
			em_options_radio_binary ( __( 'Reserved unconfirmed spaces?', 'dbem' ), 'dbem_bookings_approval_reserved', __( 'By default, event spaces become unavailable once there are enough CONFIRMED bookings. To reserve spaces even if unnapproved, choose yes.', 'dbem' ) );
			em_options_radio_binary ( __( 'Can users cancel their booking?', 'dbem' ), 'dbem_bookings_user_cancellation', __( 'If enabled, users can cancel their bookings themselves from their bookings page.', 'dbem' ) );
			em_options_radio_binary ( __( 'Allow overbooking when approving?', 'dbem' ), 'dbem_bookings_approval_overbooking', __( 'If you get a lot of pending bookings and you decide to allow more bookings than spaces allow, setting this to yes will allow you to override the event space limit when manually approving.', 'dbem' ) );
			em_options_radio_binary ( __( 'Allow double bookings?', 'dbem' ), 'dbem_bookings_double', __( 'If enabled, users can book an event more than once.', 'dbem' ) );
			echo $save_button; 
			?>
		</table>
	</div> <!-- . inside -->
	</div> <!-- .postbox -->
	
	<div  class="postbox " id="em-opt-pricing-options" >
	<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php echo sprintf(__( '%s Options', 'dbem' ),__('Pricing','dbem')); ?> </span></h3>
	<div class="inside">
		<table class='form-table'>
			<?php
			/* Tax & Currency */
			em_options_select ( __( 'Currency', 'dbem' ), 'dbem_bookings_currency', em_get_currencies()->names, __( 'Choose your currency for displaying event pricing.', 'dbem' ) );
			em_options_input_text ( __( 'Thousands Separator', 'dbem' ), 'dbem_bookings_currency_thousands_sep', '<code>'.get_option('dbem_bookings_currency_thousands_sep')." = ".em_get_currency_symbol().'100<strong>'.get_option('dbem_bookings_currency_thousands_sep').'</strong>000<strong>'.get_option('dbem_bookings_currency_decimal_point').'</strong>00</code>' );
			em_options_input_text ( __( 'Decimal Point', 'dbem' ), 'dbem_bookings_currency_decimal_point', '<code>'.get_option('dbem_bookings_currency_decimal_point')." = ".em_get_currency_symbol().'100<strong>'.get_option('dbem_bookings_currency_decimal_point').'</strong>00</code>' );
			em_options_input_text ( __( 'Currency Format', 'dbem' ), 'dbem_bookings_currency_format', __('Choose how prices are displayed. <code>@</code> will be replaced by the currency symbol, and <code>#</code> will be replaced by the number.','dbem').' <code>'.get_option('dbem_bookings_currency_format')." = ".em_get_currency_formatted('10000000').'</code>');
			em_options_input_text ( __( 'Tax Rate', 'dbem' ), 'dbem_bookings_tax', __( 'Add a tax rate to your ticket prices (entering 10 will add 10% to the ticket price).', 'dbem' ) );
			em_options_radio_binary ( __( 'Add tax to ticket price?', 'dbem' ), 'dbem_bookings_tax_auto_add', __( 'When displaying ticket prices and booking totals, include the tax automatically?', 'dbem' ) );
			echo $save_button; 
			?>
		</table>
	</div> <!-- . inside -->
	</div> <!-- .postbox --> 
	
	<div  class="postbox " id="em-opt-booking-feedbacks" >
	<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php _e( 'Customize Feedback Messages', 'dbem' ); ?> </span></h3>
	<div class="inside">
		<p><?php _e('Below you will find texts that will be displayed to users in various areas during the bookings process, particularly on booking forms.','dbem'); ?></p>
		<table class='form-table'>
			<tr class="em-header"><td colspan='2'><h4><?php _e('My Bookings messages','dbem') ?></h4></td></tr>
			<?php 
			em_options_input_text ( __( 'Booking Cancelled', 'dbem' ), 'dbem_booking_feedback_cancelled', __( 'When a user cancels their booking, this message will be displayed confirming the cancellation.', 'dbem' ) );
			em_options_input_text ( __( 'Booking Cancellation Warning', 'dbem' ), 'dbem_booking_warning_cancel', __( 'When a user chooses to cancel a booking, this warning is displayed for them to confirm.', 'dbem' ) );
			?>
			<tr class="em-header"><td colspan='2'><h4><?php _e('Booking form texts/messages','dbem') ?></h4></td></tr>
			<?php
			em_options_input_text ( __( 'Bookings disabled', 'dbem' ), 'dbem_bookings_form_msg_disabled', __( 'An event with no bookings.', 'dbem' ) );
			em_options_input_text ( __( 'Bookings closed', 'dbem' ), 'dbem_bookings_form_msg_closed', __( 'Bookings have closed (e.g. event has started).', 'dbem' ) );
			em_options_input_text ( __( 'Fully booked', 'dbem' ), 'dbem_bookings_form_msg_full', __( 'Event is fully booked.', 'dbem' ) );
			em_options_input_text ( __( 'Already attending', 'dbem' ), 'dbem_bookings_form_msg_attending', __( 'If already attending and double bookings are disabled, this message will be displayed, followed by a link to the users booking page.', 'dbem' ) );
			em_options_input_text ( __( 'Manage bookings link text', 'dbem' ), 'dbem_bookings_form_msg_bookings_link', __( 'Link text used for link to user bookings.', 'dbem' ) );
			?>
			<tr class="em-header"><td colspan='2'><h4><?php _e('Booking form feedback messages','dbem') ?></h4></td></tr>
			<tr><td colspan='2'><?php _e('When a booking is made by a user, a feedback message is shown depending on the result, which can be customized below.','dbem'); ?></td></tr>
			<?php
			em_options_input_text ( __( 'Successful booking', 'dbem' ), 'dbem_booking_feedback', __( 'When a booking is registered and confirmed.', 'dbem' ) );
			em_options_input_text ( __( 'Successful pending booking', 'dbem' ), 'dbem_booking_feedback_pending', __( 'When a booking is registered but pending.', 'dbem' ) );
			em_options_input_text ( __( 'Not enough spaces', 'dbem' ), 'dbem_booking_feedback_full', __( 'When a booking cannot be made due to lack of spaces.', 'dbem' ) );
			em_options_input_text ( __( 'Errors', 'dbem' ), 'dbem_booking_feedback_error', __( 'When a booking cannot be made due to an error when filling the form. Below this, there will be a dynamic list of errors.', 'dbem' ) );
			em_options_input_text ( __( 'Email Exists', 'dbem' ), 'dbem_booking_feedback_email_exists', __( 'When a guest tries to book using an email registered with a user account.', 'dbem' ) );
			em_options_input_text ( __( 'User must log in', 'dbem' ), 'dbem_booking_feedback_log_in', __( 'When a user must log in before making a booking.', 'dbem' ) );
			em_options_input_text ( __( 'Error mailing user', 'dbem' ), 'dbem_booking_feedback_nomail', __( 'If a booking is made and an email cannot be sent, this is added to the success message.', 'dbem' ) );
			em_options_input_text ( __( 'Already booked', 'dbem' ), 'dbem_booking_feedback_already_booked', __( 'If the user made a previous booking and cannot double-book.', 'dbem' ) );
			em_options_input_text ( __( 'No spaces booked', 'dbem' ), 'dbem_booking_feedback_min_space', __( 'If the user tries to make a booking without requesting any spaces.', 'dbem' ) );$notice_full = __('Sold Out', 'dbem');
			em_options_input_text ( __( 'Maximum spaces per booking', 'dbem' ), 'dbem_booking_feedback_spaces_limit', __( 'If the user tries to make a booking with spaces that exceeds the maximum number of spaces per booking.', 'dbem' ).' '. __('%d will be replaced by a number.','dbem') );
			?>
			<tr class="em-header"><td colspan='2'><h4><?php _e('Booking button feedback messages','dbem') ?></h4></td></tr>
			<tr><td colspan='2'><?php echo sprintf(__('When the %s placeholder, the below texts will be used.','dbem'),'<code>#_BOOKINGBUTTON</code>'); ?></td></tr>
			<?php			
			em_options_input_text ( __( 'User can book', 'dbem' ), 'dbem_booking_button_msg_book', '');
			em_options_input_text ( __( 'Booking in progress', 'dbem' ), 'dbem_booking_button_msg_booking', '');
			em_options_input_text ( __( 'Booking complete', 'dbem' ), 'dbem_booking_button_msg_booked', '');
			em_options_input_text ( __( 'Booking already made', 'dbem' ), 'dbem_booking_button_msg_already_booked', '');
			em_options_input_text ( __( 'Booking error', 'dbem' ), 'dbem_booking_button_msg_error', '');
			em_options_input_text ( __( 'Event fully booked', 'dbem' ), 'dbem_booking_button_msg_full', '');
			em_options_input_text ( __( 'Cancel', 'dbem' ), 'dbem_booking_button_msg_cancel', '');
			em_options_input_text ( __( 'Cancelation in progress', 'dbem' ), 'dbem_booking_button_msg_canceling', '');
			em_options_input_text ( __( 'Cancelation complete', 'dbem' ), 'dbem_booking_button_msg_cancelled', '');
			em_options_input_text ( __( 'Cancelation error', 'dbem' ), 'dbem_booking_button_msg_cancel_error', '');
			
			echo $save_button; 
			?>
		</table>
	</div> <!-- . inside -->
	</div> <!-- .postbox --> 
	
	<div  class="postbox " id="em-opt-booking-form-options" >
	<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php echo sprintf(__( '%s Options', 'dbem' ),__('Booking Form','dbem')); ?> </span></h3>
	<div class="inside">
		<table class='form-table'>
			<?php
			em_options_radio_binary ( __( 'Display login form?', 'dbem' ), 'dbem_bookings_login_form', __( 'Choose whether or not to display a login form in the booking form area to remind your members to log in before booking.', 'dbem' ) );
			em_options_input_text ( __( 'Submit button text', 'dbem' ), 'dbem_bookings_submit_button', sprintf(__( 'The text used by the submit button. To use an image instead, enter the full url starting with %s or %s.', 'dbem' ), '<code>http://</code>','<code>https://</code>') );
			do_action('em_options_booking_form_options');
			echo $save_button;
			?>
		</table>
	</div> <!-- . inside -->
	</div> <!-- .postbox --> 
	
	<div  class="postbox " id="em-opt-ticket-options" >
	<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php echo sprintf(__( '%s Options', 'dbem' ),__('Ticket','dbem')); ?> </span></h3>
	<div class="inside">
		<table class='form-table'>
			<?php
			em_options_radio_binary ( __( 'Single ticket mode?', 'dbem' ), 'dbem_bookings_tickets_single', __( 'In single ticket mode, users can only create one ticket per event (and will not see options to add more tickets).', 'dbem' ) );
			em_options_radio_binary ( __( 'Show ticket table in single ticket mode?', 'dbem' ), 'dbem_bookings_tickets_single_form', __( 'If you prefer a ticket table like with multiple tickets, even for single ticket events, enable this.', 'dbem' ) );
			em_options_radio_binary ( __( 'Show unavailable tickets?', 'dbem' ), 'dbem_bookings_tickets_show_unavailable', __( 'You can choose whether or not to show unavailable tickets to visitors.', 'dbem' ) );
			em_options_radio_binary ( __( 'Show member-only tickets?', 'dbem' ), 'dbem_bookings_tickets_show_member_tickets', sprintf(__('%s must be set to yes for this to work.', 'dbem' ), '<strong>'.__( 'Show unavailable tickets?', 'dbem' ).'</strong>').' '.__( 'If there are member-only tickets, you can choose whether or not to show these tickets to guests.','dbem') );
			
			em_options_radio_binary ( __( 'Show multiple tickets if logged out?', 'dbem' ), 'dbem_bookings_tickets_show_loggedout', __( 'If guests cannot make bookings, they will be asked to register in order to book. However, enabling this will still show available tickets.', 'dbem' ) );
			$ticket_orders = array(
				'ticket_price DESC, ticket_name ASC'=>__('Ticket Price (Descending)','dbem'),
				'ticket_price ASC, ticket_name ASC'=>__('Ticket Price (Ascending)','dbem'),
				'ticket_name ASC, ticket_price DESC'=>__('Ticket Name (Ascending)','dbem'),
				'ticket_name DESC, ticket_price DESC'=>__('Ticket Name (Descending)','dbem')
			);
			em_options_select ( __( 'Order Tickets By', 'dbem' ), 'dbem_bookings_tickets_orderby', $ticket_orders, __( 'Choose which order your tickets appear.', 'dbem' ) );
			echo $save_button; 
			?>
		</table>
	</div> <!-- . inside -->
	</div> <!-- .postbox --> 
		
	<div  class="postbox " id="em-opt-no-user-bookings" >
	<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php _e('No-User Booking Mode','dbem'); ?> </span></h3>
	<div class="inside">
		<table class='form-table'>
			<tr><td colspan='2'>
				<p><?php _e('By default, when a booking is made by a user, this booking is tied to a user account, if the user is not registered nor logged in and guest bookings are enabled, an account will be created for them.','dbem'); ?></p>
				<p><?php _e('The option below allows you to disable user accounts and assign all bookings to a parent user, yet you will still see the supplied booking personal information for each booking. When this mode is enabled, extra booking information about the person is stored alongside the booking record rather than as a WordPress user.','dbem'); ?></p>
				<p><?php _e('Users with accounts (which would be created by other means when this mode is enabled) will still be able to log in and make bookings linked to their account as normal.','dbem'); ?></p>
				<p><?php _e('<strong>Warning : </strong> Various features afforded to users with an account will not be available, e.g. viewing bookings. Once you enable this and select a user, modifying these values will prevent older non-user bookings from displaying the correct information.','dbem'); ?></p>
			</td></tr>
			<?php
			em_options_radio_binary ( __( 'Enable No-User Booking Mode?', 'dbem' ), 'dbem_bookings_registration_disable', __( 'This disables user registrations for bookings.', 'dbem' ) );
			em_options_radio_binary ( __( 'Allow bookings with registered emails?', 'dbem' ), 'dbem_bookings_registration_disable_user_emails', __( 'By default, if a guest tries to book an event using the email of a user account on your site they will be asked to log in, selecting yes will bypass this security measure.', 'dbem' ).'<br />'.__('<strong>Warning : </strong> By enabling this, registered users will not be able to see bookings they make as guests in their "My Bookings" page.','dbem') );
			$current_user = array();
			if( get_option('dbem_bookings_registration_user') ){
				$user = get_user_by('id',get_option('dbem_bookings_registration_user'));
				$current_user[$user->ID] = $user->display_name;
			}
			if( defined('EM_OPTIMIZE_SETTINGS_PAGE_USERS') && EM_OPTIMIZE_SETTINGS_PAGE_USERS ){
            	em_options_input_text ( __( 'Assign bookings to', 'dbem' ), 'dbem_bookings_registration_user', __('Please add a User ID.','dbem').' '.__( 'Choose a parent user to assign bookings to. People making their booking will be unaware of this and will never have access to those user details. This should be a subscriber user you do not use to log in with yourself.', 'dbem' ) );
            }else{
            	em_options_select ( __( 'Assign bookings to', 'dbem' ), 'dbem_bookings_registration_user', em_get_wp_users(array('role' => 'subscriber'), $current_user), __( 'Choose a parent user to assign bookings to. People making their booking will be unaware of this and will never have access to those user details. This should be a subscriber user you do not use to log in with yourself.', 'dbem' ) );
			}
			echo $save_button; 
			?>
		</table>
	</div> <!-- . inside -->
	</div> <!-- .postbox -->
	
	<?php do_action('em_options_page_footer_bookings'); ?>
	
</div> <!-- .em-menu-bookings -->