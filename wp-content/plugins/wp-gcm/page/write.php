<?php

function px_display_page_msg() {
  global $wpdb;
  $px_table_name = $wpdb->prefix.'gcm_users';
  $result = $wpdb->get_var( "SELECT COUNT(*) FROM $px_table_name" );
  
  if($result != false) {
  $num_rows = $result;
  }else {
  $num_rows = 0;
  }
  $info = sprintf(__('currently %s users are registered','px_gcm'),$num_rows);
  
?>

<div class="wrap">
	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2"> 
			<!-- main content -->
			<div id="post-body-content">
					<div class="postbox">
					 <h3><?php _e('New Message','px_gcm'); ?></h3>
						<div class="inside">
							 <form method="post" action="#">
							   <p><?php _e('Enter here your message','px_gcm'); ?></p>
					           <textarea id="message" name="message" type="text" cols="20" rows="5" ></textarea>
							   <p><?php _e('*Please don\'t use HTML','px_gcm'); ?></p>
	                              <?php submit_button(__('Send','px_gcm')); ?>
	                         </form>
						</div> 
					</div>
					<p><b><?php _e('Info','px_gcm'); ?> &nbsp;&nbsp;</b> <?php echo $info ?></p>
					<p></p>
			</div>
		</div>
		<br class="clear">
	</div>
</div> 
<?php
}

if(isset($_POST['message'])) {
    $message = $_POST["message"];
    px_sendGCM($message, "message");
}
?>