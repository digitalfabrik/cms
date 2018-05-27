<?php
class EM_Category extends EM_Taxonomy_Term {
	
	//static options for EM_Category, but until PHP 5.3 is the WP minimum requirement we'll make them regular properties due to lack of late static binding
	public $option_ms_global = true;
	public $option_name = 'category'; //the singular name of this taxonomy which is used in option names consistent across EM taxonomies
	public $taxonomy = 'EM_TAXONOMY_CATEGORY';
		
	/**
	 * Necessary to supply the $class_name until late static binding is reliably available on all WP sites running PHP 5.3
	 * @param string $id
	 * @param string $class_name
	 * @return EM_Taxonomy
	 */
	public static function get( $id, $class_name = 'EM_Category' ){
		return parent::get($id, $class_name);
	}
	
	public function can_manage( $capability_owner = 'edit_event_categories', $capability_admin = false, $user_to_check = false ){
		return parent::can_manage($capability_owner, $capability_admin, $user_to_check);
	}
}

/**
 * Get an category in a db friendly way, by checking globals and passed variables to avoid extra class instantiations
 * @param mixed $id
 * @return EM_Category
 * @uses EM_Category::get()
 */
function em_get_category( $id ) {
	return EM_Category::get($id);
}