<?php

abstract class WPML_Translation_Roles_Records {

	const USERS_WITH_CAPABILITY = 'LIKE';
	const USERS_WITHOUT_CAPABILITY = 'NOT LIKE';

	/** @var wpdb $wpdb */
	private $wpdb;

	/** @var WPML_WP_User_Query_Factory $user_query_factory */
	private $user_query_factory;

	public function __construct( wpdb $wpdb, WPML_WP_User_Query_Factory $user_query_factory ) {
		$this->wpdb               = $wpdb;
		$this->user_query_factory = $user_query_factory;
	}

	public function get_users_with_capability() {
		return $this->get_records( self::USERS_WITH_CAPABILITY );
	}

	public function get_number_of_users_with_capability() {
		return count( $this->get_users_with_capability() );
	}

	public function search_for_users_without_capability( $search = '', $limit = -1 ) {
		return $this->get_records( self::USERS_WITHOUT_CAPABILITY, $search, $limit );
	}

	public function does_user_have_capability( $user_id ) {
		$users = $this->get_users_with_capability();
		foreach ( $users as $user ) {
			if ( (int) $user->ID === (int) $user_id ) {
				return true;
			}
		}
		return false;
	}

	private function get_records( $compare, $search = '', $limit = - 1 ) {
		$args  = array(
			'fields'     => array( 'user_login', 'display_name', 'ID', 'user_email' ),
			'meta_query' => array(
				array(
					'key'     => "{$this->wpdb->prefix}capabilities",
					'value'   => $this->get_capability(),
					'compare' => $compare
				),
			),
			'number'     => $limit,
		);
		if ( 'NOT LIKE' === $compare ) {
			$required_wp_roles = $this->get_required_wp_roles();
			if ( $required_wp_roles ) {
				$args['role__in'] = $required_wp_roles;
			}
		}
		
		if ( $search ) {
			$args['search'] = '*' . $search . '*';
			$args['search_columns'] = array( 'user_login' );
		}
		$users = $this->user_query_factory->create( $args );

		return $users->get_results();
	}

	abstract protected function get_capability();
	abstract protected function get_required_wp_roles();

}