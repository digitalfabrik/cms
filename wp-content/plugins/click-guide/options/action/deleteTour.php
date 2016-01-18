<?php 
	if( $_POST ) { // form submitted?
 
		// delete tour
		$table_name = CLICKGUIDE_TABLE;

		global $wpdb;
		$wpdb->delete( $table_name, array( 'cg_id' => $_GET['tour'] ) );

		// Redirect
		$delRedirect = '?page=clickguide-optionspage&tab=tours&deleted=' . $tour_name->cg_name;

		?>
		
		<script type="text/javascript">
			<!--
			window.location = "<?php echo $delRedirect; ?>";
			//–>
		</script>
		
		<?php
	}
?>