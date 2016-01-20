<?php 

	if(isset($_POST['submit'])){

		update_option('welcome_popup_message',$_POST['welcome_popup_message']);
		update_option('clickguide_naming',$_POST['clickguide_naming']);

		// redirect to settings page in network
		?>
		<script type="text/javascript">
			<!--
			window.location = "<?php echo $_POST['_wp_http_referer']; ?>";
			//–>
		</script>
		<?php

	}

?>