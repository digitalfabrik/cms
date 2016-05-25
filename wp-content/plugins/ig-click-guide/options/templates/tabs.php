<?php
	if( isset( $_GET[ 'tab' ] ) ) {
        $active_tab = $_GET[ 'tab' ];
    } else {
        $active_tab = 'welcome_message';
    }

    if( isset( $_GET['tour'] ) ) {
    	$table_name = CLICKGUIDE_TABLE;
    	$tour_id = $_GET['tour'];

		global $wpdb;
		$tour_name = $wpdb->get_row( "SELECT * FROM $table_name WHERE cg_id = $tour_id", OBJECT );
    } 
?>
<h2 class="nav-tab-wrapper">
    <a href="?page=clickguide-optionspage&tab=welcome_message" class="nav-tab <?php echo $active_tab == 'welcome_message' ? 'nav-tab-active' : ''; ?>">Willkommensnachricht (PopUp)</a>
    <a href="?page=clickguide-optionspage&tab=tours" class="nav-tab <?php echo $active_tab == 'tours' ? 'nav-tab-active' : ''; ?>">Einzelne Touren</a>
    <?php if( $active_tab == 'deletetour' and isset( $_GET[ 'tour' ] ) ): ?>
    	<a href="?page=clickguide-optionspage&tab=deletetour&tour=<?php echo $_GET['tour'] ?>" class="nav-tab <?php echo $active_tab == 'deletetour' ? 'nav-tab-active' : ''; ?>">Tour l&ouml;schen: <i><?php echo $tour_name->cg_name; ?></i></a>
	<?php endif; ?>
	<?php if( $active_tab == 'edittour' and isset( $_GET[ 'tour' ] ) ): ?>
    	<a href="?page=clickguide-optionspage&tab=edittour&tour=<?php echo $_GET['tour'] ?>" class="nav-tab <?php echo $active_tab == 'edittour' ? 'nav-tab-active' : ''; ?>">Tour bearbeiten: <i><?php echo $tour_name->cg_name; ?></i></a>
	<?php endif; ?>
</h2>   