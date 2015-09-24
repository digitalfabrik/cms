<?php
/*
Plugin Name: Starter Client
Plugin URI: http://www.themeum.com
Description: Starter Client Post Type Plugins
Author: Themeum
Version: 1.0.0
Author URI: http://www.themeum.com
*/

/*--------------------------------------------------------------
*			Register Client Post Type
*-------------------------------------------------------------*/

function themeum_post_type_client()
{
	$labels = array(
			'name'                	=> _x( 'Clients', 'Clients', 'themeum' ),
			'singular_name'       	=> _x( 'Client', 'Client', 'themeum' ),
			'menu_name'           	=> __( 'Clients', 'themeum' ),
			'parent_item_colon'   	=> __( 'Parent Client:', 'themeum' ),
			'all_items'           	=> __( 'All Client', 'themeum' ),
			'view_item'           	=> __( 'View Client', 'themeum' ),
			'add_new_item'        	=> __( 'Add New Client', 'themeum' ),
			'add_new'             	=> __( 'New Client', 'themeum' ),
			'edit_item'           	=> __( 'Edit Client', 'themeum' ),
			'update_item'         	=> __( 'Update Client', 'themeum' ),
			'search_items'        	=> __( 'Search Client', 'themeum' ),
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
			'supports'           	=> array( 'title','thumbnail')
		);

	register_post_type('client',$args);

}

add_action('init','themeum_post_type_client');


/*--------------------------------------------------------------
*			Portfolio Slider Shortcode
*-------------------------------------------------------------*/

add_shortcode('themeum_client','themeum_client_shortcode');

function themeum_client_shortcode($atts, $content)
{
	$args = array(
			'post_type'			=> 'client',
			'posts_per_page' 	=> 16,
			'orderby' 			=> 'menu_order',
			'order' 			=> 'ASC'
		);

	$clients = get_posts($args);

	$output = '<div class="clients">';

	foreach ($clients as $post)
	{
		setup_postdata( $post );

		$output .= '<div class="col-xs-4 col-sm-3">';
		$output .= '<div class="client-item">';
		
		if (has_post_thumbnail($post->ID)){
			$output .= get_the_post_thumbnail($post->ID,'full',array('class'=>'img-responsive'));
			$large_image = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), 'full');
		}

		$output .= '</div>';
		$output .= '</div>';

	}

	$output .= '</div>';

	wp_reset_postdata();

	return $output;
}



/*--------------------------------------------------------------
 *					Add Submenu
 *-------------------------------------------------------------*/

function client_posts_sort()
{
    add_submenu_page('edit.php?post_type=client', 'Sort Team', 'Sort', 'edit_posts', basename(__FILE__), 'client_posts_sort_callback');
}

add_action('admin_menu' , 'client_posts_sort');


function client_posts_sort_callback()
{
	$clients = new WP_Query('post_type=client&posts_per_page=-1&orderby=menu_order&order=ASC');
?>
	<div class="wrap">
		<h3>Sort Clients<img src="<?php echo home_url(); ?>/wp-admin/images/loading.gif" id="loading-animation" /></h3>
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

function client_posts_sort_styles()
{
	$screen = get_current_screen();
	
	if($screen->post_type == 'client'){
		wp_enqueue_style( 'sort-stylesheet', plugins_url( '/css/sort-stylesheet.css' , __FILE__ ), array(), false, false );
		wp_enqueue_script('jquery-ui-sortable');
		wp_enqueue_script( 'sort-script', plugins_url( '/js/sort-script.js' , __FILE__ ), array(), false, true );
	}
}

add_action( 'admin_print_styles', 'client_posts_sort_styles' );


/*--------------------------------------------------------------
 *				Ajax Call-back
 *-------------------------------------------------------------*/

function client_posts_sort_order()
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

add_action('wp_ajax_client_sort', 'client_posts_sort_order');