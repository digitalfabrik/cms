<?php
/**
 * @package Facebook Open Graph, Google+ and Twitter Card Tags
 * @version 1.6.3
 */
/*
Plugin Name: Facebook Open Graph, Google+ and Twitter Card Tags
Plugin URI: http://www.webdados.pt/produtos-e-servicos/internet/desenvolvimento-wordpress/facebook-open-graph-meta-tags-wordpress/
Description: Inserts Facebook Open Graph, Google+ / Schema.org and Twitter Card Tags into your WordPress Blog/Website for more effective and efficient Facebook, Google+ and Twitter sharing results. You can also choose to insert the "enclosure" and "media:content" tags to the RSS feeds, so that apps like RSS Graffiti and twitterfeed post the image to Facebook correctly.
Version: 1.6.3
Author: Webdados
Author URI: http://www.webdados.pt
Text Domain: wd-fb-og
Domain Path: /lang
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$webdados_fb_open_graph_plugin_version='1.6.3';
$webdados_fb_open_graph_plugin_name='Facebook Open Graph, Google+ and Twitter Card Tags';
$webdados_fb_open_graph_plugin_settings=array(
		'fb_app_id_show',
		'fb_app_id',
		'fb_admin_id_show',
		'fb_admin_id',
		'fb_locale_show',
		'fb_locale',
		'fb_sitename_show',
		'fb_title_show',
		'fb_title_show_schema',
		'fb_title_show_twitter',
		'fb_url_show',
		'fb_url_show_twitter',
		'fb_url_canonical',
		'fb_url_add_trailing',
		'fb_type_show',
		'fb_type_homepage',
		'fb_article_dates_show',
		'fb_article_sections_show',
		'fb_publisher_show',
		'fb_publisher',
		'fb_publisher_show_schema',
		'fb_publisher_schema',
		'fb_publisher_show_twitter',
		'fb_publisher_twitteruser',
		'fb_author_show',
		'fb_author_show_meta',
		'fb_author_show_linkrelgp',
		'fb_author_show_twitter',
		'fb_author_hide_on_pages',
		'fb_desc_show',
		'fb_desc_show_meta',
		'fb_desc_show_schema',
		'fb_desc_show_twitter',
		'fb_desc_chars',
		'fb_desc_homepage',
		'fb_desc_homepage_customtext',
		'fb_image_show',
		'fb_image_size_show',
		'fb_image_show_schema',
		'fb_image_show_twitter',
		'fb_image',
		'fb_image_rss',
		'fb_image_use_specific',
		'fb_image_use_featured',
		'fb_image_use_content',
		'fb_image_use_media',
		'fb_image_use_default',
		'fb_image_min_size',
		'fb_show_wpseoyoast',
		'fb_show_subheading',
		'fb_show_businessdirectoryplugin',
		'fb_keep_data_uninstall',
		'fb_adv_force_local',
		'fb_adv_notify_fb',
		'fb_adv_supress_fb_notice',
		'fb_twitter_card_type'
);

//We have to remove canonical NOW because the plugin runs too late - We're also loading the settings which is cool
if ($webdados_fb_open_graph_settings=webdados_fb_open_graph_load_settings()) {  //To avoid activation errors
	if (intval($webdados_fb_open_graph_settings['fb_url_show'])==1) {
		if (intval($webdados_fb_open_graph_settings['fb_url_canonical'])==1) {
			remove_action('wp_head', 'rel_canonical');
		}
	}
}

//Languages
function webdados_fb_open_graph_init() {
	load_plugin_textdomain('wd-fb-og', false, dirname(plugin_basename(__FILE__)) . '/lang/');
}
add_action('plugins_loaded', 'webdados_fb_open_graph_init');

function webdados_fb_open_graph() {
	global $webdados_fb_open_graph_plugin_settings, $webdados_fb_open_graph_plugin_name, $webdados_fb_open_graph_plugin_version, $webdados_fb_open_graph_settings;

	//Upgrade
	webdados_fb_open_graph_upgrade();
	
	//Get options - OLD (until 0.5.4)
	/*foreach($webdados_fb_open_graph_plugin_settings as $key) {
		$$key=get_option('wonderm00n_open_graph_'.$key);
	}*/
	//Get options - NEW (after 0.5.4)
	extract($webdados_fb_open_graph_settings);
	
	//Also set Title Tag?
	$fb_set_title_tag=0;

	$fb_type='article';
	if (is_singular()) {
		//It's a Post or a Page or an attachment page - It can also be the homepage if it's set as a page
		global $post;
		$fb_title=esc_attr(strip_tags(stripslashes($post->post_title)));
		//SubHeading
		if ($fb_show_subheading==1) {
			if (webdados_fb_open_graph_subheadingactive()) {
				$fb_title.=' - '.get_the_subheading();
			}
		}
		$fb_url=get_permalink();
		if (is_front_page()) {
			/* Fix homepage type when it's a static page */
			$fb_url=get_option('home').(intval($fb_url_add_trailing)==1 ? '/' : '');
			$fb_type=trim($fb_type_homepage=='' ? 'website' : $fb_type_homepage);
		}
		if (trim($post->post_excerpt)!='') {
			//If there's an excerpt that's what we'll use
			$fb_desc=trim($post->post_excerpt);
		} else {
			//If not we grab it from the content
			$fb_desc=trim($post->post_content);
		}
		$fb_desc=(intval($fb_desc_chars)>0 ? mb_substr(esc_attr(strip_tags(strip_shortcodes(stripslashes($fb_desc)))),0,$fb_desc_chars) : esc_attr(strip_tags(strip_shortcodes(stripslashes($fb_desc)))));
		if (intval($fb_image_show)==1 || intval($fb_image_show_schema)==1 || intval($fb_image_show_twitter)==1) {
			$fb_image=webdados_fb_open_graph_post_image($fb_image_use_specific, $fb_image_use_featured, $fb_image_use_content, $fb_image_use_media, $fb_image_use_default, $fb_image);
		}
		//Author
		$author_id=$post->post_author;
		if ($author_id>0) {
			$fb_author=get_the_author_meta('facebook', $author_id);
			$fb_author_meta=get_the_author_meta('display_name', $author_id);
			$fb_author_linkrelgp=get_the_author_meta('googleplus', $author_id);
			$fb_author_twitter=get_the_author_meta('twitter', $author_id);
		} else {
			$fb_author='';
			$fb_author_meta='';
			$fb_author_linkrelgp='';
			$fb_author_twitter='';
		}
		//Author - Hide on pages?
		if (is_page() && $fb_author_hide_on_pages==1) {
			$fb_author='';
			$fb_author_meta='';
			$fb_author_linkrelgp='';
			$fb_author_twitter='';
		}
		//Published and Modified time
		if (is_singular('post')) {
			$fb_article_pub_date=get_the_date('c');
			$fb_article_mod_date=get_the_modified_date('c');
		} else {
			$fb_article_dates_show=0;
			$fb_article_pub_date='';
			$fb_article_mod_date='';
		}
		//Categories
		if (is_singular('post')) {
			$cats = get_the_category();
			if (!is_wp_error($cats) && (is_array($cats) && count($cats)>0)) {
				$fb_sections=array();
				foreach ($cats as $cat) {
					$fb_sections[]=$cat->name;
				}
			}
		} else {
			$fb_article_sections_show=0;
		}
		//Business Directory Plugin
		if ($fb_show_businessdirectoryplugin==1) {
			@include_once(ABSPATH . 'wp-admin/includes/plugin.php');
			if (is_plugin_active('business-directory-plugin/wpbusdirman.php')) {
				global $wpbdp;
				//$bdpaction = _wpbdp_current_action();
				$bdpaction=$wpbdp->controller->get_current_action();
				switch($bdpaction) {
					case 'showlisting':
						//Listing
						$listing_id = get_query_var('listing') ? wpbdp_get_post_by_slug(get_query_var('listing'))->ID : wpbdp_getv($_GET, 'id', get_query_var('id'));
						$bdppost=get_post($listing_id);
						$fb_title=esc_attr(strip_tags(stripslashes($bdppost->post_title))).' - '.$fb_title;
						$fb_set_title_tag=1;
						$fb_url=get_permalink($listing_id);
						if (trim($bdppost->post_excerpt)!='') {
							//If there's an excerpt that's what we'll use
							$fb_desc=trim($bdppost->post_excerpt);
						} else {
							//If not we grab it from the content
							$fb_desc=trim($bdppost->post_content);
						}
						$fb_desc=(intval($fb_desc_chars)>0 ? mb_substr(esc_attr(strip_tags(strip_shortcodes(stripslashes($fb_desc)))),0,$fb_desc_chars) : esc_attr(strip_tags(strip_shortcodes(stripslashes($fb_desc)))));
						if (intval($fb_image_show)==1 || intval($fb_image_show_schema)==1 || intval($fb_image_show_twitter)==1) {
							$thumbdone=false;
							if (intval($fb_image_use_featured)==1) {
								//Featured
								if ($id_attachment=get_post_thumbnail_id($bdppost->ID)) {
									//There's a featured/thumbnail image for this listing
									$fb_image=wp_get_attachment_url($id_attachment, false);
									$thumbdone=true;
								}
							}
							if (!$thumbdone) {
								//Main image loaded
								if ($thumbnail_id = wpbdp_listings_api()->get_thumbnail_id($bdppost->ID)) {
									$fb_image=wp_get_attachment_url($thumbnail_id, false);
								}
							}
						}
						break;
					case 'browsecategory':
							//Categories
							$term = get_term_by('slug', get_query_var('category'), wpbdp_categories_taxonomy());
							$fb_title=esc_attr(strip_tags(stripslashes($term->name))).' - '.$fb_title;
							$fb_set_title_tag=1;
							$fb_url=get_term_link($term);
							if (trim($term->description)!='') {
								$fb_desc=trim($term->description);
							}
						break;
					case 'main':
						//Main page
						//No changes
						break;
					default:
						//No changes
						break;
				}
			}
		}
	} else {
		global $wp_query;
		//Other pages - Defaults
		$fb_title=esc_attr(strip_tags(stripslashes(get_bloginfo('name'))));
		//$fb_url=get_option('home').(intval($fb_url_add_trailing)==1 ? '/' : ''); //2013-11-4 changed from 'siteurl' to 'home'
		$fb_url=((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];  //Not really canonical but will work for now

		//These are only used in posts/pages
		$fb_article_sections_show=0;
		$fb_article_dates_show=0;
		$fb_author_show=0;
		$fb_author_show_meta=0;
		$fb_author_show_linkrelgp=0;
		$fb_author_show_twitter=0;

		switch(trim($fb_desc_homepage)) {
			case 'custom':
				$fb_desc=esc_attr(strip_tags(stripslashes($fb_desc_homepage_customtext)));
				//WPML?
				if (function_exists('icl_object_id') && function_exists('icl_register_string')) {
					global $sitepress;
					if (ICL_LANGUAGE_CODE!=$sitepress->get_default_language()) {
						$fb_desc=icl_t('wd-fb-og', 'wd_fb_og_desc_homepage_customtext', $fb_desc);
					} else {
						//We got it already
					}
				}
				break;
			default:
				$fb_desc=esc_attr(strip_tags(stripslashes(get_bloginfo('description'))));
				break;
		}
		
		if (is_category()) {
			$fb_title=esc_attr(strip_tags(stripslashes(single_cat_title('', false))));
			$term=$wp_query->get_queried_object();
			$fb_url=get_term_link($term, $term->taxonomy);
			$cat_desc=trim(esc_attr(strip_tags(stripslashes(category_description()))));
			if (trim($cat_desc)!='') $fb_desc=$cat_desc;
		} else {
			if (is_tag()) {
				$fb_title=esc_attr(strip_tags(stripslashes(single_tag_title('', false))));
				$term=$wp_query->get_queried_object();
				$fb_url=get_term_link($term, $term->taxonomy);
				$tag_desc=trim(esc_attr(strip_tags(stripslashes(tag_description()))));
				if (trim($tag_desc)!='') $fb_desc=$tag_desc;
			} else {
				if (is_tax()) {
					$fb_title=esc_attr(strip_tags(stripslashes(single_term_title('', false))));
					$term=$wp_query->get_queried_object();
					$fb_url=get_term_link($term, $term->taxonomy);
				} else {
					if (is_search()) {
						$fb_title=esc_attr(strip_tags(stripslashes(__('Search for').' "'.get_search_query().'"')));
						$fb_url=get_search_link();
					} else {
						if (is_author()) {
							$fb_title=esc_attr(strip_tags(stripslashes(get_the_author_meta('display_name', get_query_var('author')))));
							$fb_url=get_author_posts_url(get_query_var('author'), get_query_var('author_name'));
						} else {
							if (is_archive()) {
								if (is_day()) {
									$fb_title=esc_attr(strip_tags(stripslashes(get_query_var('day').' '.single_month_title(' ', false).' '.__('Archives'))));
									$fb_url=get_day_link(get_query_var('year'), get_query_var('monthnum'), get_query_var('day'));
								} else {
									if (is_month()) {
										$fb_title=esc_attr(strip_tags(stripslashes(single_month_title(' ', false).' '.__('Archives'))));
										$fb_url=get_month_link(get_query_var('year'), get_query_var('monthnum'));
									} else {
										if (is_year()) {
											$fb_title=esc_attr(strip_tags(stripslashes(get_query_var('year').' '.__('Archives'))));
											$fb_url=get_year_link(get_query_var('year'));
										}
									}
								}
							} else {
								if (is_front_page()) {
									$fb_url=get_option('home').(intval($fb_url_add_trailing)==1 ? '/' : '');
									$fb_type=trim($fb_type_homepage=='' ? 'website' : $fb_type_homepage);
								} else {
									//Others... Defaults already set up there
								}
							}
						}
					}
				}
			}
		}
	}
	//If no description let's just add the title
	if (trim($fb_desc)=='') $fb_desc=$fb_title;

	//YOAST?
	if ($fb_show_wpseoyoast==1) {
		if ( defined('WPSEO_VERSION') ) {
			$wpseo = WPSEO_Frontend::get_instance();
			//App ID - From our plugin
			//Admin ID - From our plugin
			//Locale - From our plugin
			//Sitename - From our plugin
			//Title - From WPSEO
			$fb_title=strip_tags($wpseo->title(false));
			//Title - SubHeading plugin
			if ($fb_show_subheading==1) {
				if (webdados_fb_open_graph_subheadingactive()) {
					$fb_title.=' - '.get_the_subheading();
				}
			}
			//URL - From WPSEO
			$fb_url=$wpseo->canonical(false);
			//Description - From WPSEO or our plugin
			$fb_desc_temp=$wpseo->metadesc(false);
			$fb_desc=strip_tags(trim($fb_desc_temp)!='' ? trim($fb_desc_temp) : $fb_desc);
			//Image - From our plugin
		}
	}

	//Apply Filters
	$fb_title = apply_filters('fb_og_title', $fb_title);
	$fb_desc = apply_filters('fb_og_desc', $fb_desc);
	$fb_image = apply_filters('fb_og_image', $fb_image);
	$fb_locale = apply_filters('fb_og_locale', $fb_locale);
	$fb_image_size = false;
	if (intval($fb_image_show)==1 && trim($fb_image)!='') {
		if (intval($fb_image_size_show)==1) {
			if (isset($GLOBALS['webdados_fb_img_size'])) { //Already fetched
				$fb_image_size=$GLOBALS['webdados_fb_img_size'];
			} else {
				$fb_image_size=webdados_fb_open_graph_getimagesize($fb_image);
			}
		}
	} else {
		$fb_image_size_show=0;
	}

	//No spaces on URLs
	if (isset($fb_url) && trim($fb_url)!='')							$fb_url=				str_replace(' ', '%20', trim($fb_url));
	if (isset($fb_publisher) && trim($fb_publisher)!='')				$fb_publisher=			str_replace(' ', '%20', trim($fb_publisher));
	if (isset($fb_publisher_schema) && trim($fb_publisher_schema)!='')	$fb_publisher_schema=	str_replace(' ', '%20', trim($fb_publisher_schema));
	if (isset($fb_author) && trim($fb_author)!='')						$fb_author=				str_replace(' ', '%20', trim($fb_author));
	if (isset($fb_author_linkrelgp) && trim($fb_author_linkrelgp)!='')	$fb_author_linkrelgp=	str_replace(' ', '%20', trim($fb_author_linkrelgp));
	if (isset($fb_image) && trim($fb_image)!='')						$fb_image=				str_replace(' ', '%20', trim($fb_image));
	
	$html='
<!-- START - '.$webdados_fb_open_graph_plugin_name.' '.$webdados_fb_open_graph_plugin_version.' -->
';
	if (intval($fb_app_id_show)==1 && trim($fb_app_id)!='') $html.='<meta property="fb:app_id" content="'.trim(esc_attr($fb_app_id)).'"/>
';
	if (intval($fb_admin_id_show)==1 && trim($fb_admin_id)!='') $html.='<meta property="fb:admins" content="'.trim(esc_attr($fb_admin_id)).'"/>
';
	if (intval($fb_locale_show)==1) $html.='<meta property="og:locale" content="'.trim(esc_attr(trim($fb_locale)!='' ? trim($fb_locale) : trim(get_locale()))).'"/>
';
	if (intval($fb_sitename_show)==1) $html.='<meta property="og:site_name" content="'.trim(esc_attr(get_bloginfo('name'))).'"/>
';
	if (intval($fb_title_show)==1) $html.='<meta property="og:title" content="'.trim(esc_attr($fb_title)).'"/>
';
	if (intval($fb_set_title_tag)==1) {
		//Does nothing so far. We try to create the <title> tag but it's too late now
	}
	if (intval($fb_title_show_schema)==1) $html.='<meta itemprop="name" content="'.trim(esc_attr($fb_title)).'"/>
';
	if (intval($fb_title_show_twitter)==1) $html.='<meta name="twitter:title" content="'.trim(esc_attr($fb_title)).'"/>
';
	if (intval($fb_url_show)==1) $html.='<meta property="og:url" content="'.trim(esc_attr($fb_url)).'"/>
';
	if (intval($fb_url_show_twitter)==1) $html.='<meta name="twitter:url" content="'.trim(esc_attr($fb_url)).'"/>
';
	if (intval($fb_url_canonical)==1) $html.='<link rel="canonical" href="'.trim(esc_attr($fb_url)).'"/>
';
	if (intval($fb_type_show)==1) $html.='<meta property="og:type" content="'.trim(esc_attr($fb_type)).'"/>
';
if (intval($fb_article_dates_show)==1 && trim($fb_article_pub_date)!='') $html.='<meta property="article:published_time" content="'.trim(esc_attr($fb_article_pub_date)).'"/>
	';
if (intval($fb_article_dates_show)==1 && trim($fb_article_mod_date)!='') $html.='<meta property="article:modified_time" content="'.trim(esc_attr($fb_article_mod_date)).'" />
<meta property="og:updated_time" content="'.trim(esc_attr($fb_article_mod_date)).'" />
';
if (intval($fb_article_sections_show)==1 && isset($fb_sections) && is_array($fb_sections) && count($fb_sections)>0) {
	foreach($fb_sections as $fb_section) {
		$html.='<meta property="article:section" content="'.trim(esc_attr($fb_section)).'"/>
';
	}
}
	if (intval($fb_publisher_show)==1 && trim($fb_publisher)!='') $html.='<meta property="article:publisher" content="'.trim(esc_attr($fb_publisher)).'"/>
';
	if (intval($fb_publisher_show_schema)==1 && trim($fb_publisher_schema)!='') $html.='<link rel="publisher" href="'.trim(esc_attr($fb_publisher_schema)).'"/>
';
	if (intval($fb_publisher_show_twitter)==1 && trim($fb_publisher_twitteruser)!='') $html.='<meta name="twitter:site" content="@'.trim(esc_attr($fb_publisher_twitteruser)).'"/>
';
	if (intval($fb_author_show)==1 && $fb_author!='') $html.='<meta property="article:author" content="'.trim(esc_attr($fb_author)).'"/>
';
	if (intval($fb_author_show_meta)==1 && $fb_author_meta!='') $html.='<meta name="author" content="'.trim(esc_attr($fb_author_meta)).'"/>
';
	if (intval($fb_author_show_linkrelgp)==1 && trim($fb_author_linkrelgp)!='') $html.='<link rel="author" href="'.trim(esc_attr($fb_author_linkrelgp)).'"/>
';
	if (intval($fb_author_show_twitter)==1 && (trim($fb_author_twitter)!='' || trim($fb_publisher_twitteruser)!='')) $html.='<meta name="twitter:creator" content="@'.trim(esc_attr( (trim($fb_author_twitter)!='' ? trim($fb_author_twitter) : trim($fb_publisher_twitteruser) ))).'"/>
';
	if (intval($fb_desc_show)==1) $html.='<meta property="og:description" content="'.trim(esc_attr($fb_desc)).'"/>
';
	if (intval($fb_desc_show_meta)==1) $html.='<meta name="description" content="'.trim(esc_attr($fb_desc)).'"/>
';
	if (intval($fb_desc_show_schema)==1) $html.='<meta itemprop="description" content="'.trim(esc_attr($fb_desc)).'"/>
';
	if (intval($fb_desc_show_twitter)==1) $html.='<meta name="twitter:description" content="'.trim(esc_attr($fb_desc)).'"/>
';
	if(intval($fb_image_show)==1 && trim($fb_image)!='') $html.='<meta property="og:image" content="'.trim(esc_attr($fb_image)).'"/>
';
	if(intval($fb_image_size_show)==1 && isset($fb_image_size) && is_array($fb_image_size)!='') $html.='<meta property="og:image:width" content="'.intval(esc_attr($fb_image_size[0])).'"/>
<meta property="og:image:height" content="'.intval(esc_attr($fb_image_size[1])).'"/>
';
	if(intval($fb_image_show_schema)==1 && trim($fb_image)!='') $html.='<meta itemprop="image" content="'.trim(esc_attr($fb_image)).'"/>
';
	if(intval($fb_image_show_twitter)==1 && trim($fb_image)!='') $html.='<meta name="twitter:image:src" content="'.trim(esc_attr($fb_image)).'"/>
';
	if(intval($fb_title_show_twitter)==1 || intval($fb_url_show_twitter)==1 || $fb_author_show_twitter==1 || $fb_publisher_show_twitter==1 || $fb_image_show_twitter==1) $html.='<meta name="twitter:card" content="'.trim(esc_attr($fb_twitter_card_type)).'"/>
';
	$html.='<!-- END - '.$webdados_fb_open_graph_plugin_name.' -->

';
	echo $html;
}
add_action('wp_head', 'webdados_fb_open_graph', 9999);

