<?php
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once WPML_TM_PATH . '/inc/wpml_zip.php';

/**
 * Class WPML_TM_Xliff_Frontend
 */
class WPML_TM_Xliff_Frontend extends WPML_TM_Xliff_Shared {

	private $success;
	/** @var  WP_Error $error */
	private $error;
	private $attachments = array();
	/** @var  SitePress $this ->sitepress */
	private $sitepress;

	/**
	 * WPML_TM_Xliff_Frontend constructor.
	 *
	 * @param WPML_Translation_Job_Factory $job_factory
	 * @param SitePress                    $sitepress
	 */
	public function __construct( &$job_factory, &$sitepress ) {
		parent::__construct( $job_factory );
		$this->sitepress = &$sitepress;
	}

	/**
	 * @return array
	 */
	function get_available_xliff_versions() {

		return array(
			"10" => "1.0",
			"11" => "1.1",
			"12" => "1.2"
		);
	}

	/**
	 * @return bool
	 */
	function init() {
		$this->attachments = array();
		$this->error       = null;
		if ( is_admin() ) {
			add_action( 'admin_head', array( $this, 'js_scripts' ) );
			add_action( 'wp_ajax_set_xliff_options', array(
				$this,
				'ajax_set_xliff_options'
			), 10, 2 );
			if ( ! $this->sitepress->get_setting( 'xliff_newlines' ) ) {
				$this->sitepress->set_setting( 'xliff_newlines', WPML_XLIFF_TM_NEWLINES_REPLACE, true );
			}
			if ( ! $this->sitepress->get_setting( 'tm_xliff_version' ) ) {
				$this->sitepress->set_setting( 'tm_xliff_version', '12', true );
			}
			if ( 1 < count( $this->sitepress->get_active_languages() ) ) {
				add_filter( 'WPML_translation_queue_actions', array(
					$this,
					'translation_queue_add_actions'
				) );
				add_action( 'WPML_xliff_select_actions', array(
					$this,
					'translation_queue_xliff_select_actions'
				), 10, 2 );
				add_action( 'WPML_translation_queue_do_actions_export_xliff', array(
					$this,
					'translation_queue_do_actions_export_xliff'
				), 10, 2 );
				add_action( 'WPML_translator_notification', array(
					$this,
					'translator_notification'
				), 10, 0 );
				add_filter( 'WPML_new_job_notification', array(
					$this,
					'new_job_notification'
				), 10, 2 );
				add_filter( 'WPML_new_job_notification_attachments', array(
					$this,
					'new_job_notification_attachments'
				) );
			}
			if ( isset( $_GET['wpml_xliff_action'] )
			     && $_GET['wpml_xliff_action'] === 'download'
			     && wp_verify_nonce( $_GET['nonce'], 'xliff-export' )
			) {
				$this->export_xliff( $_GET["xliff_version"] );
			}
			if ( isset( $_POST['xliff_upload'] ) ) {
				$this->error = $this->import_xliff( $_FILES['import'] );
				if ( is_wp_error( $this->error ) ) {
					add_action( 'admin_notices', array( $this, '_error' ) );
				}
			}
			if ( isset( $_POST['icl_tm_action'] ) && $_POST['icl_tm_action'] === 'save_notification_settings' ) {
				$this->sitepress->save_settings(
					array(
						'include_xliff_in_notification' => isset( $_POST['include_xliff'] )
						                                   && $_POST['include_xliff']
					) );
			}
		}

		return true;
	}

	function ajax_set_xliff_options() {
		check_ajax_referer( 'icl_xliff_options_form_nonce', 'security' );
		$newlines = intval( $_POST['icl_xliff_newlines'] );
		$this->sitepress->set_setting( "xliff_newlines", $newlines, true );
		$version = intval( $_POST['icl_xliff_version'] );
		$this->sitepress->set_setting( "tm_xliff_version", $version, true );

		wp_send_json_success( array(
			'message'        => 'OK',
			'newlines_saved' => $newlines,
			'version_saved'  => $version
		) );
	}

