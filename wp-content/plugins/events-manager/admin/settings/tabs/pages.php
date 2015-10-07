<?php if( !function_exists('current_user_can') || !current_user_can('list_users') ) return; ?>
<!-- PAGE OPTIONS -->
<div class="em-menu-pages em-menu-group"  <?php if( !defined('EM_SETTINGS_TABS') || !EM_SETTINGS_TABS) : ?>style="display:none;"<?php endif; ?>>			
    	<?php
    	$template_page_tip = __( "Many themes display extra meta information on post pages such as 'posted by' or 'post date' information, which may not be desired. Usually, page templates contain less clutter.", 'dbem' );
    	$template_page_tip .= ' '. __("If you choose 'Pages' then %s will be shown using your theme default page template, alternatively choose from page templates that come with your specific theme.",'dbem');
    	$template_page_tip .= ' '. str_replace('#','http://codex.wordpress.org/Post_Types#Template_Files',__("Be aware that some themes will not work with this option, if so (or you want to make your own changes), you can create a file named <code>single-%s.php</code> <a href='#'>as shown on the wordpress codex</a>, and leave this set to Posts.", 'dbem'));
    	$body_class_tip = __('If you would like to add extra classes to your body html tag when a single %s page is displayed, enter it here. May be useful or necessary if your theme requires special class names for specific templates.','dbem');
    	$post_class_tip = __('Same concept as the body classes option, but some themes also use the <code>post_class()</code> function within page content to differentiate styling between post types.','dbem');
    	$format_override_tip = __("By using formats, you can control how your %s are displayed from within the Events Manager <a href='#formats' class='nav-tab-link' rel='#em-menu-formats'>Formatting</a> tab above without having to edit your theme files.",'dbem');
    	$page_templates = array(''=>__('Posts'), 'page' => __('Pages'), __('Theme Templates','dbem') => array_flip(get_page_templates()));
    	?>
    	<div  class="postbox" id="em-opt-permalinks" >
		<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php echo sprintf(__('Permalink Slugs','dbem')); ?></span></h3>
		<div class="inside">
			<p class="em-boxheader"><?php _e('You can change the permalink structure of your events, locations, categories and tags here. Be aware that you may want to set up redirects if you change your permalink structures to maintain SEO rankings.','dbem'); ?></p>
        	<table class="form-table">
        	<?php
        	em_options_input_text ( __( 'Events', 'dbem' ), 'dbem_cp_events_slug', sprintf(__('e.g. %s - you can use / Separators too', 'dbem' ), '<strong>'.home_url().'/<code>'.get_option('dbem_cp_events_slug',EM_POST_TYPE_EVENT_SLUG).'</code>/2012-olympics/</strong>'), EM_POST_TYPE_EVENT_SLUG );
			if( get_option('dbem_locations_enabled')  && !(EM_MS_GLOBAL && get_site_option('dbem_ms_mainblog_locations') && !is_main_site()) ){
            	em_options_input_text ( __( 'Locations', 'dbem' ), 'dbem_cp_locations_slug', sprintf(__('e.g. %s - you can use / Separators too', 'dbem' ), '<strong>'.home_url().'/<code>'.get_option('dbem_cp_locations_slug',EM_POST_TYPE_LOCATION_SLUG).'</code>/wembley-stadium/</strong>'), EM_POST_TYPE_LOCATION_SLUG );
			}
        	if( get_option('dbem_categories_enabled') && !(EM_MS_GLOBAL && !is_main_site()) ){
        		em_options_input_text ( __( 'Event Categories', 'dbem' ), 'dbem_taxonomy_category_slug', sprintf(__('e.g. %s - you can use / Separators too', 'dbem' ), '<strong>'.home_url().'/<code>'.get_option('dbem_taxonomy_category_slug',EM_TAXONOMY_CATEGORY_SLUG).'</code>/sports/</strong>'), EM_TAXONOMY_CATEGORY_SLUG );
        	}
        	if( get_option('dbem_tags_enabled') ){
            	em_options_input_text ( __( 'Event Tags', 'dbem' ), 'dbem_taxonomy_tag_slug', sprintf(__('e.g. %s - you can use / Separators too', 'dbem' ), '<strong>'.home_url().'/<code>'.get_option('dbem_taxonomy_tag_slug',EM_TAXONOMY_TAG_SLUG).'</code>/running/</strong>'), EM_TAXONOMY_TAG_SLUG );
        	}
        	echo $save_button;
        	?>
        	</table>
		</div> <!-- . inside --> 
		</div> <!-- .postbox -->	

		<div  class="postbox " id="em-opt-event-pages" >
		<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php echo sprintf(__('%s Pages','dbem'),__('Event','dbem')); ?></span></h3>
		<div class="inside">
        	<table class="form-table">
        	<?php
        	//em_options_radio_binary ( sprintf(__( 'Display %s as', 'dbem' ),__('events','dbem')), 'dbem_cp_events_template_page', sprintf($template_page_tip, EM_POST_TYPE_EVENT), array(__('Posts'),__('Pages')) );
        	em_options_select( sprintf(__( 'Display %s as', 'dbem' ),__('events','dbem')), 'dbem_cp_events_template', $page_templates, sprintf($template_page_tip, __('events','dbem'), EM_POST_TYPE_EVENT) );
        	em_options_input_text( __('Body Classes','dbem'), 'dbem_cp_events_body_class', sprintf($body_class_tip, __('event','dbem')) );
        	em_options_input_text( __('Post Classes','dbem'), 'dbem_cp_events_post_class', $post_class_tip );
        	em_options_radio_binary ( __( 'Override with Formats?', 'dbem' ), 'dbem_cp_events_formats', sprintf($format_override_tip,__('events','dbem')));
        	em_options_radio_binary ( __( 'Enable Comments?', 'dbem' ), 'dbem_cp_events_comments', sprintf(__('If you would like to disable comments entirely, disable this, otherwise you can disable comments on each single %s. Note that %s with comments enabled will still be until you resave them.','dbem'),__('event','dbem'),__('events','dbem')));
			echo $save_button;
        	?>
        	</table>
		</div> <!-- . inside --> 
		</div> <!-- .postbox -->	
    		
		<div  class="postbox " id="em-opt-event-archives" >
		<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php echo sprintf(__('%s List/Archives','dbem'),__('Event','dbem')); ?></span></h3>
		<div class="inside">
        	<table class="form-table">
			<tr>
				<th><?php echo sprintf(__( 'Events page', 'dbem' )); ?></th>
				<td>
					<?php wp_dropdown_pages(array('name'=>'dbem_events_page', 'selected'=>get_option('dbem_events_page'), 'show_option_none'=>sprintf(__('[No %s Page]', 'dbem'),__('Events','dbem')) )); ?>
					<br />
					<em><?php echo __( 'This option allows you to select which page to use as an events page. If you do not select an events page, to display event lists you can enable event archives or use the appropriate shortcodes and/or template tags.','dbem' ); ?></em>
				</td>
			</tr>
			<tbody class="em-event-page-options">
				<?php 
				em_options_radio_binary ( __( 'Show events search?', 'dbem' ), 'dbem_events_page_search_form', __( "If set to yes, a search form will appear just above your list of events.", 'dbem' ) );
				em_options_radio_binary ( __( 'Display calendar in events page?', 'dbem' ), 'dbem_display_calendar_in_events_page', __( 'This options allows to display the calendar in the events page, instead of the default list. It is recommended not to display both the calendar widget and a calendar page.','dbem' ).' '.__('If you would like to show events that span over more than one day, see the Calendar section on this page.','dbem') );
				em_options_radio_binary ( __( 'Disable title rewriting?', 'dbem' ), 'dbem_disable_title_rewrites', __( "Some WordPress themes don't follow best practices when generating navigation menus, and so the automatic title rewriting feature may cause problems, if your menus aren't working correctly on the event pages, try setting this to 'Yes', and provide an appropriate HTML title format below.",'dbem' ) );
				em_options_input_text ( __( 'Event Manager titles', 'dbem' ), 'dbem_title_html', __( "This only setting only matters if you selected 'Yes' to above. You will notice the events page titles aren't being rewritten, and you have a new title underneath the default page name. This is where you control the HTML of this title. Make sure you keep the #_PAGETITLE placeholder here, as that's what is rewritten by events manager. To control what's rewritten in this title, see settings further down for page titles.", 'dbem' ) );
				?>				
			</tbody>
			<tr class="em-header">
				<td colspan="2">
					<h4><?php echo sprintf(__('WordPress %s Archives','dbem'), __('Event','dbem')); ?></h4>
					<p><?php echo sprintf(__('%s custom post types can have archives, just like normal WordPress posts. If enabled, should you visit your base slug url %s and you will see an post-formatted archive of previous %s', 'dbem'), __('Event','dbem'), '<code>'.home_url().'/'.get_option('dbem_cp_events_slug',EM_POST_TYPE_EVENT_SLUG).'/</code>', __('events','dbem')); ?></p>
					<p><?php echo sprintf(__('Note that assigning a %s page above will override this archive if the URLs collide (which is the default setting, and is recommended for maximum plugin compatibility). You can have both at the same time, but you must ensure that your page and %s slugs are different.','dbem'), __('events','dbem'), __('event','dbem')); ?></p>
				</td>
			</tr>
			<tbody class="em-event-archive-options">
				<?php
				em_options_radio_binary ( __( 'Enable Archives?', 'dbem' ), 'dbem_cp_events_has_archive', __( "Allow WordPress post-style archives.", 'dbem' ) );
				?>
			</tbody>
			<tbody class="em-event-archive-options em-event-archive-sub-options">
				<tr valign="top">
			   		<th scope="row"><?php _e('Default event archive ordering','dbem'); ?></th>
			   		<td>   
						<select name="dbem_events_default_archive_orderby" >
							<?php 
								$event_archive_orderby_options = apply_filters('em_settings_events_default_archive_orderby_ddm', array(
									'_start_ts' => __('Order by start date, start time','dbem'),
									'title' => __('Order by name','dbem')
								)); 
							?>
							<?php foreach($event_archive_orderby_options as $key => $value) : ?>   
			 				<option value='<?php echo esc_attr($key) ?>' <?php echo ($key == get_option('dbem_events_default_archive_orderby')) ? "selected='selected'" : ''; ?>>
			 					<?php echo esc_html($value); ?>
			 				</option>
							<?php endforeach; ?>
						</select> 
						<select name="dbem_events_default_archive_order" >
							<?php 
							$ascending = __('Ascending','dbem');
							$descending = __('Descending','dbem');
							$event_archive_order_options = apply_filters('em_settings_events_default_archive_order_ddm', array(
								'ASC' => __('Ascending','dbem'),
								'DESC' => __('Descending','dbem')
							)); 
							?>
							<?php foreach( $event_archive_order_options as $key => $value) : ?>   
			 				<option value='<?php echo esc_attr($key) ?>' <?php echo ($key == get_option('dbem_events_default_archive_order')) ? "selected='selected'" : ''; ?>>
			 					<?php echo esc_html($value); ?>
			 				</option>
							<?php endforeach; ?>
						</select>
						<br/>
						<em><?php _e('When Events Manager displays lists of events the default behaviour is ordering by start date in ascending order. To change this, modify the values above.','dbem'); ?></em>
					</td>
			   	</tr>
			   	<?php 
			   	em_options_select( __('Event archives scope','dbem'), 'dbem_events_archive_scope', em_get_scopes() );
			   	?>
			</tbody>
			<tr class="em-header">
				<td colspan="2">
					<h4><?php echo _e('General settings','dbem'); ?></h4>
				</td>
			</tr>	
			<?php
			em_options_radio_binary ( __( 'Override with Formats?', 'dbem' ), 'dbem_cp_events_archive_formats', sprintf($format_override_tip,__('events','dbem')));
			em_options_radio_binary ( __( 'Override Excerpts with Formats?', 'dbem' ), 'dbem_cp_events_excerpt_formats', sprintf($format_override_tip,__('events','dbem')));
			em_options_radio_binary ( __( 'Are current events past events?', 'dbem' ), 'dbem_events_current_are_past', __( "By default, events that have an end date later than today will be included in searches, set this to yes to consider events that started 'yesterday' as past.", 'dbem' ) );
			em_options_radio_binary ( __( 'Include in WordPress Searches?', 'dbem' ), 'dbem_cp_events_search_results', sprintf(__( "Allow %s to appear in the built-in search results.", 'dbem' ),__('events','dbem')) );
			?>
			<tr class="em-header">
				<td colspan="2">
					<h4><?php echo sprintf(__('Default %s list options','dbem'), __('event','dbem')); ?></h4>
					<p><?php _e('These can be overriden when using shortcode or template tags.','dbem'); ?></p>
				</td>
			</tr>							
			<tr valign="top" id='dbem_events_default_orderby_row'>
		   		<th scope="row"><?php _e('Default event list ordering','dbem'); ?></th>
		   		<td>   
					<select name="dbem_events_default_orderby" >
						<?php 
							$orderby_options = apply_filters('em_settings_events_default_orderby_ddm', array(
								'event_start_date,event_start_time,event_name' => __('Order by start date, start time, then event name','dbem'),
								'event_name,event_start_date,event_start_time' => __('Order by name, start date, then start time','dbem'),
								'event_name,event_end_date,event_end_time' => __('Order by name, end date, then end time','dbem'),
								'event_end_date,event_end_time,event_name' => __('Order by end date, end time, then event name','dbem'),
							)); 
						?>
						<?php foreach($orderby_options as $key => $value) : ?>   
		 				<option value='<?php echo esc_attr($key) ?>' <?php echo ($key == get_option('dbem_events_default_orderby')) ? "selected='selected'" : ''; ?>>
		 					<?php echo esc_html($value); ?>
		 				</option>
						<?php endforeach; ?>
					</select> 
					<select name="dbem_events_default_order" >
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
		 				<option value='<?php echo esc_attr($key) ?>' <?php echo ($key == get_option('dbem_events_default_order')) ? "selected='selected'" : ''; ?>>
		 					<?php echo esc_html($value); ?>
		 				</option>
						<?php endforeach; ?>
					</select>
					<br/>
					<em><?php _e('When Events Manager displays lists of events the default behaviour is ordering by start date in ascending order. To change this, modify the values above.','dbem'); ?></em>
				</td>
		   	</tr>
			<?php
			em_options_select( __('Event list scope','dbem'), 'dbem_events_page_scope', em_get_scopes(), __('Only show events starting within a certain time limit on the events page. Default is future events with no end time limit.','dbem') );
			em_options_input_text ( __( 'Event List Limits', 'dbem' ), 'dbem_events_default_limit', __( "This will control how many events are shown on one list by default.", 'dbem' ) );
			echo $save_button;
        	?>
        	</table>
		</div> <!-- . inside --> 
		</div> <!-- .postbox -->	
		
		<?php if( get_option('dbem_locations_enabled') ): ?>
		<div  class="postbox " id="em-opt-location-pages" >
		<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php echo sprintf(__('%s Pages','dbem'),__('Location','dbem')); ?></span></h3>
		<div class="inside">
        	<table class="form-table">
        	<?php 
        	//em_options_radio_binary ( sprintf(__( 'Display %s as', 'dbem' ),__('locations','dbem')), 'dbem_cp_locations_template_page', sprintf($template_page_tip, EM_POST_TYPE_LOCATION), array(__('Posts'),__('Pages')) );
        	em_options_select( sprintf(__( 'Display %s as', 'dbem' ),__('locations','dbem')), 'dbem_cp_locations_template', $page_templates, sprintf($template_page_tip, __('locations','dbem'), EM_POST_TYPE_LOCATION) );
        	em_options_input_text( __('Body Classes','dbem'), 'dbem_cp_locations_body_class', sprintf($body_class_tip, __('location','dbem')) );
        	em_options_input_text( __('Post Classes','dbem'), 'dbem_cp_locations_post_class', $post_class_tip );
        	em_options_radio_binary ( __( 'Override with Formats?', 'dbem' ), 'dbem_cp_locations_formats', sprintf($format_override_tip,__('locations','dbem')));
        	em_options_radio_binary ( __( 'Enable Comments?', 'dbem' ), 'dbem_cp_locations_comments', sprintf(__('If you would like to disable comments entirely, disable this, otherwise you can disable comments on each single %s. Note that %s with comments enabled will still be until you resave them.','dbem'),__('location','dbem'),__('locations','dbem')));
			em_options_input_text ( __( 'Event List Limits', 'dbem' ), 'dbem_location_event_list_limit', sprintf(__( "Controls how many events being held at a location are shown per page when using placeholders such as %s. Leave blank for no limit.", 'dbem' ), '<code>#_LOCATIONNEXTEVENTS</code>') );
        	echo $save_button;
			?>
        	</table>
		</div> <!-- . inside --> 
		</div> <!-- .postbox -->	
		
		<div  class="postbox " id="em-opt-location-archives" >
		<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php echo sprintf(__('%s List/Archives','dbem'),__('Location','dbem')); ?></span></h3>
		<div class="inside">
        	<table class="form-table">
			<tr>
				<th><?php echo sprintf(__( '%s page', 'dbem' ),__('Locations','dbem')); ?></th>
				<td>
					<?php wp_dropdown_pages(array('name'=>'dbem_locations_page', 'selected'=>get_option('dbem_locations_page'), 'show_option_none'=>sprintf(__('[No %s Page]', 'dbem'),__('Locations','dbem')) )); ?>
					<br />
					<em><?php echo sprintf(__( 'This option allows you to select which page to use as the %s page. If you do not select a %s page, to display lists you can enable archives or use the appropriate shortcodes and/or template tags.','dbem' ),__('locations','dbem'),__('locations','dbem')); ?></em>
				</td>
			</tr>
			<?php 
				em_options_radio_binary ( __( 'Show locations search?', 'dbem' ), 'dbem_locations_page_search_form', __( "If set to yes, a search form will appear just above your list of locations.", 'dbem' ) ); 
			?>
			<tr class="em-header">
				<td colspan="2">
					<h4><?php echo sprintf(__('WordPress %s Archives','dbem'), __('Location','dbem')); ?></h4>
					<p><?php echo sprintf(__('%s custom post types can have archives, just like normal WordPress posts. If enabled, should you visit your base slug url %s and you will see an post-formatted archive of previous %s', 'dbem'), __('Location','dbem'), '<code>'.home_url().'/'.get_option('dbem_cp_locations_slug',EM_POST_TYPE_LOCATION_SLUG).'/</code>', __('locations','dbem')); ?></p>
					<p><?php echo sprintf(__('Note that assigning a %s page above will override this archive if the URLs collide (which is the default settings, and is recommended for maximum plugin compatibility). You can have both at the same time, but you must ensure that your page and %s slugs are different.','dbem'), __('locations','dbem'), __('location','dbem')); ?></p>
				</td>
			</tr>
			<tbody class="em-location-archive-options">
				<?php
				em_options_radio_binary ( __( 'Enable Archives?', 'dbem' ), 'dbem_cp_locations_has_archive', __( "Allow WordPress post-style archives.", 'dbem' ) );						
				?>
			</tbody>
			<tbody class="em-location-archive-options em-location-archive-sub-options">
				<tr valign="top">
			   		<th scope="row"><?php _e('Default archive ordering','dbem'); ?></th>
			   		<td>   
						<select name="dbem_locations_default_archive_orderby" >
							<?php 
								$orderby_options = apply_filters('em_settings_locations_default_archive_orderby_ddm', array(
									'_location_country' => sprintf(__('Order by %s','dbem'),__('Country','dbem')),
									'_location_town' => sprintf(__('Order by %s','dbem'),__('Town','dbem')),
									'title' => sprintf(__('Order by %s','dbem'),__('Name','dbem'))
								)); 
							?>
							<?php foreach($orderby_options as $key => $value) : ?>   
			 				<option value='<?php echo esc_attr($key) ?>' <?php echo ($key == get_option('dbem_locations_default_archive_orderby')) ? "selected='selected'" : ''; ?>>
			 					<?php echo esc_html($value) ?>
			 				</option>
							<?php endforeach; ?>
						</select> 
						<select name="dbem_locations_default_archive_order" >
							<?php 
							$ascending = __('Ascending','dbem');
							$descending = __('Descending','dbem');
							$order_options = apply_filters('em_settings_locations_default_archive_order_ddm', array(
								'ASC' => __('Ascending','dbem'),
								'DESC' => __('Descending','dbem')
							)); 
							?>
							<?php foreach( $order_options as $key => $value) : ?>   
			 				<option value='<?php echo esc_attr($key) ?>' <?php echo ($key == get_option('dbem_locations_default_archive_order')) ? "selected='selected'" : ''; ?>>
			 					<?php echo esc_html($value) ?>
			 				</option>
							<?php endforeach; ?>
						</select>
					</td>
			   	</tr>	
			</tbody>
			<tr class="em-header">
				<td colspan="2">
					<h4><?php echo _e('General settings','dbem'); ?></h4>
				</td>
			</tr>
			<?php 
			em_options_radio_binary ( __( 'Override with Formats?', 'dbem' ), 'dbem_cp_locations_archive_formats', sprintf($format_override_tip,__('locations','dbem')));
			em_options_radio_binary ( __( 'Override Excerpts with Formats?', 'dbem' ), 'dbem_cp_locations_excerpt_formats', sprintf($format_override_tip,__('locations','dbem')));
        	em_options_radio_binary ( __( 'Include in WordPress Searches?', 'dbem' ), 'dbem_cp_locations_search_results', sprintf(__( "Allow %s to appear in the built-in search results.", 'dbem' ),__('locations','dbem')) );
			?>
			<tr class="em-header">
				<td colspan="2">
					<h4><?php echo sprintf(__('Default %s list options','dbem'), __('location','dbem')); ?></h4>
					<p><?php _e('These can be overriden when using shortcode or template tags.','dbem'); ?></p>
				</td>
			</tr>							
			<tr valign="top" id='dbem_locations_default_orderby_row'>
		   		<th scope="row"><?php _e('Default list ordering','dbem'); ?></th>
		   		<td>   
					<select name="dbem_locations_default_orderby" >
						<?php 
							$orderby_options = apply_filters('em_settings_locations_default_orderby_ddm', array(
								'location_country' => sprintf(__('Order by %s','dbem'),__('Country','dbem')),
								'location_town' => sprintf(__('Order by %s','dbem'),__('Town','dbem')),
								'location_name' => sprintf(__('Order by %s','dbem'),__('Name','dbem'))
							)); 
						?>
						<?php foreach($orderby_options as $key => $value) : ?>
		 				<option value='<?php echo esc_attr($key) ?>' <?php echo ($key == get_option('dbem_locations_default_orderby')) ? "selected='selected'" : ''; ?>>
		 					<?php echo esc_html($value) ?>
		 				</option>
						<?php endforeach; ?>
					</select> 
					<select name="dbem_locations_default_order" >
						<?php 
						$ascending = __('Ascending','dbem');
						$descending = __('Descending','dbem');
						$order_options = apply_filters('em_settings_locations_default_order_ddm', array(
							'ASC' => __('Ascending','dbem'),
							'DESC' => __('Descending','dbem')
						)); 
						?>
						<?php foreach( $order_options as $key => $value) : ?>   
		 				<option value='<?php echo esc_attr($key) ?>' <?php echo ($key == get_option('dbem_locations_default_order')) ? "selected='selected'" : ''; ?>>
		 					<?php echo esc_html($value) ?>
		 				</option>
						<?php endforeach; ?>
					</select>
				</td>
		   	</tr>
			<?php
			em_options_input_text ( __( 'List Limits', 'dbem' ), 'dbem_locations_default_limit', sprintf(__( "This will control how many %s are shown on one list by default.", 'dbem' ),__('locations','dbem')) );
        	echo $save_button;
			?>
        	</table>
		</div> <!-- . inside --> 
		</div> <!-- .postbox -->
		<?php endif; ?>
		
		<?php if( get_option('dbem_categories_enabled') && !(EM_MS_GLOBAL && !is_main_site()) ): ?>
		<div  class="postbox " id="em-opt-categories-pages" >
		<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php echo __('Event Categories','dbem'); ?></span></h3>
		<div class="inside">
		    <div class="em-boxheader">
				<p>
					<?php echo sprintf(__('%s are a <a href="%s" target="_blank">WordPress custom taxonomy</a>.','dbem'), __('Event Categories','dbem'), 'http://codex.wordpress.org/Taxonomies');?>
					<?php echo sprintf(__('%s can be displayed just like normal WordPress custom taxonomies in an archive-style format, however Events Manager by default allows you to completely change the standard look of these archives and use our own <a href="%s">custom formatting</a> methods.','dbem'), __('Event Categories','dbem'), EM_ADMIN_URL .'&amp;page=events-manager-help#event-placeholders'); ?>
				</p>
				<p>
					<?php echo sprintf(__('Due to how we change how this custom taxonomy is displayed when overriding with formats it is strongly advised that you assign a %s page below, which increases comatability with various plugins and themes.','dbem'), __('categories','dbem')); ?>
					<?php sprintf(__('<a href="%s">See some more information</a> on how %s work when overriding with formats.','dbem'), '#', __('categories','dbem')); //not ready yet, but make translatable ?>
				</p>
			</div>
        	<table class="form-table">
			<tr>
				<th><?php echo sprintf(__( '%s page', 'dbem' ),__('Categories','dbem')); ?></th>
				<td>
					<?php wp_dropdown_pages(array('name'=>'dbem_categories_page','selected'=>get_option('dbem_categories_page'), 'show_option_none'=>sprintf(__('[No %s Page]', 'dbem'),__('Categories','dbem')) )); ?>
					<br />
					<em><?php echo sprintf(__( 'This option allows you to select which page to use as the %s page.','dbem' ),__('categories','dbem')); ?></em>
				</td>
			</tr>
			<tr class="em-header">
				<td colspan="2">
					<h4><?php echo _e('General settings','dbem'); ?></h4>
				</td>
			</tr>
			<?php
			em_options_radio_binary ( __( 'Override with Formats?', 'dbem' ), 'dbem_cp_categories_formats', sprintf($format_override_tip,__('categories','dbem'))." ".__('Setting this to yes will make categories display as a page rather than an archive.', 'dbem'));
			?>
			<tr valign="top">
		   		<th scope="row"><?php _e('Default archive ordering','dbem'); ?></th>
		   		<td>   
					<select name="dbem_categories_default_archive_orderby" >
						<?php foreach($event_archive_orderby_options as $key => $value) : ?>   
		 				<option value='<?php echo esc_attr($key) ?>' <?php echo ($key == get_option('dbem_categories_default_archive_orderby')) ? "selected='selected'" : ''; ?>>
		 					<?php echo esc_html($value) ?>
		 				</option>
						<?php endforeach; ?>
					</select> 
					<select name="dbem_categories_default_archive_order" >
						<?php foreach( $event_archive_order_options as $key => $value) : ?>   
		 				<option value='<?php echo esc_attr($key) ?>' <?php echo ($key == get_option('dbem_categories_default_archive_order')) ? "selected='selected'" : ''; ?>>
		 					<?php echo esc_html($value) ?>
		 				</option>
						<?php endforeach; ?>
					</select>
					<br /><?php echo __('When listing events for a category, this order is applied.', 'dbem'); ?>
				</td>
		   	</tr>
			<tr class="em-header">
				<td colspan="2">
					<h4><?php echo sprintf(__('Default %s list options','dbem'), __('category','dbem')); ?></h4>
					<p><?php _e('These can be overriden when using shortcode or template tags.','dbem'); ?></p>
				</td>
			</tr>							
			<tr valign="top" id='dbem_categories_default_orderby_row'>
		   		<th scope="row"><?php _e('Default list ordering','dbem'); ?></th>
		   		<td>   
					<select name="dbem_categories_default_orderby" >
						<?php 
							$orderby_options = apply_filters('em_settings_categories_default_orderby_ddm', array(
								'id' => sprintf(__('Order by %s','dbem'),__('ID','dbem')),
								'count' => sprintf(__('Order by %s','dbem'),__('Count','dbem')),
								'name' => sprintf(__('Order by %s','dbem'),__('Name','dbem')),
								'slug' => sprintf(__('Order by %s','dbem'),__('Slug','dbem')),
								'term_group' => sprintf(__('Order by %s','dbem'),'term_group'),
							)); 
						?>
						<?php foreach($orderby_options as $key => $value) : ?>
		 				<option value='<?php echo esc_attr($key) ?>' <?php echo ($key == get_option('dbem_categories_default_orderby')) ? "selected='selected'" : ''; ?>>
		 					<?php echo esc_html($value) ?>
		 				</option>
						<?php endforeach; ?>
					</select> 
					<select name="dbem_categories_default_order" >
						<?php 
						$ascending = __('Ascending','dbem');
						$descending = __('Descending','dbem');
						$order_options = apply_filters('em_settings_categories_default_order_ddm', array(
							'ASC' => __('Ascending','dbem'),
							'DESC' => __('Descending','dbem')
						)); 
						?>
						<?php foreach( $order_options as $key => $value) : ?>   
		 				<option value='<?php echo esc_attr($key) ?>' <?php echo ($key == get_option('dbem_categories_default_order')) ? "selected='selected'" : ''; ?>>
		 					<?php echo esc_html($value) ?>
		 				</option>
						<?php endforeach; ?>
					</select>
					<br /><?php echo __('When listing categories, this order is applied.', 'dbem'); ?>
				</td>
		   	</tr>
			<?php
			em_options_input_text ( __( 'List Limits', 'dbem' ), 'dbem_categories_default_limit', sprintf(__( "This will control how many %s are shown on one list by default.", 'dbem' ),__('categories','dbem')) );
			em_options_input_text ( __( 'Event List Limits', 'dbem' ), 'dbem_category_event_list_limit', sprintf(__( "Controls how many events belonging to a category are shown per page when using placeholders such as %s. Leave blank for no limit.", 'dbem' ), '<code>#_CATEGORYNEXTEVENTS</code>') );
        	echo $save_button;
			?>
        	</table>
		</div> <!-- . inside --> 
		</div> <!-- .postbox -->
		<?php endif; ?>	
		
		<?php if( get_option('dbem_tags_enabled') ): //disabled for now, will add tag stuff later ?>
		<div  class="postbox " id="em-opt-tags-pages" >
		<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php echo __('Event Tags','dbem'); ?></span></h3>
		<div class="inside">
		    <div class="em-boxheader">
				<p>
					<?php echo sprintf(__('%s are a <a href="%s" target="_blank">WordPress custom taxonomy</a>.','dbem'), __('Event Tags','dbem'), 'http://codex.wordpress.org/Taxonomies');?>
					<?php echo sprintf(__('%s can be displayed just like normal WordPress custom taxonomies in an archive-style format, however Events Manager by default allows you to completely change the standard look of these archives and use our own <a href="%s">custom formatting</a> methods.','dbem'), __('Event Tags','dbem'), EM_ADMIN_URL .'&amp;page=events-manager-help#event-placeholders'); ?>
				</p>
				<p>
					<?php echo sprintf(__('Due to how we change how this custom taxonomy is displayed when overriding with formats it is strongly advised that you assign a %s page below, which increases comatability with various plugins and themes.','dbem'), __('tags','dbem')); ?>
					<?php sprintf(__('<a href="%s">See some more information</a> on how %s work when overriding with formats.','dbem'), '#', __('tags','dbem')); //not ready yet, but make translatable ?>
				</p>
			</div>
            <table class="form-table">
				<tr>
					<th><?php echo sprintf(__( '%s page', 'dbem' ),__('Tags','dbem')); ?></th>
					<td>
						<?php wp_dropdown_pages(array('name'=>'dbem_tags_page','selected'=>get_option('dbem_tags_page'), 'show_option_none'=>sprintf(__('[No %s Page]', 'dbem'),__('Tags','dbem')) )); ?>
						<br />
						<em><?php echo sprintf(__( 'This option allows you to select which page to use as the %s page.','dbem' ),__('tags','dbem'),__('tags','dbem')); ?></em>
					</td>
				</tr>
				<tr class="em-header">
					<td colspan="2">
						<h4><?php echo _e('General settings','dbem'); ?></h4>
					</td>
				</tr>
				<?php
				em_options_radio_binary ( __( 'Override with Formats?', 'dbem' ), 'dbem_cp_tags_formats', sprintf($format_override_tip,__('tags','dbem')));
				?>
				<tr valign="top">
			   		<th scope="row"><?php _e('Default archive ordering','dbem'); ?></th>
			   		<td>   
						<select name="dbem_tags_default_archive_orderby" >
							<?php foreach($event_archive_orderby_options as $key => $value) : ?>   
			 				<option value='<?php echo esc_attr($key) ?>' <?php echo ($key == get_option('dbem_tags_default_archive_orderby')) ? "selected='selected'" : ''; ?>>
			 					<?php echo esc_html($value) ?>
			 				</option>
							<?php endforeach; ?>
						</select> 
						<select name="dbem_tags_default_archive_order" >
							<?php foreach( $event_archive_order_options as $key => $value) : ?>   
			 				<option value='<?php echo esc_attr($key) ?>' <?php echo ($key == get_option('dbem_tags_default_archive_order')) ? "selected='selected'" : ''; ?>>
			 					<?php echo esc_html($value) ?>
			 				</option>
							<?php endforeach; ?>
						</select>
					</td>
			   	</tr>	
				<tr class="em-header">
					<td colspan="2">
						<h4><?php echo sprintf(__('Default %s list options','dbem'), __('tag','dbem')); ?></h4>
						<p><?php _e('These can be overriden when using shortcode or template tags.','dbem'); ?></p>
					</td>
				</tr>			
				<tr valign="top" id='dbem_tags_default_orderby_row'>
			   		<th scope="row"><?php _e('Default list ordering','dbem'); ?></th>
			   		<td>   
						<select name="dbem_tags_default_orderby" >
							<?php 
								$orderby_options = apply_filters('em_settings_tags_default_orderby_ddm', array(
									'id' => sprintf(__('Order by %s','dbem'),__('ID','dbem')),
									'count' => sprintf(__('Order by %s','dbem'),__('Count','dbem')),
									'name' => sprintf(__('Order by %s','dbem'),__('Name','dbem')),
									'slug' => sprintf(__('Order by %s','dbem'),__('Slug','dbem')),
									'term_group' => sprintf(__('Order by %s','dbem'),'term_group'),
								)); 
							?>
							<?php foreach($orderby_options as $key => $value) : ?>
			 				<option value='<?php echo esc_attr($key) ?>' <?php echo ($key == get_option('dbem_tags_default_orderby')) ? "selected='selected'" : ''; ?>>
			 					<?php echo esc_html($value) ?>
			 				</option>
							<?php endforeach; ?>
						</select> 
						<select name="dbem_tags_default_order" >
							<?php 
							$ascending = __('Ascending','dbem');
							$descending = __('Descending','dbem');
							$order_options = apply_filters('em_settings_tags_default_order_ddm', array(
								'ASC' => __('Ascending','dbem'),
								'DESC' => __('Descending','dbem')
							)); 
							?>
							<?php foreach( $order_options as $key => $value) : ?>   
			 				<option value='<?php echo esc_attr($key) ?>' <?php echo ($key == get_option('dbem_tags_default_order')) ? "selected='selected'" : ''; ?>>
			 					<?php echo esc_html($value) ?>
			 				</option>
							<?php endforeach; ?>
						</select>
						<br /><?php echo __('When listing tags, this order is applied.', 'dbem'); ?>
					</td>
			   	</tr>
				<?php
				em_options_input_text ( __( 'List Limits', 'dbem' ), 'dbem_tags_default_limit', sprintf(__( "This will control how many %s are shown on one list by default.", 'dbem' ),__('tags','dbem')) );
				em_options_input_text ( __( 'Event List Limits', 'dbem' ), 'dbem_tag_event_list_limit', sprintf(__( "Controls how many events belonging to a tag are shown per page when using placeholders such as %s. Leave blank for no limit.", 'dbem' ), '<code>#_TAGNEXTEVENTS</code>') );
		   		echo $save_button; ?>
            </table>					    
		</div> <!-- . inside --> 
		</div> <!-- .postbox -->
		<?php endif; ?>
		
		<div  class="postbox " id="em-opt-other-pages" >
		<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php echo sprintf(__('%s Pages','dbem'),__('Other','dbem')); ?></span></h3>
		<div class="inside">
        	<p class="em-boxheader"><?php _e('These pages allow you to provide an event management interface outside the admin area on whatever page you want on your website. Bear in mind that this is overriden by BuddyPress if activated.', 'dbem'); ?></p>
        	<table class="form-table">
			<?php
			$other_pages_tip = 'Using the %s shortcode, you can allow users to manage %s outside the admin area.';
			?>
			<tr class="em-header">
				<td colspan="2">
					<h4><?php _e('My Bookings','dbem'); ?></h4>
					<p><?php _e('This page is where people that have made bookings for an event can go and view their previous bookings.','dbem'); ?>
				</td>
			</tr>
			<tr>
				<th><?php echo sprintf(__( '%s page', 'dbem' ),__('My bookings','dbem')); ?>
				</th>
				<td>
					<?php wp_dropdown_pages(array('name'=>'dbem_my_bookings_page', 'selected'=>get_option('dbem_my_bookings_page'), 'show_option_none'=>'['.__('None', 'dbem').']' )); ?>
					<br />
					<em><?php echo sprintf(__('Users can view their bookings for other events on this page.','dbem' ),'<code>[my_bookings]</code>',__('bookings','dbem')); ?></em>
				</td>
			</tr>	
			<tr valign="top" id='dbem_bookings_default_orderby_row'>
		   		<th scope="row"><?php _e('Default list ordering','dbem'); ?></th>
		   		<td>   
					<select name="dbem_bookings_default_orderby" >
						<?php 
							$orderby_options = apply_filters('em_settings_bookings_default_orderby_ddm', array(
								'event_name' => sprintf(__('Order by %s','dbem'),__('Event Name','dbem')),
								'event_start_date' => sprintf(__('Order by %s','dbem'),__('Start Date','dbem')),
								'booking_date' => sprintf(__('Order by %s','dbem'),__('Booking Date','dbem'))
							)); 
						?>
						<?php foreach($orderby_options as $key => $value) : ?>
		 				<option value='<?php echo esc_attr($key) ?>' <?php echo ($key == get_option('dbem_bookings_default_orderby')) ? "selected='selected'" : ''; ?>>
		 					<?php echo esc_html($value) ?>
		 				</option>
						<?php endforeach; ?>
					</select> 
					<select name="dbem_bookings_default_order" >
						<?php 
						$ascending = __('Ascending','dbem');
						$descending = __('Descending','dbem');
						$order_options = apply_filters('em_settings_bookings_default_order_ddm', array(
							'ASC' => __('Ascending','dbem'),
							'DESC' => __('Descending','dbem')
						));
						?>
						<?php foreach( $order_options as $key => $value) : ?>   
		 				<option value='<?php echo esc_attr($key) ?>' <?php echo ($key == get_option('dbem_bookings_default_order')) ? "selected='selected'" : ''; ?>>
		 					<?php echo esc_html($value) ?>
		 				</option>
						<?php endforeach; ?>
					</select>
				</td>
		   	</tr>
			<tr class="em-header">
				<td colspan="2">
					<h4><?php _e('Front-end management pages','dbem'); ?></h4>
					<p><?php _e('Users with the relevant permissions can manage their own events and bookings to these events on the following pages.','dbem'); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php echo sprintf(__( '%s page', 'dbem' ),__('Edit events','dbem')); ?></th>
				<td>
					<?php wp_dropdown_pages(array('name'=>'dbem_edit_events_page', 'selected'=>get_option('dbem_edit_events_page'), 'show_option_none'=>'['.__('None', 'dbem').']' )); ?>
					<br />
					<em><?php echo sprintf(__('Users can view, add and edit their %s on this page.','dbem'),__('events','dbem')); ?></em>
				</td>
			</tr>	            	
			<tr>
				<th><?php echo sprintf(__( '%s page', 'dbem' ),__('Edit locations','dbem')); ?></th>
				<td>
					<?php wp_dropdown_pages(array('name'=>'dbem_edit_locations_page', 'selected'=>get_option('dbem_edit_locations_page'), 'show_option_none'=>'['.__('None', 'dbem').']' )); ?>
					<br />
					<em><?php echo sprintf(__('Users can view, add and edit their %s on this page.','dbem'),__('locations','dbem')); ?></em>
				</td>
			</tr>	            	
			<tr>
				<th><?php echo sprintf(__( '%s page', 'dbem' ),__('Manage bookings','dbem')); ?></th>
				<td>
					<?php wp_dropdown_pages(array('name'=>'dbem_edit_bookings_page', 'selected'=>get_option('dbem_edit_bookings_page'), 'show_option_none'=>'['.__('None', 'dbem').']' )); ?>
					<br />
					<em><?php _e('Users can manage bookings for their events on this page.','dbem'); ?></em>
				</td>
			</tr>
			<?php echo $save_button; ?>
        	</table>
		</div> <!-- . inside --> 
		</div> <!-- .postbox -->
		
		<?php do_action('em_options_page_footer_pages'); ?>
		
	</div> <!-- .em-menu-pages -->