function webdados_fb_open_graph_add_opengraph_namespace( $output ) {
	if (stristr($output,'xmlns:og')) {
		//Already there
	} else {
		//Let's add it
		$output=$output . ' xmlns:og="http://ogp.me/ns#"';
	}
	if (stristr($output,'xmlns:fb')) {
		//Already there
	} else {
		//Let's add it
		$output=$output . ' xmlns:fb="http://ogp.me/ns/fb#"';
	}
	return $output;
}
//We want to be last to add the namespace because some other plugin may already added it ;-)
add_filter('language_attributes', 'webdados_fb_open_graph_add_opengraph_namespace',9999);

//Add images also to RSS feed. Most code from WP RSS Images by Alain Gonzalez
function webdados_fb_open_graph_images_on_feed($for_comments) {
	global $webdados_fb_open_graph_settings;
	if (intval($webdados_fb_open_graph_settings['fb_image_rss'])==1) {
		if (!$for_comments) {
			add_action('rss2_ns', 'webdados_fb_open_graph_images_on_feed_yahoo_media_tag');
			add_action('rss_item', 'webdados_fb_open_graph_images_on_feed_image');
			add_action('rss2_item', 'webdados_fb_open_graph_images_on_feed_image');
		}
	}
}
function webdados_fb_open_graph_images_on_feed_yahoo_media_tag() {
	echo 'xmlns:media="http://search.yahoo.com/mrss/"';
}
function webdados_fb_open_graph_images_on_feed_image() {
	global $webdados_fb_open_graph_settings;
	$fb_image = webdados_fb_open_graph_post_image($webdados_fb_open_graph_settings['fb_image_use_specific'], $webdados_fb_open_graph_settings['fb_image_use_featured'], $webdados_fb_open_graph_settings['fb_image_use_content'], $webdados_fb_open_graph_settings['fb_image_use_media'], $webdados_fb_open_graph_settings['fb_image_use_default'], $webdados_fb_open_graph_settings['fb_image']);
	if ($fb_image!='') {
		$uploads = wp_upload_dir();
		$url = parse_url($fb_image);
		$path = $uploads['basedir'] . preg_replace( '/.*uploads(.*)/', '${1}', $url['path'] );
		if (file_exists($path)) {
			$filesize=filesize($path);
			$url=$path;
		} else {		
			$header=get_headers($fb_image, 1);					   
			$filesize=$header['Content-Length'];	
			$url=$fb_image;				
		}
		list($width, $height, $type, $attr) = webdados_fb_open_graph_getimagesize($url);
		echo '<enclosure url="' . $fb_image . '" length="' . $filesize . '" type="'.image_type_to_mime_type($type).'"/>';
		echo '<media:content url="'.$fb_image.'" width="'.$width.'" height="'.$height.'" medium="image" type="'.image_type_to_mime_type($type).'"/>';
	}
}
add_action("do_feed_rss","webdados_fb_open_graph_images_on_feed",5,1);
add_action("do_feed_rss2","webdados_fb_open_graph_images_on_feed",5,1);

