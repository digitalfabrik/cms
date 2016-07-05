<?php

add_filter('show_admin_bar', '__return_false');

define('THEMEUMNAME', wp_get_theme()->get( 'Name' ));

define('THMCSS', get_template_directory_uri().'/css/');

define('THMJS', get_template_directory_uri().'/js/');

// Re-define meta box path and URL

define( 'RWMB_URL', trailingslashit( get_stylesheet_directory_uri() . '/lib/meta-box' ) );
define( 'RWMB_DIR', trailingslashit(  get_stylesheet_directory() . '/lib/meta-box' ) );

// Include the meta box script
require_once RWMB_DIR . 'meta-box.php';

require_once (get_template_directory().'/lib/metabox.php');



/*-------------------------------------------------------
 *				SMOF Theme Options Added
 *-------------------------------------------------------*/

require_once( get_template_directory()  . '/admin/index.php');

/*-------------------------------------------*
 *				Register Navigation
 *------------------------------------------*/

register_nav_menu( 'primary','Primary Menu' );
register_nav_menu( 'secondary','Secondary Menu' );



function getContrast50($hexcolor){
    return (hexdec($hexcolor) > 0xffffff/2) ? 'light-bg':'dark-bg';
}


/*-------------------------------------------*
 *				Themeum setup
 *------------------------------------------*/

if(!function_exists('thmtheme_setup')):

	function thmtheme_setup()
	{
		// load textdomain
    	load_theme_textdomain('themeum', get_template_directory() . '/languages');

		add_theme_support( 'post-thumbnails' );

		add_image_size( 'blog-thumb', 750, 350, true );

		add_theme_support( 'post-formats', array( 'aside','audio','chat','gallery','image','link','quote','status','video' ) );

		add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form' ) );

		add_theme_support( 'automatic-feed-links' );

		add_editor_style('');

		if ( ! isset( $content_width ) )
		$content_width = 660;
	}

	add_action('after_setup_theme','thmtheme_setup');

endif;


/*-------------------------------------------*
 *		Themeum Widget Registration
 *------------------------------------------*/

if(!function_exists('thmtheme_widdget_init')):

	function thmtheme_widdget_init()
	{

		register_sidebar(array( 'name' 			=> __( 'Sidebar', 'themeum' ),
							  	'id' 			=> 'sidebar',
							  	'description' 	=> __( 'Widgets in this area will be shown on Sidebar.', 'themeum' ),
							  	'before_title' 	=> '<h3  class="widget_title">',
							  	'after_title' 	=> '</h3>',
							  	'before_widget' => '<div id="%1$s" class="widget %2$s" >',
							  	'after_widget' 	=> '</div>'
					)
		);

		register_sidebar(array( 'name' 			=> __( 'Bottom', 'themeum' ),
							  	'id' 			=> 'bottom',
							  	'description' 	=> __( 'Widgets in this area will be shown before Footer.' , 'themeum'),
							  	'before_title' 	=> '<h3 class="widget_title">',
							  	'after_title' 	=> '</h3>',
							  	'before_widget' => '<div class="col-sm-3 col-xs-6 bottom-widget"><div id="%1$s" class="widget %2$s" >',
							  	'after_widget' 	=> '</div></div>'
				)
		);
	}
	
	add_action('widgets_init','thmtheme_widdget_init');

endif;


/*-------------------------------------------*
 *		Themeum Style
 *------------------------------------------*/

if(!function_exists('themeum_style')):

    function themeum_style(){

    	global $themeum;

        wp_enqueue_style('thm-style',get_stylesheet_uri());
        wp_enqueue_style('font-awesome',THMCSS.'font-awesome.min.css');

        if(isset($themeum['g_select'])):
			wp_enqueue_style(themeum_slug($themeum['g_select']).'_one','http://fonts.googleapis.com/css?family='.$themeum['g_select'].':100,200,300,400,500,600,700,800,900',array(),false,'all');
		endif;

		if(isset($themeum['head_font'])):
			wp_enqueue_style(themeum_slug($themeum['head_font']).'_two','http://fonts.googleapis.com/css?family='.$themeum['head_font'].':100,200,300,400,500,600,700,800,900',array(),false,'all');
		endif;

		if(isset($themeum['nav_font'])):
			wp_enqueue_style(themeum_slug($themeum['nav_font']).'_three','http://fonts.googleapis.com/css?family='.$themeum['nav_font'].':100,200,300,400,500,600,700,800,900',array(),false,'all');
		endif;

        wp_enqueue_script('jquery');
        wp_enqueue_script('bootstrap',THMJS.'bootstrap.min.js',array(),false,true);
        wp_enqueue_script('SmoothScroll',THMJS.'SmoothScroll.js',array(),false,true);
        wp_enqueue_script('scrollTo',THMJS.'jquery.scrollTo.js',array(),false,true);
        wp_enqueue_script('nav',THMJS.'jquery.nav.js',array(),false,true);
        wp_enqueue_script('parallax',THMJS.'jquery.parallax.js',array(),false,true);
        wp_enqueue_script('main',THMJS.'main.js',array(),false,true);
        wp_enqueue_style('quick-style',get_template_directory_uri().'/quick-style.php',array(),false,'all');


		if(isset($themeum['presets'])):
			if(!empty($themeum['presets'])):
				$style_name = $themeum['presets'];
			else:
				$style_name = 'preset1';
			endif;
		else:
			$style_name 	= 'preset1';
		endif;

		wp_enqueue_style('sportson_'.$style_name,get_template_directory_uri().'/css/presets/'.$style_name.'.css');

    }

    add_action('wp_enqueue_scripts','themeum_style');

