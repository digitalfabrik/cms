<?php if( !function_exists('current_user_can') || !current_user_can('list_users') ) return; ?>
<!-- GENERAL OPTIONS -->
<div class="em-menu-general em-menu-group">
	<div  class="postbox " id="em-opt-general"  >
	<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php _e ( 'General Options', 'dbem' ); ?> </span></h3>
	<div class="inside">
        <table class="form-table">
            <?php em_options_radio_binary ( __( 'Disable thumbnails?', 'dbem' ), 'dbem_thumbnails_enabled', __( 'Select yes to disable Events Manager from enabling thumbnails (some themes may already have this enabled, which we cannot be turned off here).','dbem' ) );  ?>					
			<tr class="em-header">
				<td colspan="2">
					<h4><?php echo sprintf(__('%s Settings','dbem'),__('Event','dbem')); ?></h4>
				</td>
			</tr>
			<?php
			em_options_radio_binary ( __( 'Enable recurrence?', 'dbem' ), 'dbem_recurrence_enabled', __( 'Select yes to enable the recurrence features feature','dbem' ) ); 
			em_options_radio_binary ( __( 'Enable bookings?', 'dbem' ), 'dbem_rsvp_enabled', __( 'Select yes to allow bookings and tickets for events.','dbem' ) );     
			em_options_radio_binary ( __( 'Enable tags?', 'dbem' ), 'dbem_tags_enabled', __( 'Select yes to enable the tag features','dbem' ) );
			if( !(EM_MS_GLOBAL && !is_main_site()) ){
				em_options_radio_binary ( __( 'Enable categories?', 'dbem' ), 'dbem_categories_enabled', __( 'Select yes to enable the category features','dbem' ) );     
				if( get_option('dbem_categories_enabled') ){
					/*default category*/
					$category_options = array();
					$category_options[0] = __('no default category','dbem');
					$EM_Categories = EM_Categories::get();
					foreach($EM_Categories as $EM_Category){
				 		$category_options[$EM_Category->id] = $EM_Category->name;
				 	}
				 	echo "<tr><th>".__( 'Default Category', 'dbem' )."</th><td>";
					wp_dropdown_categories(array( 'hide_empty' => 0, 'name' => 'dbem_default_category', 'hierarchical' => true, 'taxonomy' => EM_TAXONOMY_CATEGORY, 'selected' => get_option('dbem_default_category'), 'show_option_none' => __('None','dbem'), 'class'=>''));
					echo "</br><em>" .__( 'This option allows you to select the default category when adding an event.','dbem' ).' '.__('If an event does not have a category assigned when editing, this one will be assigned automatically.','dbem')."</em>";
					echo "</td></tr>";
				}
			}
			em_options_radio_binary ( sprintf(__( 'Enable %s attributes?', 'dbem' ),__('event','dbem')), 'dbem_attributes_enabled', __( 'Select yes to enable the attributes feature','dbem' ) );
			em_options_radio_binary ( sprintf(__( 'Enable %s custom fields?', 'dbem' ),__('event','dbem')), 'dbem_cp_events_custom_fields', __( 'Custom fields are the same as attributes, except you cannot restrict specific values, users can add any kind of custom field name/value pair. Only available in the WordPress admin area.','dbem' ) );
			if( get_option('dbem_attributes_enabled') ){
				em_options_textarea ( sprintf(__( '%s Attributes', 'dbem' ),__('Event','dbem')), 'dbem_placeholders_custom', sprintf(__( "You can also add event attributes here, one per line in this format <code>#_ATT{key}</code>. They will not appear on event pages unless you insert them into another template below, but you may want to store extra information about an event for other uses. <a href='%s'>More information on placeholders.</a>", 'dbem' ), EM_ADMIN_URL .'&amp;page=events-manager-help') );
			}
			if( get_option('dbem_locations_enabled') ){
				/*default location*/
				if( defined('EM_OPTIMIZE_SETTINGS_PAGE_LOCATIONS') && EM_OPTIMIZE_SETTINGS_PAGE_LOCATIONS ){
	            	em_options_input_text( __( 'Default Location', 'dbem' ), 'dbem_default_location', __('Please enter your Location ID, or leave blank for no location.','dbem').' '.__( 'This option allows you to select the default location when adding an event.','dbem' )." ".__('(not applicable with event ownership on presently, coming soon!)','dbem') );
	            }else{
					$location_options = array();
					$location_options[0] = __('no default location','dbem');
					$EM_Locations = EM_Locations::get();
					foreach($EM_Locations as $EM_Location){
				 		$location_options[$EM_Location->location_id] = $EM_Location->location_name;
				 	}
					em_options_select ( __( 'Default Location', 'dbem' ), 'dbem_default_location', $location_options, __('Please enter your Location ID.','dbem').' '.__( 'This option allows you to select the default location when adding an event.','dbem' )." ".__('(not applicable with event ownership on presently, coming soon!)','dbem') );
				}
				
				/*default location country*/
				em_options_select ( __( 'Default Location Country', 'dbem' ), 'dbem_location_default_country', em_get_countries(__('no default country', 'dbem')), __('If you select a default country, that will be pre-selected when creating a new location.','dbem') );
			}
			?>
			<tr class="em-header">
				<td colspan="2">
					<h4><?php echo sprintf(__('%s Settings','dbem'),__('Location','dbem')); ?></h4>
				</td>
			</tr>
			<?php
			em_options_radio_binary ( __( 'Enable locations?', 'dbem' ), 'dbem_locations_enabled', __( 'If you disable locations, bear in mind that you should remove your location page, shortcodes and related placeholders from your <a href="#formats" class="nav-tab-link" rel="#em-menu-formats">formats</a>.','dbem' ) );
			if( get_option('dbem_locations_enabled') ){ 
				em_options_radio_binary ( __( 'Require locations for events?', 'dbem' ), 'dbem_require_location', __( 'Setting this to no will allow you to submit events without locations. You can use the <code>{no_location}...{/no_location}</code> or <code>{has_location}..{/has_location}</code> conditional placeholder to selectively display location information.','dbem' ) );
				em_options_radio_binary ( __( 'Use dropdown for locations?', 'dbem' ), 'dbem_use_select_for_locations', __( 'Select yes to select location from a drop-down menu; location selection will be faster, but you will lose the ability to insert locations with events','dbem' ) );
				em_options_radio_binary ( sprintf(__( 'Enable %s attributes?', 'dbem' ),__('location','dbem')), 'dbem_location_attributes_enabled', __( 'Select yes to enable the attributes feature','dbem' ) );
				em_options_radio_binary ( sprintf(__( 'Enable %s custom fields?', 'dbem' ),__('location','dbem')), 'dbem_cp_locations_custom_fields', __( 'Custom fields are the same as attributes, except you cannot restrict specific values, users can add any kind of custom field name/value pair. Only available in the WordPress admin area.','dbem' ) );
				if( get_option('dbem_location_attributes_enabled') ){
					em_options_textarea ( sprintf(__( '%s Attributes', 'dbem' ),__('Location','dbem')), 'dbem_location_placeholders_custom', sprintf(__( "You can also add location attributes here, one per line in this format <code>#_LATT{key}</code>. They will not appear on location pages unless you insert them into another template below, but you may want to store extra information about an event for other uses. <a href='%s'>More information on placeholders.</a>", 'dbem' ), EM_ADMIN_URL .'&amp;page=events-manager-help') );
				}
			}
			?>
			<tr class="em-header">
				<td colspan="2">
					<h4><?php echo sprintf(__('%s Settings','dbem'),__('Other','dbem')); ?></h4>
				</td>
			</tr>
			<?php
			em_options_radio_binary ( __('Show some love?','dbem'), 'dbem_credits', __( 'Hundreds of free hours have gone into making this free plugin, show your support and add a small link to the plugin website at the bottom of your event pages.','dbem' ) );
			echo $save_button;
			?>
		</table>
		    
	</div> <!-- . inside --> 
	</div> <!-- .postbox -->
	
	<?php if ( !is_multisite() ){ em_admin_option_box_image_sizes(); } ?>
	
	<?php if ( !is_multisite() || (is_super_admin() && !get_site_option('dbem_ms_global_caps')) ){ em_admin_option_box_caps(); } ?>
	
	<div  class="postbox" id="em-opt-event-submissions" >
	<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php _e ( 'Event Submission Forms', 'dbem' ); ?></span></h3>
	<div class="inside">
            <table class="form-table">
            <tr><td colspan="2" class="em-boxheader">
            	<?php echo sprintf(__('You can allow users to publicly submit events on your blog by using the %s shortcode, and enabling anonymous submissions below.','dbem'), '<code>[event_form]</code>'); ?>
			</td></tr>
			<?php
				em_options_radio_binary ( __( 'Use Visual Editor?', 'dbem' ), 'dbem_events_form_editor', __( 'Users can now use the WordPress editor for easy HTML entry in the submission form.', 'dbem' ) );
				em_options_radio_binary ( __( 'Show form again?', 'dbem' ), 'dbem_events_form_reshow', __( 'When a user submits their event, you can display a new event form again.', 'dbem' ) );
				em_options_textarea ( __( 'Success Message', 'dbem' ), 'dbem_events_form_result_success', __( 'Customize the message your user sees when they submitted their event.', 'dbem' ).$events_placeholder_tip );
				em_options_textarea ( __( 'Successfully Updated Message', 'dbem' ), 'dbem_events_form_result_success_updated', __( 'Customize the message your user sees when they resubmit/update their event.', 'dbem' ).$events_placeholder_tip );
			?>
            <tr class="em-header"><td colspan="2">
            	<h4><?php echo sprintf(__('Anonymous event submissions','dbem'), '<code>[event_form]</code>'); ?></h4>
			</td></tr>
            <?php
				em_options_radio_binary ( __( 'Allow anonymous event submissions?', 'dbem' ), 'dbem_events_anonymous_submissions', __( 'Would you like to allow users to submit bookings anonymously? If so, you can use the new [event_form] shortcode or <code>em_event_form()</code> template tag with this enabled.', 'dbem' ) );
				if( defined('EM_OPTIMIZE_SETTINGS_PAGE_USERS') && EM_OPTIMIZE_SETTINGS_PAGE_USERS ){
	            	em_options_input_text( __('Guest Default User', 'dbem'), 'dbem_events_anonymous_user', __('Please add a User ID.','dbem').' '.__( 'Events require a user to own them. In order to allow events to be submitted anonymously you need to assign that event a specific user. We recommend you create a "Anonymous" subscriber with a very good password and use that. Guests will have the same event permissions as this user when submitting.', 'dbem' ) );
	            }else{
	            	em_options_select ( __('Guest Default User', 'dbem'), 'dbem_events_anonymous_user', em_get_wp_users (), __( 'Events require a user to own them. In order to allow events to be submitted anonymously you need to assign that event a specific user. We recommend you create a "Anonymous" subscriber with a very good password and use that. Guests will have the same event permissions as this user when submitting.', 'dbem' ) );
				}
            	em_options_textarea ( __( 'Success Message', 'dbem' ), 'dbem_events_anonymous_result_success', __( 'Anonymous submitters cannot see or modify their event once submitted. You can customize the success message they see here.', 'dbem' ).$events_placeholder_tip );
			?>
	        <?php echo $save_button; ?>
		</table>
	</div> <!-- . inside --> 
	</div> <!-- .postbox --> 

	<?php do_action('em_options_page_footer'); ?>
	
	<?php /* 
	<div  class="postbox" id="em-opt-geo" >
	<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php _e ( 'Geo APIs', 'dbem' ); ?> <em>(Beta)</em></span></h3>
	<div class="inside">
		<p><?php esc_html_e('Geocoding is the process of converting addresses into geographic coordinates, which can be used to find events and locations near a specific coordinate.','dbem'); ?></p>
		<table class="form-table">
			<?php
				em_options_radio_binary ( __( 'Enable Geocoding Features?', 'dbem' ), 'dbem_geo', '', '', '.em-settings-geocoding');
			?>
		</table>
		<div class="em-settings-geocoding">
		<h4>GeoNames API (geonames.org)</h4>
		<p>We make use of the <a href="http://www.geonames.org">GeoNames</a> web service to suggest locations/addresses to users when searching, and converting these into coordinates.</p>
		<p>To be able to use these services, you must <a href="http://www.geonames.org/login">register an account</a>, activate the free webservice and enter your username below. You are allowed up to 30,000 requests per day, if you require more you can purchase credits from your account.</p>
        <table class="form-table">
			<?php em_options_input_text ( __( 'GeoNames Username', 'dbem' ), 'dbem_geonames_username', __('If left blank, this service will not be used.','dbem')); ?>
		</table>
		</div>
		<table class="form-table"><?php echo $save_button; ?></table>
	</div> <!-- . inside --> 
	</div> <!-- .postbox -->
	*/ ?>
	
	<div  class="postbox" id="em-opt-performance-optimization" >
	<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php _e ( 'Performance Optimization', 'dbem' ); ?> (<?php _e('Advanced','dbem'); ?>)</span></h3>
	<div class="inside">
		<?php 
			$performance_opt_page_instructions = __('In the boxes below, you are expected to write the page IDs. For multiple pages, use comma-seperated values e.g. 1,2,3. Entering 0 means EVERY page, -1 means the home page.','dbem');
		?>
		<div class="em-boxheader">
			<p><?php _e('This section allows you to configure parts of this plugin that will improve performance on your site and increase page speeds by reducing extra files from being unnecessarily included on pages as well as reducing server loads where possible. This only applies to pages outside the admin area.','dbem'); ?></p>
			<p><strong><?php _e('Warning!','dbem'); ?></strong> <?php echo sprintf(__('This is for advanced users, you should know what you\'re doing here or things will not work properly. For more information on how these options work see our <a href="%s" target="_blank">optimization recommendations</a>','dbem'), 'http://wp-events-plugin.com/documentation/optimization-recommendations/'); ?></p>
		</div>
            <table class="form-table">
            <tr class="em-header"><td colspan="2">
            	<h4><?php _e('JavaScript Files','dbem'); ?></h4>
            	<p><?php echo sprintf(__('If you are not using it already, we recommend you try the <a href="%s" target="_blank">Use Google Libraries</a> plugin, because without further optimization options below it already significantly reduces the number of files needed to display your Event pages and will most likely speed up your overall website loading time.' ,'dbem'),'http://wordpress.org/extend/plugins/use-google-libraries/'); ?>
			</td></tr>
			<?php
				em_options_radio_binary ( __( 'Limit JS file loading?', 'dbem' ), 'dbem_js_limit', __( 'Prevent unnecessary loading of JavaScript files on pages where they are not needed.', 'dbem' ) );
			?>
			<tbody id="dbem-js-limit-options">
				<tr class="em-subheader"><td colspan="2">
	            	<?php 
	            	_e('Aside from pages we automatically generate and include certain jQuery files, if you are using Widgets, Shortcode or PHP to display specific items you may need to tell us where you are using them for them to work properly. Below are options for you to include specific jQuery dependencies only on certain pages.','dbem');
	            	echo $performance_opt_page_instructions;
	            	?>
				</td></tr>
				<?php
				em_options_input_text( __( 'General JS', 'dbem' ), 'dbem_js_limit_general', __( 'Loads our own JS file if no other dependencies are already loaded, which is still needed for many items generated by EM using JavaScript such as Calendars, Maps and Booking Forms/Buttons', 'dbem' ), 0 );
				em_options_input_text( __( 'Search Forms', 'dbem' ), 'dbem_js_limit_search', __( 'Include pages where you use shortcodes or widgets to display event search forms.', 'dbem' ) );
				em_options_input_text( __( 'Event Edit and Submission Forms', 'dbem' ), 'dbem_js_limit_events_form', __( 'Include pages where you use shortcode or PHP to display event submission forms.', 'dbem' ) );
				em_options_input_text( __( 'Booking Management Pages', 'dbem' ), 'dbem_js_limit_edit_bookings', __( 'Include pages where you use shortcode or PHP to display event submission forms.', 'dbem' ) );
				?>
			</tbody>
            <tr class="em-header"><td colspan="2">
                <h4><?php _e('CSS File','dbem'); ?></h4>
			</td></tr>
            <?php
				em_options_radio_binary ( __( 'Limit loading of our CSS files?', 'dbem' ), 'dbem_css_limit', __( 'Enabling this will prevent us from loading our CSS file on every page, and will only load on specific pages generated by Events Manager.', 'dbem' ) );
				?>
				<tbody id="dbem-css-limit-options">
				<tr class="em-subheader"><td colspan="2">
	            	<?php echo $performance_opt_page_instructions; ?>
				</td></tr>
				<?php
				em_options_input_text( __( 'Include on', 'dbem' ), 'dbem_css_limit_include', __( 'Our CSS file will only be INCLUDED on all of these pages.', 'dbem' ), 0 );
				em_options_input_text( __( 'Exclude on', 'dbem' ), 'dbem_css_limit_exclude', __( 'Our CSS file will be EXCLUDED on all of these pages. Takes precedence over inclusion rules.', 'dbem' ), 0 );
            	?>
            	</tbody>
            	<?php
			?>
			<tr  class="em-header"><td  colspan="2">  
			    <h4><?php  _e('Thumbnails','dbem');  ?></h4>  
			</td></tr>  
			<?php
            em_options_radio_binary  (  __(  'Disable  WordPress Thumbnails?',  'dbem'  ),  'dbem_disable_thumbnails',  __(  'If set to yes, full sized images will be used and HTML width and height attributes will be used to determine the size.',  'dbem'  ).' '.sprintf(__('Setting this to yes will also make your images crop efficiently with the %s feature in the %s plugin.','dbem'), '<a href="http://jetpack.me/support/photon/">Photon</a>','<a href="https://wordpress.org/plugins/jetpack/">JetPack</a>') );  
            ?>  
	        <?php echo $save_button; ?>
		</table>
		<script type="text/javascript">
			jQuery(document).ready(function($){
				$('input:radio[name="dbem_js_limit"]').change(function(){
					if( $('input:radio[name="dbem_js_limit"]:checked').val() == 1 ){
						$('tbody#dbem-js-limit-options').show();
					}else{
						$('tbody#dbem-js-limit-options').hide();					
					}
				}).trigger('change');
				
				$('input:radio[name="dbem_css_limit"]').change(function(){
					if( $('input:radio[name="dbem_css_limit"]:checked').val() == 1 ){
						$('tbody#dbem-css-limit-options').show();
					}else{
						$('tbody#dbem-css-limit-options').hide();					
					}
				}).trigger('change');
			});
		</script>
	</div> <!-- . inside --> 
	</div> <!-- .postbox --> 
	
	<div  class="postbox" id="em-opt-style-options" >
	<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span><?php _e ( 'Styling Options', 'dbem' ); ?> (<?php _e('Advanced','dbem'); ?>) <em>(Beta)</em></span></h3>
	<div class="inside">
		<p class="em-boxheader">
			<?php _e('Events Manager imposes a minimal amount of styling on websites so that your themes can take over.','dbem'); ?>
			<?php _e('Below are some additional options for individual pages and sections, which you can turn on to enforce custom styling provided by the plugin or off if you want to do your own custom styling.','dbem'); ?>
		</p>
        <table class="form-table">
			<?php
				em_options_radio_binary ( __( 'Search forms', 'dbem' ), 'dbem_css_search');
			?>
			<tr class="em-subheader"><td colspan="2">The options below currently have no effect, but are there so you know what may be added in future updates. You can leave them on if you want furture styling to take effect, or turn them off to keep your current styles as is.</td><tr>
			<?php
				em_options_radio_binary ( __( 'Event/Location admin pages', 'dbem' ), 'dbem_css_editors' );
				em_options_radio_binary ( __( 'Booking admin pages', 'dbem' ), 'dbem_css_rsvpadmin' );
				em_options_radio_binary ( __( 'Events list page', 'dbem' ), 'dbem_css_evlist' );
				em_options_radio_binary ( __( 'Locations list page', 'dbem' ), 'dbem_css_loclist' );
				em_options_radio_binary ( __( 'Event booking forms', 'dbem' ), 'dbem_css_rsvp' );
				em_options_radio_binary ( __( 'Categories list page', 'dbem' ), 'dbem_css_catlist' );
				em_options_radio_binary ( __( 'Tags list page', 'dbem' ), 'dbem_css_taglist' );
				echo $save_button;
			?>
		</table>
	</div> <!-- . inside --> 
	</div> <!-- .postbox -->
	
	<?php if ( !is_multisite() ) { em_admin_option_box_uninstall(); } ?>
	
	<?php if( get_option('dbem_migrate_images') ): ?>
	<div  class="postbox " >
	<div class="handlediv" title="<?php __('Click to toggle', 'dbem'); ?>"><br /></div><h3><span>Migrate Images From Version 4</span></h3>
	<div class="inside">
		<?php /* Not translating as it's temporary */ //EM4 ?>
	   <p>You have the option of migrating images from version 4 so they become the equivalent of 'featured images' like with regular WordPress posts and pages and are also available in your media library.</p>
	   <p>Your event and location images will still display correctly on the front-end even if you don't migrate, but will not show up within your edit location/event pages in the admin area.</p>
	   <p>
	      <a href="<?php echo $_SERVER['REQUEST_URI'] ?>&amp;em_migrate_images=1&amp;_wpnonce=<?php echo wp_create_nonce('em_migrate_images'); ?>" />Migrate Images</a><br />
	      <a href="<?php echo $_SERVER['REQUEST_URI'] ?>&amp;em_not_migrate_images=1&amp;_wpnonce=<?php echo wp_create_nonce('em_not_migrate_images'); ?>" />Do Not Migrate Images</a>
	   </p>
	</div> <!-- . inside --> 
	</div> <!-- .postbox -->
	<?php endif; ?>
</div> <!-- .em-menu-general -->