//Post image
function webdados_fb_open_graph_post_image($fb_image_use_specific=1,$fb_image_use_featured=1, $fb_image_use_content=1, $fb_image_use_media=1, $fb_image_use_default=1, $default_image='') {
	global $post, $webdados_fb_open_graph_settings;
	$thumbdone=false;
	$fb_image='';
	$minsize=intval($webdados_fb_open_graph_settings['fb_image_min_size']);
	//Attachment page? - This overrides the other options
	if (is_attachment()) {
		if ($temp=wp_get_attachment_image_src(null, 'full')) {
			$fb_image=trim($temp[0]);
			$img_size=array(intval($temp[1]), intval($temp[2]));
			if (trim($fb_image)!='') {
				$thumbdone=true;
			}
		}
	}
	//Specific post image
	if (!$thumbdone) {
		if (intval($fb_image_use_specific)==1) {
			if ($fb_image=trim(get_post_meta($post->ID, '_webdados_fb_open_graph_specific_image', true))) {
				if (trim($fb_image)!='') {
					$thumbdone=true;
				}
			}
		}
	}
	//Featured image
	if (!$thumbdone) {
		if (function_exists('get_post_thumbnail_id')) {
			if (intval($fb_image_use_featured)==1) {
				if ($id_attachment=get_post_thumbnail_id($post->ID)) {
					//There's a featured/thumbnail image for this post
					$fb_image=wp_get_attachment_url($id_attachment, false);
					$thumbdone=true;
				}
			}
		}
	}
	//From post/page content
	if (!$thumbdone) {
		if (intval($fb_image_use_content)==1) {
			$imgreg = '/<img .*src=["\']([^ ^"^\']*)["\']/';
			preg_match_all($imgreg, trim($post->post_content), $matches);
			if ($matches[1]) {
				$imagetemp=false;
				foreach($matches[1] as $image) {
					//There's an image on the content
					$pos = strpos($image, site_url());
					if ($pos === false) {
						if (stristr($image, 'http://') || stristr($image, 'https://') || mb_substr($image, 0, 2)=='//') {
							if (mb_substr($image, 0, 2)=='//') $image=((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https:' : 'http:').$image;
							//Complete URL - offsite
							//if (intval(ini_get('allow_url_fopen'))==1) {
								$imagetemp=$image;
								$imagetempsize=$imagetemp;
							//} else {
								//If it's offsite we can't getimagesize'it, so we won't use it
								//We could save a temporary version locally and then getimagesize'it but do we want to do this every single time?
							//}
						} else {
							//Partial URL - we guess it's onsite because no http(s)://
							$imagetemp=site_url().$image;
							$imagetempsize=(
								intval(ini_get('allow_url_fopen'))==1
								?
								(
									intval($webdados_fb_open_graph_settings['fb_adv_force_local'])==1
									?
									ABSPATH.str_replace(trailingslashit(site_url()), '', $imagetemp)
									:
									$imagetemp
								)
								:
								ABSPATH.str_replace(trailingslashit(site_url()), '', $imagetemp)
							);
						}
					} else {
						//Complete URL - onsite
						$imagetemp=$image;
						$imagetempsize=(
							intval(ini_get('allow_url_fopen'))==1
							?
							(
								intval($webdados_fb_open_graph_settings['fb_adv_force_local'])==1
								?
								ABSPATH.str_replace(trailingslashit(site_url()), '', $imagetemp)
								:
								$imagetemp
							)
							:
							ABSPATH.str_replace(trailingslashit(site_url()), '', $imagetemp)
						);
					}
					if ($imagetemp) {
						if ($img_size = webdados_fb_open_graph_getimagesize($imagetempsize)) {
							if ($img_size[0] >= $minsize && $img_size[1] >= $minsize) {
								$fb_image=$imagetemp;
								$thumbdone=true;
								break;
							}
						}
					}
				}
			}
		}
	}
	//From media gallery
	if (!$thumbdone) {
		if (intval($fb_image_use_media)==1) {
			$images = get_posts(array('post_type' => 'attachment','numberposts' => -1,'post_status' => null,'order' => 'ASC','orderby' => 'menu_order','post_mime_type' => 'image','post_parent' => $post->ID));
			if ($images) {
				foreach($images as $image) {
					$imagetemp=wp_get_attachment_url($image->ID, false);
					$imagetempsize=(
						intval(ini_get('allow_url_fopen'))==1
						?
						(
							intval($webdados_fb_open_graph_settings['fb_adv_force_local'])==1
							?
							ABSPATH.str_replace(trailingslashit(site_url()), '', $imagetemp)
							:
							$imagetemp
						)
						:
						ABSPATH.str_replace(trailingslashit(site_url()), '', $imagetemp)
					);
					if ($img_size = webdados_fb_open_graph_getimagesize($imagetempsize)) {
						if ($img_size[0] >= $minsize && $img_size[1] >= $minsize) {
							$fb_image=$imagetemp;
							$thumbdone=true;
							break;
						}
					}
				}
			}
		}
	}
	//From default
	if (!$thumbdone) {
		if (intval($fb_image_use_default)==1) {
			//Well... We sure did try. We'll just keep the default one!
			$fb_image=$default_image;
		} else {
			//User chose not to use default on pages/posts
			$fb_image='';
		}
	}
	return $fb_image;
}

//Get image size
function webdados_fb_open_graph_getimagesize($image) {
	if (stristr($image, 'http://') || stristr($image, 'https://') || mb_substr($image, 0, 2)=='//') {
		if (function_exists('curl_version') && function_exists('imagecreatefromstring')) {
			//We'll get just a part of the image to speed things up. From http://stackoverflow.com/questions/4635936/super-fast-getimagesize-in-php
			$headers = array(
				"Range: bytes=0-32768"
			);
			$curl = curl_init($image);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			//Set HTTP REFERER and USER AGENT just in case. Some servers may have hotlinking protection
			curl_setopt($curl, CURLOPT_REFERER, ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
			curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
			if ($data = curl_exec($curl)) {
				if ($im = @imagecreatefromstring($data)) { //Mute errors because we're not loading the all image
					if ($x=imagesx($im)) {
						//We have to fake the image type - For RSS
						$ext = pathinfo($image, PATHINFO_EXTENSION);
						switch(strtolower($ext)) {
							case 'gif':
								$type=1;
								break;
							case 'jpg':
							case 'jpeg':
								$type=2;
								break;
							case 'png':
								$type=3;
								break;
							default:
								$type=2;
								break;
						}
						$img_size=array($x, imagesy($im), $type, '');
					} else {
						$img_size=false;
					}
				} else {
					$img_size=false;
				}
			} else {
				$img_size=false;
			}
			curl_close($curl);
		} else {
			if (intval(ini_get('allow_url_fopen'))==1) {
				$img_size=getimagesize($image);
			} else {
				//We give up!
				$img_size=false;
			}
		}
	} else {
		//Local path
		$img_size=getimagesize($image);
	}
	$GLOBALS['webdados_fb_img_size']=$img_size;
	return $img_size;
}

function webdados_fb_open_graph_add_excerpts_to_pages() {
     add_post_type_support('page', 'excerpt');
}
add_action('init', 'webdados_fb_open_graph_add_excerpts_to_pages');


//Admin
if (is_admin()) {
	
	add_action('admin_menu', 'webdados_fb_open_graph_add_options');
	
	register_activation_hook(__FILE__, 'webdados_fb_open_graph_activate');
	
	function webdados_fb_open_graph_add_options() {
		global $webdados_fb_open_graph_plugin_name;
		if(function_exists('add_options_page')){
			add_options_page($webdados_fb_open_graph_plugin_name, $webdados_fb_open_graph_plugin_name, 'manage_options', basename(__FILE__), 'webdados_fb_open_graph_admin');
		}
	}
	
	function webdados_fb_open_graph_activate() {
		//Clear WPSEO notices
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare("DELETE FROM ".$wpdb->usermeta." WHERE meta_key LIKE %s", 'wd_fb_og_wpseo_notice_ignore')
		);
	}
	
	function webdados_fb_open_graph_settings_link( $links, $file ) {
		if( $file == 'wonderm00ns-simple-facebook-open-graph-tags/wonderm00n-open-graph.php' && function_exists( "admin_url" ) ) {
			$settings_link = '<a href="' . admin_url( 'options-general.php?page=wonderm00n-open-graph.php' ) . '">' . __('Settings') . '</a>';
			array_push( $links, $settings_link ); // after other links
		}
		return $links;
	}
	add_filter('plugin_row_meta', 'webdados_fb_open_graph_settings_link', 9, 2 );
	
	
	function webdados_fb_open_graph_admin() {
		global $webdados_fb_open_graph_plugin_settings, $webdados_fb_open_graph_plugin_name, $webdados_fb_open_graph_plugin_version;
		webdados_fb_open_graph_upgrade();
		include_once 'includes/settings-page.php';
	}
	
	function webdados_fb_open_graph_scripts() {
		wp_enqueue_script('media-upload');
		wp_enqueue_script('thickbox');
		wp_enqueue_script('jquery');
	}
	function webdados_fb_open_graph_styles() {
		wp_enqueue_style('thickbox');
	}
	add_action('admin_print_scripts', 'webdados_fb_open_graph_scripts');
	add_action('admin_print_styles', 'webdados_fb_open_graph_styles');

	function webdados_fb_open_graph_add_posts_options() {
		global $webdados_fb_open_graph_settings, $webdados_fb_open_graph_plugin_name;
		if (intval($webdados_fb_open_graph_settings['fb_image_use_specific'])==1) {
			global $post;
			add_meta_box(
				'webdados_fb_open_graph',
				$webdados_fb_open_graph_plugin_name,
				'webdados_fb_open_graph_add_posts_options_box',
					$post->post_type
			);
		}
	}
	function webdados_fb_open_graph_add_posts_options_box() {
		global $post;
		// Add an nonce field so we can check for it later.
  		wp_nonce_field( 'webdados_fb_open_graph_custom_box', 'webdados_fb_open_graph_custom_box_nonce' );
  		// Current value
  		$value = get_post_meta($post->ID, '_webdados_fb_open_graph_specific_image', true);
  		echo '<label for="webdados_fb_open_graph_specific_image">';
	   	_e('Use this image:', 'wd-fb-og');
  		echo '</label> ';
  		echo '<input type="text" id="webdados_fb_open_graph_specific_image" name="webdados_fb_open_graph_specific_image" value="' . esc_attr( $value ) . '" size="75"/>
  			  <input id="webdados_fb_open_graph_specific_image_button" class="button" type="button" value="'.__('Upload/Choose Open Graph Image','wd-fb-og').'"/>
  			  <input id="webdados_fb_open_graph_specific_image_button_clear" class="button" type="button" value="'.__('Clear field','wd-fb-og').'"/>';
  		echo '<br/>'.__('Recommended size: 1200x630px', 'wd-fb-og');
  		echo '<script type="text/javascript">
				jQuery(document).ready(function(){
					jQuery(\'#webdados_fb_open_graph_specific_image_button\').live(\'click\', function() {
						tb_show(\'Upload image\', \'media-upload.php?post_id='.$post->ID.'&type=image&context=webdados_fb_open_graph_specific_image_button&TB_iframe=true\');
					});
					jQuery(\'#webdados_fb_open_graph_specific_image_button_clear\').live(\'click\', function() {
						jQuery(\'#webdados_fb_open_graph_specific_image\').val(\'\');
					});
				});
			</script>';
	}
	add_action('add_meta_boxes', 'webdados_fb_open_graph_add_posts_options');
	function webdados_fb_open_graph_add_posts_options_box_save($post_id) {
		global $webdados_fb_open_graph_settings;
		$save=true;

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || empty($_POST['post_type']))
			return $post_id;

		// If the post is not publicly_queryable (or a page) this doesn't make sense
		$post_type=get_post_type_object(get_post_type($post_id));
		if ($post_type->publicly_queryable || $post_type->name=='page') {
			//OK - Go on
		} else {
			//Not publicly_queryable (or page) -> Go away
			return $post_id;
		}

		// Check if our nonce is set.
		if (!isset($_POST['webdados_fb_open_graph_custom_box_nonce']))
			$save=false;
	  	
	  	$nonce=(isset($_POST['webdados_fb_open_graph_custom_box_nonce']) ? $_POST['webdados_fb_open_graph_custom_box_nonce'] : '');

		// Verify that the nonce is valid.
		if (!wp_verify_nonce($nonce, 'webdados_fb_open_graph_custom_box'))
			$save=false;

		// Check the user's permissions.
		if ('page' == $_POST['post_type']) {
			if (!current_user_can('edit_page', $post_id))
				$save=false;
		} else {
			if (!current_user_can('edit_post', $post_id))
				$save=false;
		}

		if ($save) {
			/* OK, its safe for us to save the data now. */
			// Sanitize user input.
			$mydata = sanitize_text_field($_POST['webdados_fb_open_graph_specific_image']);
			// Update the meta field in the database.
			update_post_meta($post_id, '_webdados_fb_open_graph_specific_image', $mydata);
		}

		if ($save) {
			//Force Facebook update anyway - Our meta box could be hidden - Not really! We'll just update if we got our metabox
			if (get_post_status($post_id)=='publish' && intval($webdados_fb_open_graph_settings['fb_adv_notify_fb'])==1) {
				$fb_debug_url='http://graph.facebook.com/?id='.urlencode(get_permalink($post_id)).'&scrape=true&method=post';
				$response=wp_remote_get($fb_debug_url);
				if (is_wp_error($response)) {
					$_SESSION['wd_fb_og_updated_error']=1;
					$_SESSION['wd_fb_og_updated_error_message']=__('URL failed:', 'wd-fb-og').' '.$fb_debug_url;
				} else {
					if ($response['response']['code']==200 && intval($webdados_fb_open_graph_settings['fb_adv_supress_fb_notice'])==0) {
						$_SESSION['wd_fb_og_updated']=1;
					} else {
						if ($response['response']['code']==500) {
							$_SESSION['wd_fb_og_updated_error']=1;
							$error=json_decode($response['body']);
							$_SESSION['wd_fb_og_updated_error_message']=__('Facebook returned:', 'wd-fb-og').' '.$error->error->message;
						}
					}
				}
			}
		}

		return $post_id;

	}
	add_action('save_post', 'webdados_fb_open_graph_add_posts_options_box_save');
	function webdados_fb_open_graph_facebook_updated() {
		if ($screen = get_current_screen()) {
			if (isset($_SESSION['wd_fb_og_updated']) && $_SESSION['wd_fb_og_updated']==1 && $screen->parent_base=='edit' && $screen->base=='post') {
				global $post;
				?>
				<div class="updated">
					<p><?php _e('Facebook Open Graph Tags cache updated/purged.', 'wd-fb-og'); ?> <a href="http://www.facebook.com/sharer.php?u=<?php echo urlencode(get_permalink($post->ID));?>" target="_blank"><?php _e('Share this on Facebook', 'wd-fb-og'); ?></a></p>
				</div>
				<?php
			} else {
				if (isset($_SESSION['wd_fb_og_updated_error']) && $_SESSION['wd_fb_og_updated_error']==1 && $screen->parent_base=='edit' && $screen->base=='post') {
					?>
					<div class="error">
						<p><?php
							echo '<b>'.__('Error: Facebook Open Graph Tags cache NOT updated/purged.', 'wd-fb-og').'</b>';
							echo '<br/>'.$_SESSION['wd_fb_og_updated_error_message'];
						?></p>
					</div>
					<?php
				}
			}
		}
		unset($_SESSION['wd_fb_og_updated']);
		unset($_SESSION['wd_fb_og_updated_error']);
		unset($_SESSION['wd_fb_og_updated_error_message']);
	}
	add_action('admin_notices', 'webdados_fb_open_graph_facebook_updated');
	

	// Media insert code
	function webdados_fb_open_graph_media_admin_head() {
		?>
		<script type="text/javascript">
			function wdfbogFieldsFileMediaTrigger(guid) {
				window.parent.jQuery('#webdados_fb_open_graph_specific_image').val(guid);
				window.parent.jQuery('#TB_closeWindowButton').trigger('click');
			}
		</script>
		<style type="text/css">
			tr.submit, .ml-submit, #save, #media-items .A1B1 p:last-child  { display: none; }
		</style>
		<?php
	}
	function webdados_fb_open_graph_media_fields_to_edit_filter($form_fields, $post) {
		// Reset form
		$form_fields = array();
		$url = wp_get_attachment_url( $post->ID );
		$form_fields['wd-fb-og_fields_file'] = array(
			'label' => '',
			'input' => 'html',
			'html' => '<a href="#" title="' . $url
			. '" class="wd-fb-og-fields-file-insert-button'
			. ' button-primary" onclick="wdfbogFieldsFileMediaTrigger(\''
			. $url . '\')">'
			. __( 'Use as Image Open Graph Tag', 'wd-fb-og') . '</a><br /><br />',
		);
		return $form_fields;
	}
	if ( (isset( $_GET['context'] ) && $_GET['context'] == 'webdados_fb_open_graph_specific_image_button')
			|| (isset( $_SERVER['HTTP_REFERER'] )
			&& strpos( $_SERVER['HTTP_REFERER'],
					'context=webdados_fb_open_graph_specific_image_button' ) !== false)
	) {
		// Add button
		add_filter( 'attachment_fields_to_edit', 'webdados_fb_open_graph_media_fields_to_edit_filter', 9999, 2 );
		// Add JS
		add_action( 'admin_head', 'webdados_fb_open_graph_media_admin_head' );
	}

	//Facebook, Google+ and Twitter user fields
	function webdados_fb_open_graph_add_usercontacts($usercontacts) {
		if (!defined('WPSEO_VERSION')) {
			//Google+
			$usercontacts['googleplus'] = __('Google+', 'wd-fb-og');
			//Twitter
			$usercontacts['twitter'] = __('Twitter username (without @)', 'wd-fb-og');
			//Facebook
			$usercontacts['facebook'] = __('Facebook profile URL', 'wd-fb-og');
		}
		return $usercontacts;
	}
	//WPSEO already adds the fields, so we'll just add them if WPSEO is not active
	add_filter('user_contactmethods', 'webdados_fb_open_graph_add_usercontacts', 10, 1);

	//WPSEO warning
	function webdados_fb_open_graph_wpseo_notice() {
		if (defined('WPSEO_VERSION')) {
			global $current_user, $webdados_fb_open_graph_plugin_name;
			$user_id=$current_user->ID;
			if (!get_user_meta($user_id,'wd_fb_og_wpseo_notice_ignore')) {
				?>
				<div class="error">
					<p>
						<b><?php echo $webdados_fb_open_graph_plugin_name; ?>:</b>
						<br/>
						<?php _e('Please ignore the (dumb) Yoast WordPress SEO warning regarding open graph issues with this plugin. Just disable WPSEO Social settings at', 'wd-fb-og'); ?>
						<a href="admin.php?page=wpseo_social&amp;wd_fb_og_wpseo_notice_ignore=1"><?php _e('SEO &gt; Social','wd-fb-og'); ?></a>
					</p>
					<p><a href="?wd_fb_og_wpseo_notice_ignore=1">Ignore this message</a></p>
				</div>
				<?php
			}
		}
	}
	add_action('admin_notices', 'webdados_fb_open_graph_wpseo_notice');
	function webdados_fb_open_graph_wpseo_notice_ignore() {
		if (defined('WPSEO_VERSION')) {
			global $current_user;
			$user_id=$current_user->ID;
			if (isset($_GET['wd_fb_og_wpseo_notice_ignore'])) {
				if (intval($_GET['wd_fb_og_wpseo_notice_ignore'])==1) {
					add_user_meta($user_id, 'wd_fb_og_wpseo_notice_ignore', '1', true);
				}
			}
		}
	}
	function webdados_fb_open_graph_register_session(){
		if(!session_id())
			session_start();
	}
	function webdados_fb_open_graph_admin_init() {
		webdados_fb_open_graph_wpseo_notice_ignore();
		webdados_fb_open_graph_register_session();
	}
	add_action('admin_init', 'webdados_fb_open_graph_admin_init');

}


	
function webdados_fb_open_graph_default_values() {
	return array(
		'fb_locale_show' => 1,
		'fb_sitename_show' => 1,
		'fb_title_show' => 1,
		'fb_title_show_schema' => 1,
		'fb_url_show' => 1,
		'fb_type_show' => 1,
		'fb_article_dates_show' => 1,
		'fb_article_sections_show' => 1,
		'fb_desc_show' => 1,
		'fb_desc_show_schema' => 1,
		'fb_desc_chars' => 300,
		'fb_image_show' => 1,
		'fb_image_show_schema' => 1,
		'fb_image_use_specific' => 1,
		'fb_image_use_featured' => 1,
		'fb_image_use_content' => 1,
		'fb_image_use_media' => 1,
		'fb_image_use_default' => 1,
		'fb_keep_data_uninstall' => 1,
		'fb_image_min_size' => 200,
		'fb_adv_notify_fb' => 1,
		'fb_twitter_card_type' => 'summary_large_image'
	);
}
function webdados_fb_open_graph_load_settings() {
	global $webdados_fb_open_graph_plugin_settings;
	if (is_array($webdados_fb_open_graph_plugin_settings)) {  //To avoid activation errors
		$defaults=webdados_fb_open_graph_default_values();
		//Load the user settings (if they exist)
		if ($usersettings=get_option('wonderm00n_open_graph_settings')) {
			//Merge the settings "all together now" (yes, it's a Beatles reference)
			foreach($webdados_fb_open_graph_plugin_settings as $key) {
				if (isset($usersettings[$key])) {
					if (mb_strlen(trim($usersettings[$key]))==0) {
						if (!empty($defaults[$key])) {
							$usersettings[$key]=$defaults[$key];
						}
					}
				} else {
					if (!empty($defaults[$key])) {
						$usersettings[$key]=$defaults[$key];
					} else {
						$usersettings[$key]=''; //Avoid notices
					}
				}
			}
			/*foreach($usersettings as $key => $value) {
				//if ($value=='') {
				if (mb_strlen(trim($value))==0) {
					if (!empty($defaults[$key])) {
						$usersettings[$key]=$defaults[$key];
					}
				}
			}*/
		} else {
			foreach($webdados_fb_open_graph_plugin_settings as $key) {
				if (!empty($defaults[$key])) {
					$usersettings[$key]=$defaults[$key];
				} else {
					$usersettings[$key]=''; //Avoid notices
				}
			}
		}
		return $usersettings;
	} else {
		return false; //To avoid activation errors
	}
}

//Subheading plugin active?
function webdados_fb_open_graph_subheadingactive() {
	//@include_once(ABSPATH . 'wp-admin/includes/plugin.php');
	//if (is_plugin_active('subheading/index.php')) {
		if (class_exists('SubHeading') && function_exists('get_the_subheading')) {
			return true;
		}
	//}
	return false;
}

function webdados_fb_open_graph_upgrade() {
	global $webdados_fb_open_graph_plugin_version;
	$upgrade=false;
	//Upgrade from 0.5.4 - Last version with individual settings
	if (!$v=get_option('wonderm00n_open_graph_version')) {
		//Convert settings
		$upgrade=true;
		global $webdados_fb_open_graph_plugin_settings;
		foreach($webdados_fb_open_graph_plugin_settings as $key) {
			$webdados_fb_open_graph_settings[$key]=get_option('wonderm00n_open_graph_'.$key);
		}
		// New fb_image_use_specific
		$webdados_fb_open_graph_settings['fb_image_use_specific']=1;
		update_option('wonderm00n_open_graph_settings', $webdados_fb_open_graph_settings);
		foreach($webdados_fb_open_graph_plugin_settings as $key) {
			delete_option('wonderm00n_open_graph_'.$key);
		}
	} else {
		if ($v<$webdados_fb_open_graph_plugin_version) {
			//Any version upgrade
			$upgrade=true;
		}
	}
	//Set version on database
	if ($upgrade) {
		update_option('wonderm00n_open_graph_version', $webdados_fb_open_graph_plugin_version);
	}
}

//Uninstall stuff
register_uninstall_hook(__FILE__, 'webdados_fb_open_graph_uninstall'); //NOT WORKING! WHY?
function webdados_fb_open_graph_uninstall() {
	//NOT WORKING! WHY?
	//global $webdados_fb_open_graph_plugin_settings;
	//Remove data
	/*foreach($webdados_fb_open_graph_plugin_settings as $key) {
		delete_option('wonderm00n_open_graph_'.$key);
	}
	delete_option('wonderm00n_open_graph_activated');*/
}

//To avoid notices when updating options on settings-page.php
//Hey @flynsarmy you are here, see?
function webdados_fb_open_graph_post($var, $default='') {
	return isset($_POST[$var]) ? $_POST[$var] : $default;
}