endif;


if(!function_exists('themeum_admin_style')):

	function themeum_admin_style()
	{
		if(is_admin())
		{
			wp_register_script('thmpostmeta', get_template_directory_uri() .'/js/admin/zee-post-meta.js');
			wp_enqueue_script('thmpostmeta');
		}
	}

	add_action('admin_enqueue_scripts','themeum_admin_style');

endif;

/*-------------------------------------------*
 *				Excerpt Length
 *------------------------------------------*/

if(!function_exists('new_excerpt_more')):

	function new_excerpt_more( $more )
	{
		return '&nbsp;<br /><br /><a class="btn btn-success btn-lg" href="'. get_permalink( get_the_ID() ) . '">'.__('Continue Reading','themeum').' &rarr;</a>';
	}
	add_filter( 'excerpt_more', 'new_excerpt_more' );

endif;



if(!function_exists('themeum_slug')):

	function themeum_slug($text)
{
	return preg_replace('/[^a-z0-9_]/i','-', strtolower($text));
}

endif;



/*-------------------------------------------------------
*			Include the TGM Plugin Activation class
*-------------------------------------------------------*/

require_once( get_template_directory()  . '/lib/class-tgm-plugin-activation.php');

add_action( 'tgmpa_register', 'themeum_plugins_include');