	/**
	 * @param array $mail
	 * @param int   $job_id
	 *
	 * @return array
	 */
	function new_job_notification( $mail, $job_id ) {
		if ( $this->sitepress->get_setting( 'include_xliff_in_notification' ) ) {
			$xliff_version = $this->get_user_xliff_version();
			$xliff_file    = $this->get_xliff_file( $job_id, $xliff_version );
			$temp_dir      = get_temp_dir();
			$file_name     = $temp_dir . get_bloginfo( 'name' ) . '-translation-job-' . $job_id . '.xliff';
			$fh            = fopen( $file_name, 'w' );
			if ( $fh ) {
				fwrite( $fh, $xliff_file );
				fclose( $fh );
				$mail['attachment']           = $file_name;
				$this->attachments[ $job_id ] = $file_name;
				$mail['body'] .= __( ' - A xliff file is attached.', 'wpml-translation-management' );
			}
		}

		return $mail;
	}

	/**
	 * @param $job_ids
	 *
	 * @return string
	 */
	private function _get_zip_name_from_jobs( $job_ids ) {
		$min_job = min( $job_ids );
		$max_job = max( $job_ids );
		if ( $max_job == $min_job ) {
			return get_bloginfo( 'name' ) . '-translation-job-' . $max_job . '.zip';
		} else {
			return get_bloginfo( 'name' ) . '-translation-job-' . $min_job . '-' . $max_job . '.zip';
		}
	}

	/**
	 * @param $attachments
	 *
	 * @return array
	 */
	function new_job_notification_attachments( $attachments ) {
		$found   = false;
		$archive = new wpml_zip();

		foreach ( $attachments as $index => $attachment ) {
			if ( in_array( $attachment, $this->attachments ) ) {
				$fh         = fopen( $attachment, 'r' );
				$xliff_file = fread( $fh, filesize( $attachment ) );
				fclose( $fh );
				$archive->addFile( $xliff_file, basename( $attachment ) );

				unset( $attachments[ $index ] );
				$found = true;
			}
		}

		if ( $found ) {
			// add the zip file to the attachments.
			$archive_data = $archive->getZipData();
			$temp_dir     = get_temp_dir();
			$file_name    = $temp_dir
			                . $this->_get_zip_name_from_jobs(
					array_keys( $this->attachments ) );
			$fh           = fopen( $file_name, 'w' );
			fwrite( $fh, $archive_data );
			fclose( $fh );
			$attachments[] = $file_name;
		}

		return $attachments;
	}

	/**
	 * @param int    $job_id
	 * @param string $xliff_version
	 *
	 * @return string
	 */
	private function get_xliff_file( $job_id, $xliff_version = WPML_XLIFF_DEFAULT_VERSION ) {
		$xliff = new WPML_TM_Xliff_Writer( $this->job_factory, $xliff_version );

		return $xliff->generate_job_xliff( $job_id );
	}

