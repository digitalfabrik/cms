<?php

    if( $tour->cg_waypoints != '' ) {
        $waypointIds = explode(',',$tour->cg_waypoints);

        $wayointLowestOrder = null;
        foreach ($waypointIds as &$value) {
            $waypoint = $wpdb->get_row( "SELECT * FROM $cg_table_name WHERE cg_id = $value" );

            if( !isset($wayointLowestOrder) ) {
                $wayointLowestOrder = $waypoint;
            }

            if( $waypoint->cg_order < $wayointLowestOrder->cg_order ) {
                $wayointLowestOrder = $waypoint;
            }
        }

        if( $wayointLowestOrder != null ) {
            $out .= '<li>';
            $out .= '<a href="';
            $out .= $this->getLinkOfWaypoint( $wayointLowestOrder->cg_id );
            $out .= '">' . $tour->cg_name . '</a>';
            $out .= '</li>';
        }
    }

?>