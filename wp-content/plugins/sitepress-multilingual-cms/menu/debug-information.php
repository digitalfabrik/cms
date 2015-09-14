<?php

include_once ICL_PLUGIN_PATH . '/inc/functions-debug-information.php';
$debug_information = new ICL_Debug_Information();
$debug_data = $debug_information->get_debug_info();

/* DEBUG ACTION */
/**
 * @param $term_object
 *
 * @return callable
 */
?>
<div class="wrap">
<div id="icon-wpml" class="icon32"><br/></div>
<h2><?php echo __( 'Debug information', 'sitepress' ) ?></h2>
<?php

$message = filter_input(INPUT_GET, 'message', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE);

if ( $message ){ ?>
	<div class="updated message fade"><p>
			<?php echo esc_html( $message ); ?>
		</p></div>
<?php } ?>
<?php
echo '<a href="#wpml-debug-information">' . __( 'Debug information', 'sitepress' ) . '</a>';
?>
<div id="poststuff">
	<div id="wpml-debug-info" class="postbox">
		<h3 class="handle"><span><?php _e( 'Debug information', 'sitepress' ) ?></span></h3>
        <div class="inside">
        <p><?php _e( 'This information allows our support team to see the versions of WordPress, plugins and theme on your site. Provide this information if requested in our support forum. No passwords or other confidential information is included.', 'sitepress' ) ?></p><br/>
		<?php
        echo '<textarea style="font-size:10px;width:100%;height:150px;" rows="16" readonly="readonly">';
        echo esc_html( $debug_information->do_json_encode( $debug_data ) );
        echo '</textarea>';
        ?>
        </div>
	</div>
</div>

<?php do_action( 'icl_menu_footer' ); ?>
</div>
