<?php
	function wpse_92155_before_delete_post() {
		wp_redirect(admin_url('edit.php'));
		exit();
	} // function wpse_92155_before_delete_post
	add_action('before_delete_post', 'wpse_92155_before_delete_post', 1);

	add_action( 'admin_head-edit.php', 'hide_delete_css_wpse_92155' );
	add_filter( 'post_row_actions', 'hide_row_action_wpse_92155', 10, 2 );
	add_filter( 'page_row_actions', 'hide_row_action_wpse_92155', 10, 2 );

	function hide_delete_css_wpse_92155()
	{
		if( isset( $_REQUEST['post_status'] ) && 'trash' == $_REQUEST['post_status'] ) 
		{
			echo "<style>
				.alignleft.actions:first-child, #delete_all {
					display: none;
				}
				</style>";
		}
	}

	function hide_row_action_wpse_92155( $actions, $post ) 
	{
		if( isset( $_REQUEST['post_status'] ) && 'trash' == $_REQUEST['post_status'] ) 
			unset( $actions['delete'] );

		return $actions; 
	}
?>
