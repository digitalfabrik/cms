<?php

if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

class RevisionaryAdminHardway_Ltd {
	// low-level filtering of otherwise unhookable queries
	//
	// Todo: review all queries for version-specificity; apply regular expressions to make it less brittle
	public static function flt_last_resort_query($query) {
		global $wpdb;

		$posts = $wpdb->posts;
			
		// todo: confirm this is still necessary for elevated users
		// kill extra capability checks for revisions (user already passed our scoped test)		TODO: confirm did_action check is not needed, eliminate it
		if ( strpos($query, 'ELECT ') && strpos($query, $posts) ) {
			if ( preg_match("/SELECT\s*DISTINCT\s*$posts.ID\s*FROM\s*$posts\s*WHERE\s*1=1\s*AND\s*\(\s*post_author\s*=/", $query) && preg_match("/AND\s*$posts.ID\s*IN'/", $query) && did_action('posts_selection') ) {
				global $current_user;	
				$query = preg_replace( "/AND \( post_author = '{$current_user->ID}' \)/", '', $query);
				return $query;
			}
		}
		
		// totals on edit.php
		$matches = array();
		if ( strpos($query, "ELECT post_status, COUNT( * ) AS num_posts ") && preg_match("/FROM\s*{$posts}\s*WHERE post_type\s*=\s*'([^ ]+)'/", $query, $matches) ) {
			if ( ! empty( $matches[1] ) ) {
				$_post_type = $matches[1];
				
				if ( $type_obj = get_post_type_object( $_post_type ) ) {
					global $current_user;

					if ( ! empty( $current_user->allcaps[ $type_obj->cap->edit_others_posts ] ) ) {
						static $private_stati;
						
						if ( ! isset( $private_stati ) )
							$private_stati = get_post_stati( array( 'private' => true ) );
						
						foreach( $private_stati as $_status )  // as of WP 2.8.4, this substring is wrapped by parenthesis with nonstandard padding, so reduce chance of breakage by leaving them out of the replacement
							$query = str_replace( "post_status != '$_status' OR ( post_author = '{$current_user->ID}' AND post_status = '$_status' )", '1=1', $query);
					}
				}
			}
		}
			
		return $query;
	} // end function flt_last_resort_query
	
} // end class
?>