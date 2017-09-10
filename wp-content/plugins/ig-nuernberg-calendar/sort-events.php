<?php

function ig_ncal_menu() {
        add_menu_page( 'Calendar Import', 'Calendar Import', 'edit_events', 'ig_ncal_edit', 'ig_ncal_sort_events', 'dashicons-clock', $position = 99 );
}
add_action( 'admin_menu', 'ig_ncal_menu' );

function ig_ncal_man_import() {

}

function ig_ncal_sort_events() {
    ig_ncal_import();
}

?>
