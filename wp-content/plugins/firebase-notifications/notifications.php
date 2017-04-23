<?php

function WriteFirebaseNotification () {
	wp_enqueue_style( 'ig-fb-style-send', plugin_dir_url(__FILE__) . '/css/send.css' );
	wp_enqueue_script( 'ig-fb-js-send', plugin_dir_url(__FILE__) . '/js/send.js' );
	$header = "
<form>
	<div class='notification-editor'>
		<div class='tabs'>
";

	$footer = "
		</div>
	</div>
	<p>".__('Please fill in the title and message for the red underlined language. Optionally, translations can be filled in as well.', 'firebase-notifications')."</p>
	<p>".__('Languages without manual translation receive:', 'firebase-notifications')."</p>
	<fieldset>
		<ul>
			<li><input type='radio' id='at' name='pn-translate' value='at' checked='checked'><label for='at'> ".__('Automatic translation', 'firebase-notifications')."</label></li>
			<li><input type='radio' id='no' name='pn-translate' value='no'><label for='no'></label> ".__('No message', 'firebase-notifications')."</li>
			<li><input type='radio' id='or' name='pn-translate' value='or'><label for='or'> ".__('Message in original language (marked red)', 'firebase-notifications')."</label></li>
		</ul>
	</fieldset>
	<button>".__('Send Notification', 'firebase-notifications')."</button>
</form>
";

	$tabs = "";
	$languages = icl_get_languages();
	foreach( $languages as $key => $value ) {
		$default = ($value['active'] == "1" ? "deflang" : $value['code'] );
		$tabs .= "
			<div id='".$default."'>
				<a href='#".$default."'>".$value['translated_name']."</a>
				<div>
					<table class='tabtable'>
						<tr><td>".__('Title', 'firebase-notifications')."</td><td><input type='text' class='pn-title' maxlength='50'></td></tr>
						<tr><td>".__('Message', 'firebase-notifications')."</td><td><textarea class='pn-message' maxlength='140'></textarea></td></tr>
					</table>
				</div>
			</div>
";
	}
	echo $header.$tabs.$footer;
}

?>
