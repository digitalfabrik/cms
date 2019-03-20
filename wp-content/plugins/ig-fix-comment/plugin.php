<?php
/**
 * Plugin Name: Remove Comment Content
 * Description: Drop all content from comments to prevent exploits.
 * Version: 1.0
 * Author: Integreat Team / Sven Seeberg
 * Author URI: https://github.com/Integreat
 * License: MIT
 */

function ig_drop_comment_content( $commentdata ) {
	$commentdata['comment_author'] = "";
	$commentdata['comment_author_email'] = "";
	$commentdata['comment_author_url'] = "";
	$commentdata['comment_content'] = "";
	$commentdata['comment_type'] = "";
	$commentdata['user_ID'] = "";
	return $commentdata;
}
add_filter( 'preprocess_comment' , 'ig_drop_comment_content' );
?>

