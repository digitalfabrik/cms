<?php
/**
 * Class WPML_Media_Attachment_Image_Update
 * Allows adding a custom image to a translated attachment
 */
class WPML_Media_Attachment_Image_Update {

	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * WPML_Media_Attachment_Image_Update constructor.
	 *
	 * @param wpdb $wpdb
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	public function add_hooks() {
		if ( is_admin() ) {
			add_action( 'init', array( $this, 'update' ) );
		}
	}

	public function update(){
		if ( $this->is_valid_action() ) {

			$attachment_id = (int) $_POST['attachment_id'];
			$file_array = $_FILES['image'];

			$file = wp_handle_upload( $file_array, array( 'test_form' => false ) );
			if ( ! isset( $file['error'] ) ) {
				$post_data = $this->get_attachment_post_data( $file );
				$this->wpdb->update( $this->wpdb->posts, $post_data, array( 'ID' => $attachment_id ) );
				update_attached_file( $attachment_id, $file['file'] );
				wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $file['file'] ) );
				do_action( 'wpml_added_image_translation', $attachment_id, $file );
			} else {
				throw new WPML_Media_Exception( $file['error'] );
			}

		}
	}

	private function is_valid_action() {
		$is_attachment_id = isset( $_POST['attachment_id'] ) && is_numeric( $_POST['attachment_id'] );
		$is_post_action = isset( $_POST['action'] ) && 'wpml-upload-attachment-image' === $_POST['action'];
		return $is_attachment_id && $is_post_action && wp_verify_nonce( $_POST['_wpnonce'], $_POST['action'] );
	}

	/**
	 * @param array $file
	 *
	 * @return array
	 */
	private function get_attachment_post_data( $file ) {
		$postarr = array(
			'post_mime_type'    => $file['type'],
			'guid'              => $file['url'],
			'post_modified'     => current_time( 'mysql' ),
			'post_modified_gmt' => current_time( 'mysql', 1 )
		);

		return $postarr;
	}

}