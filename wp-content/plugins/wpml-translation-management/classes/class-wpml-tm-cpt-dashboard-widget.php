<?php

class WPML_TM_CPT_Dashboard_Widget extends WPML_WPDB_And_SP_User {

	function render() {
		global $wp_taxonomies;

		$icl_post_types = $this->sitepress->get_translatable_documents( true );
		$cposts         = array();
		$notice         = '';
		foreach ( $icl_post_types as $k => $v ) {
			if ( ! in_array( $k, array( 'post', 'page' ) ) ) {
				$cposts[ $k ] = $v;
			}
		}

		$cpt_sync_settings = $this->sitepress->get_setting( 'custom_posts_sync_option' );

		foreach ( $cposts as $k => $cpost ) {
			if ( ! isset( $cpt_sync_settings[ $k ] ) ) {
				$cposts_sync_not_set[] = $cpost->labels->name;
			}
		}
		if ( ! empty( $cposts_sync_not_set ) ) {
			$notice = '<p class="updated fade">';
			$notice .= sprintf( __( "You haven't set your <a %s>synchronization preferences</a> for these custom posts: %s. Default value was selected.", 'sitepress' ), 'href="admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=mcsetup"', '<i>' . join( '</i>, <i>', $cposts_sync_not_set ) . '</i>' );
			$notice .= '</p>';
		}

		$icl_post_types = $this->sitepress->get_translatable_documents( true );
		if ( $icl_post_types ) {
			$custom_posts     = array();
			$icl_post_types   = $this->sitepress->get_translatable_documents( true );

			foreach ( $icl_post_types as $k => $v ) {
				if ( ! in_array( $k, array( 'post', 'page' ) ) ) {
					$custom_posts[ $k ] = $v;
				}
			}

			$slug_settings = $this->sitepress->get_setting( 'posts_slug_translation' );
			foreach ( $custom_posts as $k => $custom_post ) {
				$_has_slug  = isset( $custom_post->rewrite['slug'] ) && $custom_post->rewrite['slug'];
				$_translate = ! empty( $slug_settings['types'][ $k ] );
				if ( $_has_slug ) {
					$string_id_prepared = $this->wpdb->prepare( "SELECT id FROM {$this->wpdb->prefix}icl_strings WHERE name = %s AND value = %s ", array(
						'Url slug: ' . trim( $custom_post->rewrite['slug'], '/' ),
						trim( $custom_post->rewrite['slug'], '/' )
					) );
					$string_id = $this->wpdb->get_var( $string_id_prepared );
					if ( $slug_settings['on'] && $_translate && ! $string_id ) {
						$message = sprintf( __( "%s slugs are set to be translated, but they are missing their translation", 'sitepress' ), $custom_post->labels->name );
						$notice .= ICL_AdminNotifier::displayInstantMessage( $message, 'error', 'below-h2', true );
					}
				}
			}
		}

		$ctaxonomies = array_diff( array_keys( (array) $wp_taxonomies ), array(
			'post_tag',
			'category',
			'nav_menu',
			'link_category',
			'post_format'
		) );

		$tax_sync_settings = $this->sitepress->get_setting( 'taxonomies_sync_option' );
		foreach ( $ctaxonomies as $ctax ) {
			if ( ! isset( $tax_sync_settings[ $ctax ] ) ) {
				$tax_sync_not_set[] = $wp_taxonomies[ $ctax ]->label;
			}
		}
		if ( ! empty( $tax_sync_not_set ) ) {
			$notice .= '<p class="updated">';
			$notice .= sprintf( __( "You haven't set your <a %s>synchronization preferences</a> for these taxonomies: %s. Default value was selected.", 'sitepress' ), 'href="admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=mcsetup"', '<i>' . join( '</i>, <i>', $tax_sync_not_set ) . '</i>' );
			$notice .= '</p>';
		}

		return $notice;
	}
}