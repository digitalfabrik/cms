<?php

class WPML_Update_Term_Count {
	
	public function update_for_post( $post_id ) {
		$taxonomies = get_taxonomies();

		foreach ( $taxonomies as $taxonomy ) {
			$terms_for_post = wp_get_post_terms( $post_id, $taxonomy );
			
			foreach ( $terms_for_post as $term ) {
				if ( isset( $term->term_taxonomy_id ) ) {
					wp_update_term_count( $term->term_taxonomy_id, $taxonomy );
				}
			}
		}
	}
}
