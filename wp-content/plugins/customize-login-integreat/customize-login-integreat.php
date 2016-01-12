<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
/*
Plugin Name: Customize Login Integreat
Description: Helps to customize the login page
Version: 1.0
Author: Blanz
*/

function replace_logo() {
	error_log(get_stylesheet_directory_uri().$path,3,'C:\xampp\php\logs\test.txt');
	?>
	<style type="text/css">
		.login h1 a {
			background-size: 320px 100px;
			width: 320px;
			height: 100px;
			background-image: url(<?php echo get_stylesheet_directory_uri(); ?>/images/integreat/integreat_logo.png);
		}

		.message{
			border-left: 4px solid #FDDA0E !important;
		}

/*		TODO
		input.button-primary,
		button.button-primary,
		a.button-primary,
		.wp-core-ui.button-primary{
			background: #FDDA0E !important;
			border-color: #FDDA0E !important;
			text-shadow: #FDDA0E !important;
			box-shadow: #FDDA0E !important;
		}
*/
	</style>
<?php }
add_action( 'login_enqueue_scripts', 'replace_logo' );