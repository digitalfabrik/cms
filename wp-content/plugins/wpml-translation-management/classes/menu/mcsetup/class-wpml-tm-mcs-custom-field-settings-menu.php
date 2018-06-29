<?php

abstract class WPML_TM_MCS_Custom_Field_Settings_Menu {

	/** @var  WPML_Custom_Field_Setting_Factory $settings_factory */
	protected $settings_factory;

	/** @var WPML_UI_Unlock_Button $unlock_button_ui */
	private $unlock_button_ui;

	/**
	 * WPML_TM_Post_Edit_Custom_Field_Settings_Menu constructor.
	 *
	 * @param WPML_Custom_Field_Setting_Factory $settings_factory
	 */
	public function __construct( $settings_factory, WPML_UI_Unlock_Button $unlock_button_ui ) {
		$this->settings_factory = $settings_factory;
		$this->unlock_button_ui = $unlock_button_ui;
	}

	/**
	 * @return string
	 */
	public function render() {
		$custom_fields_keys = $this->get_meta_keys();

		if ( $custom_fields_keys ) {
			natcasesort( $custom_fields_keys );
		}

		$custom_field_options = array(
			WPML_IGNORE_CUSTOM_FIELD    => __( "Don't translate", 'wpml-translation-management' ),
			WPML_COPY_CUSTOM_FIELD      => __( 'Copy from original to translation', 'wpml-translation-management' ),
			WPML_COPY_ONCE_CUSTOM_FIELD => __( 'Copy once', 'wpml-translation-management' ),
			WPML_TRANSLATE_CUSTOM_FIELD => __( 'Translate', 'wpml-translation-management' )
		);

		ob_start();
		?>
		<div class="wpml-section wpml-section-<?php echo esc_attr( $this->kind_shorthand() ); ?>-translation"
		     id="ml-content-setup-sec-<?php echo esc_attr( $this->kind_shorthand() ); ?>">
			<div class="wpml-section-header">
				<h3><?php echo esc_html( $this->get_title() ) ?></h3>
				<p>
					<?php
					$toggle_system_fields = array(
						'url'  => add_query_arg( array( 'show_system_fields' => ! $this->settings_factory->show_system_fields ) ),
						'text' => $this->settings_factory->show_system_fields ? __( 'Hide system fields', 'wpml-translation-management' ) : __( 'Show system fields', 'wpml-translation-management' ),
					);
					?>
					<a href="<?php echo esc_url( $toggle_system_fields['url'] ); ?>"><?php echo esc_html( $toggle_system_fields['text'] ); ?></a>
				</p>

			</div>
			<div class="wpml-section-content wpml-section-content-wide">
				<form id="icl_<?php echo esc_attr( $this->kind_shorthand() ) ?>_translation"
				      name="icl_<?php echo esc_attr( $this->kind_shorthand() ) ?>_translation" action="">
					<?php wp_nonce_field( 'icl_' . $this->kind_shorthand() . '_translation_nonce', '_icl_nonce' ); ?>
					<?php
					if ( empty( $custom_fields_keys ) ) {
						?>
						<p class="no-data-found">
							<?php echo esc_html( $this->get_no_data_message() ); ?>
						</p>
						<?php
					} else {
						?>

						<div class="wpml-flex-table wpml-translation-setup-table wpml-margin-top-sm">

							<?php echo $this->render_heading() ?>

							<div class="wpml-flex-table-body">
								<?php
								foreach ( $custom_fields_keys as $cf_key ) {
									$setting = $this->get_setting( $cf_key );
									if ( $setting->excluded() ) {
										continue;
									}
									$status        = $setting->status();
									$html_disabled = $setting->is_read_only() && ! $setting->is_unlocked() ? 'disabled="disabled"' : '';
									?>
									<div class="wpml-flex-table-row">
										<div class="wpml-flex-table-cell name">
											<?php
											$this->unlock_button_ui->render( $setting->is_read_only(), $setting->is_unlocked(), $this->get_radio_name( $cf_key ), $this->get_unlock_name( $cf_key ) );
											echo esc_html( $cf_key );
											?>
										</div>
										<?php
										foreach (
											$custom_field_options as $ref_status => $title
										) {
											?>
											<div class="wpml-flex-table-cell text-center">
												<?php echo $this->render_radio( $cf_key, $html_disabled, $status, $ref_status ) ?>
											</div>
											<?php
										}
										?>
									</div>
									<?php
								}
								?>
							</div>
						</div>
						<p class="buttons-wrap">
							<span class="icl_ajx_response"
							      id="icl_ajx_response_<?php echo esc_attr( $this->kind_shorthand() ) ?>"></span>
							<input type="submit" class="button-primary"
							       value="<?php echo esc_attr__( 'Save', 'wpml-translation-management' ) ?>"/>
						</p>
						<?php
					}
					?>
				</form>
			</div>
			<!-- .wpml-section-content -->
		</div> <!-- .wpml-section -->
		<?php

		return ob_get_clean();
	}

	/**
	 * @return string
	 */
	protected abstract function kind_shorthand();

	/**
	 * @return string
	 */
	protected abstract function get_title();

	/**
	 * @return string[]
	 */
	protected abstract function get_meta_keys();

	/**
	 * @param string $key
	 *
	 * @return WPML_Custom_Field_Setting
	 */
	protected abstract function get_setting( $key );

	private function render_radio( $cf_key, $html_disabled, $status, $ref_status ) {
		ob_start();
		?>
		<input type="radio" name="<?php echo $this->get_radio_name( $cf_key ); ?>"
		       value="<?php echo esc_attr( $ref_status ) ?>"
		       title="<?php echo esc_attr( $ref_status ) ?>" <?php echo $html_disabled ?>
		       <?php if ( $status == $ref_status ): ?>checked<?php endif; ?> />
		<?php

		return ob_get_clean();
	}

	private function get_radio_name( $cf_key ) {
		return 'cf[' . esc_attr( base64_encode( $cf_key ) ) . ']';
	}

	private function get_unlock_name( $cf_key ) {
		return 'cf_unlocked[' . esc_attr( base64_encode( $cf_key ) ) . ']';
	}

	/**
	 * @return string header and footer of the setting table
	 */
	private function render_heading() {
		ob_start();
		?>
		<div class="wpml-flex-table-header wpml-flex-table-sticky">
			<div class="wpml-flex-table-row">
				<div class="wpml-flex-table-cell name">
					<?php echo esc_html( $this->get_column_header( 'name' ) ) ?>
				</div>
				<div class="wpml-flex-table-cell text-center">
					<?php echo esc_html__( "Don't translate", 'wpml-translation-management' ) ?>
				</div>
				<div class="wpml-flex-table-cell text-center">
					<?php echo esc_html_x( "Copy", 'Verb', 'wpml-translation-management' ) ?>
				</div>
				<div class="wpml-flex-table-cell text-center">
					<?php echo esc_html__( "Copy once", 'wpml-translation-management' ) ?>
				</div>
				<div class="wpml-flex-table-cell text-center">
					<?php echo esc_html__( "Translate", 'wpml-translation-management' ) ?>
				</div>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	public abstract function get_no_data_message();

	public abstract function get_column_header( $id );
}