if(!function_exists('themeum_plugins_include')):

	function themeum_plugins_include()
	{
		$plugins = array(
				array(
					'name'                  => 'Starter Client', // The plugin name
					'slug'                  => 'starter-client', // The plugin slug (typically the folder name)
					'source'                => get_stylesheet_directory() . '/lib/plugins/starter-client.zip', // The plugin source
					'required'              => true, // If false, the plugin is only 'recommended' instead of required
					'version'               => '', // E.g. 1.0.0. If set, the active plugin must be this version or higher, otherwise a notice is presented
					'force_activation'      => true, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch
					'force_deactivation'    => true, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins
					'external_url'          => '', // If set, overrides default API URL and points to an external URL
				),

				array(
					'name'                  => 'Starter Slider', // The plugin name
					'slug'                  => 'starter-slider', // The plugin slug (typically the folder name)
					'source'                => get_stylesheet_directory() . '/lib/plugins/starter-slider.zip', // The plugin source
					'required'              => true, // If false, the plugin is only 'recommended' instead of required
					'version'               => '', // E.g. 1.0.0. If set, the active plugin must be this version or higher, otherwise a notice is presented
					'force_activation'      => true, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch
					'force_deactivation'    => true, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins
					'external_url'          => '', // If set, overrides default API URL and points to an external URL
				),

				array(
					'name'                  => 'Starter Team', // The plugin name
					'slug'                  => 'starter-team', // The plugin slug (typically the folder name)
					'source'                => get_stylesheet_directory() . '/lib/plugins/starter-team.zip', // The plugin source
					'required'              => true, // If false, the plugin is only 'recommended' instead of required
					'version'               => '', // E.g. 1.0.0. If set, the active plugin must be this version or higher, otherwise a notice is presented
					'force_activation'      => true, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch
					'force_deactivation'    => true, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins
					'external_url'          => '', // If set, overrides default API URL and points to an external URL
				),



				array(
					'name'                  => 'Themeum Project', // The plugin name
					'slug'                  => 'themeum-project', // The plugin slug (typically the folder name)
					'source'                => get_stylesheet_directory() . '/lib/plugins/themeum-project.zip', // The plugin source
					'required'              => true, // If false, the plugin is only 'recommended' instead of required
					'version'               => '', // E.g. 1.0.0. If set, the active plugin must be this version or higher, otherwise a notice is presented
					'force_activation'      => true, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch
					'force_deactivation'    => true, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins
					'external_url'          => '', // If set, overrides default API URL and points to an external URL
				)
			);

	$theme_text_domain = 'themeum';

	/**
	* Array of configuration settings. Amend each line as needed.
	* If you want the default strings to be available under your own theme domain,
	* leave the strings uncommented.
	* Some of the strings are added into a sprintf, so see the comments at the
	* end of each line for what each argument will be.
	*/
	$config = array(
			'domain'            => $theme_text_domain,           // Text domain - likely want to be the same as your theme.
			'default_path'      => '',                           // Default absolute path to pre-packaged plugins
			'parent_menu_slug'  => 'themes.php',         		 // Default parent menu slug
			'parent_url_slug'   => 'themes.php',         		 // Default parent URL slug
			'menu'              => 'install-required-plugins',   // Menu slug
			'has_notices'       => true,                         // Show admin notices or not
			'is_automatic'      => false,            			 // Automatically activate plugins after installation or not
			'message'           => '',               			 // Message to output right before the plugins table
			'strings'           => array(
						'page_title'                                => __( 'Install Required Plugins', $theme_text_domain ),
						'menu_title'                                => __( 'Install Plugins', $theme_text_domain ),
						'installing'                                => __( 'Installing Plugin: %s', $theme_text_domain ), // %1$s = plugin name
						'oops'                                      => __( 'Something went wrong with the plugin API.', $theme_text_domain ),
						'notice_can_install_required'               => _n_noop( 'This theme requires the following plugin: %1$s.', 'This theme requires the following plugins: %1$s.' ), // %1$s = plugin name(s)
						'notice_can_install_recommended'            => _n_noop( 'This theme recommends the following plugin: %1$s.', 'This theme recommends the following plugins: %1$s.' ), // %1$s = plugin name(s)
						'notice_cannot_install'                     => _n_noop( 'Sorry, but you do not have the correct permissions to install the %s plugin. Contact the administrator of this site for help on getting the plugin installed.', 'Sorry, but you do not have the correct permissions to install the %s plugins. Contact the administrator of this site for help on getting the plugins installed.' ), // %1$s = plugin name(s)
						'notice_can_activate_required'              => _n_noop( 'The following required plugin is currently inactive: %1$s.', 'The following required plugins are currently inactive: %1$s.' ), // %1$s = plugin name(s)
						'notice_can_activate_recommended'           => _n_noop( 'The following recommended plugin is currently inactive: %1$s.', 'The following recommended plugins are currently inactive: %1$s.' ), // %1$s = plugin name(s)
						'notice_cannot_activate'                    => _n_noop( 'Sorry, but you do not have the correct permissions to activate the %s plugin. Contact the administrator of this site for help on getting the plugin activated.', 'Sorry, but you do not have the correct permissions to activate the %s plugins. Contact the administrator of this site for help on getting the plugins activated.' ), // %1$s = plugin name(s)
						'notice_ask_to_update'                      => _n_noop( 'The following plugin needs to be updated to its latest version to ensure maximum compatibility with this theme: %1$s.', 'The following plugins need to be updated to their latest version to ensure maximum compatibility with this theme: %1$s.' ), // %1$s = plugin name(s)
						'notice_cannot_update'                      => _n_noop( 'Sorry, but you do not have the correct permissions to update the %s plugin. Contact the administrator of this site for help on getting the plugin updated.', 'Sorry, but you do not have the correct permissions to update the %s plugins. Contact the administrator of this site for help on getting the plugins updated.' ), // %1$s = plugin name(s)
						'install_link'                              => _n_noop( 'Begin installing plugin', 'Begin installing plugins' ),
						'activate_link'                             => _n_noop( 'Activate installed plugin', 'Activate installed plugins' ),
						'return'                                    => __( 'Return to Required Plugins Installer', $theme_text_domain ),
						'plugin_activated'                          => __( 'Plugin activated successfully.', $theme_text_domain ),
						'complete'                                  => __( 'All plugins installed and activated successfully. %s', $theme_text_domain ) // %1$s = dashboard link
				)
	);

	tgmpa( $plugins, $config );

	}

endif;



/*-------------------------------------------------------
 *			Themeum Pagination
 *-------------------------------------------------------*/

