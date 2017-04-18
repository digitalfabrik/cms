<?php

//$info = "Nothing new";
function px_sendFCM($message) {
  $result;
  $options = get_option('gcm_setting');
  $apiKey = $options['api-key'];
  $url = 'https://fcm.googleapis.com/fcm/send';
  $fields = array(
            'to' => '/topics/news',
            'notification' => array('body' => $message),);
  $headers = array(
            'Authorization: key=' . $apiKey,
            'Content-Type: application/json');

  $response = wp_remote_post($url, array(
	"method" => "POST",
	"headers" => $headers,
	"body" => $fields
  )
  );
  /*$ch = curl_init();
  /*curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
  $result = curl_exec($ch);

  $answer = json_decode($result);
  $suc = $answer->{'success'};
  $fail = $answer->{'failure'};
  $options = get_option('gcm_setting');
  curl_close($ch);
  print_r($inf);
  $info = sprintf(__('%s','px_gcm'),"Push Notification sent successfully!");*/
  return $headers;
}

function px_display_page_msg() {
$response = px_sendFCM("hello");
$response = $response[0];
//$response = print_r($response);
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
					<p><b><?php _e('Status','px_gcm'); ?> &nbsp;&nbsp;</b> <?php echo $response ?></p>
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
    px_sendFCM($message);
}
?>
