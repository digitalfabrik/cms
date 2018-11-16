<?php

class WPML_Media_Set_Posts_Media_Flag implements IWPML_Action {
	const HAS_MEDIA_POST_FLAG = '_wpml_media_has_media';

	const BATCH_SIZE = 100;
	/**
	 * @var wpdb $wpdb
	 */
	private $wpdb;
	/**
	 * @var WPML_Notices
	 */
	private $wpml_notices;
	/**
	 * @var WPML_Media_Post_Media_Usage
	 */
	private $post_media_usage;
	/**
	 * @var WPML_Media_Post_With_Media_Files_Factory
	 */
	private $post_with_media_files_factory;

	public function __construct(
		wpdb $wpdb,
		WPML_Notices $wpml_notices,
		WPML_Media_Post_Media_Usage $post_media_usage,
		WPML_Media_Post_With_Media_Files_Factory $post_with_media_files_factory
	) {
		$this->wpdb                          = $wpdb;
		$this->wpml_notices                  = $wpml_notices;
		$this->post_media_usage              = $post_media_usage;
		$this->post_with_media_files_factory = $post_with_media_files_factory;
	}

	public function add_hooks() {
		if ( ! WPML_Media::has_setup_run() ) {
			add_action( 'wp_ajax_wpml_media_set_has_media_flag_prepare', array( $this, 'clear_flags' ) );
			add_action( 'wp_ajax_wpml_media_set_has_media_flag', array( $this, 'process_batch' ) );
		}

		add_action( 'save_post', array( $this, 'update_post_flag' ) );
	}

	public function clear_flags() {
		$this->wpdb->query( $this->wpdb->prepare(
			"DELETE FROM {$this->wpdb->postmeta} WHERE meta_key=%s",
			self::HAS_MEDIA_POST_FLAG
		) );

		wp_send_json_success( array( 'status' => __( 'Running setup...', 'wpml-media' ) ) );
	}

	public function process_batch() {
		if ( isset( $_POST['nonce'] ) && ( $_POST['nonce'] === wp_create_nonce( WPML_Media_Posts_Media_Flag_Notice::NONCE ) ) ) {
			$offset = isset( $_POST['offset'] ) ? (int) $_POST['offset'] : 0;

			$sql = $this->wpdb->prepare( "
			SELECT SQL_CALC_FOUND_ROWS ID, post_content FROM {$this->wpdb->posts} p
			JOIN {$this->wpdb->prefix}icl_translations t 
				ON t.element_id = p.ID AND t.element_type LIKE 'post_%'
			WHERE p.post_type NOT IN ( 'auto-draft', 'attachment', 'revision' )
				AND t.source_language_code IS NULL	
			ORDER BY ID ASC
			LIMIT %d, %d
		", $offset, self::BATCH_SIZE );

			$posts = $this->wpdb->get_results( $sql );

			$total_posts_found = $this->wpdb->get_var( 'SELECT FOUND_ROWS()' );

			if ( $continue = ( count( $posts ) > 0 ) ) {
				$this->flag_posts( $posts );
				$this->record_media_usage( $posts );
				$progress = round( 100 * min( $offset, $total_posts_found ) / $total_posts_found );
				$status   = sprintf( __( 'Setup in progress: %d%% complete...', 'wpml-media' ), $progress );
			} else {
				$status = __( 'Setup complete!', 'wpml-media' );
				$this->mark_complete();
			}

			wp_send_json_success( array(
				'status'   => $status,
				'offset'   => $offset + self::BATCH_SIZE,
				'continue' => $continue
			) );

		} else {
			wp_send_json_error( array( 'status' => 'Invalid nonce' ) );
		}
	}

	/**
	 * @param array $posts
	 */
	private function flag_posts( $posts ) {
		foreach ( $posts as $post ) {
			$this->update_post_flag( $post->ID );
		}
	}

	public function update_post_flag( $post_id ) {

		$post_with_media_files = $this->post_with_media_files_factory->create( $post_id );

		if ( $post_with_media_files->get_media_ids() ) {
			update_post_meta( $post_id, self::HAS_MEDIA_POST_FLAG, 1 );
		} else {
			delete_post_meta( $post_id, self::HAS_MEDIA_POST_FLAG );
		}
	}

	/**
	 * @param array $posts
	 */
	private function record_media_usage( $posts ) {
		foreach ( $posts as $post ) {
			$this->post_media_usage->update_media_usage( $post->ID );
		}
	}

	private function mark_complete() {
		WPML_Media::set_setup_run();
		$this->wpml_notices->remove_notice(
			WPML_Media_Posts_Media_Flag_Notice::NOTICE_GROUP,
			WPML_Media_Posts_Media_Flag_Notice::NOTICE_ID
		);
	}


}