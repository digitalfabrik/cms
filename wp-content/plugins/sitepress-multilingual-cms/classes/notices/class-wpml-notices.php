<?php

/**
 * @author OnTheGo Systems
 */
class WPML_Notices {
	private $default_group_name = 'default';

	/**
	 * @var array
	 */
	private $notices = array();
	/**
	 * @var string
	 */
	private $notices_option_key = 'wpml_notices';
	private $notices_to_remove  = array();

	/**
	 * WPML_Notices constructor.
	 *
	 * @param WPML_Notice_Render $notice_render
	 */
	public function __construct( WPML_Notice_Render $notice_render ) {
		$this->current_user_id = get_current_user_id();
		$this->notice_render   = $notice_render;
		$this->notices         = $this->get_all_notices();
	}

	/**
	 * @return int
	 */
	public function count() {
		$all_notices = $this->get_all_notices();
		$count       = 0;
		foreach ( $all_notices as $group => $group_notices ) {
			$count += count( $group_notices );
		}

		return $count;
	}

	/**
	 * @return array
	 */
	public function get_all_notices() {
		return get_option( $this->notices_option_key, array() );
	}

	public function add_notice( WPML_Notice $notice ) {
		if ( ! $this->notice_exists( $notice ) ) {
			$notices = $this->get_notices_for_group( $notice->get_group() );

			if ( ! array_key_exists( $notice->get_id(), $notices ) ) {
				$this->notices[ $notice->get_group() ][ $notice->get_id() ] = $notice;
			}
			$this->save_notices();
		}
	}

	/**
	 * @param WPML_Notice $notice
	 *
	 * @return bool
	 */
	private function notice_exists( WPML_Notice $notice ) {
		$notice_id    = $notice->get_id();
		$notice_group = $notice->get_group();

		return $this->notice_group_and_id_exists( $notice_group, $notice_id );
	}

	private function get_notices_for_group( $group ) {
		if ( array_key_exists( $group, $this->notices ) ) {
			return $this->notices[ $group ];
		}

		return array();
	}

	private function save_notices() {
		$this->remove_notices();
		update_option( $this->notices_option_key, $this->notices, false );
	}

	public function remove_notices() {
		if ( $this->notices_to_remove ) {
			foreach ( $this->notices_to_remove as $group => &$group_notices ) {
				/** @var array $group_notices */
				foreach ( $group_notices as $id ) {
					if ( array_key_exists( $group, $this->notices ) && array_key_exists( $id, $this->notices[ $group ] ) ) {
						unset( $this->notices[ $group ][ $id ] );
						$group_notices = array_diff( $this->notices_to_remove[ $group ], array( $id ) );
					}
				}
				if ( array_key_exists( $group, $this->notices_to_remove ) && ! $this->notices_to_remove[ $group ] ) {
					unset( $this->notices_to_remove[ $group ] );
				}
				if ( array_key_exists( $group, $this->notices ) && ! $this->notices[ $group ] ) {
					unset( $this->notices[ $group ] );
				}
			}
		}
	}

	function admin_enqueue_scripts() {
		if ( $this->must_display_notices() ) {
			wp_enqueue_style( 'otgs-notices', ICL_PLUGIN_URL . '/res/css/otgs-notices.css', array( 'sitepress-style' ) );
			wp_enqueue_script( 'otgs-notices', ICL_PLUGIN_URL . '/res/js/otgs-notices.js' );
		}
	}

	private function must_display_notices() {
		if ( $this->notices ) {
			/**
			 * @var string $group
			 */
			foreach ( $this->notices as $group => $notices ) {
				/**
				 * @var array       $notices
				 * @var WPML_Notice $notice
				 */
				foreach ( $notices as $notice ) {
					if ( $this->notice_render->must_display_notice( $notice ) ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	function admin_notices() {
		if ( $this->notices && $this->must_display_notices() ) {
			foreach ( $this->notices as $group => $notices ) {
				/**
				 * @var array       $notices
				 * @var WPML_Notice $notice
				 */
				foreach ( $notices as $notice ) {
					if ( $notice instanceof WPML_Notice ) {
						$this->notice_render->render( $notice );
					}
				}
			}
		}
	}

	function hide_notice() {
		$notice_id    = sanitize_text_field( $_POST['id'] );
		$notice_group = sanitize_text_field( $_POST['group'] );
		if ( ! $notice_group ) {
			$notice_group = $this->default_group_name;
		}

		if ( $this->notice_group_and_id_exists( $notice_group, $notice_id ) ) {
			$this->remove_notice( $notice_group, $notice_id );
			wp_send_json_success( true );
		}
		wp_send_json_error( __( 'Notice does not exists.', 'sitepress' ) );
	}

	private function notice_group_and_id_exists( $group, $id ) {
		return array_key_exists( $group, $this->notices ) && array_key_exists( $id, $this->notices[ $group ] );
	}

	/**
	 * @param string $group
	 * @param string $id
	 */
	public function remove_notice( $group, $id ) {
		if ( $group && $id ) {
			$this->notices_to_remove[ $group ][] = $id;
			$this->notices_to_remove[ $group ]   = array_unique( $this->notices_to_remove[ $group ] );
			$this->save_notices();
		}
	}

	public function init_hooks() {
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'wp_ajax_otgs-hide-notice', array( $this, 'hide_notice' ) );
	}
}
