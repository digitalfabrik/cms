<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css">
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
<link href="<?php echo CLICKGUIDE_BASEURL; ?>/assets/css/options.css" rel="stylesheet" type="text/css" />
<script src="<?php echo CLICKGUIDE_BASEURL; ?>/assets/js/options.js" type="text/javascript"></script>

<?php 
	require_once __DIR__ . '/tabs.php'; 
    
    if( $active_tab == 'welcome_message' ) {
    	
    	require_once __DIR__ . '/formWelcomeMessage.php';
    
    } elseif( $active_tab == 'tours' ) {                    
    
        require_once __DIR__ . '/tours.php';
    
    } elseif( $active_tab == 'deletetour' ) {

    	require_once __DIR__ . '/formDeleteTour.php';

    } else {
    
        require_once __DIR__ . '/formEditTour.php';
    
    }
?>