<?php

if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

// TODO: only for edit / write post/page URIs and dashboard ?
add_filter('query', array('RevisionaryAdminHardway', 'flt_include_pending_revisions'), 13 ); // allow regular hardway filter to apply scoping first

if ( ! is_content_administrator_rvy() ) {
	// URIs ending in specified filename will not be subjected to low-level query filtering
	$nomess_uris = apply_filters( 'rvy_skip_lastresort_filter_uris', array( 'p-admin/categories.php', 'p-admin/themes.php', 'p-admin/plugins.php', 'p-admin/profile.php' ) );
	$nomess_uris = array_merge($nomess_uris, array('p-admin/admin-ajax.php'));

	$haystack = $_SERVER['REQUEST_URI'];
	$haystack_length = strlen($haystack);
	$matched = false;
	
	foreach($nomess_uris as $needle) {
		$pos = strpos($haystack, $needle);
		if ( is_numeric($pos) && ( $pos == ( $haystack_length - strlen($needle) ) ) ) {
			$matched = true;
			break;
		}
	}
	
	if ( ! $matched ) {
		require_once( dirname(__FILE__).'/hardway-admin_non-administrator_rvy.php' );
		add_filter('query', array('RevisionaryAdminHardway_Ltd', 'flt_last_resort_query'), 12 );
	}
} 	


/**
 * RevisionaryAdminHardway PHP class for the WordPress plugin Revisionary
 * hardway-admin_rvy.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2009-2013
 *
 */
class RevisionaryAdminHardway {
	
	public static function flt_include_pending_revisions($query) {
		global $wpdb;
		
		if ( strpos( $query, 'num_comments' ) )
			return $query;
		
		// Require current user to be a site-wide editor due to complexity of applying scoped roles to revisions
		if ( strpos($query, "FROM $wpdb->posts") && ( strpos($query, ".post_status = 'pending'") || strpos($query, ".post_status = 'future'") || strpos($query, 'GROUP BY post_status') || strpos($query, "GROUP BY $wpdb->posts.post_status") || ( empty($_GET['post_status']) || ( 'all' == $_GET['post_status'] ) ) ) ) {

			$post_types = array_diff( get_post_types(), array( 'revision', 'attachment', 'nav_menu_item' ) );
			
			// counts for edit posts / pages
			if ( strpos($query, "GROUP BY post_status") ) {
				$p = ( strpos( $query, 'p.post_type' ) ) ? 'p.' : '';
	
				foreach ( $post_types as $post_type )
					$query = str_replace("{$p}post_type = '$post_type'", "( {$p}post_type = '$post_type' OR ( {$p}post_type = 'revision' AND ( {$p}post_status = 'pending' OR {$p}post_status = 'future' ) AND {$p}post_parent IN ( SELECT ID from $wpdb->posts WHERE post_type = '$post_type' ) ) )", $query);
	
			} elseif ( strpos($query, "GROUP BY $wpdb->posts.post_status") && strpos($query, "ELECT $wpdb->posts.post_status," ) ) {
				
				// also post-process the scoped equivalent 
				foreach ( $post_types as $post_type ) {
					if ( ! strpos( $query, "'$post_type' OR ( $wpdb->posts.post_type = 'revision'" ) )
						$query = str_replace(" post_type = '$post_type'", "( $wpdb->posts.post_type = '$post_type' OR ( $wpdb->posts.post_type = 'revision' AND $wpdb->posts.post_status IN ('pending', 'future') AND $wpdb->posts.post_parent IN ( SELECT ID from $wpdb->posts WHERE post_type = '$post_type' ) ) )", $query);
				}
					
			// edit pages / posts listing items
			} elseif ( strpos($query, 'ELECT') ) {	
				// include pending/scheduled revs in All, Pending or Scheduled list
				$status_clause = '';
				if ( strpos($query, ".post_status = 'pending'") || empty($_GET['post_status']) || ( 'all' == $_GET['post_status'] ) )
					$status_clause = "$wpdb->posts.post_status = 'pending'";
				
				if ( strpos($query, ".post_status = 'future'") || empty($_GET['post_status']) || ( 'all' == $_GET['post_status'] ) ) {
					$or = ( $status_clause ) ? ' OR ' : '';
					$status_clause .= $or . "$wpdb->posts.post_status = 'future'";
				}
				
				foreach ( $post_types as $post_type ) {
					$query = str_replace("$wpdb->posts.post_type = '$post_type' AND 1=2", "1=2", $query );
					$query = str_replace("$wpdb->posts.post_type = '$post_type'", "( $wpdb->posts.post_type = '$post_type' OR ( $wpdb->posts.post_type = 'revision' AND $wpdb->posts.post_parent IN ( SELECT ID from $wpdb->posts WHERE post_type = '$post_type' ) ) AND ( $status_clause ) )", $query);
				}

               	// work around Event Calendar Pro conflict
				if ( strpos( $query, "eventStart.meta_value as EventStartDate" ) ) {
					$query = str_replace( 
					'( eventStart.meta_key = "_EventStartDate" AND eventEnd.meta_key = "_EventEndDate" )', 
					"( ( eventStart.meta_key = '_EventStartDate' AND eventEnd.meta_key = '_EventEndDate' ) OR $wpdb->posts.post_type = 'revision' )", $query );
				
					$query = str_replace( 
					"( eventStart.meta_key = '_EventStartDate' AND eventEnd.meta_key = '_EventEndDate' )", 
					"( ( eventStart.meta_key = '_EventStartDate' AND eventEnd.meta_key = '_EventEndDate' ) OR $wpdb->posts.post_type = 'revision' )", $query );
				}
			} // endif SELECT query
			
		} // endif query pertains in any way to pending status and/or revisions
		
		return $query;
	}
}
?>