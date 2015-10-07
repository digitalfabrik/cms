<?php if( !function_exists('current_user_can') || !current_user_can('list_users') ) return; ?>
<!-- FORMAT OPTIONS -->
<div class="em-menu-formats em-menu-group"  <?php if( !defined('EM_SETTINGS_TABS') || !EM_SETTINGS_TABS) : ?>style="display:none;"<?php endif; ?>>				
	<div  class="postbox " id="em-opt-events-formats" >
	<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php _e ( 'Events', 'dbem' ); ?> </span></h3>
	<div class="inside">
    	<table class="form-table">
		 	<tr class="em-header"><td colspan="2">
		 		<h4><?php echo sprintf(__('%s Page','dbem'),__('Events','dbem')); ?></h4>
		 		<p><?php _e('These formats will be used on your events page. This will also be used if you do not provide specified formats in other event lists, like in shortcodes.','dbem'); ?></p>
		 	</td></tr>
			<?php
			$grouby_modes = array(0=>__('None','dbem'), 'yearly'=>__('Yearly','dbem'), 'monthly'=>__('Monthly','dbem'), 'weekly'=>__('Weekly','dbem'), 'daily'=>__('Daily','dbem'));
			em_options_select(__('Events page grouping','dbem'), 'dbem_event_list_groupby', $grouby_modes, __('If you choose a group by mode, your events page will display events in groups of your chosen time range.','dbem'));
			em_options_input_text(__('Events page grouping header','dbem'), 'dbem_event_list_groupby_header_format', __('Choose how to format your group headings.','dbem').' '. sprintf(__('#s will be replaced by the date format below', 'dbem'), 'http://codex.wordpress.org/Formatting_Date_and_Time'));
			em_options_input_text(__('Events page grouping date format','dbem'), 'dbem_event_list_groupby_format', __('Choose how to format your group heading dates. Leave blank for default.','dbem').' '. sprintf(__('Date and Time formats follow the <a href="%s">WordPress time formatting conventions</a>', 'dbem'), 'http://codex.wordpress.org/Formatting_Date_and_Time'));
			em_options_textarea ( __( 'Default event list format header', 'dbem' ), 'dbem_event_list_item_format_header', __( 'This content will appear just above your code for the default event list format. Default is blank', 'dbem' ) );
		 	em_options_textarea ( __( 'Default event list format', 'dbem' ), 'dbem_event_list_item_format', __( 'The format of any events in a list.', 'dbem' ).$events_placeholder_tip );
			em_options_textarea ( __( 'Default event list format footer', 'dbem' ), 'dbem_event_list_item_format_footer', __( 'This content will appear just below your code for the default event list format. Default is blank', 'dbem' ) );
			em_options_input_text ( __( 'No events message', 'dbem' ), 'dbem_no_events_message', __( 'The message displayed when no events are available.', 'dbem' ) );
			em_options_input_text ( __( 'List events by date title', 'dbem' ), 'dbem_list_date_title', __( 'If viewing a page for events on a specific date, this is the title that would show up. To insert date values, use <a href="http://www.php.net/manual/en/function.date.php">PHP time format characters</a>  with a <code>#</code> symbol before them, i.e. <code>#m</code>, <code>#M</code>, <code>#j</code>, etc.<br/>', 'dbem' ) );
			?>
		 	<tr class="em-header">
		 	    <td colspan="2">
		 	        <h4><?php echo sprintf(__('Single %s Page','dbem'),__('Event','dbem')); ?></h4>
		 	        <em><?php echo sprintf(__('These formats can be used on %s pages or on other areas of your site displaying an %s.','dbem'),__('event','dbem'),__('event','dbem'));?></em>
		 	</tr>
		 	<?php
			if( EM_MS_GLOBAL && !get_option('dbem_ms_global_events_links') ){
			 	em_options_input_text ( sprintf(__( 'Single %s title format', 'dbem' ),__('event','dbem')), 'dbem_event_page_title_format', sprintf(__( 'The format of a single %s page title.', 'dbem' ),__('event','dbem')).' '.__( 'This is only used when showing events from other blogs.', 'dbem' ).$events_placeholder_tip );
			}
			em_options_textarea ( sprintf(__('Single %s page format', 'dbem' ),__('event','dbem')), 'dbem_single_event_format', sprintf(__( 'The format used to display %s content on single pages or elsewhere on your site.', 'dbem' ),__('event','dbem')).$events_placeholder_tip );
			?>
			<tr class="em-header">
			    <td colspan="2">
			        <h4><?php echo sprintf(__('%s Excerpts','dbem'),__('Event','dbem')); ?></h4>
		 	        <em><?php echo sprintf(__('These formats can be used when WordPress automatically displays %s excerpts on your site and %s is enabled in your %s settings tab.','dbem'),__('event','dbem'),'<strong>'.__( 'Override Excerpts with Formats?', 'dbem' ).'</strong>','<a href="#formats" class="nav-tab-link" rel="#em-menu-pages">'.__('Pages','dbem').'  &gt; '.sprintf(__('%s List/Archives','dbem'),__('Event','dbem')).'</a>');?></em>
			    </td>
			</tr>
		 	<?php
		 	em_options_textarea ( sprintf(__('%s excerpt', 'dbem' ),__('Event','dbem')), 'dbem_event_excerpt_format', __( 'Used if an excerpt has been defined.', 'dbem' ).$events_placeholder_tip );				 	
		 	em_options_textarea ( sprintf(__('%s excerpt fallback', 'dbem' ),__('Event','dbem')), 'dbem_event_excerpt_alt_format', __( 'Used if an excerpt has not been defined.', 'dbem' ).$events_placeholder_tip );
			
			echo $save_button;
			?>
		</table>
	</div> <!-- . inside -->
	</div> <!-- .postbox -->

	<div  class="postbox " id="em-opt-search-form" >
	<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php _e ( 'Search Form', 'dbem' ); ?> </span></h3>
	<div class="inside">
		<table class="form-table em-search-form-main">
		    <tr class="em-header"><td colspan="2"><h4><?php _e('Main Search Fields','dbem'); ?></h4></td></tr>
		    <tbody class="em-subsection">
			<tr class="em-subheader"><td colspan="2"><h5><?php esc_html_e( 'Search', 'dbem' ); ?></h5></td></tr>
			<?php
			em_options_radio_binary ( __( 'Show text search?', 'dbem' ), 'dbem_search_form_text', '', '', '#dbem_search_form_text_label_row' );
			em_options_input_text ( __( 'Label', 'dbem' ), 'dbem_search_form_text_label', __('Appears within the input box.','dbem') );
			?>
			</tbody>
			<tbody class="em-settings-geocoding em-subsection">
			<tr class="em-subheader"><td colspan="2"><h5><?php esc_html_e( 'Geolocation Search', 'dbem' ); ?></h5></td></tr>
			<?php
			em_options_radio_binary ( __( 'Show geolocation search?', 'dbem' ), 'dbem_search_form_geo', '', '', '#dbem_search_form_geo_label_row, #dbem_search_form_geo_distance_default_row, #dbem_search_form_geo_unit_default_row' );
			em_options_input_text ( __( 'Label', 'dbem' ), 'dbem_search_form_geo_label', __('Appears within the input box.','dbem') );
			em_options_input_text ( __( 'Default distance', 'dbem' ), 'dbem_search_form_geo_distance_default', __('Enter a number.','dbem'), '');
			em_options_select ( __( 'Default distance unit', 'dbem' ), 'dbem_search_form_geo_unit_default', array('km'=>'km','mi'=>'mi'), '');
			?>
			</tbody>
		</table>
		<table class="form-table">
		    <tr class="em-header"><td colspan="2"><h4><?php _e('Advanced Search Fields','dbem'); ?></h4></td></tr>
			<?php
			em_options_radio_binary ( __( 'Enable advanced fields?', 'dbem' ), 'dbem_search_form_advanced', __('Enables additional advanced search fields such as dates, country, etc.','dbem'), '', '.em-search-form-advanced' );
			?>
			<tbody class="em-search-form-advanced">
			<?php 
			em_options_input_text ( __( 'Search button text', 'dbem' ), 'dbem_search_form_submit', __("If there's no fields to show in the main search section, this button will be used instead at the bottom of the advanced fields.",'dbem'));
			em_options_radio_binary ( __( 'Hidden by default?', 'dbem' ), 'dbem_search_form_advanced_hidden', __('If set to yes, advanced search fields will be hidden by default and can be revealed by clicking the "Advanced Search" link.','dbem'), '', '#dbem_search_form_advanced_show_row, #dbem_search_form_advanced_hide_row' );
			em_options_input_text ( __( 'Show label', 'dbem' ), 'dbem_search_form_advanced_show', __('Appears as the label for this search option.','dbem') );
			em_options_input_text ( __( 'Hide label', 'dbem' ), 'dbem_search_form_advanced_hide', __('Appears as the label for this search option.','dbem') );
			?>
			</tbody>
			<tbody class="em-search-form-advanced em-subsection">
			<tr class="em-subheader"><td colspan="2"><h5><?php esc_html_e( 'Dates', 'dbem' ); ?></h5></td></tr>
			<?php
			em_options_radio_binary ( __( 'Show date range?', 'dbem' ), 'dbem_search_form_dates', '', '', '#dbem_search_form_dates_label_row, #dbem_search_form_dates_separator_row' );
			em_options_input_text ( __( 'Label', 'dbem' ), 'dbem_search_form_dates_label', __('Appears as the label for this search option.','dbem') );
			em_options_input_text ( __( 'Date Separator', 'dbem' ), 'dbem_search_form_dates_separator', sprintf(__( 'For when start/end %s are present, this will seperate the two (include spaces here if necessary).', 'dbem' ), __('dates','dbem')) );
			?>
			<tr class="em-subheader"><td colspan="2"><h5><?php esc_html_e( 'Category', 'dbem' ); ?></h5></td></tr>
			<?php
			em_options_radio_binary ( __( 'Show categories?', 'dbem' ), 'dbem_search_form_categories', '', '', '#dbem_search_form_category_label_row, #dbem_search_form_categories_label_row' );
			em_options_input_text ( __( 'Label', 'dbem' ), 'dbem_search_form_category_label', __('Appears as the label for this search option.','dbem') );
			em_options_input_text ( __( 'Categories dropdown label', 'dbem' ), 'dbem_search_form_categories_label', __('Appears as the first default search option.','dbem') );
			?>
			<tr class="em-subheader"><td colspan="2"><h5><?php esc_html_e( 'Geolocation Search', 'dbem' ); ?></h5></td></tr>
			<?php
			em_options_radio_binary ( __( 'Show distance options?', 'dbem' ), 'dbem_search_form_geo_units', '', '', '#dbem_search_form_geo_units_label_row, #dbem_search_form_geo_distance_options_row' );
			em_options_input_text ( __( 'Label', 'dbem' ), 'dbem_search_form_geo_units_label', __('Appears as the label for this search option.','dbem') );
			em_options_input_text ( __( 'Distance Values', 'dbem' ), 'dbem_search_form_geo_distance_options', __('The numerical units shown to those searching by distance. Use comma-seperated numers, such as "25,50,100".','dbem') );
			?>
			<tr class="em-subheader"><td colspan="2"><h5><?php esc_html_e( 'Country', 'dbem' ); ?></h5></td></tr>
			<?php
			em_options_radio_binary ( __( 'Show countries?', 'dbem' ), 'dbem_search_form_countries', '', '', '#dbem_search_form_country_label_row, #dbem_search_form_countries_label_row' );
			em_options_select ( __( 'Default Country', 'dbem' ), 'dbem_search_form_default_country', em_get_countries(__('no default country', 'dbem')), __('Search form will be pre-selected with this country, if searching by country is disabled above, only search results from this country will be returned.','dbem') );
			em_options_input_text ( __( 'Label', 'dbem' ), 'dbem_search_form_country_label', __('Appears as the label for this search option.','dbem') );
			em_options_input_text ( __( 'All countries text', 'dbem' ), 'dbem_search_form_countries_label', __('Appears as the first default search option.','dbem') );
			?>
			<tr class="em-subheader"><td colspan="2"><h5><?php esc_html_e( 'Region', 'dbem' ); ?></h5></td></tr>
			<?php
			em_options_radio_binary ( __( 'Show regions?', 'dbem' ), 'dbem_search_form_regions', '', '', '#dbem_search_form_region_label_row, #dbem_search_form_regions_label_row' );
			em_options_input_text ( __( 'Label', 'dbem' ), 'dbem_search_form_region_label', __('Appears as the label for this search option.','dbem') );
			em_options_input_text ( __( 'All regions text', 'dbem' ), 'dbem_search_form_regions_label', __('Appears as the first default search option.','dbem') );
			?>
			<tr class="em-subheader"><td colspan="2"><h5><?php esc_html_e( 'State/County', 'dbem' ); ?></h5></td></tr>
			<?php
			em_options_radio_binary ( __( 'Show states?', 'dbem' ), 'dbem_search_form_states', '', '', '#dbem_search_form_state_label_row, #dbem_search_form_states_label_row' );
			em_options_input_text ( __( 'Label', 'dbem' ), 'dbem_search_form_state_label', __('Appears as the label for this search option.','dbem') );
			em_options_input_text ( __( 'All states text', 'dbem' ), 'dbem_search_form_states_label', __('Appears as the first default search option.','dbem') );
			?>
			<tr class="em-subheader"><td colspan="2"><h5><?php esc_html_e( 'City/Town', 'dbem' ); ?></h5></td></tr>
			<?php
			em_options_radio_binary ( __( 'Show towns/cities?', 'dbem' ), 'dbem_search_form_towns', '', '', '#dbem_search_form_town_label_row, #dbem_search_form_towns_label_row' );
			em_options_input_text ( __( 'Label', 'dbem' ), 'dbem_search_form_town_label', __('Appears as the label for this search option.','dbem') );
			em_options_input_text ( __( 'All towns/cities text', 'dbem' ), 'dbem_search_form_towns_label', __('Appears as the first default search option.','dbem') );
			?>
			</tbody>
			<?php echo $save_button; ?>
		</table>
	</div> <!-- . inside -->
	</div> <!-- .postbox -->

	<div  class="postbox " id="em-opt-date-time" >
	<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php _e ( 'Date/Time', 'dbem' ); ?> </span></h3>
	<div class="inside">
		<p class="em-boxheader"><?php
			$date_time_format_tip = sprintf(__('Date and Time formats follow the <a href="%s">WordPress time formatting conventions</a>', 'dbem'), 'http://codex.wordpress.org/Formatting_Date_and_Time');
			echo $date_time_format_tip; 
		?></p>
		<table class="form-table">
    		<?php
			em_options_input_text ( __( 'Date Format', 'dbem' ), 'dbem_date_format', sprintf(__('For use with the %s placeholder','dbem'),'<code>#_EVENTDATES</code>') );
			em_options_input_text ( __( 'Date Picker Format', 'dbem' ), 'dbem_date_format_js', sprintf(__( 'Same as <em>Date Format</em>, but this is used for the datepickers used by Events Manager. This uses a slightly different format to the others on here, for a list of characters to use, visit the <a href="%s">jQuery formatDate reference</a>', 'dbem' ),'http://docs.jquery.com/UI/Datepicker/formatDate') );
			em_options_input_text ( __( 'Date Separator', 'dbem' ), 'dbem_dates_separator', sprintf(__( 'For when start/end %s are present, this will seperate the two (include spaces here if necessary).', 'dbem' ), __('dates','dbem')) );
			em_options_input_text ( __( 'Time Format', 'dbem' ), 'dbem_time_format', sprintf(__('For use with the %s placeholder','dbem'),'<code>#_EVENTTIMES</code>') );
			em_options_input_text ( __( 'Time Separator', 'dbem' ), 'dbem_times_separator', sprintf(__( 'For when start/end %s are present, this will seperate the two (include spaces here if necessary).', 'dbem' ), __('times','dbem')) );
			em_options_input_text ( __( 'All Day Message', 'dbem' ), 'dbem_event_all_day_message', sprintf(__( 'If an event lasts all day, this text will show if using the %s placeholder', 'dbem' ), '<code>#_EVENTTIMES</code>') );
			em_options_radio_binary ( __( 'Use 24h Format?', 'dbem' ), 'dbem_time_24h', __( 'When creating events, would you like your times to be shown in 24 hour format?', 'dbem' ) );
			echo $save_button;
			?>
		</table>
	</div> <!-- . inside -->
	</div> <!-- .postbox -->
	      
   	<div  class="postbox " id="em-opt-calendar-formats" >
	<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php _e ( 'Calendar', 'dbem' ); ?></span></h3>
	<div class="inside">
    	<table class="form-table">
    		<?php
		    em_options_radio_binary ( __( 'Link directly to event on day with single event?', 'dbem' ), 'dbem_calendar_direct_links', __( "If a calendar day has only one event, you can force a direct link to the event (recommended to avoid duplicate content).",'dbem' ) );
		    em_options_radio_binary ( __( 'Show list on day with single event?', 'dbem' ), 'dbem_display_calendar_day_single', __( "By default, if a calendar day only has one event, it display a single event when clicking on the link of that calendar date. If you select Yes here, you will get always see a list of events.",'dbem' ) );
    		?>
    		<tr class="em-header"><td colspan="2"><h4><?php _e('Small Calendar','dbem'); ?></h4></td></tr>
			<?php
		    em_options_input_text ( __( 'Month format', 'dbem' ), 'dbem_small_calendar_month_format', __('The format of the month/year header of the calendar.','dbem').' '.$date_time_format_tip);
		    em_options_input_text ( __( 'Event titles', 'dbem' ), 'dbem_small_calendar_event_title_format', __( 'The format of the title, corresponding to the text that appears when hovering on an eventful calendar day.', 'dbem' ).$events_placeholder_tip );
		    em_options_input_text ( __( 'Title separator', 'dbem' ), 'dbem_small_calendar_event_title_separator', __( 'The separator appearing on the above title when more than one events are taking place on the same day.', 'dbem' ) );
		    em_options_radio_binary( __( 'Abbreviated weekdays', 'dbem' ), 'dbem_small_calendar_abbreviated_weekdays', __( 'The calendar headings uses abbreviated weekdays','dbem') );
		    em_options_input_text ( __( 'Initial lengths', 'dbem' ), 'dbem_small_calendar_initials_length', __( 'Shorten the calendar headings containing the days of the week, use 0 for the full name.', 'dbem' ).$events_placeholder_tip );
		    em_options_radio_binary( __( 'Show Long Events?', 'dbem' ), 'dbem_small_calendar_long_events', __( 'Events with multiple dates will appear on each of those dates in the calendar.','dbem') );
		    ?>
    		<tr class="em-header"><td colspan="2"><h4><?php _e('Full Calendar','dbem'); ?></h4></td></tr>
		    <?php
		    em_options_input_text ( __( 'Month format', 'dbem' ), 'dbem_full_calendar_month_format', __('The format of the month/year header of the calendar.','dbem').' '.$date_time_format_tip);
		    em_options_input_text ( __( 'Event format', 'dbem' ), 'dbem_full_calendar_event_format', __( 'The format of each event when displayed in the full calendar. Remember to include <code>li</code> tags before and after the event.', 'dbem' ).$events_placeholder_tip );
		    em_options_radio_binary( __( 'Abbreviated weekdays?', 'dbem' ), 'dbem_full_calendar_abbreviated_weekdays', __( 'Use abbreviations, e.g. Friday = Fri. Useful for certain languages where abbreviations differ from full names.','dbem') );
		    em_options_input_text ( __( 'Initial lengths', 'dbem' ), 'dbem_full_calendar_initials_length', __( 'Shorten the calendar headings containing the days of the week, use 0 for the full name.', 'dbem' ).$events_placeholder_tip);
		    em_options_radio_binary( __( 'Show Long Events?', 'dbem' ), 'dbem_full_calendar_long_events', __( 'Events with multiple dates will appear on each of those dates in the calendar.','dbem') );
		    ?>		
		    <tr class="em-header"><td colspan="2"><h4><?php echo __('Calendar Day Event List Settings','dbem'); ?></h4></td></tr>			
			<tr valign="top" id='dbem_display_calendar_orderby_row'>
		   		<th scope="row"><?php _e('Default event list ordering','dbem'); ?></th>
		   		<td>   
					<select name="dbem_display_calendar_orderby" >
						<?php 
							$orderby_options = apply_filters('dbem_display_calendar_orderby_ddm', array(
								'event_name,event_start_time' => __('Order by event name, then event start time','dbem'),
								'event_start_time,event_name' => __('Order by event start time, then event name','dbem')
							)); 
						?>
						<?php foreach($orderby_options as $key => $value) : ?>   
		 				<option value='<?php echo esc_attr($key) ?>' <?php echo ($key == get_option('dbem_display_calendar_orderby')) ? "selected='selected'" : ''; ?>>
		 					<?php echo esc_html($value) ?>
		 				</option>
						<?php endforeach; ?>
					</select> 
					<select name="dbem_display_calendar_order" >
						<?php 
						$ascending = __('Ascending','dbem');
						$descending = __('Descending','dbem');
						$order_options = apply_filters('dbem_display_calendar_order_ddm', array(
							'ASC' => __('All Ascending','dbem'),
							'DESC,ASC' => "$descending, $ascending",
							'DESC,DESC' => "$descending, $descending",
							'DESC' => __('All Descending','dbem')
						)); 
						?>
						<?php foreach( $order_options as $key => $value) : ?>   
		 				<option value='<?php echo esc_attr($key) ?>' <?php echo ($key == get_option('dbem_display_calendar_order')) ? "selected='selected'" : ''; ?>>
		 					<?php echo esc_html($value) ?>
		 				</option>
						<?php endforeach; ?>
					</select>
					<br/>
					<em><?php _e('When Events Manager displays lists of events the default behaviour is ordering by start date in ascending order. To change this, modify the values above.','dbem'); ?></em>
				</td>
		   	</tr>
		   	<?php 
		   		em_options_input_text ( __( 'Calendar events/day limit', 'dbem' ), 'dbem_display_calendar_events_limit', __( 'Limits the number of events on each calendar day. Leave blank for no limit.', 'dbem' ) );
		   		em_options_input_text ( __( 'More Events message', 'dbem' ), 'dbem_display_calendar_events_limit_msg', __( 'Text with link to calendar day page with all events for that day if there are more events than the limit above, leave blank for no link as the day number is also a link.', 'dbem' ) );
		   	?>
		    <tr class="em-header"><td colspan="2"><h4><?php echo sprintf(__('iCal Feed Settings','dbem'),__('Event','dbem')); ?></h4></td></tr>
		    <?php 
			em_options_input_text ( __( 'iCal Title', 'dbem' ), 'dbem_ical_description_format', __( 'The title that will appear in the calendar.', 'dbem' ).$events_placeholder_tip );
			em_options_input_text ( __( 'iCal Description', 'dbem' ), 'dbem_ical_real_description_format', __( 'The description of the event that will appear in the calendar.', 'dbem' ).$events_placeholder_tip );
			em_options_input_text ( __( 'iCal Location', 'dbem' ), 'dbem_ical_location_format', __( 'The location information that will appear in the calendar.', 'dbem' ).$events_placeholder_tip );
			em_options_select( __('iCal Scope','dbem'), 'dbem_ical_scope', em_get_scopes(), __('Choose to show events within a specific time range.','dbem'));
			em_options_input_text ( __( 'iCal Limit', 'dbem' ), 'dbem_ical_limit', __( 'Limits the number of future events shown (0 = unlimited).', 'dbem' ) );						
		    echo $save_button;        
			?>
		</table>
	</div> <!-- . inside -->
	</div> <!-- .postbox -->
	
	<?php if( get_option('dbem_locations_enabled') ): ?>
	<div  class="postbox " id="em-opt-locations-formats" >
	<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php _e ( 'Locations', 'dbem' ); ?> </span></h3>
	<div class="inside">
    	<table class="form-table">
		 	<tr class="em-header"><td colspan="2"><h4><?php echo sprintf(__('%s Page','dbem'),__('Locations','dbem')); ?></h4></td></tr>
			<?php
			em_options_textarea ( sprintf(__('%s list header format','dbem'),__('Locations','dbem')), 'dbem_location_list_item_format_header', sprintf(__( 'This content will appear just above your code for the %s list format below. Default is blank', 'dbem' ), __('locations','dbem')) );
		 	em_options_textarea ( sprintf(__('%s list item format','dbem'),__('Locations','dbem')), 'dbem_location_list_item_format', sprintf(__( 'The format of a single %s in a list.', 'dbem' ), __('locations','dbem')).$locations_placeholder_tip );
			em_options_textarea ( sprintf(__('%s list footer format','dbem'),__('Locations','dbem')), 'dbem_location_list_item_format_footer', sprintf(__( 'This content will appear just below your code for the %s list format above. Default is blank', 'dbem' ), __('locations','dbem')) );
			em_options_input_text ( sprintf(__( 'No %s message', 'dbem' ),__('Locations','dbem')), 'dbem_no_locations_message', sprintf( __( 'The message displayed when no %s are available.', 'dbem' ), __('locations','dbem')) );
		 	?>
		 	<tr class="em-header">
		 	    <td colspan="2">
		 	        <h4><?php echo sprintf(__('Single %s Page','dbem'),__('Location','dbem')); ?></h4>
		 	        <em><?php echo sprintf(__('These formats can be used on %s pages or on other areas of your site displaying an %s.','dbem'),__('location','dbem'),__('location','dbem'));?></em>
		 	</tr>
		 	<?php
			if( EM_MS_GLOBAL && get_option('dbem_ms_global_location_links') ){
			  em_options_input_text (sprintf( __( 'Single %s title format', 'dbem' ),__('location','dbem')), 'dbem_location_page_title_format', sprintf(__( 'The format of a single %s page title.', 'dbem' ),__('location','dbem')).$locations_placeholder_tip );
			}
			em_options_textarea ( sprintf(__('Single %s page format', 'dbem' ),__('location','dbem')), 'dbem_single_location_format', sprintf(__( 'The format of a single %s page.', 'dbem' ),__('location','dbem')).$locations_placeholder_tip );
			?>
			<tr class="em-header">
			    <td colspan="2">
			        <h4><?php echo sprintf(__('%s Excerpts','dbem'),__('Location','dbem')); ?></h4>
		 	        <em><?php echo sprintf(__('These formats can be used when WordPress automatically displays %s excerpts on your site and %s is enabled in your %s settings tab.','dbem'),__('location','dbem'),'<strong>'.__( 'Override Excerpts with Formats?', 'dbem' ).'</strong>','<a href="#formats" class="nav-tab-link" rel="#em-menu-pages">'.__('Pages','dbem').'  &gt; '.sprintf(__('%s List/Archives','dbem'),__('Location','dbem')).'</a>');?></em>
			    </td>
			</tr>
		 	<?php
		 	em_options_textarea ( sprintf(__('%s excerpt', 'dbem' ),__('Location','dbem')), 'dbem_location_excerpt_format', __( 'Used if an excerpt has been defined.', 'dbem' ).$locations_placeholder_tip );				 	
		 	em_options_textarea ( sprintf(__('%s excerpt fallback', 'dbem' ),__('Location','dbem')), 'dbem_location_excerpt_alt_format', __( 'Used if an excerpt has not been defined.', 'dbem' ).$locations_placeholder_tip );
			?>
		 	<tr class="em-header"><td colspan="2"><h4><?php echo sprintf(__('%s List Formats','dbem'),__('Event','dbem')); ?></h4></td></tr>
		 	<?php
		 	em_options_input_text ( __( 'Default event list format header', 'dbem' ), 'dbem_location_event_list_item_header_format', __( 'This content will appear just above your code for the default event list format. Default is blank', 'dbem' ) );
		 	em_options_textarea ( sprintf(__( 'Default %s list format', 'dbem' ),__('events','dbem')), 'dbem_location_event_list_item_format', sprintf(__( 'The format of the events the list inserted in the location page through the %s element.', 'dbem' ).$events_placeholder_tip, '<code>#_LOCATIONNEXTEVENTS</code>, <code>#_LOCATIONPASTEVENTS</code>, <code>#_LOCATIONALLEVENTS</code>') );
			em_options_input_text ( __( 'Default event list format footer', 'dbem' ), 'dbem_location_event_list_item_footer_format', __( 'This content will appear just below your code for the default event list format. Default is blank', 'dbem' ) );
			em_options_textarea ( sprintf(__( 'No %s message', 'dbem' ),__('events','dbem')), 'dbem_location_no_events_message', sprintf(__( 'The message to be displayed in the list generated by %s when no events are available.', 'dbem' ), '<code>#_LOCATIONNEXTEVENTS</code>, <code>#_LOCATIONPASTEVENTS</code>, <code>#_LOCATIONALLEVENTS</code>') );
			?>
		 	<tr class="em-header"><td colspan="2">
		 		<h4><?php echo sprintf(__('Single %s Format','dbem'),__('Event','dbem')); ?></h4>
		 		<p><?php echo sprintf(__('The settings below are used when using the %s placeholder','dbem'), '<code>#_LOCATIONNEXTEVENT</code>'); ?></p>
		 	</td></tr>
		 	<?php
		 	em_options_input_text ( __( 'Next event format', 'dbem' ), 'dbem_location_event_single_format', sprintf(__( 'The format of the next upcoming event in this %s.', 'dbem' ),__('location','dbem')).$events_placeholder_tip );
		 	em_options_input_text ( sprintf(__( 'No %s message', 'dbem' ),__('events','dbem')), 'dbem_location_no_event_message', sprintf(__( 'The message to be displayed in the list generated by %s when no events are available.', 'dbem' ), '<code>#_LOCATIONNEXTEVENT</code>') );
			echo $save_button;
			?>
		</table>
	</div> <!-- . inside -->
	</div> <!-- .postbox -->
	<?php endif; ?>
	
	<?php if( get_option('dbem_categories_enabled') && !(EM_MS_GLOBAL && !is_main_site()) ): ?>
	<div  class="postbox " id="em-opt-categories-formats" >
	<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php _e ( 'Event Categories', 'dbem' ); ?> </span></h3>
	<div class="inside">
    	<table class="form-table">
    		<?php
    		em_options_input_text(sprintf(esc_html__('Default %s color','dbem'), esc_html__('category','dbem')), 'dbem_category_default_color', sprintf(esc_html_x('Colors must be in a valid %s format, such as #FF00EE.', 'hex format', 'dbem'), '<a href="http://en.wikipedia.org/wiki/Web_colors">hex</a>'));
    		?>
		 	<tr class="em-header"><td colspan="2"><h4><?php echo sprintf(__('%s Page','dbem'),__('Categories','dbem')); ?></h4></td></tr>
			<?php
			em_options_textarea ( sprintf(__('%s list header format','dbem'),__('Categories','dbem')), 'dbem_categories_list_item_format_header', sprintf(__( 'This content will appear just above your code for the %s list format below. Default is blank', 'dbem' ), __('categories','dbem')) );
		 	em_options_textarea ( sprintf(__('%s list item format','dbem'),__('Categories','dbem')), 'dbem_categories_list_item_format', sprintf(__( 'The format of a single %s in a list.', 'dbem' ), __('categories','dbem')).$categories_placeholder_tip );
			em_options_textarea ( sprintf(__('%s list footer format','dbem'),__('Categories','dbem')), 'dbem_categories_list_item_format_footer', sprintf(__( 'This content will appear just below your code for the %s list format above. Default is blank', 'dbem' ), __('categories','dbem')) );
			em_options_input_text ( sprintf(__( 'No %s message', 'dbem' ),__('Categories','dbem')), 'dbem_no_categories_message', sprintf( __( 'The message displayed when no %s are available.', 'dbem' ), __('categories','dbem')) );
		 	?>
		 	<tr class="em-header"><td colspan="2"><h4><?php echo sprintf(__('Single %s Page','dbem'),__('Category','dbem')); ?></h4></td></tr>
		 	<?php
			em_options_input_text ( sprintf(__( 'Single %s title format', 'dbem' ),__('category','dbem')), 'dbem_category_page_title_format', __( 'The format of a single category page title.', 'dbem' ).$categories_placeholder_tip );
			em_options_textarea ( sprintf(__('Single %s page format', 'dbem' ),__('category','dbem')), 'dbem_category_page_format', sprintf(__( 'The format of a single %s page.', 'dbem' ),__('category','dbem')).$categories_placeholder_tip );
		 	?>
		 	<tr class="em-header"><td colspan="2"><h4><?php echo sprintf(__('%s List Formats','dbem'),__('Event','dbem')); ?></h4></td></tr>
		 	<?php
		 	em_options_input_text ( __( 'Default event list format header', 'dbem' ), 'dbem_category_event_list_item_header_format', __( 'This content will appear just above your code for the default event list format. Default is blank', 'dbem' ) );
		 	em_options_textarea ( sprintf(__( 'Default %s list format', 'dbem' ),__('events','dbem')), 'dbem_category_event_list_item_format', sprintf(__( 'The format of the events the list inserted in the category page through the %s element.', 'dbem' ).$events_placeholder_tip, '<code>#_CATEGORYPASTEVENTS</code>, <code>#_CATEGORYNEXTEVENTS</code>, <code>#_CATEGORYALLEVENTS</code>') );
			em_options_input_text ( __( 'Default event list format footer', 'dbem' ), 'dbem_category_event_list_item_footer_format', __( 'This content will appear just below your code for the default event list format. Default is blank', 'dbem' ) );
			em_options_textarea ( sprintf(__( 'No %s message', 'dbem' ),__('events','dbem')), 'dbem_category_no_events_message', sprintf(__( 'The message to be displayed in the list generated by %s when no events are available.', 'dbem' ), '<code>#_CATEGORYPASTEVENTS</code>, <code>#_CATEGORYNEXTEVENTS</code>, <code>#_CATEGORYALLEVENTS</code>') );
			?>
		 	<tr class="em-header"><td colspan="2">
		 		<h4><?php echo sprintf(__('Single %s Format','dbem'),__('Event','dbem')); ?></h4>
		 		<p><?php echo sprintf(__('The settings below are used when using the %s placeholder','dbem'), '<code>#_CATEGORYNEXTEVENT</code>'); ?></p>
		 	</td></tr>
		 	<?php
		 	em_options_input_text ( __( 'Next event format', 'dbem' ), 'dbem_category_event_single_format', sprintf(__( 'The format of the next upcoming event in this %s.', 'dbem' ),__('category','dbem')).$events_placeholder_tip );
		 	em_options_input_text ( sprintf(__( 'No %s message', 'dbem' ),__('events','dbem')), 'dbem_category_no_event_message', sprintf(__( 'The message to be displayed in the list generated by %s when no events are available.', 'dbem' ), '<code>#_CATEGORYNEXTEVENT</code>') );
			echo $save_button;
			?>
		</table>
	</div> <!-- . inside -->
	</div> <!-- .postbox -->
	<?php endif; ?>
	
	<?php if( get_option('dbem_tags_enabled') ): ?>
	<div  class="postbox " id="em-opt-tags-formats" >
	<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php _e ( 'Event Tags', 'dbem' ); ?> </span></h3>
	<div class="inside">
    	<table class="form-table">
		 	<tr class="em-header"><td colspan="2"><h4><?php echo sprintf(__('%s Page','dbem'),__('Tags','dbem')); ?></h4></td></tr>
			<?php
			em_options_textarea ( sprintf(__('%s list header format','dbem'),__('Tags','dbem')), 'dbem_tags_list_item_format_header', sprintf(__( 'This content will appear just above your code for the %s list format below. Default is blank', 'dbem' ), __('tags','dbem')) );
		 	em_options_textarea ( sprintf(__('%s list item format','dbem'),__('Tags','dbem')), 'dbem_tags_list_item_format', sprintf(__( 'The format of a single %s in a list.', 'dbem' ), __('tags','dbem')).$categories_placeholder_tip );
			em_options_textarea ( sprintf(__('%s list footer format','dbem'),__('Tags','dbem')), 'dbem_tags_list_item_format_footer', sprintf(__( 'This content will appear just below your code for the %s list format above. Default is blank', 'dbem' ), __('tags','dbem')) );
			em_options_input_text ( sprintf(__( 'No %s message', 'dbem' ),__('Tags','dbem')), 'dbem_no_tags_message', sprintf( __( 'The message displayed when no %s are available.', 'dbem' ), __('tags','dbem')) );
		 	?>
		 	<tr class="em-header"><td colspan="2"><h4><?php echo sprintf(__('Single %s Page','dbem'),__('Tag','dbem')); ?></h4></td></tr>
		 	<?php
			em_options_input_text ( sprintf(__( 'Single %s title format', 'dbem' ),__('tag','dbem')), 'dbem_tag_page_title_format', __( 'The format of a single tag page title.', 'dbem' ).$categories_placeholder_tip );
			em_options_textarea ( sprintf(__('Single %s page format', 'dbem' ),__('tag','dbem')), 'dbem_tag_page_format', sprintf(__( 'The format of a single %s page.', 'dbem' ),__('tag','dbem')).$categories_placeholder_tip );
		 	?>
		 	<tr class="em-header"><td colspan="2"><h4><?php echo sprintf(__('%s List Formats','dbem'),__('Event','dbem')); ?></h4></td></tr>
		 	<?php
			em_options_input_text ( __( 'Default event list format header', 'dbem' ), 'dbem_tag_event_list_item_header_format', __( 'This content will appear just above your code for the default event list format. Default is blank', 'dbem' ) );
		 	em_options_textarea ( sprintf(__( 'Default %s list format', 'dbem' ),__('events','dbem')), 'dbem_tag_event_list_item_format', __( 'The format of the events the list inserted in the tag page through the <code>#_TAGNEXTEVENTS</code>, <code>#_TAGNEXTEVENTS</code> and <code>#_TAGALLEVENTS</code> element.', 'dbem' ).$categories_placeholder_tip );
			em_options_input_text ( __( 'Default event list format footer', 'dbem' ), 'dbem_tag_event_list_item_footer_format', __( 'This content will appear just below your code for the default event list format. Default is blank', 'dbem' ) );
			em_options_textarea ( sprintf(__( 'No %s message', 'dbem' ),__('events','dbem')), 'dbem_tag_no_events_message', __( 'The message to be displayed in the list generated by <code>#_TAGNEXTEVENTS</code>, <code>#_TAGNEXTEVENTS</code> and <code>#_TAGALLEVENTS</code> when no events are available.', 'dbem' ) );
			?>
		 	<tr class="em-header"><td colspan="2">
		 		<h4><?php echo sprintf(__('Single %s Format','dbem'),__('Event','dbem')); ?></h4>
		 		<p><?php echo sprintf(__('The settings below are used when using the %s placeholder','dbem'), '<code>#_TAGNEXTEVENT</code>'); ?></p>
		 	</td></tr>
		 	<?php
		 	em_options_input_text ( __( 'Next event format', 'dbem' ), 'dbem_tag_event_single_format', sprintf(__( 'The format of the next upcoming event in this %s.', 'dbem' ),__('tag','dbem')).$events_placeholder_tip );
		 	em_options_input_text ( sprintf(__( 'No %s message', 'dbem' ),__('events','dbem')), 'dbem_tag_no_event_message', sprintf(__( 'The message to be displayed in the list generated by %s when no events are available.', 'dbem' ), '<code>#_CATEGORYNEXTEVENT</code>') );
			echo $save_button;
			?>
		</table>
	</div> <!-- . inside -->
	</div> <!-- .postbox -->
	<?php endif; ?>
	
	<div  class="postbox " id="em-opt-rss-formats" >
	<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php _e ( 'RSS', 'dbem' ); ?> </span></h3>
	<div class="inside">
    	<table class="form-table">
			<?php				
			em_options_input_text ( __( 'RSS main title', 'dbem' ), 'dbem_rss_main_title', __( 'The main title of your RSS events feed.', 'dbem' ).$events_placeholder_tip );
			em_options_input_text ( __( 'RSS main description', 'dbem' ), 'dbem_rss_main_description', __( 'The main description of your RSS events feed.', 'dbem' ) );
			em_options_input_text ( __( 'RSS title format', 'dbem' ), 'dbem_rss_title_format', __( 'The format of the title of each item in the events RSS feed.', 'dbem' ).$events_placeholder_tip );
			em_options_input_text ( __( 'RSS description format', 'dbem' ), 'dbem_rss_description_format', __( 'The format of the description of each item in the events RSS feed.', 'dbem' ).$events_placeholder_tip );
			em_options_input_text ( __( 'RSS limit', 'dbem' ), 'dbem_rss_limit', __( 'Limits the number of future events shown (0 = unlimited).', 'dbem' ) );
			em_options_select( __('RSS Scope','dbem'), 'dbem_rss_scope', em_get_scopes(), __('Choose to show events within a specific time range.','dbem'));
			?>							
			<tr valign="top" id='dbem_rss_orderby_row'>
		   		<th scope="row"><?php _e('Default event list ordering','dbem'); ?></th>
		   		<td>   
					<select name="dbem_rss_orderby" >
						<?php 
							$orderby_options = apply_filters('em_settings_events_default_orderby_ddm', array(
								'event_start_date,event_start_time,event_name' => __('Order by start date, start time, then event name','dbem'),
								'event_name,event_start_date,event_start_time' => __('Order by name, start date, then start time','dbem'),
								'event_name,event_end_date,event_end_time' => __('Order by name, end date, then end time','dbem'),
								'event_end_date,event_end_time,event_name' => __('Order by end date, end time, then event name','dbem'),
							)); 
						?>
						<?php foreach($orderby_options as $key => $value) : ?>   
		 				<option value='<?php echo esc_attr($key) ?>' <?php echo ($key == get_option('dbem_rss_orderby')) ? "selected='selected'" : ''; ?>>
		 					<?php echo esc_html($value); ?>
		 				</option>
						<?php endforeach; ?>
					</select> 
					<select name="dbem_rss_order" >
						<?php 
						$ascending = __('Ascending','dbem');
						$descending = __('Descending','dbem');
						$order_options = apply_filters('em_settings_events_default_order_ddm', array(
							'ASC' => __('All Ascending','dbem'),
							'DESC,ASC,ASC' => __("$descending, $ascending, $ascending",'dbem'),
							'DESC,DESC,ASC' => __("$descending, $descending, $ascending",'dbem'),
							'DESC' => __('All Descending','dbem'),
							'ASC,DESC,ASC' => __("$ascending, $descending, $ascending",'dbem'),
							'ASC,DESC,DESC' => __("$ascending, $descending, $descending",'dbem'),
							'ASC,ASC,DESC' => __("$ascending, $ascending, $descending",'dbem'),
							'DESC,ASC,DESC' => __("$descending, $ascending, $descending",'dbem'),
						)); 
						?>
						<?php foreach( $order_options as $key => $value) : ?>   
		 				<option value='<?php echo esc_attr($key) ?>' <?php echo ($key == get_option('dbem_rss_order')) ? "selected='selected'" : ''; ?>>
		 					<?php echo esc_html($value); ?>
		 				</option>
						<?php endforeach; ?>
					</select>
					<br/>
					<em><?php _e('When Events Manager displays lists of events the default behaviour is ordering by start date in ascending order. To change this, modify the values above.','dbem'); ?></em>
				</td>
		   	</tr>
			<?php
			echo $save_button;
			?>
		</table>
	</div> <!-- . inside -->
	</div> <!-- .postbox -->
	
	<div  class="postbox " id="em-opt-maps-formats" >
	<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php _e ( 'Maps', 'dbem' ); ?> </span></h3>
	<div class="inside">
		<p class="em-boxheader"><?php echo sprintf(__('You can use Google Maps to show where your events are located. For more information on using maps, <a href="%s">see our documentation</a>.','dbem'),'http://wp-events-plugin.com/documentation/google-maps/'); ?>
		<table class='form-table'> 
			<?php $gmap_is_active = get_option ( 'dbem_gmap_is_active' ); ?>
			<tr valign="top">
				<th scope="row"><?php _e ( 'Enable Google Maps integration?', 'dbem' ); ?></th>
				<td>
					<?php _e ( 'Yes' ); ?> <input id="dbem_gmap_is_active_yes" name="dbem_gmap_is_active" type="radio" value="1" <?php echo ($gmap_is_active) ? "checked='checked'":''; ?> />
					<?php _e ( 'No' ); ?> <input name="dbem_gmap_is_active" type="radio" value="0" <?php echo ($gmap_is_active) ? '':"checked='checked'"; ?> /><br />
					<em><?php _e ( 'Check this option to enable Goggle Map integration.', 'dbem' )?></em>
				</td>
				<?php em_options_input_text(__('Default map width','dbem'), 'dbem_map_default_width', sprintf(__('Can be in form of pixels or a percentage such as %s or %s.', 'dbem'), '<code>100%</code>', '<code>100px</code>')); ?>
				<?php em_options_input_text(__('Default map height','dbem'), 'dbem_map_default_height', sprintf(__('Can be in form of pixels or a percentage such as %s or %s.', 'dbem'), '<code>100%</code>', '<code>100px</code>')); ?>
			</tr>
			<tr class="em-header"><td colspan="2">
				<h4><?php _e('Global Map Format','dbem'); ?></h4>
				<p><?php echo sprintf(__('If you use the %s <a href="%s">shortcode</a>, you can display a map of all your locations and events, the settings below will be used.','dbem'), '<code>[locations_map]</code>','http://wp-events-plugin.com/documentation/shortcodes/'); ?></p>
			</td></tr>
			<?php
			em_options_textarea ( __( 'Location balloon format', 'dbem' ), 'dbem_map_text_format', __( 'The format of of the text appearing in the balloon describing the location.', 'dbem' ).' '.__( 'Event.', 'dbem' ).$locations_placeholder_tip );
			?>
			<tr class="em-header"><td colspan="2">
				<h4><?php _e('Single Location/Event Map Format','dbem'); ?></h4>
				<p><?php echo sprintf(_e('If you use the <code>#_LOCATIONMAP</code> <a href="%s">placeholder</a> when displaying individual event and location information, the settings below will be used.','dbem'), '<code>[locations_map]</code>','http://wp-events-plugin.com/documentation/placeholders/'); ?></p>
			</td></tr>
			<?php
			em_options_textarea ( __( 'Location balloon format', 'dbem' ), 'dbem_location_baloon_format', __( 'The format of of the text appearing in the balloon describing the location.', 'dbem' ).$events_placeholder_tip );
			echo $save_button;     
			?> 
		</table>
	</div> <!-- . inside -->
	</div> <!-- .postbox -->
	
	<?php do_action('em_options_page_footer_formats'); ?>
	
</div> <!-- .em-menu-formats -->