if(!function_exists('thm_pagination')):

	function thm_pagination($pages = '', $range = 2)
	{  
	     $showitems = ($range * 1)+1;  

	     global $paged;

	     if(empty($paged)) $paged = 1;

	     if($pages == '')
	     {
	         global $wp_query;
	         $pages = $wp_query->max_num_pages;

	         if(!$pages)
	         {
	             $pages = 1;
	         }
	     }   

	     if(1 != $pages)
	     {
			echo "<ul class='pagination'>";

			if($paged > 2 && $paged > $range+1 && $showitems < $pages){
				echo "<li><a href='".get_pagenum_link(1)."'>&laquo;</a></li>";
			}

			if($paged > 1 && $showitems < $pages){ 
				echo '<li>';
				previous_posts_link("Previous");
				echo '</li>';
			}

			for ($i=1; $i <= $pages; $i++)
			{
				if (1 != $pages &&( !($i >= $paged+$range+1 || $i <= $paged-$range-1) || $pages <= $showitems ))
				{
					echo ($paged == $i)? "<li class='active'><a href='#'>".$i."</a></li>":"<li><a href='".get_pagenum_link($i)."' >".$i."</a></li>";
				}
			}

			if ($paged < $pages && $showitems < $pages){
				echo '<li>';
				next_posts_link("Next");
				echo '</li>';
			}

			if ($paged < $pages-1 &&  $paged+$range-1 < $pages && $showitems < $pages){
				echo "<li><a href='".get_pagenum_link($pages)."'>&raquo;</a></li>";
			}
			
			echo "</ul>";
	     }
	}

endif;


/*-------------------------------------------------------
 *				Themeum Comment
 *-------------------------------------------------------*/

