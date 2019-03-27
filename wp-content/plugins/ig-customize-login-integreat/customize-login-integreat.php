<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
/*
Plugin Name: Customize Login Integreat
Description: Helps to customize the login page
Version: 1.0
Author: Blanz
*/
function replace_logo() {

	$color_hex="FDDA0E";
	$color_rgb="253, 218, 14,";

	$path = get_admin_url();
	$path .= 'wp-admin/images/';

	?>
	<style type="text/css">

		#login h1 a {
			border: 2px solid #F1F1F1;
			border-radius 15px;
			background-size: 320px 100px;
			width: 320px;
			height: 100px;
			background-image:none, url(<?php echo $path ?>integreat_logo.png);
		}

		#login>h1>a:focus, #login>h1>a:hover{
			border-radius: 15px;
			box-shadow: none;
			background-image: url(<?php echo $path; ?>integreat_logo_yellow.png);
		}

		#login>h1>a:focus{
			border: 2px solid rgba(<?php echo($color_rgb);?> 1);
			border-radius: 15px;
			box-shadow: none;
			background-image: url(<?php echo $path; ?>integreat_logo_yellow.png);
		}

		.login p.message{
			border-left: 4px solid #<?php echo($color_hex);?>;
		}

		#user_login:focus{
			border-color: #<?php echo($color_hex);?>;
			box-shadow: 0px 0px 2px rgba(<?php echo($color_rgb);?> 0.8);
		}

		#user_pass:focus{
			border-color: #<?php echo($color_hex);?>;
			box-shadow: 0px 0px 2px rgba(<?php echo($color_rgb);?> 0.8);
		}

		#rememberme:focus{
			border-color: #<?php echo($color_hex);?>;
			box-shadow: 0px 0px 2px rgba(<?php echo($color_rgb);?> 0.8);
		}

		#wp-submit {
			color: #777;
			font-weight: bold;
			box-shadow: none;
			background: rgba(<?php echo($color_rgb);?> 0.6);
			border: 2px solid rgba(<?php echo($color_rgb);?> 1);
			text-decoration: none;
			text-shadow: none;
		}

		#wp-submit:hover{
			background: rgba(<?php echo($color_rgb);?> 1);
			box-shadow: none;
		}

		#backtoblog>a:hover, #nav>a:hover{
			color: #<?php echo($color_hex);?>!important;
			border:none!important;
			box-shadow: none!important;
		}

		#backtoblog>a:focus, #nav>a:focus{
			color: #<?php echo($color_hex);?>!important;
			border:none!important;
			box-shadow: none!important;
		}

	</style>
<?php }
add_action( 'login_enqueue_scripts', 'replace_logo' );


function ig_successful_login( $user_login, $user ) {
	$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	syslog(LOG_NOTICE, "INTEGREAT CMS - LOGIN SUCCEEDED: $user_login via $url.");
}
add_action('wp_login', 'ig_successful_login', 10, 2);

function ig_failed_login( $user_login ) {
	$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	syslog(LOG_WARNING, "INTEGREAT CMS - LOGIN FAILED: $user_login from ".$_SERVER['REMOTE_ADDR']." via $url.");
}
add_action('wp_login_failed', 'ig_failed_login', 10, 1);
