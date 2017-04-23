<?php

function igWritePushNotification () {
	wp_enqueue_style( 'ig-fb-send', plugin_dir_url(__FILE__) . '/css/style-send.css' );
	$header = <<<EOL
<script type="text/javascript">
jQuery( document ).ready(function() {
	window.location.href = "#deflang";
});
</script>
<form>
	<div class="notification-editor">
		<div class="tabs">
EOL;

	$footer = <<<EOL
		</div>
	</div>
	<button>Autotranslate</button><button>Senden</button>
</form>
EOL;
	$tabs = "";
	$languages = icl_get_languages();
	foreach( $languages as $key => $value ) {
	$default = ($value['active'] == "1" ? "deflang" : $value['code'] );
	$tabs .= "
			<div id='".$default."'>
				<a href='#".$default."'>".$value['translated_name']."</a>
				<div>
					<table class='tabtable'>
						<tr><td>Titel</td><td><input type='text' class='pn-title' maxlength='50'></td></tr>
						<tr><td>Nachricht</td><td><textarea class='pn-message' maxlength='140'></textarea></td></tr>
					</table>
				</div>
			</div>
";
	}
	echo $header.$tabs.$footer;
}

?>