	/**
	 * @param $xliff_version
	 *
	 * @throws Exception
	 */
	function export_xliff( $xliff_version ) {
		global $wpdb, $current_user;
		get_currentuserinfo();

		$data    = unserialize( base64_decode( $_GET['xliff_export_data'] ) );
		$archive = new wpml_zip();
		$job_ids = array();
		foreach ( $data['job'] as $job_id => $dummy ) {
			$xliff_file = $this->get_xliff_file( $job_id, $xliff_version );

			// assign the job to this translator
			$rid        = $wpdb->get_var( $wpdb->prepare( "SELECT rid
														  FROM {$wpdb->prefix}icl_translate_job
														  WHERE job_id=%d ", $job_id ) );
			$data       = array( 'translator_id' => $current_user->ID );
			$data_where = array( 'job_id' => $job_id );
			$wpdb->update( $wpdb->prefix . 'icl_translate_job', $data, $data_where );
			$data_where = array( 'rid' => $rid );
			$wpdb->update( $wpdb->prefix . 'icl_translation_status', $data, $data_where );
			$archive->addFile( $xliff_file, get_bloginfo( 'name' ) . '-translation-job-' . $job_id . '.xliff' );
			$job_ids[] = $job_id;
		}

		$archive->sendZip( $this->_get_zip_name_from_jobs( $job_ids ) );
		exit;
	}

	/**
	 * Stops any redirects from happening when we call the
	 * translation manager to save the translations.
	 *
	 * @param $location
	 *
	 * @return null
	 */
	function _stop_redirect( $location ) {

		return null;
	}

	/**
	 * @param array $file
	 *
	 * @return bool|WP_Error
	 */
	private function import_xliff( $file ) {
		global $current_user;
		get_currentuserinfo();

		// We don't want any redirects happening when we save the translation
		add_filter( 'wp_redirect', array( $this, '_stop_redirect' ) );

		$this->success = array();
		$contents      = array();

		if ( isset( $file['tmp_name'] ) && $file['tmp_name'] ) {
			$fh   = fopen( $file['tmp_name'], 'r' );
			$data = fread( $fh, 4 );
			fclose( $fh );
			if ( $data[0] == 'P' && $data[1] == 'K' && $data[2] == chr( 03 ) && $data[3] == chr( 04 ) ) {
				if ( class_exists( 'ZipArchive' ) ) {
					$z     = new ZipArchive();
					$zopen = $z->open( $file['tmp_name'],
						4 );
					if ( true !== $zopen ) {
						return new WP_Error( 'incompatible_archive', __( 'Incompatible Archive.' ) );
					}
					for ( $i = 0; $i < $z->numFiles; $i ++ ) {
						if ( ! $info = $z->statIndex( $i ) ) {
							return new WP_Error( 'stat_failed', __( 'Could not retrieve file from archive.' ) );
						}
						$content = $z->getFromIndex( $i );
						if ( false === $content ) {
							return new WP_Error( 'extract_failed', __( 'Could not extract file from archive.' ), $info['name'] );
						}
						$contents[ $info['name'] ] = $content;
					}
				} else {
					require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );
					$archive = new PclZip( $file['tmp_name'] );
					// Is the archive valid?
					if ( false == ( $archive_files = $archive->extract( PCLZIP_OPT_EXTRACT_AS_STRING ) ) ) {
						return new WP_Error( 'incompatible_archive', __( 'Incompatible Archive.' ), $archive->errorInfo( true ) );
					}
					if ( 0 == count( $archive_files ) ) {
						return new WP_Error( 'empty_archive', __( 'Empty archive.' ) );
					}
					foreach ( $archive_files as $content ) {
						$contents[ $content['filename'] ] = $content['content'];
					}
				}
			} else {
				$fh   = fopen( $file['tmp_name'], 'r' );
				$data = fread( $fh, $file['size'] );
				fclose( $fh );
				$contents[ $file['name'] ] = $data;
			}

			foreach ( $contents as $name => $content ) {
				$new_error_handler = create_function( '$errno, $errstr, $errfile, $errline', 'throw new ErrorException( $errstr, $errno, 1, $errfile, $errline );' );
				set_error_handler( $new_error_handler );
				try {
					$xml = simplexml_load_string( $content );
				} catch ( Exception $e ) {
					$xml = false;
				}
				restore_error_handler();
				if ( ! $xml || ! isset( $xml->file ) ) {
					return new WP_Error( 'not_xml_file', sprintf( __( '"%s" is not a valid XLIFF file.', 'wpml-translation-management' ), $name ) );
				}
				$job = $this->get_job_for_xliff( $xml );
				if ( is_wp_error( $job ) ) {
					return $job;
				}
				if ( $current_user->ID != $job->translator_id ) {
					return new WP_Error( 'not_your_job', sprintf( __( 'The translation job (%s) doesn\'t belong to you.', 'wpml-translation-management' ), $job->job_id ) );
				}
				wpml_tm_save_data( $this->generate_job_data( $xml, $job ) );
				$this->success[] = sprintf( __( 'Translation of job %s has been uploaded and completed.', 'wpml-translation-management' ), $job->job_id );
			}
			if ( sizeof( $this->success ) > 0 ) {
				add_action( 'admin_notices', array( $this, '_success' ) );

				return true;
			}
		}

		return false;
	}

	/**
	 * @param $actions
	 * @param $action_name
	 */
	function translation_queue_xliff_select_actions( $actions, $action_name ) {
		if ( sizeof( $actions ) > 0 ):
			$user_version = $this->get_user_xliff_version();
			?>
			<div class="alignleft actions">
				<select name="<?php echo $action_name; ?>">
					<option
						value="-1" <?php echo $user_version == false ? "selected='selected'" : ""; ?>><?php _e( 'Bulk Actions' ); ?></option>
					<?php foreach ( $actions as $key => $action ): ?>
						<option
							value="<?php echo $key; ?>" <?php echo $user_version == $key ? "selected='selected'" : ""; ?>><?php echo $action; ?></option>
					<?php endforeach; ?>
				</select>
				<input type="submit" value="<?php esc_attr_e( 'Apply' ); ?>"
				       name="do<?php echo $action_name; ?>"
				       class="button-secondary action"/>
			</div>
			<?php
		endif;
	}

	/**
	 * Adds the various possible XLIFF versions to translations queue page's export actions on display.
	 *
	 * @param array $actions
	 *
	 * @return array
	 */
	function translation_queue_add_actions( $actions ) {
		foreach ( $this->get_available_xliff_versions() as $key => $value ) {
			$actions[ $key ] = __( sprintf( 'Export XLIFF %s', $value ), 'wpml-translation-management' );
		}

		return $actions;
	}

	/**
	 * @param array  $data
	 * @param string $xliff_version
	 */
	function translation_queue_do_actions_export_xliff( $data, $xliff_version ) {
		?>
		<script type="text/javascript">
			<?php
			if (isset( $data['job'] )) { ?>

			var xliff_export_data = "<?php echo base64_encode( serialize( $data ) ); ?>";
			var xliff_export_nonce = "<?php echo wp_create_nonce( 'xliff-export' ); ?>";
			var xliff_version = "<?php echo $xliff_version; ?>";
			addLoadEvent(function () {
				window.location = "<?php echo htmlentities( $_SERVER['REQUEST_URI'] ) ?>&wpml_xliff_action=download&xliff_export_data=" + xliff_export_data + "&nonce=" + xliff_export_nonce + "&xliff_version=" + xliff_version;
			});
			<?php
			} else {
			?>
			var error_message = "<?php echo __( 'No translation jobs were selected for export.', 'wpml-translation-management' ); ?>";
			alert(error_message);
			<?php
			}
			?>
		</script>
		<?php
	}

	function _error() {
		if ( is_wp_error( $this->error ) ) {
			?>
			<div class="message error">
				<p><?php echo $this->error->get_error_message() ?></p></div>
			<?php
		}
	}

	function _success() {
		?>
		<div class="message updated"><p>
			<ul>
				<?php
				foreach ( $this->success as $message ) {
					echo '<li>' . $message . '</li>';
				}
				?>
			</ul>
			</p></div>
		<?php
	}

	function js_scripts() {
		if ( ! defined( 'DOING_AJAX' ) ) {
			global $pagenow;

			if ( $pagenow === 'admin.php' && isset( $_GET['page'] ) && $_GET['page'] === WPML_TM_FOLDER . '/menu/translations-queue.php' ) {
				$form_data = '<br /><form enctype="multipart/form-data" method="post" id="translation-xliff-upload" action="">';
				$form_data .= '<table class="widefat"><thead><tr><th>' . __( 'Import XLIFF', 'wpml-translation-management' ) . '</th></tr></thead><tbody><tr><td>';
				$form_data .= '<label for="upload-xliff-file">' . __( 'Select the xliff file or zip file to upload from your computer:&nbsp;', 'wpml-translation-management' ) . '</label>';
				$form_data .= '<input type="file" id="upload-xliff-file" name="import" /><input type="submit" value="' . __( 'Upload', 'wpml-translation-management' ) . '" name="xliff_upload" id="xliff_upload" class="button-secondary action" />';
				$form_data .= '</td></tr></tbody></table>';
				$form_data .= '</form>';
				?>
				<script type="text/javascript">
					addLoadEvent(function () {
						jQuery('form[name$="translation-jobs-action"]').append('<?php echo $form_data?>');
					});
				</script>
				<?php
			}
			?>
			<script type="text/javascript">
				var wpml_xliff_ajax_nonce = '<?php echo wp_create_nonce( "icl_xliff_options_form_nonce" ); ?>';
			</script>
			<?php
		}
	}

	function translator_notification() {
		$checked = $this->sitepress->get_setting( 'include_xliff_in_notification' ) ? 'checked="checked"' : '';
		?>
		<input type="checkbox" name="include_xliff" id="icl_include_xliff"
		       value="1" <?php echo $checked; ?>/>
		<label
			for="icl_include_xliff"><?php _e( 'Include XLIFF files in notification emails', 'wpml-translation-management' ); ?></label>
		<?php
	}

	/**
	 * @return bool|string
	 */
	private function get_user_xliff_version() {

		return $this->sitepress->get_setting( "tm_xliff_version", false );
	}
}
