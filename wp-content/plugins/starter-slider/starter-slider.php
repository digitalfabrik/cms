<?php
/*
 Plugin Name: Starter Slider
 Plugin URI: http://www.themeum.com
 Description: Starter Slider Post Type Plugins
 Author: Themeum
 Version: 1.0.0
 Author URI: http://www.themeum.com
 */

/*--------------------------------------------------------------
 *			Register Slider Post Type
 *-------------------------------------------------------------*/

function themeum_slider()
{
	$labels = array( 
		'name'                	=> _x( 'Sliders', 'Sliders', 'themeum' ),
		'singular_name'       	=> _x( 'Slider', 'Slider', 'themeum' ),
		'menu_name'           	=> __( 'Sliders', 'themeum' ),
		'parent_item_colon'   	=> __( 'Parent Slider:', 'themeum' ),
		'all_items'           	=> __( 'All Sliders', 'themeum' ),
		'view_item'           	=> __( 'View Slider', 'themeum' ),
		'add_new_item'        	=> __( 'Add New Slider', 'themeum' ),
		'add_new'             	=> __( 'New Slider', 'themeum' ),
		'edit_item'           	=> __( 'Edit Slider', 'themeum' ),
		'update_item'         	=> __( 'Update Slider', 'themeum' ),
		'search_items'        	=> __( 'Search Slider', 'themeum' ),
		'not_found'           	=> __( 'No article found', 'themeum' ),
		'not_found_in_trash'  	=> __( 'No article found in Trash', 'themeum' )
		);

	$args = array(  
		'labels'             	=> $labels,
		'public'             	=> true,
		'publicly_queryable' 	=> true,
		'show_ui'            	=> true,
		'show_in_menu'       	=> true,
		'query_var'          	=> true,
		'rewrite' 				=> true,
		'capability_type'    	=> 'post',
		'show_in_admin_bar'   	=> true,
		'can_export'          	=> true,
		'has_archive'        	=> true,
		'hierarchical'       	=> false,
		'menu_position'      	=> null,
		'supports'           	=> array( 'title', 'editor', 'thumbnail')
		);

	register_post_type('slider',$args);

}

add_action('init','themeum_slider');


/*--------------------------------------------------------------
 *					Starter Slider
 *-------------------------------------------------------------*/

add_shortcode('themeum_slider','themeum_slider_shortcode');

function themeum_slider_shortcode( $atts, $content = null )
{
	$output = '';
	$slides = get_posts(array( 'post_type' => 'slider', 'posts_per_page' => 5, 'orderby' => 'menu_order', 'order' => 'ASC' ));

	if(count($slides))
	{
		$output .= '<div id="slider">';
		$output .= '<div id="carousel-main" class="carousel slide">';
		$output .= '<div class="carousel-inner">';
		
		foreach ($slides as $key => $post)
		{
			setup_postdata($post);

			$output .= '<div class="item '.(($key == 0)?"active":"").'">';

			if (has_post_thumbnail($post->ID)) {
				$output .= get_the_post_thumbnail($post->ID,'full',array('class'=>'img-responsive'));
			}

			$output .= '<div class="carousel-caption">';
			$output .= '<div class="container">';
			$output .= '<h2>'.get_the_title( $post->ID ).'</h2>';
			$output .= '<p class="lead">'.get_the_content().'</p>';

			$btn_link = get_post_meta( $post->ID,'thm_btn_link',true );
			$btn_text = get_post_meta( $post->ID,'thm_btn_text',true );
			$btn_two_link = get_post_meta( $post->ID,'thm_btn_two_link',true );
			$btn_two_text = get_post_meta( $post->ID,'thm_btn_two_text',true );

			if( !empty( $btn_text ) ){
				$output .= '<a class="btn btn-success btn-lg" href="'.$btn_link.'">'.$btn_text.'</a>  &nbsp; &nbsp;';
			}
			if( !empty( $btn_two_text ) ){
				$output .= '<a class="btn btn-default btn-lg" href="'.$btn_two_link.'">'.$btn_two_text.'</a>';
			}
			
			$output .= '</div>';
			$output .= '</div>';
			$output .= '</div>';
		}

		wp_reset_postdata();

		$output .= '</div>';
		$output .= '<a class="left carousel-control" href="#carousel-main" data-slide="prev">';
		$output .= '<i class="fa fa-angle-left"></i>';
		$output .= '</a>';
		$output .= '<a class="right carousel-control" href="#carousel-main" data-slide="next">';
		$output .= '<i class="fa fa-angle-right"></i>';
		$output .= '</a>';
		$output .= '</div>';
		$output .= '</div>';
	}
	return $output;
}


/*--------------------------------------------------------------
 *					Add Submenu
 *-------------------------------------------------------------*/

function slider_posts_sort()
{
    add_submenu_page('edit.php?post_type=slider', 'Sort Slide', 'Sort', 'edit_posts', basename(__FILE__), 'slider_posts_sort_callback');
}

add_action('admin_menu' , 'slider_posts_sort');


function slider_posts_sort_callback()
{
	$slides = new WP_Query('post_type=slider&posts_per_page=-1&orderby=menu_order&order=ASC');
?>
	<div class="wrap">
		<h3>Sort Slides<img src="<?php echo home_url(); ?>/wp-admin/images/loading.gif" id="loading-animation" /></h3>
		<ul id="slide-list">
			<?php if($slides->have_posts()): ?>
				<?php while ( $slides->have_posts() ){ $slides->the_post(); ?>
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

function slider_posts_sort_styles()
{
	$screen = get_current_screen();
	
	if($screen->post_type == 'slider')
	{
		wp_enqueue_style( 'sort-stylesheet', plugins_url( '/css/sort-stylesheet.css' , __FILE__ ), array(), false, false );
		wp_enqueue_script('jquery-ui-sortable');
		wp_enqueue_script( 'sort-script', plugins_url( '/js/sort-script.js' , __FILE__ ), array(), false, true );
	}
}

add_action( 'admin_print_styles', 'slider_posts_sort_styles' );


/*--------------------------------------------------------------
 *				Ajax Call-back
 *-------------------------------------------------------------*/

function slider_posts_sort_order()
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

add_action('wp_ajax_slide_sort', 'slider_posts_sort_order');