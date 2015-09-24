<?php
/*
 Plugin Name: Starter Team
 Plugin URI: http://www.themeum.com
 Description: Starter Team Post Type Plugins
 Author: Themeum
 Version: 1.0.0
 Author URI: http://www.themeum.com
 */

/*--------------------------------------------------------------
 *			Register Team Post Type
 *-------------------------------------------------------------*/

function themeum_post_type_team()
{
	$labels = array(
		'name'                	=> _x( 'Members', 'Members', 'themeum' ),
		'singular_name'       	=> _x( 'Member', 'Member', 'themeum' ),
		'menu_name'           	=> __( 'Teams', 'themeum' ),
		'parent_item_colon'   	=> __( 'Parent Member:', 'themeum' ),
		'all_items'           	=> __( 'All Member', 'themeum' ),
		'view_item'           	=> __( 'View Member', 'themeum' ),
		'add_new_item'        	=> __( 'Add New Member', 'themeum' ),
		'add_new'             	=> __( 'New Member', 'themeum' ),
		'edit_item'           	=> __( 'Edit Member', 'themeum' ),
		'update_item'         	=> __( 'Update Member', 'themeum' ),
		'search_items'        	=> __( 'Search Member', 'themeum' ),
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

	register_post_type('team',$args);

}

add_action('init','themeum_post_type_team');


/*--------------------------------------------------------------
 *			Portfolio Slider Shortcode
 *-------------------------------------------------------------*/

add_shortcode('starter_team','starter_team_slider_shortcode');

function starter_team_slider_shortcode($atts, $content)
{
	$args = array(
			'post_type'			=> 'team',
			'posts_per_page' 	=> 12,
			'orderby' 			=> 'menu_order',
			'order' 			=> 'ASC'
		);

	$members = get_posts($args);

	$output = '';
	$output .= '<div id="team-carousel" class="carousel slide">';
	$output .= '<div class="carousel-inner">';

	$total = count($members);
	$count = 0;
	$index = 0;

	foreach ($members as $post)
	{
		setup_postdata( $post );

		if($index == 0)
		{
			$output .= '<div class="item '.(($count == 0)?'active':'').'">';
			$output .= '<div class="row">';
		}

		$output .= '<div class="col-xs-12 col-sm-6  col-md-3">';
		$output .= '<div class="team-box">';
		$output .= '<div class="team-image">';

		if (has_post_thumbnail($post->ID)){
			$output .= get_the_post_thumbnail($post->ID,'full',array('class'=>'img-responsive'));
			$large_image = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), 'full');
		}

		$output .= '</div>';
		$output .= '<div class="team-desc">';
		$output .= '<strong class="name">'.get_the_title( $post->ID ).'</strong>';

		$designation = get_post_meta( $post->ID, 'thm_designation', true );
		if(!empty($designation)){
			$output .= '<p class="small text-muted designation">'. $designation .'</p>';	
		}

		$output .= '<p class="desc">'.get_the_content( $post->ID ).'</p>';		

		$facebook 	= get_post_meta( $post->ID, 'thm_facebook_url', true );
		$twitter 	= get_post_meta( $post->ID, 'thm_twitter_url', true );
		$plusone 	= get_post_meta( $post->ID, 'thm_plusone_url', true );
		$pinterest 	= get_post_meta( $post->ID, 'thm_pinterest_url', true );
		$linkedin 	= get_post_meta( $post->ID, 'thm_linkedin_url', true );
		$dribbble 	= get_post_meta( $post->ID, 'thm_dribbble_url', true );
		$behance 	= get_post_meta( $post->ID, 'thm_behance_url', true );
		$flickr 	= get_post_meta( $post->ID, 'thm_flickr_url', true );

		if( ( $facebook !='' ) ||
			( $twitter !='' ) ||
			( $plusone !='' ) ||
			( $pinterest !='' ) ||
			( $linkedin !='' ) ||
			( $dribbble !='' ) ||
			( $flickr !='' ) )
		{
			$output .='<ul class="social">';

			if( $facebook !='' ){
				$output .='<li class="facebook"><a target="_blank" href="' . $facebook . '"><i class="fa fa-facebook"></i></a></li>';
			}

			if( $twitter !='' ){
				$output .='<li class="twitter"><a target="_blank" href="' . $twitter . '"><i class="fa fa-twitter"></i></a></li>';
			}

			if( $plusone !='' ){
				$output .='<li class="plusone"><a target="_blank" href="' . $plusone . '"><i class="fa fa-google-plus"></i></a></li>';
			}

			if( $pinterest !='' ){
				$output .='<li class="pinterest"><a target="_blank" href="' . $pinterest . '"><i class="fa fa-pinterest"></i></a></li>';
			}

			if( $linkedin !='' ){
				$output .='<li class="linkedin"><a target="_blank" href="' . $linkedin . '"><i class="fa fa-linkedin"></i></a></li>';
			}

			if( $dribbble !='' ){
				$output .='<li class="dribbble"><a target="_blank" href="' . $dribbble . '"><i class="fa fa-dribbble"></i></a></li>';
			}

			if( $flickr !='' ){
				$output .='<li class="flickr"><a target="_blank" href="' . $flickr . '"><i class="fa fa-flickr"></i></a></li>';
			}

			$output .='</ul>';
		}

		$output .= '</div>';
		$output .= '</div>';
		$output .= '</div>';

		$count++;
		$index++;

		if ($index == 4)
		{
			$output .= '</div>';
			$output .= '</div>';
			$index = 0;
		}
		elseif($count == $total){
			$output .= '</div>';
			$output .= '</div>';
		}
	}

	$output .= '</div>';
	$output .= '<a class="left carousel-control" href="#team-carousel" data-slide="prev">';
	$output .= '<i class="fa fa-angle-left"></i>';
	$output .= '</a>';
	$output .= '<a class="right carousel-control" href="#team-carousel" data-slide="next">';
	$output .= '<i class="fa fa-angle-right"></i>';
	$output .= '</a>';
	$output .= '</div>';

	wp_reset_postdata();

	return $output;
}


