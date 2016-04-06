<link href="<?php echo CLICKGUIDE_BASEURL; ?>/assets/css/presentation.css" rel="stylesheet" type="text/css" />

<?php
    global $wpdb;
    $optionsTableName = CLICKGUIDE_TABLE;
    $waypointID = $_GET['cgwp'];

    // get tour of current waypoint
    $tours = $wpdb->get_results( "SELECT cg_id, cg_waypoints FROM $optionsTableName WHERE cg_type = 0" );
    $tourOfCurrentWaypoint = null;
    foreach( $tours as &$tour ) {
        if( strpos($tour->cg_waypoints, $waypointID) !== false ) {
            $tourOfCurrentWaypoint = $tour->cg_id;
        }
    }
    $tourOfCurrentWaypointID = $tourOfCurrentWaypoint;
    $tourOfCurrentWaypoint = $wpdb->get_row( "SELECT * FROM $optionsTableName WHERE cg_id = $tourOfCurrentWaypoint" );
    $tourOfCurrentWaypoint = $tourOfCurrentWaypoint->cg_name;

    // get current waypoint as object
    $currentWaypoint = $wpdb->get_row( "SELECT * FROM $optionsTableName WHERE cg_id = $waypointID" );

    // current url without param 'cgwp'
    $urlWithoutCGWP = parse_url($_SERVER['REQUEST_URI']);
    parse_str( $urlWithoutCGWP['query'], $urlWithoutCGWPQuery );
    unset( $urlWithoutCGWPQuery['cgwp'] );
    $urlWithoutCGWP = $urlWithoutCGWP['path'] . ( !empty( $urlWithoutCGWPQuery ) ? '?' : '' ) . http_build_query( $urlWithoutCGWPQuery );
?>

<div id="waypointBox" class="presentationBox" data-position="<?php echo $currentWaypoint->cg_position; ?>">
    <div class="waypointBoxArrow left"></div>
    <div class="waypointBoxArrow right"></div>
    <div class="title">
        <?php echo $currentWaypoint->cg_name; ?>
        <?php if( $tourOfCurrentWaypoint ): ?>
            <small><?php echo $tourOfCurrentWaypoint; ?></small>
        <?php endif; ?>
    </div>

    <div class="presentationBoxInner">
        <div class="text">
            <?php echo $currentWaypoint->cg_desc; ?>
        </div>

        <div class="buttons">
            <?php if( $previousWaypointID = $this->getPreviousWaypopint() ): ?>
                <a href="<?php echo $this->getLinkOfWaypoint( $previousWaypointID ); ?>" class="prev button-secondary">Zur&uuml;ck</a>
            <?php endif; ?>
            <a href="<?php echo $urlWithoutCGWP; ?>" class="close button-secondary">Schlie&szlig;en</a>
            <?php if( $nextWaypointID = $this->getNextWaypopint() ): ?>
                <a href="<?php echo $this->getLinkOfWaypoint( $nextWaypointID ); ?>" class="next button-primary">Weiter</a>
            <?php endif; ?>
            <div class="clearboth"></div>
        </div>
    </div>
</div>