if(!function_exists('themeum_comment')):

	function themeum_comment($comment, $args, $depth)
	{
		$GLOBALS['comment'] = $comment;
		switch ( $comment->comment_type ) :
			case 'pingback' :
			case 'trackback' :
			// Display trackbacks differently than normal comments.
		?>
		<li <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">
			<p>Pingback: <?php comment_author_link(); ?> <?php edit_comment_link( __( '(Edit)', 'themeum' ), '<span class="edit-link">', '</span>' ); ?></p>
		<?php
				break;
			default :
			
			global $post;
		?>
		<li <?php comment_class(); ?> id="li-comment-<?php comment_ID(); ?>">
			<div id="comment-<?php comment_ID(); ?>" class="comment-body media">
				
					<div class="comment-avartar pull-left">
						<?php
							echo get_avatar( $comment, $args['avatar_size'] );
						?>
					</div>
					<div class="comment-context media-body">
						<div class="comment-head">
							<?php
								printf( '<span class="comment-author">%1$s</span>',
									get_comment_author_link());
							?>
							<span class="comment-date"><?php echo get_comment_date() ?></span><span class="comment-time"> at <?php echo get_comment_time()?></span>

							<?php edit_comment_link( __( 'Edit', 'themeum' ), '<span class="edit-link">', '</span>' ); ?>
							<span class="comment-reply">
								<?php comment_reply_link( array_merge( $args, array( 'reply_text' => __( 'Reply', 'themeum' ), 'after' => '', 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ); ?>
							</span>
						</div>

						<?php if ( '0' == $comment->comment_approved ) : ?>
						<p class="comment-awaiting-moderation"><?php _e( 'Your comment is awaiting moderation.', 'themeum' ); ?></p>
						<?php endif; ?>

						<div class="comment-content">
							<?php comment_text(); ?>
						</div>
					</div>
				
			</div>
		<?php
			break;
		endswitch; 
	}

endif;

/*--------------------------------------------------------------
 *			Theme Shortcode
 *-------------------------------------------------------------*/

// service shortcode

add_shortcode('service','service_shortcode');

function service_shortcode($atts,$content = null)
{
	extract(shortcode_atts(array( 'icon' => '', 'title' => ''),$atts));

	$output = '';

	$output .= '<div class="service-box col-md-4 col-sm-6 col-xs-12">';
	$output .= '<div class="service-box-1 pull-left">';
	$output .= '<span><i class="fa fa-'.$icon.' icon-custom-style"></i></span>';
	$output .= '</div>';
	$output .= '<div class="service-box-2">';
	$output .= '<h3>'.$title.'</h3>';
	$output .= '<p>'.$content.'</p>';
	$output .= '</div>';
	$output .= '</div>';

	return $output;
}

// feature shortcode

add_shortcode('feature','feature_shortcode');

function feature_shortcode($atts,$content = null)
{
	extract(shortcode_atts(array( 'icon' => '', 'title' => '', 'color' => '1'),$atts));

	$output = '';
	$output .= '<div class="feature-box col-md-4 col-sm-6 col-xs-12">';
	$output .= '<div class="feature-box-1 pull-left color-'.$color.'">';
	$output .= '<span><i class="fa fa-'.$icon.' icon-custom-style"></i></span>';
	$output .= '</div>';
	$output .= '<div class="feature-box-2">';
	$output .= '<h3>'.$title.'</h3>';
	$output .= '<p>'.$content.'</p>';
	$output .= '</div>';
	$output .= '</div>';

	return $output;
}

// feature shortcode

add_shortcode('action','call_to_action_shortcode');

function call_to_action_shortcode($atts,$content = null)
{
	extract(shortcode_atts(array( 'title' => '', 'link' => '#', 'button' => 'Purchase Now'),$atts));

	$output = '';
	$output .= '<div id="call-to-action">';
	$output .= '<div class="container">';
	$output .= '<div class="row">';
	$output .= '<div class="col-xs-12 col-sm-7 col-md-9">';
	$output .= '<h2>'.$content.'</h2>';
	$output .= '</div>';
	$output .= '<div class="col-xs-12 col-sm-5 col-md-3">';
	$output .= '<a class="btn btn-success btn-lg pull-right" href="'.$link.'">'.$button.'</a>';
	$output .= '</div>';
	$output .= '</div>';
	$output .= '</div>';
	$output .= '</div>';

	return $output;
}


/*--------------------------------------------------------------
 * Get All Terms of Taxonomy 
 * @author : Themeum
 *-------------------------------------------------------------*/


function get_all_term_names( $post_id, $taxonomy = 'post_tag' )
{
	$terms = get_the_terms( $post_id, $taxonomy );

	$term_names = '';
    if ( $terms && ! is_wp_error( $terms ) )
    { 
        $term_name = array();

        foreach ( $terms as $term ) {
            $term_name[] = $term->name;
        }

        $term_names = join( ", ", $term_name );
    }

    return $term_names;
}


/*--------------------------------------------------------------
 *				One-Page Nav Walker
 *-------------------------------------------------------------*/

class Onepage_Walker extends Walker_Nav_menu{

	function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0){

		global $wp_query;

		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

		$class_names = $value = '';
		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$class_names = join(' ', $classes);

       	$class_names = ' class="'. esc_attr( $class_names ) . '"';

       
		$attributes 	= ! empty( $item->target )     ? ' target="' . esc_attr( $item->target     ) .'"' : '';
		$attributes 	.= ! empty( $item->xfn )        ? ' rel="'    . esc_attr( $item->xfn        ) .'"' : '';


		if($item->object == 'page')
		{
		    $post_object = get_post($item->object_id);

		    $separate_page = get_post_meta($item->object_id, "thm_no_hash", true);

		    $disable_item = get_post_meta($item->object_id, "thm_disable_menu", true);

			$current_page_id = get_option('page_on_front');

		    if ( ( $disable_item != true ) && ( $post_object->ID != $current_page_id ) ) {

		    	$output .= $indent . '<li id="menu-item-'. $item->ID . '"' . $value . $class_names.'>';

		    	if ( $separate_page == true )
		        	$attributes .= ! empty( $item->url ) ? ' href="'   . esc_attr( $item->url ) .'" class="no-scroll"' : '';
		        else{
		        	if (is_front_page()) 
		        		$attributes .= ' href="#' . $post_object->post_name . '"'; 
		        	else 
		        		$attributes .= ' href="' . home_url() . '#' . $post_object->post_name . '" class="no-scroll"';
		        }	

		        $item_output = $args->before;
		        $item_output .= '<a'. $attributes .'>';
		        $item_output .= $args->link_before .apply_filters( 'the_title', $item->title, $item->ID );
		        $item_output .= $args->link_after;
		        $item_output .= '</a>';
		        $item_output .= $args->after;

		        $output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );            	              	
		    }
		                             
		}
		else
		{

			$output .= $indent . '<li id="menu-item-'. $item->ID . '"' . $value . $class_names.'>';

		    $attributes .= ! empty( $item->url ) ? ' href="' . esc_attr( $item->url ) .'" class="no-scroll"' : '';

		    $item_output = $args->before;
	        $item_output .= '<a'. $attributes .'>';
	        $item_output .= $args->link_before .apply_filters( 'the_title', $item->title, $item->ID );
	        $item_output .= $args->link_after;
	        $item_output .= '</a>';
	        $item_output .= $args->after;

		    $output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
		}
	}
}