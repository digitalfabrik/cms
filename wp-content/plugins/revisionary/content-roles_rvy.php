<?php

// base class to be extended and implemented by external plugin
class RevisionaryContentRoles {
	function filter_object_terms( $terms, $taxonomy ) { return array(); }
	function get_metagroup_edit_link( $metagroup_name ) { return ''; }
	function get_metagroup_members( $metagroup_name ) { return array(); }
	function users_who_can( $reqd_caps, $object_id = 0, $args = array() ) { return array(); }
	function ensure_init() { }
	function add_listed_ids( $src_name, $object_type, $id ) { }
	function set_hascap_flags( $flags ) { }
	function is_direct_file_access() { return false; }
}

?>