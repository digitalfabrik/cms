<?php
/*
 Plugin Name: Themeum Project
 Plugin URI: http://www.themeum.com
 Description: Themeum Porfolio Post Type Plugins
 Author: Themeum
 Version: 1.0.0
 Author URI: http://www.themeum.com
 */

/*--------------------------------------------------------------
 *			Register project Post Type
 *-------------------------------------------------------------*/

function themeum_post_type_project()
{
	$labels = array( 
		'name'                	=> _x( 'Projects', 'Projects', 'themeum' ),
		'singular_name'       	=> _x( 'Project', 'Project', 'themeum' ),
		'menu_name'           	=> __( 'Projects', 'themeum' ),
		'parent_item_colon'   	=> __( 'Parent Project:', 'themeum' ),
		'all_items'           	=> __( 'All Project', 'themeum' ),
		'view_item'           	=> __( 'View Project', 'themeum' ),
		'add_new_item'        	=> __( 'Add New Project', 'themeum' ),
		'add_new'             	=> __( 'New Project', 'themeum' ),
		'edit_item'           	=> __( 'Edit Project', 'themeum' ),
		'update_item'         	=> __( 'Update Project', 'themeum' ),
		'search_items'        	=> __( 'Search Project', 'themeum' ),
		'not_found'           	=> __( 'No article found', 'themeum' ),
		'not_found_in_trash'  	=> __( 'No article found in Trash', 'themeum' )
		);

	$args = array(  
		'labels'             	=> $labels,
		'public'             	=> true,
		'publicly_queryable' 	=> true,
		'show_in_menu'       	=> true,
		'show_in_admin_bar'   	=> true,
		'can_export'          	=> true,
		'has_archive'        	=> true,
		'hierarchical'       	=> false,
		'menu_position'      	=> null,
		'supports'           	=> array( 'title','editor','thumbnail','comments')
		);

	register_post_type('project',$args);

}

add_action('init','themeum_post_type_project');


/*--------------------------------------------------------------
 *			View Message When Updated Project
 *-------------------------------------------------------------*/

