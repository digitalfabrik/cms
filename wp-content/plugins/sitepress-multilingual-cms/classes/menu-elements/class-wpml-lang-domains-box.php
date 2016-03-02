<?php

/**
 * Class WPML_Lang_Domains_Box
 *
 * Displays the table holding the language domains on languages.php
 */
class WPML_Lang_Domains_Box extends WPML_SP_User {

	public function render() {
		$active_languages = $this->sitepress->get_active_languages();
		$default_language = $this->sitepress->get_default_language();
		$language_domains = $this->sitepress->get_setting( 'language_domains',
			array() );
		$default_home     = $this->sitepress->convert_url( $this->sitepress->get_wp_api()->get_home_url(),
			$default_language );
		$home_schema      = parse_url( $default_home, PHP_URL_SCHEME ) . '://';
		$home_path        = parse_url( $default_home, PHP_URL_PATH );
		ob_start();
		?>
		<table class="language_domains">
			<?php foreach ( $active_languages as $code => $lang ) : ?>
				<?php $textbox_id = 'language_domain_' . $code; ?>
				<tr>
					<td>
						<label
							for="<?php echo $textbox_id ?>">
							<?php echo $lang['display_name'] ?>
						</label>
					</td>
					<?php if ( $code === $default_language ): ?>
						<td id="icl_ln_home">
							<code>
								<?php echo $default_home ?>
							</code>
						</td>
						<td>&nbsp;</td>
					<?php else: ?>
						<td style="white-space: nowrap">
							<code><?php echo $home_schema ?></code>
							<input
								type="text"
								id="<?php echo $textbox_id ?>"
								name="language_domains[<?php echo $code ?>]"
								value="<?php echo isset( $language_domains[ $code ] ) ? preg_replace( array(
									'#^' . $home_schema . '#',
									'#' . $home_path . '$#'
								), '',
									$language_domains[ $code ] ) : $this->render_suggested_url( $default_home,
									$code ); ?>"
								data-language="<?php echo $code; ?>"
								size="30"/>
							<?php if ( isset( $home_path[1] ) ): ?>
								<code><?php echo $home_path ?></code>
							<?php endif; ?>
						</td>
						<td>
							<p style="white-space: nowrap"><input
									class="validate_language_domain"
									type="checkbox"
									id="validate_language_domains_<?php echo $code ?>"
									name="validate_language_domains[]"
									value="<?php echo $code ?>"
									checked="checked"/>
								<label
									for="validate_language_domains_<?php echo $code ?>">
									<?php _e( 'Validate on save',
										'sitepress' ) ?>
								</label>
							</p>
							<p style="white-space: nowrap">
							<span
								class="spinner spinner-<?php echo $code ?>"></span>
								<span
									id="ajx_ld_<?php echo $code ?>"></span>
							</p>
						</td>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
		</table>
		<?php

		return ob_get_clean();
	}

	private function render_suggested_url( $home, $lang ) {
		$url_parts        = parse_url( $home );
		$exp              = explode( '.', $url_parts['host'] );
		$suggested_url    = $lang . '.';
		array_shift( $exp );
		$suggested_url .= count( $exp ) < 2 ? $url_parts['host'] : join( '.',
			$exp );

		return $suggested_url;
	}
}