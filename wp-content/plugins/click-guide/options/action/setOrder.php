<?php 
	if( $_POST and !isset( $_POST['createNewTour'] ) ) {
		$cgids = $_POST;

		$table_name = CLICKGUIDE_TABLE;
		global $wpdb;

		foreach($cgids as $key => $order) {
			$cgid = str_replace( 'cgid', '', $key );
			$wpdb->update( $table_name, array('cg_order' => $order), array( 'cg_id' => $cgid ) );
		}

		// Redirect
		$orderRedirect = '?page=clickguide-optionspage&tab=tours&order=y';

		?>
		
		<script type="text/javascript">
			<!--
			window.location = "<?php echo $orderRedirect; ?>";
			//–>
		</script>

		<?php
	}
?>