function themeum_update_message_project()
{
	global $post, $post_ID;

	$message['project'] = array(
		0 => '',
		1 => sprintf( __('Project updated. <a href="%s">View project</a>', 'themeum' ), esc_url( get_permalink($post_ID) ) ),
		2 => __('Custom field updated.', 'themeum' ),
		3 => __('Custom field deleted.', 'themeum' ),
		4 => __('Project updated.', 'themeum' ),
		5 => isset($_GET['revision']) ? sprintf( __('Project restored to revision from %s', 'themeum' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => sprintf( __('Project published. <a href="%s">View project</a>', 'themeum' ), esc_url( get_permalink($post_ID) ) ),
		7 => __('Project saved.', 'themeum' ),
		8 => sprintf( __('Project submitted. <a target="_blank" href="%s">Preview project</a>', 'themeum' ), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		9 => sprintf( __('Project scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview project</a>', 'themeum' ), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
		10 => sprintf( __('Project draft updated. <a target="_blank" href="%s">Preview project</a>', 'themeum' ), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		);

return $message;
}

add_filter( 'post_updated_messages', 'themeum_update_message_project' );


/*--------------------------------------------------------------
 *			Register Custom Taxonomies
 *-------------------------------------------------------------*/

function themeum_create_project_taxonomy()
{
	$labels = array(	'name'              => _x( 'Categories', 'taxonomy general name' ),
		'singular_name'     => _x( 'Category', 'taxonomy singular name' ),
		'search_items'      => __( 'Search Category' ),
		'all_items'         => __( 'All Category' ),
		'parent_item'       => __( 'Parent Category' ),
		'parent_item_colon' => __( 'Parent Category:' ),
		'edit_item'         => __( 'Edit Category' ),
		'update_item'       => __( 'Update Category' ),
		'add_new_item'      => __( 'Add New Category' ),
		'new_item_name'     => __( 'New Category Name' ),
		'menu_name'         => __( 'Category' )
		);

	$args = array(	'hierarchical'      => true,
		'labels'            => $labels,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true
		);

	register_taxonomy('project_tag',array( 'project' ),$args);

}

add_action('init','themeum_create_project_taxonomy');


/*--------------------------------------------------------------
 *			Portfolio Slider Shortcode
 *-------------------------------------------------------------*/

add_shortcode('themeum_project_slider','themeum_project_slider_shortcode');

function themeum_project_slider_shortcode($atts, $content)
{

	extract(shortcode_atts(array('title' => ''),$atts));

	$args = array(
			'post_type'			=> 'project',
			'posts_per_page' 	=> -1,
			'orderby' 			=> 'menu_order',
			'order' 			=> 'ASC'
		);

	$myprojects = get_posts($args);

	$output = '';

	$output .= '<div id="recent-projects" class="portfolio clearfix">';

	$total = count($myprojects);
	$count = 0;
	$index = 0;

	foreach ($myprojects as $post){
		setup_postdata( $post );

		$output .= '<div class="col-xs-6 col-sm-3">';
		$output .= '<div class="portfolio-item">';
		
		if (has_post_thumbnail($post->ID)) {
			$thumb 			= wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), 'full');
			$large_image 	= wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), 'full');

			$output .= '<img class="img-responsive" src="'.$thumb[0].'" title="" alt="">';
		}else{
			$output .= '<img class="img-responsive" src="images/work_1.jpg" title="" alt="">';
		}

		$output .= '<div class="overlay">';
		$output .= '<a class="btn-preview" data-rel="prettyPhoto" href="'.$large_image[0].'"><i class="fa fa-link"></i></a>';
		$output .= '</div>';
		
		$output .= '</div>';
		$output .= '</div>';

		$count++;
		$index++;
	}

	$output .= '</div>';

	wp_reset_postdata();

	return $output;
}


/*--------------------------------------------------------------
 *			Project Single Template Loaded
 *-------------------------------------------------------------*/

function project_template($single_template) {
	global $post;

	if ($post->post_type == 'project') {
		$single_template = dirname( __FILE__ ) . '/project-template.php';
	}
	
	return $single_template;
}

add_filter( "single_template", "project_template" ) ;


/*--------------------------------------------------------------
 *				Add Script 
 *-------------------------------------------------------------*/

function themeum_project_scripts() {
	wp_enqueue_style( 'prettyCss', plugins_url( '/css/prettyPhoto.css' , __FILE__ ), array(), false, false );
	wp_enqueue_script( 'prettyPhoto', plugins_url( '/js/jquery.prettyPhoto.js' , __FILE__ ), array(), false, true );
	wp_enqueue_script( 'pretty-custom', plugins_url( '/js/prettyphoto-custom.js' , __FILE__ ), array(), false, true );
}

add_action( 'wp_enqueue_scripts', 'themeum_project_scripts', 40 );


/*--------------------------------------------------------------
 *				Add Script 
 *-------------------------------------------------------------*/

function project_posts_sort()
{
    add_submenu_page('edit.php?post_type=project', 'Sort Team', 'Sort', 'edit_posts', basename(__FILE__), 'project_posts_sort_callback');
}

add_action('admin_menu' , 'project_posts_sort');


function project_posts_sort_callback()
{
	$clients = new WP_Query('post_type=project&posts_per_page=-1&orderby=menu_order&order=ASC');
?>
	<div class="wrap">
		<h3>Sort Projects<img src="<?php echo home_url(); ?>/wp-admin/images/loading.gif" id="loading-animation" /></h3>
		<ul id="slide-list">
			<?php if($clients->have_posts()): ?>
				<?php while ( $clients->have_posts() ){ $clients->the_post(); ?>
					<li id="<?php the_id(); ?>"><?php the_title(); ?></li>			
				<?php } ?>
			<?php else: ?>
				<li>There is no Slide Created</li>		
			<?php endif; ?>
		</ul>
	</div>
<?php
}


/*--------------------------------------------------------------
 *				Add Sub-Menu Admin Style
 *-------------------------------------------------------------*/

function project_posts_sort_styles()
{
	$screen = get_current_screen();
	
	if($screen->post_type == 'project')
	{
		wp_enqueue_style( 'sort-stylesheet', plugins_url( '/css/sort-stylesheet.css' , __FILE__ ), array(), false, false );
		wp_enqueue_script('jquery-ui-sortable');
		wp_enqueue_script( 'sort-script', plugins_url( '/js/sort-script.js' , __FILE__ ), array(), false, false );
	}
}

add_action( 'admin_print_styles', 'project_posts_sort_styles' );


/*--------------------------------------------------------------
 *				Ajax Call-back
 *-------------------------------------------------------------*/

function project_posts_sort_order()
{
	global $wpdb; // WordPress database class

	$order = explode(',', $_POST['order']);
	$counter = 0;
	
	foreach ($order as $slide_id) {
		$wpdb->update($wpdb->posts, array( 'menu_order' => $counter ), array( 'ID' => $slide_id) );
		$counter++;
	}
	die(1);
}

add_action('wp_ajax_project_sort', 'project_posts_sort_order');