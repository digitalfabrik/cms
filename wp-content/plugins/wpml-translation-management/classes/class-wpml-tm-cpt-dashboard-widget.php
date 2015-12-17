<?php

class WPML_TM_CPT_Dashboard_Widget extends WPML_SP_User {

	/** @var array $wp_taxonomies */
	private $wp_taxonomies;

	/**
	 * WPML_TM_CPT_Dashboard_Widget constructor.
	 *
	 * @param SitePress $sitepress
	 * @param array     $wp_taxonomies
	 */
	public function __construct( &$sitepress, &$wp_taxonomies ) {
		parent::__construct( $sitepress );
		$this->wp_taxonomies = &$wp_taxonomies;
	}

	function render() {
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
		if ( $icl_post_types ) {
			$custom_posts = array();
			foreach ( $icl_post_types as $k => $v ) {
				if ( ! in_array( $k, array( 'post', 'page' ) ) ) {
					$custom_posts[ $k ] = $v;
				}
			}
			$notice = apply_filters( 'wpml_tm_dashboard_cpt_notice', $notice, $custom_posts );
		}

		$ctaxonomies = array_diff( array_keys( (array) $this->wp_taxonomies ), array(
			'post_tag',
			'category',
			'nav_menu',
			'link_category',
			'post_format'
		) );

		$tax_sync_settings = $this->sitepress->get_setting( 'taxonomies_sync_option' );
		foreach ( $ctaxonomies as $ctax ) {
			if ( ! isset( $tax_sync_settings[ $ctax ] ) ) {
				$tax_sync_not_set[] = $this->wp_taxonomies[ $ctax ]->label;
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