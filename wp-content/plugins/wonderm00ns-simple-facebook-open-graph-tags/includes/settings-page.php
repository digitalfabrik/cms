<?php
/**
 * @package Facebook Open Graph Meta Tags for WordPress
 * @subpackage Settings Page
 *
 * @since 0.1
 * @author Webdados
 *
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
		
	//First we save!
	if ( isset($_POST['action']) ) {
		if (trim($_POST['action'])=='save') {
			//This should also use the $wonderm00n_open_graph_plugin_settings array, but because of intval and trim we still can't
			$usersettings['fb_app_id_show']= 					intval(webdados_fb_open_graph_post('fb_app_id_show'));
			$usersettings['fb_app_id']= 						trim(webdados_fb_open_graph_post('fb_app_id'));
			$usersettings['fb_admin_id_show']= 					intval(webdados_fb_open_graph_post('fb_admin_id_show'));
			$usersettings['fb_admin_id']= 						trim(webdados_fb_open_graph_post('fb_admin_id'));
			$usersettings['fb_locale_show']= 					intval(webdados_fb_open_graph_post('fb_locale_show'));
			$usersettings['fb_locale']= 						trim(webdados_fb_open_graph_post('fb_locale'));
			$usersettings['fb_sitename_show']= 					intval(webdados_fb_open_graph_post('fb_sitename_show'));
			$usersettings['fb_title_show']= 					intval(webdados_fb_open_graph_post('fb_title_show'));
			$usersettings['fb_title_show_schema']= 				intval(webdados_fb_open_graph_post('fb_title_show_schema'));
			$usersettings['fb_title_show_twitter']= 			intval(webdados_fb_open_graph_post('fb_title_show_twitter'));
			$usersettings['fb_url_show']= 						intval(webdados_fb_open_graph_post('fb_url_show'));
			$usersettings['fb_url_show_twitter']= 				intval(webdados_fb_open_graph_post('fb_url_show_twitter'));
			$usersettings['fb_url_canonical']= 					intval(webdados_fb_open_graph_post('fb_url_canonical'));
			$usersettings['fb_url_add_trailing']= 				intval(webdados_fb_open_graph_post('fb_url_add_trailing'));
			$usersettings['fb_type_show']= 						intval(webdados_fb_open_graph_post('fb_type_show'));
			$usersettings['fb_type_homepage']= 					trim(webdados_fb_open_graph_post('fb_type_homepage'));
			$usersettings['fb_article_dates_show']=				intval(webdados_fb_open_graph_post('fb_article_dates_show'));
			$usersettings['fb_article_sections_show']=			intval(webdados_fb_open_graph_post('fb_article_sections_show'));
			$usersettings['fb_publisher_show']= 				intval(webdados_fb_open_graph_post('fb_publisher_show'));
			$usersettings['fb_publisher']= 						trim(webdados_fb_open_graph_post('fb_publisher'));
			$usersettings['fb_publisher_show_schema']= 			intval(webdados_fb_open_graph_post('fb_publisher_show_schema'));
			$usersettings['fb_publisher_schema']= 				trim(webdados_fb_open_graph_post('fb_publisher_schema'));
			$usersettings['fb_publisher_show_twitter']= 		intval(webdados_fb_open_graph_post('fb_publisher_show_twitter'));
			$usersettings['fb_publisher_twitteruser']= 			trim(webdados_fb_open_graph_post('fb_publisher_twitteruser'));
			$usersettings['fb_author_show']= 					intval(webdados_fb_open_graph_post('fb_author_show'));
			$usersettings['fb_author_show_meta']= 				intval(webdados_fb_open_graph_post('fb_author_show_meta'));
			$usersettings['fb_author_show_linkrelgp']= 			intval(webdados_fb_open_graph_post('fb_author_show_linkrelgp'));
			$usersettings['fb_author_show_twitter']= 			intval(webdados_fb_open_graph_post('fb_author_show_twitter'));
			$usersettings['fb_author_hide_on_pages']= 			intval(webdados_fb_open_graph_post('fb_author_hide_on_pages'));
			$usersettings['fb_desc_show']= 						intval(webdados_fb_open_graph_post('fb_desc_show'));
			$usersettings['fb_desc_show_meta']= 				intval(webdados_fb_open_graph_post('fb_desc_show_meta'));
			$usersettings['fb_desc_show_schema']= 				intval(webdados_fb_open_graph_post('fb_desc_show_schema'));
			$usersettings['fb_desc_show_twitter']= 				intval(webdados_fb_open_graph_post('fb_desc_show_twitter'));
			$usersettings['fb_desc_chars']= 					intval(webdados_fb_open_graph_post('fb_desc_chars'));
			$usersettings['fb_desc_homepage']= 					trim(webdados_fb_open_graph_post('fb_desc_homepage'));
			$usersettings['fb_desc_homepage_customtext']= 		trim(webdados_fb_open_graph_post('fb_desc_homepage_customtext'));
			$usersettings['fb_image_show']= 					intval(webdados_fb_open_graph_post('fb_image_show'));
			$usersettings['fb_image_size_show']= 				intval(webdados_fb_open_graph_post('fb_image_size_show'));
			$usersettings['fb_image_show_schema']= 				intval(webdados_fb_open_graph_post('fb_image_show_schema'));
			$usersettings['fb_image_show_twitter']= 			intval(webdados_fb_open_graph_post('fb_image_show_twitter'));
			$usersettings['fb_image']= 							trim(webdados_fb_open_graph_post('fb_image'));
			$usersettings['fb_image_rss']= 						intval(webdados_fb_open_graph_post('fb_image_rss'));
			$usersettings['fb_image_use_specific']= 			intval(webdados_fb_open_graph_post('fb_image_use_specific'));
			$usersettings['fb_image_use_featured']= 			intval(webdados_fb_open_graph_post('fb_image_use_featured'));
			$usersettings['fb_image_use_content']= 				intval(webdados_fb_open_graph_post('fb_image_use_content'));
			$usersettings['fb_image_use_media']= 				intval(webdados_fb_open_graph_post('fb_image_use_media'));
			$usersettings['fb_image_use_default']= 				intval(webdados_fb_open_graph_post('fb_image_use_default'));
			$usersettings['fb_show_wpseoyoast']= 				intval(webdados_fb_open_graph_post('fb_show_wpseoyoast'));
			$usersettings['fb_show_subheading']= 				intval(webdados_fb_open_graph_post('fb_show_subheading'));
			$usersettings['fb_show_businessdirectoryplugin']=	intval(webdados_fb_open_graph_post('fb_show_businessdirectoryplugin'));
			$usersettings['fb_adv_force_local']= 				intval(webdados_fb_open_graph_post('fb_adv_force_local'));
			$usersettings['fb_adv_notify_fb']= 					intval(webdados_fb_open_graph_post('fb_adv_notify_fb'));
			$usersettings['fb_adv_supress_fb_notice']= 			intval(webdados_fb_open_graph_post('fb_adv_supress_fb_notice'));
			$usersettings['fb_twitter_card_type']= 				trim(webdados_fb_open_graph_post('fb_twitter_card_type'));
			//Update
			update_option('wonderm00n_open_graph_settings', $usersettings);
			//WPML - Register custom website description
			if (function_exists('icl_object_id') && function_exists('icl_register_string')) {
				icl_register_string('wd-fb-og', 'wd_fb_og_desc_homepage_customtext', trim(webdados_fb_open_graph_post('fb_desc_homepage_customtext')));
			}
		}
	}
	
	//Load the settings
	extract(webdados_fb_open_graph_load_settings());

	?>
	<div class="wrap">
		
	<?php screen_icon(); ?>
	<h2><?php echo $webdados_fb_open_graph_plugin_name; ?> (<?php echo $webdados_fb_open_graph_plugin_version; ?>)</h2>
	<br class="clear"/>
	<p><?php _e('Please set some default values and which tags should, or should not, be included. It may be necessary to exclude some tags if other plugins are already including them.', 'wd-fb-og'); ?></p>
	
	<?php
	settings_fields('wonderm00n_open_graph');
	?>
	
	<div class="postbox-container og_left_col">
		<div id="poststuff">
			<form name="form1" method="post">
				
				<div id="webdados_fb_open_graph-settings" class="postbox">
					<h3 id="settings"><?php _e('Settings'); ?></h3>
					<div class="inside">
						<table width="100%" class="form-table">
							<tr>
								<th scope="row"><i class="dashicons-before dashicons-facebook-alt"></i><?php _e('Include Facebook Platform App ID (fb:app_id) tag', 'wd-fb-og'); ?></th>
								<td>
									<input type="checkbox" name="fb_app_id_show" id="fb_app_id_show" value="1" <?php echo (intval($fb_app_id_show)==1 ? ' checked="checked"' : ''); ?> onclick="showAppidOptions();"/>
								</td>
							</tr>
							<tr class="fb_app_id_options">
								<th scope="row"><i class="dashicons-before dashicons-facebook-alt"></i><?php _e('Facebook Platform App ID', 'wd-fb-og'); ?>:</th>
								<td>
									<input type="text" name="fb_app_id" id="fb_app_id" size="30" value="<?php echo trim(esc_attr($fb_app_id)); ?>"/>
								</td>
							</tr>
							<tr>
								<td colspan="2"><hr/></td>
							</tr>
							<tr>
								<th scope="row"><i class="dashicons-before dashicons-facebook-alt"></i><?php _e('Include Facebook Admin(s) ID (fb:admins) tag', 'wd-fb-og'); ?></th>
								<td>
									<input type="checkbox" name="fb_admin_id_show" id="fb_admin_id_show" value="1" <?php echo (intval($fb_admin_id_show)==1 ? ' checked="checked"' : ''); ?> onclick="showAdminOptions();"/>
								</td>
							</tr>
							<tr class="fb_admin_id_options">
								<th scope="row"><i class="dashicons-before dashicons-facebook-alt"></i><?php _e('Facebook Admin(s) ID', 'wd-fb-og'); ?>:</th>
								<td>
									<input type="text" name="fb_admin_id" id="fb_admin_id" size="30" value="<?php echo trim(esc_attr($fb_admin_id)); ?>"/>
									<br/>
									<?php _e('Comma separated if more than one', 'wd-fb-og'); ?>
								</td>
							</tr>
							<tr>
								<td colspan="2"><hr/></td>
							</tr>
							<tr>
								<th scope="row"><i class="dashicons-before dashicons-facebook-alt"></i><?php _e('Include locale (fb:locale) tag', 'wd-fb-og'); ?></th>
								<td>
									<input type="checkbox" name="fb_locale_show" id="fb_locale_show" value="1" <?php echo (intval($fb_locale_show)==1 ? ' checked="checked"' : ''); ?> onclick="showLocaleOptions();"/>
								</td>
							</tr>
							<tr class="fb_locale_options">
								<th scope="row"><i class="dashicons-before dashicons-facebook-alt"></i><?php _e('Locale', 'wd-fb-og'); ?>:</th>
								<td>
									<select name="fb_locale" id="fb_locale">
										<option value=""<?php if (trim($fb_locale)=='') echo ' selected="selected"'; ?>><?php _e('WordPress current locale/language', 'wd-fb-og'); ?> (<?php echo get_locale(); ?>)&nbsp;</option>
										<?php
											$listLocales=false;
											$loadedOnline=false;
											$loadedOffline=false;
											//Online
											if (!empty($_GET['localeOnline'])) {
												if (intval($_GET['localeOnline'])==1) {
													if ($ch = curl_init('http://www.facebook.com/translations/FacebookLocales.xml')) {
														curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
														$fb_locales=curl_exec($ch);
														if (curl_errno($ch)) {
															//echo curl_error($ch);
														} else {
															$info = curl_getinfo($ch);
															if (intval($info['http_code'])==200) {
																//Save the file locally
																$fh = fopen(ABSPATH . 'wp-content/plugins/wonderm00ns-simple-facebook-open-graph-tags/includes/FacebookLocales.xml', 'w') or die("Can't open file");
																fwrite($fh, $fb_locales);
																fclose($fh);
																$listLocales=true;
																$loadedOnline=true;
															}
														}
														curl_close($ch);
													}
												}
											}
											//Offline
											if (!$listLocales) {
												if ($fb_locales=file_get_contents(ABSPATH . 'wp-content/plugins/wonderm00ns-simple-facebook-open-graph-tags/includes/FacebookLocales.xml')) {
													$listLocales=true;
													$loadedOffline=true;
												}
											}
											//OK
											if ($listLocales) {
												$xml=simplexml_load_string($fb_locales);
												$json = json_encode($xml);
												$locales = json_decode($json,TRUE);
												if (is_array($locales['locale'])) {
													foreach ($locales['locale'] as $locale) {
														?><option value="<?php echo $locale['codes']['code']['standard']['representation']; ?>"<?php if (trim($fb_locale)==trim($locale['codes']['code']['standard']['representation'])) echo ' selected="selected"'; ?>><?php echo $locale['englishName']; ?> (<?php echo $locale['codes']['code']['standard']['representation']; ?>)</option><?php
													}
												}
											}
										?>
									</select>
									<br/>
									<?php
									if ($loadedOnline) {
										_e('List loaded from Facebook (online)', 'wd-fb-og');
									} else {
										if ($loadedOffline) {
											_e('List loaded from local cache (offline)', 'wd-fb-og'); ?> - <a href="?page=wonderm00n-open-graph.php&amp;localeOnline=1" onClick="return(confirm('<?php _e('You\\\'l lose any changes you haven\\\'t saved. Are you sure?', 'wd-fb-og'); ?>'));"><?php _e('Reload from Facebook', 'wd-fb-og'); ?></a><?php
										} else {
											_e('List not loaded', 'wd-fb-og');
										}
									}
									?>
								</td>
							</tr>
							<tr>
								<td colspan="2"><hr/></td>
							</tr>
							<tr>
								<th scope="row"><i class="dashicons-before dashicons-facebook-alt"></i><?php _e('Include Site Name (og:site_name) tag', 'wd-fb-og');?></th>
								<td>
									<input type="checkbox" name="fb_sitename_show" id="fb_sitename_show" value="1" <?php echo (intval($fb_sitename_show)==1 ? ' checked="checked"' : ''); ?>/>
								</td>
							</tr>
							<tr>
								<td colspan="2"><hr/></td>
							</tr>
							<tr>
								<th scope="row"><i class="dashicons-before dashicons-facebook-alt"></i><?php _e('Include Post/Page title (og:title) tag', 'wd-fb-og');?></th>
								<td>
									<input type="checkbox" name="fb_title_show" id="fb_title_show" value="1" <?php echo (intval($fb_title_show)==1 ? ' checked="checked"' : ''); ?> onclick="showTitleOptions();"/>
								</td>
							</tr>
							<tr class="fb_title_options">
								<th scope="row"><i class="dashicons-before dashicons-googleplus"></i><?php _e('Include Schema.org "itemprop" Name tag', 'wd-fb-og');?></th>
								<td>
									<input type="checkbox" name="fb_title_show_schema" id="fb_title_show_schema" value="1" <?php echo (intval($fb_title_show_schema)==1 ? ' checked="checked"' : ''); ?>/>
									<br/>
									<i>&lt;meta itemprop="name" content="..."/&gt;</i>
									<br/>
									<?php _e('Recommended for Google+ sharing purposes if no other plugin is setting it already', 'wd-fb-og');?>
								</td>
							</tr>
							<tr class="fb_title_options">
								<th scope="row"><i class="dashicons-before dashicons-twitter"></i><?php _e('Include Twitter Card Title tag', 'wd-fb-og');?></th>
								<td>
									<input type="checkbox" name="fb_title_show_twitter" id="fb_title_show_twitter" value="1" <?php echo (intval($fb_title_show_twitter)==1 ? ' checked="checked"' : ''); ?>/>
									<br/>
									<i>&lt;meta name="twitter:title" content=..."/&gt;</i>
									<br/>
									<?php _e('Recommended for Twitter sharing purposes if no other plugin is setting it already', 'wd-fb-og');?>
								</td>
							</tr>
							<tr>
								<td colspan="2"><hr/></td>
							</tr>
							<tr>
								<th scope="row"><i class="dashicons-before dashicons-facebook-alt"></i><?php _e('Include URL (og:url) tag', 'wd-fb-og');?></th>
								<td>
									<input type="checkbox" name="fb_url_show" id="fb_url_show" value="1" <?php echo (intval($fb_url_show)==1 ? ' checked="checked"' : ''); ?> onclick="showUrlOptions();"/>
								</td>
							</tr>
							<tr>
								<th scope="row"><i class="dashicons-before dashicons-twitter"></i><?php _e('Include Twitter Card URL tag', 'wd-fb-og');?></th>
								<td>
									<input type="checkbox" name="fb_url_show_twitter" id="fb_url_show_twitter" value="1" <?php echo (intval($fb_url_show_twitter)==1 ? ' checked="checked"' : ''); ?>/>
								</td>
							</tr>
							<tr class="fb_url_options">
								<th scope="row"><i class="dashicons-before dashicons-admin-site"></i><?php _e('Add trailing slash at the end', 'wd-fb-og');?>:</th>
								<td>
									<input type="checkbox" name="fb_url_add_trailing" id="fb_url_add_trailing" value="1" <?php echo (intval($fb_url_add_trailing)==1 ? ' checked="checked"' : ''); ?> onclick="showUrlTrail();"/>
									<br/>
									<?php _e('On the homepage will be', 'wd-fb-og');?>: <i><?php echo get_option('siteurl'); ?><span id="fb_url_add_trailing_example">/</span></i>
								</td>
							</tr>
							<tr class="fb_url_options">
								<th scope="row"><i class="dashicons-before dashicons-admin-site"></i><?php _e('Set Canonical URL', 'wd-fb-og');?>:</th>
								<td>
									<input type="checkbox" name="fb_url_canonical" id="fb_url_canonical" value="1" <?php echo (intval($fb_url_canonical)==1 ? ' checked="checked"' : ''); ?>/>
									<br/>
									<i>&lt;link rel="canonical" href="..."/&gt;</i>
								</td>
							</tr>
							<tr>
								<td colspan="2"><hr/></td>
							</tr>
							<tr>
								<th scope="row"><i class="dashicons-before dashicons-facebook-alt"></i><?php _e('Include Type (og:type) tag', 'wd-fb-og');?></th>
								<td>
									<input type="checkbox" name="fb_type_show" id="fb_type_show" value="1" <?php echo (intval($fb_type_show)==1 ? ' checked="checked"' : ''); ?> onclick="showTypeOptions();"/>
									<br/>
									<?php printf( __('Will be "%1$s" for posts and pages and "%2$s" or "%3$s"; for the homepage', 'wd-fb-og'), 'article', 'website', 'blog' );?>
								</td>
							</tr>
							<tr class="fb_type_options">
								<th scope="row"><i class="dashicons-before dashicons-facebook-alt"></i><?php _e('Homepage type', 'wd-fb-og');?>:</th>
								<td>
									<?php _e('Use', 'wd-fb-og');?>
									<select name="fb_type_homepage" id="fb_type_homepage">
										<option value="website"<?php if (trim($fb_type_homepage)=='' || trim($fb_type_homepage)=='website') echo ' selected="selected"'; ?>>website&nbsp;</option>
										<option value="blog"<?php if (trim($fb_type_homepage)=='blog') echo ' selected="selected"'; ?>>blog&nbsp;</option>
									</select>
								</td>
							</tr>
							<tr>
								<td colspan="2"><hr/></td>
							</tr>
							<tr>
								<th scope="row"><i class="dashicons-before dashicons-facebook-alt"></i><?php _e('Include published and modified dates (article:published_time, article:modified_time and og:updated_time) tags', 'wd-fb-og');?></th>
								<td>
									<input type="checkbox" name="fb_article_dates_show" id="fb_article_dates_show" value="1" <?php echo (intval($fb_article_dates_show)==1 ? ' checked="checked"' : ''); ?>/>
									<br/>
									<?php _e('Works for posts only', 'wd-fb-og'); ?>
								</td>
							</tr>
							<tr>
								<td colspan="2"><hr/></td>
							</tr>
							<tr>
								<th scope="row"><i class="dashicons-before dashicons-facebook-alt"></i><?php _e('Include article section (article:section) tags', 'wd-fb-og');?></th>
								<td>
									<input type="checkbox" name="fb_article_sections_show" id="fb_article_sections_show" value="1" <?php echo (intval($fb_article_sections_show)==1 ? ' checked="checked"' : ''); ?>/>
									<br/>
									<?php _e('Works for posts only', 'wd-fb-og'); ?>, <?php _e('from the categories', 'wd-fb-og'); ?>
								</td>
							</tr>
							<tr>
								<td colspan="2"><hr/></td>
							</tr>
							<tr>
									<th scope="row"><i class="dashicons-before dashicons-facebook-alt"></i><?php _e('Include Publisher Page (article:publisher) tag', 'wd-fb-og');?></th>
									<td>
										<input type="checkbox" name="fb_publisher_show" id="fb_publisher_show" value="1" <?php echo (intval($fb_publisher_show)==1 ? ' checked="checked"' : ''); ?> onclick="showPublisherOptions();"/>
										<br/>
										<?php _e('Links the website to the publisher Facebook Page.', 'wd-fb-og');?>
									</td>
								</tr>
								<tr class="fb_publisher_options">
									<th scope="row"><i class="dashicons-before dashicons-facebook-alt"></i><?php _e('Website\'s Facebook Page', 'wd-fb-og');?>:</th>
									<td>
										<input type="text" name="fb_publisher" id="fb_publisher" size="50" value="<?php echo trim(esc_attr($fb_publisher)); ?>"/>
										<br/>
										<?php _e('Full URL with http://', 'wd-fb-og');?>
									</td>
								</tr>
								<tr>
									<th scope="row"><i class="dashicons-before dashicons-googleplus"></i><?php _e('Include Google+ "publisher" tag', 'wd-fb-og');?>:</th>
									<td>
										<input type="checkbox" name="fb_publisher_show_schema" id="fb_publisher_show_schema" value="1" <?php echo (intval($fb_publisher_show_schema)==1 ? ' checked="checked"' : ''); ?> onclick="showPublisherSchemaOptions();"/>
										<br/>
										<?php _e('Links the website to the publisher Google+ Page.', 'wd-fb-og');?>
									</td>
								</tr>
								<tr class="fb_publisher_schema_options">
									<th scope="row"><i class="dashicons-before dashicons-googleplus"></i><?php _e('Website\'s Google+ Page', 'wd-fb-og');?>:</th>
									<td>
										<input type="text" name="fb_publisher_schema" id="fb_publisher_schema" size="50" value="<?php echo trim(esc_attr($fb_publisher_schema)); ?>"/>
										<br/>
										<?php _e('Full URL with http://', 'wd-fb-og');?>
									</td>
								</tr>
								<tr>
									<th scope="row"><i class="dashicons-before dashicons-twitter"></i><?php _e('Include Twitter Card Website Username tag', 'wd-fb-og');?>:</th>
									<td>
										<input type="checkbox" name="fb_publisher_show_twitter" id="fb_publisher_show_twitter" value="1" <?php echo (intval($fb_publisher_show_twitter)==1 ? ' checked="checked"' : ''); ?> onclick="showPublisherTwitterOptions();"/>
										<br/>
										<?php _e('Links the website to the publisher Twitter Username.', 'wd-fb-og');?>
									</td>
								</tr>
								<tr class="fb_publisher_twitter_options">
									<th scope="row"><i class="dashicons-before dashicons-twitter"></i><?php _e('Website\'s Twitter Username', 'wd-fb-og');?>:</th>
									<td>
										<input type="text" name="fb_publisher_twitteruser" id="fb_publisher_twitteruser" size="20" value="<?php echo trim(esc_attr($fb_publisher_twitteruser)); ?>"/>
										<br/>
										<?php _e('Twitter username (without @)', 'wd-fb-og');?>
									</td>
								</tr>
								<tr>
									<td colspan="2"><hr/></td>
								</tr>

								<tr>
									<th scope="row"><i class="dashicons-before dashicons-facebook-alt"></i><?php _e('Include Author Profile (article:author) tag', 'wd-fb-og');?></th>
									<td>
										<input type="checkbox" name="fb_author_show" id="fb_author_show" value="1" <?php echo (intval($fb_author_show)==1 ? ' checked="checked"' : ''); ?> onclick="showAuthorOptions();"/>
										<br/>
										<?php _e('Links the article to the author Facebook Profile. The user\'s Facebook profile URL must be filled in.', 'wd-fb-og');?>
									</td>
								</tr>
								<tr class="fb_author_options">
									<th scope="row"><i class="dashicons-before dashicons-admin-site"></i><?php _e('Include Meta Author tag', 'wd-fb-og');?></th>
									<td>
										<input type="checkbox" name="fb_author_show_meta" id="fb_author_show_meta" value="1" <?php echo (intval($fb_author_show_meta)==1 ? ' checked="checked"' : ''); ?>/>
										<br/>
										<i>&lt;meta name="author" content="..."/&gt;</i>
										<br/>
										<?php _e('Sets the article author name', 'wd-fb-og');?>
									</td>
								</tr>
								<tr class="fb_author_options">
									<th scope="row"><i class="dashicons-before dashicons-googleplus"></i><?php _e('Include Google+ link rel "author" tag', 'wd-fb-og');?></th>
									<td>
										<input type="checkbox" name="fb_author_show_linkrelgp" id="fb_author_show_linkrelgp" value="1" <?php echo (intval($fb_author_show_linkrelgp)==1 ? ' checked="checked"' : ''); ?>/>
										<br/>
										<i>&lt;link rel="author" href="..."/&gt;</i>
										<br/>
										<?php _e('Links the article to the author Google+ Profile (authorship). The user\'s Google+ profile URL must be filled in.', 'wd-fb-og');?>
									</td>
								</tr>
								<tr class="fb_author_options">
									<th scope="row"><i class="dashicons-before dashicons-twitter"></i><?php _e('Include Twitter Card Creator tag', 'wd-fb-og');?></th>
									<td>
										<input type="checkbox" name="fb_author_show_twitter" id="fb_author_show_twitter" value="1" <?php echo (intval($fb_author_show_twitter)==1 ? ' checked="checked"' : ''); ?>/>
										<br/>
										<i>&lt;meta name="twitter:creator" content="@..."/&gt;</i>
										<br/>
										<?php _e('Links the article to the author Twitter profile. The user\'s Twitter user must be filled in.', 'wd-fb-og');?>
									</td>
								</tr>
								<tr class="fb_author_options">
									<th scope="row"><i class="dashicons-before dashicons-admin-site"></i><?php _e('Hide author on pages', 'wd-fb-og');?></th>
									<td>
										<input type="checkbox" name="fb_author_hide_on_pages" id="fb_author_hide_on_pages" value="1" <?php echo (intval($fb_author_hide_on_pages)==1 ? ' checked="checked"' : ''); ?>/>
										<br/>
										<?php _e('Hides all author tags on pages.', 'wd-fb-og');?>
									</td>
								</tr>
								<tr>
									<td colspan="2"><hr/></td>
								</tr>

								<tr>
									<th scope="row"><i class="dashicons-before dashicons-facebook-alt"></i><?php _e('Include Description (og:description) tag', 'wd-fb-og');?></th>
									<td>
										<input type="checkbox" name="fb_desc_show" id="fb_desc_show" value="1" <?php echo (intval($fb_desc_show)==1 ? ' checked="checked"' : ''); ?> onclick="showDescriptionOptions();"/>
									</td>
								</tr>
								<tr class="fb_description_options">
									<th scope="row"><i class="dashicons-before dashicons-admin-site"></i><?php _e('Include Meta Description tag', 'wd-fb-og');?></th>
									<td>
										<input type="checkbox" name="fb_desc_show_meta" id="fb_desc_show_meta" value="1" <?php echo (intval($fb_desc_show_meta)==1 ? ' checked="checked"' : ''); ?>/>
										<br/>
										<i>&lt;meta name="description" content="..."/&gt;</i>
										<br/>
										<?php _e('Recommended for SEO purposes if no other plugin is setting it already', 'wd-fb-og');?>
									</td>
								</tr>
								<tr class="fb_description_options">
									<th scope="row"><i class="dashicons-before dashicons-googleplus"></i><?php _e('Include Schema.org "itemprop" Description tag', 'wd-fb-og');?></th>
									<td>
										<input type="checkbox" name="fb_desc_show_schema" id="fb_desc_show_schema" value="1" <?php echo (intval($fb_desc_show_schema)==1 ? ' checked="checked"' : ''); ?>/>
										<br/>
										<i>&lt;meta itemprop="description" content="..."/&gt;</i>
										<br/>
										<?php _e('Recommended for Google+ sharing purposes if no other plugin is setting it already', 'wd-fb-og');?>
									</td>
								</tr>
								<tr class="fb_description_options">
									<th scope="row"><i class="dashicons-before dashicons-twitter"></i><?php _e('Include Twitter Card Description tag', 'wd-fb-og');?></th>
									<td>
										<input type="checkbox" name="fb_desc_show_twitter" id="fb_desc_show_twitter" value="1" <?php echo (intval($fb_desc_show_twitter)==1 ? ' checked="checked"' : ''); ?>/>
										<br/>
										<i>&lt;meta name="twitter:description" content"..."/&gt;</i>
										<br/>
										<?php _e('Recommended for Twitter sharing purposes if no other plugin is setting it already', 'wd-fb-og');?>
									</td>
								</tr>
								<tr class="fb_description_options">
									<th scope="row"><i class="dashicons-before dashicons-admin-site"></i><?php _e('Description maximum length', 'wd-fb-og');?>:</th>
									<td>
										<input type="text" name="fb_desc_chars" id="fb_desc_chars" size="3" maxlength="3" value="<?php echo (intval($fb_desc_chars)>0 ? intval($fb_desc_chars) : ''); ?>"/> characters,
										<br/>
										<?php _e('0 or blank for no maximum length', 'wd-fb-og');?>
									</td>
								</tr>
								<tr class="fb_description_options">
									<th scope="row"><i class="dashicons-before dashicons-admin-site"></i><?php _e('Homepage description', 'wd-fb-og');?>:</th>
									<td>
										<?php
										$hide_home_description=false;
										if (get_option('show_on_front')=='page') {
											$hide_home_description=true;
											_e('The description of your front page:', 'wd-fb-og');
											echo ' <a href="'.get_edit_post_link(get_option('page_on_front')).'" target="_blank">'.get_the_title(get_option('page_on_front')).'</a>';
										}; ?>
										<div<?php if ($hide_home_description) echo ' style="display: none;"'; ?>><?php _e('Use', 'wd-fb-og');?>
											<select name="fb_desc_homepage" id="fb_desc_homepage" onchange="showDescriptionCustomText();">
												<option value=""<?php if (trim($fb_desc_homepage)=='') echo ' selected="selected"'; ?>><?php _e('Website tagline', 'wd-fb-og');?>&nbsp;</option>
												<option value="custom"<?php if (trim($fb_desc_homepage)=='custom') echo ' selected="selected"'; ?>><?php _e('Custom text', 'wd-fb-og');?>&nbsp;</option>
											</select>
											<div id="fb_desc_homepage_customtext_div">
												<textarea name="fb_desc_homepage_customtext" id="fb_desc_homepage_customtext" rows="3" cols="50"><?php echo trim(esc_attr($fb_desc_homepage_customtext)); ?></textarea>
												<?php
												if (function_exists('icl_object_id') && function_exists('icl_register_string')) {
													?>
													<br/>
													<?php
													printf(
														__('WPML users: Set the default language description here, save changes and then go to <a href="%s">WPML &gt; String translation</a> to set it for other languages.', 'wd-fb-og'),
														'admin.php?page=wpml-string-translation/menu/string-translation.php&amp;context=wd-fb-og'
													); 
												}
												?>
											</div>
										</div>
									</td>
								</tr>
								<tr>
									<td colspan="2"><hr/></td>
								</tr>
								<tr>
									<th scope="row"><i class="dashicons-before dashicons-facebook-alt"></i><?php _e('Include Image (og:image) tag', 'wd-fb-og');?></th>
									<td>
										<input type="checkbox" name="fb_image_show" id="fb_image_show" value="1" <?php echo (intval($fb_image_show)==1 ? ' checked="checked"' : ''); ?> onclick="showImageOptions();"/>
										<br/>
										<?php _e('All images MUST have at least 200px on both dimensions in order to Facebook to load them at all.<br/>1200x630px for optimal results.<br/>Minimum of 600x315px is recommended.', 'wd-fb-og');?>
									</td>
								</tr>
								<tr class="fb_image_options">
									<th scope="row"><i class="dashicons-before dashicons-facebook-alt"></i><?php _e('Include Image size (og:image:width and og:image:height) tags', 'wd-fb-og');?></th>
									<td>
										<input type="checkbox" name="fb_image_size_show" id="fb_image_size_show" value="1" <?php echo (intval($fb_image_size_show)==1 ? ' checked="checked"' : ''); ?>/>
										<br/>
										<?php _e('Recommended only if Facebook is having problems loading the image when the post is shared for the first time.', 'wd-fb-og');?>
									</td>
								</tr>
								<tr class="fb_image_options">
									<th scope="row"><i class="dashicons-before dashicons-googleplus"></i><?php _e('Include Schema.org "itemprop" Image tag', 'wd-fb-og');?></th>
									<td>
										<input type="checkbox" name="fb_image_show_schema" id="fb_image_show_schema" value="1" <?php echo (intval($fb_image_show_schema)==1 ? ' checked="checked"' : ''); ?>/>
										<br/>
										<i>&lt;meta itemprop="image" content="..."/&gt;</i>
										<br/>
										<?php _e('Recommended for Google+ sharing purposes if no other plugin is setting it already', 'wd-fb-og');?>
									</td>
								</tr>
								<tr class="fb_image_options">
									<th scope="row"><i class="dashicons-before dashicons-twitter"></i><?php _e('Include Twitter Card Image tag', 'wd-fb-og');?></th>
									<td>
										<input type="checkbox" name="fb_image_show_twitter" id="fb_image_show_twitter" value="1" <?php echo (intval($fb_image_show_twitter)==1 ? ' checked="checked"' : ''); ?>/>
										<br/>
										<i>&lt;meta name="twitter:image:src" content="..."/&gt;</i>
										<br/>
										<?php _e('Recommended for Twitter sharing purposes if no other plugin is setting it already', 'wd-fb-og');?>
									</td>
								</tr>
								<tr class="fb_image_options">
									<th scope="row"><i class="dashicons-before dashicons-admin-site"></i><?php _e('Default image', 'wd-fb-og');?>:</th>
									<td>
										<input type="text" name="fb_image" id="fb_image" size="50" value="<?php echo trim(esc_attr($fb_image)); ?>"/>
										<input id="fb_image_button" class="button" type="button" value="Upload/Choose image" />
										<br/>
										<?php _e('Full URL with http://', 'wd-fb-og');?>
										<br/>
										<?php _e('Recommended size: 1200x630px', 'wd-fb-og'); ?>
									</td>
								</tr>
								<tr class="fb_image_options">
									<th scope="row"><i class="dashicons-before dashicons-rss"></i><?php _e('Add image to RSS/RSS2 feeds', 'wd-fb-og');?></th>
									<td>
										<input type="checkbox" name="fb_image_rss" id="fb_image_rss" value="1" <?php echo (intval($fb_image_rss)==1 ? ' checked="checked"' : ''); ?> onclick="showImageOptions();"/>
										<br/>
										<?php _e('For auto-posting apps like RSS Graffiti, twitterfeed, ...', 'wd-fb-og');?>
									</td>
								</tr>
								<tr class="fb_image_options">
									<th scope="row"><i class="dashicons-before dashicons-admin-site"></i><?php _e('On posts/pages', 'wd-fb-og');?>:</th>
									<td>
										<div>
											1) <input type="checkbox" name="fb_image_use_specific" id="fb_image_use_specific" value="1" <?php echo (intval($fb_image_use_specific)==1 ? ' checked="checked"' : ''); ?>/>
											<?php _e('Image will be fetched from the specific "Open Graph Image" custom field on the post', 'wd-fb-og');?>
										</div>
										<div>
											2) <input type="checkbox" name="fb_image_use_featured" id="fb_image_use_featured" value="1" <?php echo (intval($fb_image_use_featured)==1 ? ' checked="checked"' : ''); ?>/>
											<?php _e('If it\'s not set, image will be fetched from post/page featured/thumbnail picture', 'wd-fb-og');?>
										</div>
										<div>
											3) <input type="checkbox" name="fb_image_use_content" id="fb_image_use_content" value="1" <?php echo (intval($fb_image_use_content)==1 ? ' checked="checked"' : ''); ?>/>
											<?php _e('If it doesn\'t exist, use the first image from the post/page content', 'wd-fb-og');?>
										</div>
										<div>
											4) <input type="checkbox" name="fb_image_use_media" id="fb_image_use_media" value="1" <?php echo (intval($fb_image_use_media)==1 ? ' checked="checked"' : ''); ?>/>
											<?php _e('If it doesn\'t exist, use first image from the post/page media gallery', 'wd-fb-og');?>
										</div>
										<div>
											5) <input type="checkbox" name="fb_image_use_default" id="fb_image_use_default" value="1" <?php echo (intval($fb_image_use_default)==1 ? ' checked="checked"' : ''); ?>/>
											<?php _e('If it doesn\'t exist, use the default image above', 'wd-fb-og');?>
										</div>
									</td>
								</tr>
								<tr>
									<td colspan="2"><hr/></td>
								</tr>
								<tr>
									<th scope="row"><i class="dashicons-before dashicons-twitter"></i><?php _e('Twitter Card Type', 'wd-fb-og');?>:</th>
									<td>
										<select name="fb_twitter_card_type" id="fb_twitter_card_type">
											<option value="summary"<?php if (trim($fb_twitter_card_type)=='summary') echo ' selected="selected"'; ?>><?php _e('Summary Card', 'wd-fb-og');?></option>
											<option value="summary_large_image"<?php if (trim($fb_twitter_card_type)=='summary_large_image') echo ' selected="selected"'; ?>><?php _e('Summary Card with Large Image', 'wd-fb-og');?></option>
										</select>
									</td>
								</tr>
						</table>
					</div>
				</div>

				<div id="webdados_fb_open_graph-thirdparty" class="postbox">
					<h3 id="thirdparty"><?php _e('3rd Party Integration', 'wd-fb-og');?></h3>
					<div class="inside">
						<?php
						$thirdparty=false;
						//WordPress SEO by Yoast
						if ( defined('WPSEO_VERSION') ) {
							$thirdparty=true;
							?>
							<hr/>
							<a name="wpseo"></a>
							<h4><a href="http://wordpress.org/plugins/wordpress-seo/" target="_blank">WordPress SEO by Yoast</a></h4>
							<p><?php _e('It\'s HIGHLY recommended to go to <a href="admin.php?page=wpseo_social" target="_blank">SEO &gt; Social</a> and disable "Add Open Graph meta data", "Add Twitter card meta data" and "Add Google+ specific post meta data"', 'wd-fb-og'); ?> <?php _e('even if you don\'t enable integration bellow. You will get duplicate tags if you don\'t do this.', 'wd-fb-og'); ?></p>
							<table width="100%" class="form-table">
									<tr>
										<th scope="row"><i class="dashicons-before dashicons-admin-site"></i><?php _e('Use title, url (canonical) and description from WPSEO', 'wd-fb-og');?></th>
										<td>
											<input type="checkbox" name="fb_show_wpseoyoast" id="fb_show_wpseoyoast" value="1" <?php echo (intval($fb_show_wpseoyoast)==1 ? ' checked="checked"' : ''); ?>/>
										</td>
									</tr>
								</table>
							<?php
						}
						//SubHeading
						if (webdados_fb_open_graph_subheadingactive()) {
							$thirdparty=true;
							?>
							<hr/>
							<h4><a href="http://wordpress.org/extend/plugins/subheading/" target="_blank">SubHeading</a></h4>
							<table width="100%" class="form-table">
									<tr>
										<th scope="row"><i class="dashicons-before dashicons-admin-site"></i><?php _e('Add SubHeading to Post/Page title', 'wd-fb-og');?></th>
										<td>
											<input type="checkbox" name="fb_show_subheading" id="fb_show_subheading" value="1" <?php echo (intval($fb_show_subheading)==1 ? ' checked="checked"' : ''); ?>/>
										</td>
									</tr>
								</table>
							<?php
						}
						//Business Directory Plugin 
						if(is_plugin_active('business-directory-plugin/wpbusdirman.php')) {
							$thirdparty=true;
							?>
							<hr/>
							<h4><a href="http://wordpress.org/extend/plugins/business-directory-plugin/" target="_blank">Business Directory Plugin</a></h4>
							<table width="100%" class="form-table">
									<tr>
										<th scope="row"><i class="dashicons-before dashicons-admin-site"></i><?php _e('Use BDP listing contents as OG tags', 'wd-fb-og');?></th>
										<td>
											<input type="checkbox" name="fb_show_businessdirectoryplugin" id="fb_show_businessdirectoryplugin" value="1" <?php echo (intval($fb_show_businessdirectoryplugin)==1 ? ' checked="checked"' : ''); ?>/>
											<br/>
											<?php _e('Setting "Include URL", "Set Canonical URL", "Include Description" and "Include Image" options above is HIGHLY recommended', 'wd-fb-og');?>
										</td>
									</tr>
								</table>
							<?php
						}
						if (!$thirdparty) {
							?>
							<p><?php _e('You don\'t have any compatible 3rd Party plugin installed/active.', 'wd-fb-og');?></p>
							<p><?php _e('This plugin is currently compatible with:', 'wd-fb-og');?></p>
							<ul>
								<li><a href="http://wordpress.org/extend/plugins/wordpress-seo/" target="_blank">WordPress SEO by Yoast</a></li>
								<li><a href="http://wordpress.org/extend/plugins/subheading/" target="_blank">SubHeading</a></li>
								<li><a href="http://wordpress.org/extend/plugins/business-directory-plugin/" target="_blank">Business Directory Plugin</a></li>
							</ul>
							<?php
						}
						?>
					</div>
				</div>

				<div id="webdados_fb_open_graph-advanced" class="postbox">
					<h3 id="advanced"><?php _e('Advanced settings', 'wd-fb-og');?></h3>
					<div class="inside">
						<p><?php _e('Don\'t mess with this unless you know what you\'re doing', 'wd-fb-og');?></p>
						<table width="100%" class="form-table">
							<tr>
								<th scope="row"><i class="dashicons-before dashicons-admin-generic"></i><?php _e('Force getimagesize on local file even if allow_url_fopen=1', 'wd-fb-og'); ?></th>
								<td>
									<input type="checkbox" name="fb_adv_force_local" id="fb_adv_force_local" value="1" <?php echo (intval($fb_adv_force_local)==1 ? ' checked="checked"' : ''); ?>/>
									<br/>
									<?php _e('May cause problems with some multisite configurations but fix "HTTP request failed" errors', 'wd-fb-og');?>
								</td>
							</tr>
							<tr>
								<th scope="row"><i class="dashicons-before dashicons-facebook-alt"></i><?php _e('Try to update Facebook Open Graph Tags cache when saving the post', 'wd-fb-og'); ?></th>
								<td>
									<input type="checkbox" name="fb_adv_notify_fb" id="fb_adv_notify_fb" value="1" onclick="showFBNotifyOptions();"<?php echo (intval($fb_adv_notify_fb)==1 ? ' checked="checked"' : ''); ?>/>
								</td>
							</tr>
							<tr class="fb_adv_notify_fb_options">
								<th scope="row"><i class="dashicons-before dashicons-facebook-alt"></i><?php _e('Supress Facebook Open Graph Tags cache updated notice', 'wd-fb-og'); ?></th>
								<td>
									<input type="checkbox" name="fb_adv_supress_fb_notice" id="fb_adv_supress_fb_notice" value="1" <?php echo (intval($fb_adv_supress_fb_notice)==1 ? ' checked="checked"' : ''); ?>/>
								</td>
							</tr>
						</table>
					</div>
				</div>
				
				<p class="submit">
					<input type="hidden" name="action" value="save"/>
					<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
				</p>

			</form>
		</div>
	</div>
	
	<?php
		$links[0]['text']=__('Test your URLs at Facebook URL Linter / Debugger', 'wd-fb-og');
		$links[0]['url']='https://developers.facebook.com/tools/debug';
		
		$links[5]['text']=__('Test (and request approval for) your URLs at Twitter Card validator', 'wd-fb-og');
		$links[5]['url']='https://cards-dev.twitter.com/validator';

		$links[10]['text']=__('About the Open Graph Protocol (on Facebook)', 'wd-fb-og');
		$links[10]['url']='https://developers.facebook.com/docs/opengraph/';

		$links[20]['text']=__('The Open Graph Protocol (official website)', 'wd-fb-og');
		$links[20]['url']='http://ogp.me/';

		$links[25]['text']=__('About Twitter Cards', 'wd-fb-og');
		$links[25]['url']='hhttps://dev.twitter.com/cards/getting-started';

		$links[30]['text']=__('Plugin official URL', 'wd-fb-og');
		$links[30]['url']='http://www.webdados.pt/produtos-e-servicos/internet/desenvolvimento-wordpress/facebook-open-graph-meta-tags-wordpress/?utm_source=fb_og_wp_plugin_settings&amp;utm_medium=link&amp;utm_campaign=fb_og_wp_plugin';

		$links[40]['text']=__('Author\'s website: Webdados', 'wd-fb-og');
		$links[40]['url']='http://www.webdados.pt/?utm_source=fb_og_wp_plugin_settings&amp;utm_medium=link&amp;utm_campaign=fb_og_wp_plugin';

		$links[50]['text']=__('Author\'s Facebook page: Webdados', 'wd-fb-og');
		$links[50]['url']='http://www.facebook.com/Webdados';

		$links[60]['text']=__('Author\'s Twitter account: @Wonderm00n<br/>(Webdados founder)', 'wd-fb-og');
		$links[60]['url']='http://twitter.com/wonderm00n';
	?>
	<div class="postbox-container og_right_col">
		
		<div id="poststuff">
			<div id="webdados_fb_open_graph_links" class="postbox">
				<h3 id="settings"><?php _e('Rate this plugin', 'wd-fb-og');?></h3>
				<div class="inside">
					<?php _e('If you like this plugin,', 'wd-fb-og');?> <a href="http://wordpress.org/extend/plugins/wonderm00ns-simple-facebook-open-graph-tags/" target="_blank"><?php _e('please give it a high Rating', 'wd-fb-og');?></a>.
				</div>
			</div>
		</div>
		
		<div id="poststuff">
			<div id="webdados_fb_open_graph_links" class="postbox">
				<h3 id="settings"><?php _e('Useful links', 'wd-fb-og');?></h3>
				<div class="inside">
					<ul>
						<?php foreach($links as $link) { ?>
							<li>- <a href="<?php echo $link['url']; ?>" target="_blank"><?php echo $link['text']; ?></a></li>
						<?php } ?>
					</ul>
				</div>
			</div>
		</div>
	
		<div id="poststuff">
			<div id="webdados_fb_open_graph_donation" class="postbox">
				<h3 id="settings"><?php _e('Donate', 'wd-fb-og');?></h3>
				<div class="inside">
					<p><?php _e('If you find this plugin useful and want to make a contribution towards future development please consider making a small, or big ;-), donation.', 'wd-fb-og');?></p>
					<center><form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
						<input type="hidden" name="cmd" value="_donations">
						<input type="hidden" name="business" value="wonderm00n@gmail.com">
						<input type="hidden" name="lc" value="PT">
						<input type="hidden" name="item_name" value="Marco Almeida (Wonderm00n)">
						<input type="hidden" name="item_number" value="wonderm00n_open_graph">
						<input type="hidden" name="currency_code" value="USD">
						<input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHosted">
						<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
						<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
					</form></center>
				</div>
			</div>
		</div>
		
	</div>
	
	<div class="clear">
		<p><br/>&copy 2011<?php if(date('Y')>2011) echo '-'.date('Y'); ?> <a href="http://www.webdados.pt/?utm_source=fb_og_wp_plugin_settings&amp;utm_medium=link&amp;utm_campaign=fb_og_wp_plugin" target="_blank">Webdados</a> &amp; <a href="http://wonderm00n.com/?utm_source=fb_og_wp_plugin_settings&amp;utm_medium=link&amp;utm_campaign=fb_og_wp_plugin" target="_blank">Marco Almeida (Wonderm00n)</a></p>
	</div>
		
	</div>
	
	<script type="text/javascript">
		jQuery(document).ready(function() {
			jQuery('#fb_image_button').click(function(){
				tb_show('',"media-upload.php?type=image&TB_iframe=true");
			});
			window.send_to_editor = function(html) {
				var imgurl = jQuery('<div>'+html+'</div>').find('img').attr('src');
				jQuery("input"+"#fb_image").val(imgurl);
				tb_remove();
			}
			showAppidOptions();
			showAdminOptions();
			showLocaleOptions();
			showTypeOptions();
			showPublisherOptions();
			showPublisherSchemaOptions();
			showPublisherTwitterOptions();
			showAuthorOptions();
			showUrlOptions();
			showUrlTrail();
			jQuery('.fb_description_options').hide();
			showDescriptionOptions();
			showTitleOptions();
			jQuery('#fb_desc_homepage_customtext').hide();
			showDescriptionCustomText();
			showImageOptions();
			showFBNotifyOptions();
		});
		function showAppidOptions() {
			if (jQuery('#fb_app_id_show').is(':checked')) {
				jQuery('.fb_app_id_options').show();
			} else {
				jQuery('.fb_app_id_options').hide();
			}
		}
		function showAdminOptions() {
			if (jQuery('#fb_admin_id_show').is(':checked')) {
				jQuery('.fb_admin_id_options').show();
			} else {
				jQuery('.fb_admin_id_options').hide();
			}
		}
		function showLocaleOptions() {
			if (jQuery('#fb_locale_show').is(':checked')) {
				jQuery('.fb_locale_options').show();
			} else {
				jQuery('.fb_locale_options').hide();
			}
		}
		function showUrlOptions() {
			/*if (jQuery('#fb_url_show').is(':checked')) {
				jQuery('.fb_url_options').show();
			} else {
				jQuery('.fb_url_options').hide();
			}*/
			jQuery('.fb_url_options').show();
		}
		function showUrlTrail() {
			if (jQuery('#fb_url_add_trailing').is(':checked')) {
				jQuery('#fb_url_add_trailing_example').show();
			} else {
				jQuery('#fb_url_add_trailing_example').hide();
			}
		}
		function showTypeOptions() {
			if (jQuery('#fb_type_show').is(':checked')) {
				jQuery('.fb_type_options').show();
			} else {
				jQuery('.fb_type_options').hide();
			}
		}
		function showAuthorOptions() {
			/*if (jQuery('#fb_author_show').is(':checked')) {
				jQuery('.fb_author_options').show();
			} else {
				jQuery('.fb_author_options').hide();
			}*/
			jQuery('.fb_author_options').show();
		}
		function showPublisherOptions() {
			if (jQuery('#fb_publisher_show').is(':checked')) {
				jQuery('.fb_publisher_options').show();
			} else {
				jQuery('.fb_publisher_options').hide();
			}
		}
		function showPublisherTwitterOptions() {
			if (jQuery('#fb_publisher_show_twitter').is(':checked')) {
				jQuery('.fb_publisher_twitter_options').show();
			} else {
				jQuery('.fb_publisher_twitter_options').hide();
			}
		}
		function showPublisherSchemaOptions() {
			if (jQuery('#fb_publisher_show_schema').is(':checked')) {
				jQuery('.fb_publisher_schema_options').show();
			} else {
				jQuery('.fb_publisher_schema_options').hide();
			}
		}
		function showTypeOptions() {
			if (jQuery('#fb_author_show').is(':checked')) {
				jQuery('.fb_author_options').show();
			} else {
				jQuery('.fb_author_options').hide();
			}
		}
		function showDescriptionOptions() {
			/*if (jQuery('#fb_desc_show').is(':checked')) {
				jQuery('.fb_description_options').show();
			} else {
				jQuery('.fb_description_options').hide();
			}*/
			jQuery('.fb_description_options').show();
		}
		function showTitleOptions() {
			/*if (jQuery('#fb_title_show').is(':checked')) {
				jQuery('.fb_title_options').show();
			} else {
				jQuery('.fb_title_options').hide();
			}*/
			jQuery('.fb_title_options').show();  //Not exclusive
		}
		function showDescriptionCustomText() {
			if (jQuery('#fb_desc_homepage').val()=='custom') {
				jQuery('#fb_desc_homepage_customtext').show().focus();
			} else {
				jQuery('#fb_desc_homepage_customtext').hide();
			}
		}
		function showImageOptions() {
			/*if (jQuery('#fb_image_show').is(':checked')) {
				jQuery('.fb_image_options').show();
			} else {
				jQuery('.fb_image_options').hide();
			}*/
			jQuery('.fb_image_options').show();
		}
		function showFBNotifyOptions() {
			if (jQuery('#fb_adv_notify_fb').is(':checked')) {
				jQuery('.fb_adv_notify_fb_options').show();
			} else {
				jQuery('.fb_adv_notify_fb_options').hide();
			}
		}
	</script>
	<style type="text/css">
		.og_left_col {
			width: 69%;
		}
		.og_right_col {
			width: 29%;
			float: right;
		}
		.og_left_col #poststuff,
		.og_right_col #poststuff {
			min-width: 0;
		}
		table.form-table tr th,
		table.form-table tr td {
			line-height: 1.5;
		}
		table.form-table tr th {
			font-weight: bold;
		}
		table.form-table tr th[scope=row] {
			min-width: 300px;
		}
		table.form-table tr td hr {
			height: 1px;
			margin: 0px;
			background-color: #DFDFDF;
			border: none;
		}
		table.form-table .dashicons-before {
			margin-right: 10px;
			font-size: 12px;
			opacity: 0.5;
		}
		table.form-table .dashicons-facebook-alt {
			color: #3B5998;
		}
		table.form-table .dashicons-googleplus {
			color: #D34836;
		}
		table.form-table .dashicons-twitter {
			color: #55ACEE;
		}
		table.form-table .dashicons-rss {
			color: #FF6600;
		}
		table.form-table .dashicons-admin-site,
		table.form-table .dashicons-admin-generic {
			color: #666;
		}
	</style>