/*--------------------------------------------------------------
 *			Team Single Template Loaded
 *-------------------------------------------------------------*/

function team_template($single_template) {
	global $post;

	if ($post->post_type == 'team') {
		wp_redirect( home_url() );
	}

	return $single_template;
}

add_filter( "single_template", "team_template" ) ;



/*--------------------------------------------------------------
 *					Add Submenu
 *-------------------------------------------------------------*/

function team_posts_sort()
{
    add_submenu_page('edit.php?post_type=team', 'Sort Team', 'Sort', 'edit_posts', basename(__FILE__), 'team_posts_sort_callback');
}

add_action('admin_menu' , 'team_posts_sort');


function team_posts_sort_callback()
{
	$members = new WP_Query('post_type=team&posts_per_page=-1&orderby=menu_order&order=ASC');
?>
	<div class="wrap">
		<h3>Sort Members<img src="<?php echo home_url(); ?>/wp-admin/images/loading.gif" id="loading-animation" /></h3>
		<ul id="slide-list">
			<?php if($members->have_posts()): ?>
				<?php while ( $members->have_posts() ){ $members->the_post(); ?>
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

function team_posts_sort_styles()
{
	$screen = get_current_screen();
	
	if($screen->post_type == 'team')
	{
		wp_enqueue_style( 'sort-stylesheet', plugins_url( '/css/sort-stylesheet.css' , __FILE__ ), array(), false, false );
		wp_enqueue_script('jquery-ui-sortable');
		wp_enqueue_script( 'sort-script', plugins_url( '/js/sort-script.js' , __FILE__ ), array(), false, true );
	}
}

add_action( 'admin_print_styles', 'team_posts_sort_styles' );


/*--------------------------------------------------------------
 *				Ajax Call-back
 *-------------------------------------------------------------*/

function team_posts_sort_order()
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

add_action('wp_ajax_team_sort', 'team_posts_sort_order');