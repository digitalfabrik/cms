<?php
/* Used by the admin area to display recurring event time-related information - edit with caution */
global $EM_Event;
$days_names = em_get_days_names();
$hours_format = em_get_hour_format();
$classes = array();
?>
<div id="em-form-recurrence" class="event-form-recurrence event-form-when">
	<p class="em-time-range">
		<?php _e('Events start from','events-manager'); ?>
		<input id="start-time" class="em-time-input em-time-start" type="text" size="8" maxlength="8" name="event_start_time" value="<?php echo date( $hours_format, $EM_Event->start ); ?>" />
		<?php _e('to','events-manager'); ?>
		<input id="end-time" class="em-time-input em-time-end" type="text" size="8" maxlength="8" name="event_end_time" value="<?php echo date( $hours_format, $EM_Event->end ); ?>" />
		<?php _e('All day','events-manager'); ?> <input type="checkbox" class="em-time-allday" name="event_all_day" id="em-time-all-day" value="1" <?php if(!empty($EM_Event->event_all_day)) echo 'checked="checked"'; ?> />
	</p>
	<div class="<?php if( !empty($EM_Event->event_id) ) echo 'em-recurrence-reschedule'; ?>">
	<?php if( !empty($EM_Event->event_id) ): ?>
	<div class="recurrence-reschedule-warning">
	    <p><em><?php echo sprintf(esc_html__('Current Recurrence Pattern: %s', 'events-manager'), $EM_Event->get_recurrence_description()); ?></em></p>
	    <p><strong><?php esc_html_e( 'Modifications to event dates will cause all recurrences of this event to be deleted and recreated, previous bookings will be deleted.', 'events-manager'); ?></strong></p>
	    <p>
	       <a href="<?php echo esc_url( add_query_arg(array('scope'=>'all', 'recurrence_id'=>$EM_Event->event_id), em_get_events_admin_url()) ); ?>">
                <strong><?php esc_html_e('You can edit individual recurrences and disassociate them with this recurring event.', 'events-manager'); ?></strong>
    	   </a>
	    </p>
	</div>
	<?php endif; ?>
	<div class="event-form-when-wrap <?php if( !empty($EM_Event->event_id) && empty($_REQUEST['reschedule']) ) echo 'reschedule-hidden'; ?>">
    <?php _e ( 'This event repeats', 'events-manager'); ?> 
		<select id="recurrence-frequency" name="recurrence_freq">
			<?php
				$freq_options = array ("daily" => __ ( 'Daily', 'events-manager'), "weekly" => __ ( 'Weekly', 'events-manager'), "monthly" => __ ( 'Monthly', 'events-manager'), 'yearly' => __('Yearly','events-manager') );
				em_option_items ( $freq_options, $EM_Event->recurrence_freq ); 
			?>
		</select>
		<?php _e ( 'every', 'events-manager')?>
		<input id="recurrence-interval" name='recurrence_interval' size='2' value='<?php echo $EM_Event->recurrence_interval ; ?>' />
		<span class='interval-desc' id="interval-daily-singular"><?php _e ( 'day', 'events-manager')?></span>
		<span class='interval-desc' id="interval-daily-plural"><?php _e ( 'days', 'events-manager') ?></span>
		<span class='interval-desc' id="interval-weekly-singular"><?php _e ( 'week on', 'events-manager'); ?></span>
		<span class='interval-desc' id="interval-weekly-plural"><?php _e ( 'weeks on', 'events-manager'); ?></span>
		<span class='interval-desc' id="interval-monthly-singular"><?php _e ( 'month on the', 'events-manager')?></span>
		<span class='interval-desc' id="interval-monthly-plural"><?php _e ( 'months on the', 'events-manager')?></span>
		<span class='interval-desc' id="interval-yearly-singular"><?php _e ( 'year', 'events-manager')?></span> 
		<span class='interval-desc' id="interval-yearly-plural"><?php _e ( 'years', 'events-manager') ?></span>
		<p class="alternate-selector" id="weekly-selector">
			<?php
				$saved_bydays = ($EM_Event->is_recurring() && $EM_Event->recurrence_byday != '' ) ? explode ( ",", $EM_Event->recurrence_byday ) : array(); 
				em_checkbox_items ( 'recurrence_bydays[]', $days_names, $saved_bydays ); 
			?>
		</p>
		<p class="alternate-selector" id="monthly-selector" style="display:inline;">
			<select id="monthly-modifier" name="recurrence_byweekno">
				<?php
					$weekno_options = array ("1" => __ ( 'first', 'events-manager'), '2' => __ ( 'second', 'events-manager'), '3' => __ ( 'third', 'events-manager'), '4' => __ ( 'fourth', 'events-manager'), '5' => __ ( 'fifth', 'events-manager'), '-1' => __ ( 'last', 'events-manager') ); 
					em_option_items ( $weekno_options, $EM_Event->recurrence_byweekno  ); 
				?>
			</select>
			<select id="recurrence-weekday" name="recurrence_byday">
				<?php em_option_items ( $days_names, $EM_Event->recurrence_byday  ); ?>
			</select>
			<?php _e('of each month','events-manager'); ?>
			&nbsp;
		</p>
		<div class="event-form-recurrence-when">
			<p class="em-date-range">
				<?php _e ( 'Recurrences span from ', 'events-manager'); ?>					
				<input class="em-date-start em-date-input-loc" type="text" />
				<input class="em-date-input" type="hidden" name="event_start_date" value="<?php echo $EM_Event->event_start_date ?>" />
				<?php _e('to','events-manager'); ?>
				<input class="em-date-end em-date-input-loc" type="text" />
				<input class="em-date-input" type="hidden" name="event_end_date" value="<?php echo $EM_Event->event_end_date ?>" />
			</p>
			<p class="em-duration-range">
				<?php echo sprintf(__('Each event spans %s day(s)','events-manager'), '<input id="end-days" type="text" size="8" maxlength="8" name="recurrence_days" value="'. $EM_Event->recurrence_days .'" />'); ?>
			</p>
			<p class="em-range-description"><em><?php _e( 'For a recurring event, a one day event will be created on each recurring date within this date range.', 'events-manager'); ?></em></p>
		</div>
	</div>
	<?php if( !empty($EM_Event->event_id) ): ?>
	<div class="recurrence-reschedule-buttons">
	    <a href="<?php echo esc_url(add_query_arg('reschedule', null)); ?>" class="button-secondary em-button em-reschedule-cancel<?php if( empty($_REQUEST['reschedule']) ) echo ' reschedule-hidden'; ?>" data-target=".event-form-when-wrap">
	    	<?php esc_html_e('Cancel Reschedule', 'events-manager'); ?>
	    </a>
	    <a href="<?php echo esc_url(add_query_arg('reschedule', '1')); ?>" class="em-reschedule-trigger em-button button-secondary<?php if( !empty($_REQUEST['reschedule']) ) echo ' reschedule-hidden'; ?>" data-target=".event-form-when-wrap">
	    	<?php esc_html_e('Reschedule Recurring Event', 'events-manager'); ?>
	    </a>
	    <input type="hidden" name="event_reschedule" class="em-reschedule-value" value="<?php echo empty($_REQUEST['reschedule']) ? 0:1 ?>" />
	</div>
	<?php endif; ?>
	</div>
</div>