<?php

class WPML_Set_Attachments_Language implements IWPML_Action {

	const AJAX_ACTION = 'wpml_media_dismiss_starting_help';

	public function add_hooks() {
		add_action( 'admin_notices', array( $this, 'set_languages_notice' ) );
		add_action( 'wp_ajax_wpml_media_dismiss_starting_help', array( $this, 'dismiss_wpml_media_starting_help' ) );
	}

	public function set_languages_notice() {
		$settings_page = defined( 'WPML_TM_FOLDER' )
			? admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/settings#' . WPML_Media_Settings::ID )
			: admin_url( 'admin.php?page=' . WPML_PLUGIN_FOLDER . '/menu/translation-options.php#' . WPML_Media_Settings::ID );
		?>
		<div class="error message otgs-is-dismissible">
			<p>
				<?php esc_html_e( 'WPML needs to set languages to existing media in your site.', 'sitepress' ) ?>
				<a href="<?php echo $settings_page ?>"
				   class="button-secondary"><?php esc_html_e( 'Set media languages', 'sitepress' ) ?></a>

				<span id="wpml_media_dismiss_1" class="notice-dismiss"
				      onclick="jQuery.ajax({url:ajaxurl,method:'POST',data:{action:'<?php echo self::AJAX_ACTION ?>', nonce:'<?php echo wp_create_nonce( self::AJAX_ACTION ); ?>'},success:function(){jQuery('#wpml_media_dismiss_1').closest('.message').fadeOut()}}); return false;">
					<span class="screen-reader-text"><?php esc_html_e( "Dismiss", 'sitepress' ) ?></span>
				</span>
			</p>
		</div>
		<?php
	}

	public function dismiss_wpml_media_starting_help() {
		if ( isset( $_POST['nonce'] ) && $_POST['nonce'] === wp_create_nonce( self::AJAX_ACTION ) ) {
			$wpml_media_settings                  = get_option( '_wpml_media', array() );
			$wpml_media_settings['starting_help'] = 1;
			update_option( '_wpml_media', $wpml_media_settings );
		}
		exit;
	}

}
