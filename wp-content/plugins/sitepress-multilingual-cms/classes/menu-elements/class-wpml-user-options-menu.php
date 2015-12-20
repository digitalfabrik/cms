<?php

/**
 * Class WPML_User_Options_Menu
 *
 * Renders the WPML UI elements on the WordPress user profile edit screen
 */
class WPML_User_Options_Menu extends WPML_SP_User {

	/** @var WP_User $this ->current_user */
	private $current_user;

	/**
	 * WPML_User_Options_Menu constructor.
	 *
	 * @param SitePress $sitepress
	 * @param WP_User   $current_user
	 */
	public function __construct( &$sitepress, &$current_user ) {
		parent::__construct( $sitepress );
		$this->current_user = &$current_user;
	}

	/**
	 *
	 * @return string the html for the user profile edit screen element WPML
	 * adds to it
	 */
	public function render() {
		$wp_api                 = $this->sitepress->get_wp_api();
		$user_language          = $wp_api->get_user_meta( $this->current_user->ID, 'icl_admin_language', true );
		$user_admin_def_lang    = $this->sitepress->get_setting( 'admin_default_language' );
		$all_languages          = $this->sitepress->get_languages( $user_language ? $user_language : $user_admin_def_lang );
		$user_admin_def_lang    = $user_admin_def_lang === '_default_' ? $this->sitepress->get_default_language() : $user_admin_def_lang;
		$lang_details           = $this->sitepress->get_language_details( $user_admin_def_lang );
		$admin_default_language = $lang_details['display_name'];
		ob_start();
		?>
		<a name="wpml"></a>
		<h3><?php _e( 'WPML language settings', 'sitepress' ); ?></h3>
		<table class="form-table">
			<tbody>
			<tr>
				<th><?php _e( 'Select your language:', 'sitepress' ) ?></th>
				<td>
					<select name="icl_user_admin_language">
						<option
							value=""<?php if ( $user_language === $user_admin_def_lang )
							echo ' selected="selected"' ?>><?php printf( __( 'Default admin language (currently %s)', 'sitepress' ), $admin_default_language ); ?>
							&nbsp;</option>
						<?php
						$admin_language = $this->sitepress->get_admin_language();
						foreach ( $all_languages as $lang_code => $al ) {
							if ( $al['active'] ) {
								?>
								<option
									value="<?php echo $lang_code ?>"<?php if ( $user_language === $lang_code )
									echo ' selected="selected"' ?>><?php echo $al['display_name'];
									if ( $admin_language !== $lang_code ) {
										echo ' (' . $al['native_name'] . ')';
									} ?>&nbsp;</option>
								<?php
							}
						}
						foreach ( $all_languages as $lang_code => $al ) {
							if ( ! $al['active'] ) {
								?>
								<option
									value="<?php echo $lang_code ?>"<?php if ( $user_language === $lang_code )
									echo ' selected="selected"' ?>><?php echo $al['display_name'];
									if ( $admin_language !== $lang_code ) {
										echo ' (' . $al['native_name'] . ')';
									} ?>&nbsp;</option>
								<?php
							}
						}
						?>
					</select>
					<span
						class="description"><?php _e( 'this will be your admin language and will also be used for translating comments.', 'sitepress' ); ?></span>
					<br/>
					<label><input type="checkbox"
					              name="icl_admin_language_for_edit" value="1"
					              <?php if ( $wp_api->get_user_meta( $this->current_user->ID, 'icl_admin_language_for_edit', true ) ): ?>checked="checked"<?php endif; ?> />&nbsp;<?php _e( 'Set admin language as editing language.', 'sitepress' ); ?>
					</label>
				</td>
			</tr>
			<?php
			if ( $wp_api->current_user_can( 'manage_options' ) ): ?>
				<tr>
					<th><?php _e( 'Hidden languages:', 'sitepress' ) ?></th>
					<?php $hidden_language_setting = $this->sitepress->get_setting( 'hidden_languages' ); ?>
					<td>
						<p>
							<?php if ( ! empty( $hidden_language_setting ) ): ?>
								<?php
								if ( 1 == count( $hidden_language_setting ) ) {
									printf( __( '%s is currently hidden to visitors.', 'sitepress' ), $all_languages[ end( $hidden_language_setting ) ]['display_name'] );
								} else {
									$hidden_languages_array = array();
									foreach ( $hidden_language_setting as $l ) {
										$hidden_languages_array[] = $all_languages[ $l ]['display_name'];
									}
									$hidden_languages = join( ', ', $hidden_languages_array );
									printf( __( '%s are currently hidden to visitors.', 'sitepress' ), $hidden_languages );
								}
								?>
							<?php else: ?>
								<?php _e( 'All languages are currently displayed. Choose what to do when site languages are hidden.', 'sitepress' ); ?>
							<?php endif; ?>
						</p>
						<p>
							<label><input name="icl_show_hidden_languages"
							              type="checkbox" value="1" <?php
							              if ( $wp_api->get_user_meta( $this->current_user->ID, 'icl_show_hidden_languages', true ) ): ?>checked="checked"<?php endif ?> />&nbsp;<?php
								_e( 'Display hidden languages', 'sitepress' ) ?>
							</label>
						</p>
					</td>
				</tr>
			<?php endif; ?>
			</tbody>
		</table>
		<?php

		return ob_get_clean();
	}
}