<?php

if ( ! class_exists( 'ICL_Language_Switcher' ) ) {
	include ICL_PLUGIN_PATH . '/inc/widgets/icl-language-switcher.class.php';
}

class SitePressLanguageSwitcher {

	public  $settings; 
	public  $widget_preview;
	public  $widget_css_defaults;
	public  $footer_css_defaults;
	public  $color_schemes;

	private $current_language_color_selector_item;

	function __construct() {
		$this->widget_preview     = false;
		$this->color_schemes = $this->get_default_color_schemes();
		$this->widget_css_defaults = $this->color_schemes[ 'White' ];
		$this->footer_css_defaults = $this->color_schemes[ 'White' ];

		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	function init() {
		global $sitepress_settings;
		$this->settings = $sitepress_settings;
		if ( ! empty( $this->settings[ 'icl_lang_sel_footer' ] ) ) {
			add_action( 'wp_head', array( $this, 'language_selector_footer_style' ), 19 );
			add_action( 'wp_footer', array( $this, 'language_selector_footer' ), 19 );
		}
		if ( is_admin() ) {
			add_action( 'icl_language_switcher_options', array( $this, 'admin' ), 1 );
		} else if ( ! empty( $this->settings[ 'icl_post_availability' ] ) ) {
			if ( function_exists( 'icl_register_string' ) ) {
				icl_register_string( 'WPML', 'Text for alternative languages for posts', $this->settings[ 'icl_post_availability_text' ] );
			}
			add_filter( 'the_content', array( $this, 'post_availability' ), 100 );
		}

		// the language selector widget
		add_action( 'widgets_init', array( $this, 'language_selector_widget_init' ) );

		if ( is_admin() && isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == ICL_PLUGIN_FOLDER . '/menu/languages.php' ) {
			add_action( 'admin_head', 'icl_lang_sel_nav_css', 1, 1, true );
			add_action( 'admin_head', array( $this, 'custom_language_switcher_style' ) );
		}
		if ( ! is_admin() ) {
			add_action( 'wp_head', array( $this, 'custom_language_switcher_style' ) );
		}

		add_filter( 'wp_nav_menu_items', array( $this, 'wp_nav_menu_items_filter' ), 10, 2 );
		add_filter( 'wp_page_menu', array( $this, 'wp_page_menu_filter' ), 10, 2 );
	}

	function language_selector_widget_init() {
		register_widget( 'ICL_Language_Switcher' );
		add_action( 'template_redirect', 'icl_lang_sel_nav_ob_start', 0 );
		add_action( 'wp_head', 'icl_lang_sel_nav_ob_end' );
	}

	private function must_filter_menus() {
		global $sitepress_settings;

		return ! empty( $sitepress_settings[ 'display_ls_in_menu' ] ) && ( ! function_exists( 'wpml_home_url_ls_hide_check' ) || ! wpml_home_url_ls_hide_check() );
	}

	function set_widget() {
		global $sitepress, $sitepress_settings;
		if ( isset( $_POST[ 'icl_widget_update' ] ) ) {
			$sitepress_settings[ 'icl_widget_title_show' ] = ( isset( $_POST[ 'icl_widget_title_show' ] ) ) ? 1 : 0;
			$sitepress->save_settings( $sitepress_settings );
		}
		echo '<input type="hidden" name="icl_widget_update" value="1">';
		echo '<label><input type="checkbox" name="icl_widget_title_show" value="1"';
		if ( $sitepress_settings[ 'icl_widget_title_show' ] ) {
			echo ' checked="checked"';
		}
		echo '>&nbsp;' . __( 'Display \'Languages\' as the widget\'s title', 'sitepress' ) . '</label><br>';
	}

	function post_availability( $content ) {
		$out = '';
		if ( is_singular() ) {
			$languages = icl_get_languages( 'skip_missing=true' );
			if ( 1 < count( $languages ) ) {
				//$out .= $this->settings['post_available_before'] ? $this->settings['post_available_before'] : '';
				$langs = array();
				foreach ( $languages as $l ) {
					if ( ! $l[ 'active' ] ) {
						$langs[ ] = '<a href="' . apply_filters( 'WPML_filter_link', $l[ 'url' ], $l ) . '">' . $l[ 'translated_name' ] . '</a>';
					}
				}
				$out .= join( ', ', $langs );
				//$out .= $this->settings['post_available_after'] ? $this->settings['post_available_after'] : '';
				$str = function_exists( 'icl_t' ) ? icl_t( 'WPML', 'Text for alternative languages for posts', $this->settings[ 'icl_post_availability_text' ] ) : $this->settings[ 'icl_post_availability_text' ];
				$out = '<p class="icl_post_in_other_langs">' . sprintf( $str, $out ) . '</p>';
			}
		}

		$out = apply_filters( 'icl_post_alternative_languages', $out );

		if ( $this->settings[ 'icl_post_availability_position' ] == 'above' ) {
			$content = $out . $content;
		} else {
			$content = $content . $out;
		}

		return $content;
	}

	function language_selector_footer_style() {

		$add = false;
		foreach ( $this->footer_css_defaults as $key => $d ) {
			if ( isset( $this->settings[ 'icl_lang_sel_footer_config' ][ $key ] ) && $this->settings[ 'icl_lang_sel_footer_config' ][ $key ] != $d ) {
				$add = true;
				break;
			}
		}
		if ( $add ) {
			echo "\n<style type=\"text/css\">";
			foreach ( $this->settings[ 'icl_lang_sel_footer_config' ] as $k => $v ) {
				switch ( $k ) {
					case 'font-current-normal':
						//if($v != $this->color_schemes[$k])
						echo '#lang_sel_footer a, #lang_sel_footer a.lang_sel_sel, #lang_sel_footer a.lang_sel_sel:visited{color:' . $v . ';}';
						break;
					case 'font-current-hover':
						//if($v != $this->color_schemes[$k])
						echo '#lang_sel_footer a:hover, #lang_sel_footer a.lang_sel_sel:hover{color:' . $v . ';}';
						break;
					case 'background-current-normal':
						//if($v != $this->color_schemes[$k])
						echo '#lang_sel_footer a.lang_sel_sel, #lang_sel_footer a.lang_sel_sel:visited{background-color:' . $v . ';}';
						break;
					case 'background-current-hover':
						//if($v != $this->color_schemes[$k])
						echo '#lang_sel_footer a.lang_sel_sel:hover{background-color:' . $v . ';}';
						break;
					case 'font-other-normal':
						//if($v != $this->color_schemes[$k])
						echo '#lang_sel_footer ul a, #lang_sel_footer ul a:visited{color:' . $v . ';}';
						break;
					case 'font-other-hover':
						//if($v != $this->color_schemes[$k])
						echo '#lang_sel_footer ul a:hover{color:' . $v . ';}';
						break;
					case 'background-other-normal':
						//if($v != $this->color_schemes[$k])
						echo '#lang_sel_footer ul a, #lang_sel_footer ul a:visited{background-color:' . $v . ';}';
						break;
					case 'background-other-hover':
						//if($v != $this->color_schemes[$k])
						echo '#lang_sel_footer ul a:hover{background-color:' . $v . ';}';
						break;
					case 'border':
						//if($v != $this->color_schemes[$k])
						echo '#lang_sel_footer{border-color:' . $v . ';}';
						break;
					case 'background':
						//if($v != $this->color_schemes[$k])
						echo '#lang_sel_footer{background-color:' . $v . ';}';
						break;
				}
			}
			echo "</style>\n";
		}
	}

	static function get_language_selector_footer() {
		global $sitepress;

		$language_selector_footer = '';
		$languages                = array();

		if ( ! function_exists( 'wpml_home_url_ls_hide_check' ) || ! wpml_home_url_ls_hide_check() ) {
			$languages = $sitepress->get_ls_languages();
		}

		if ( ! empty( $languages ) ) {
			$language_selector_footer = '
							<div id="lang_sel_footer">
									<ul>
									';
			foreach ( $languages as $lang ) {

				$alt_title_lang = $sitepress->get_setting( 'icl_lso_display_lang' ) ? esc_attr( $lang[ 'translated_name' ] ) : esc_attr( $lang[ 'native_name' ] );

				$language_selector_footer .= '    <li>';
				$language_selector_footer .= '<a href="' . apply_filters( 'WPML_filter_link', $lang[ 'url' ], $lang ) . '"';
				if ( $lang[ 'active' ] ) {
					$language_selector_footer .= ' class="lang_sel_sel"';
				}
				$language_selector_footer .= '>';
				if ( $sitepress->get_setting( 'icl_lso_flags' ) || $sitepress->footer_preview ) {
					$language_selector_footer .= '<img src="' . $lang[ 'country_flag_url' ] . '" alt="' . $alt_title_lang . '" class="iclflag" title="' . $alt_title_lang . '" ';
				}
				if ( ! $sitepress->get_setting( 'icl_lso_flags' ) && $sitepress->footer_preview ) {
					$language_selector_footer .= ' style="display:none;"';
				}
				if ( $sitepress->get_setting( 'icl_lso_flags' ) || $sitepress->footer_preview ) {
					$language_selector_footer .= ' />&nbsp;';
				}

				if ( $sitepress->footer_preview ) {
					$lang_native = $lang[ 'native_name' ];
					$lang_native_hidden = false;
					$lang_translated = $lang[ 'translated_name' ];
					$lang_translated_hidden = false;
				} else {
					if ( $sitepress->get_setting( 'icl_lso_native_lang' ) ) {
						$lang_native = $lang[ 'native_name' ];
					} else {
						$lang_native = false;
					}
					if ( $sitepress->get_setting( 'icl_lso_display_lang' ) ) {
						$lang_translated = $lang[ 'translated_name' ];
					} else {
						$lang_translated = false;
					}
					$lang_native_hidden     = false;
					$lang_translated_hidden = false;
				}
				$language_selector_footer .= icl_disp_language( $lang_native, $lang_translated, $lang_native_hidden, $lang_translated_hidden );

				$language_selector_footer .= '</a>';
				$language_selector_footer .= '</li>
									';
			}
			$language_selector_footer .= '</ul>
							</div>';
		}

		return $language_selector_footer;
	}

	function language_selector_footer() {
		echo self::get_language_selector_footer();
	}

	function admin() {
		global $sitepress;
		foreach ( $this->color_schemes as $color_scheme_name => $color_scheme_data ) {
			foreach ( $this->widget_css_defaults as $scheme_attribute => $scheme_attribute_value ) {
				?>
				<input type="hidden" id="icl_lang_sel_config_alt_<?php echo $color_scheme_name ?>_<?php echo $scheme_attribute ?>" value="<?php echo $this->color_schemes[ $color_scheme_name ][ $scheme_attribute ] ?>"/>
			<?php
			}
		}
		if ( $this->load_language_selector_css() ) {
			$this->render_admin_language_preview();
		} else {
			$this->render_admin_language_blocked_preview();
		}
		?>
		<div class="wpml-section-content-inner">

		<h4><?php _e( 'Footer language switcher style', 'sitepress' ) ?></h4>
		<p>
			<label>
				<input type="checkbox" name="icl_lang_sel_footer" value="1" <?php if ( ! empty( $this->settings[ 'icl_lang_sel_footer' ] )): ?>checked="checked"<?php endif ?> />
				<?php _e( 'Show language switcher in footer', 'sitepress' ) ?>
			</label>
		</p>
		<div id="icl_lang_sel_footer_preview_wrap" class="language-selector-preview language-selector-preview-footer">
			<div id="icl_lang_sel_footer_preview">
				<p><strong><?php _e( 'Footer language switcher preview', 'sitepress' ) ?></strong></p>
				<?php
				$sitepress->footer_preview = true;
				$this->language_selector_footer();
				?>
			</div>
		</div>
		<?php
		if ( $this->load_language_selector_css() ) {
			?>

			<?php foreach ( $this->color_schemes as $color_scheme_name => $color_scheme_data ): ?>
				<?php foreach ( $this->footer_css_defaults as $scheme_attribute => $scheme_attribute_value ): ?>
					<input type="hidden" id="icl_lang_sel_footer_config_alt_<?php echo $color_scheme_name ?>_<?php echo $scheme_attribute ?>" value="<?php echo $this->color_schemes[ $color_scheme_name ][ $scheme_attribute ] ?>"/>
				<?php endforeach; ?>
			<?php endforeach; ?>

			<p>
				<a href="#icl_lang_preview_config_footer_editor_wrapper" id="icl_lang_sel_footer_preview_link" class="js-toggle-colors-edit">
					<?php _e( 'Edit the footer language switcher colors', 'sitepress' ) ?>
					<i class="icon-caret-down js-arrow-toggle"></i>
				</a>
			</p>
			<div class="hidden" id="icl_lang_preview_config_footer_editor_wrapper">
				<table id="icl_lang_preview_config_footer" style="width:auto;">
					<thead>
					<tr>
						<th>&nbsp;</th>
						<th><?php _e( 'Normal', 'sitepress' ) ?></th>
						<th><?php _e( 'Hover', 'sitepress' ) ?></th>
					</tr>
					</thead>
					<tbody>
					<tr>
						<td><?php _e( 'Current language font color', 'sitepress' ) ?></td>
						<td>
							<?php
							$this->set_current_language_color_selector_control_arguments( 'font-current', 'font-current', 'normal', true );
							$this->current_language_color_selector_control();
							?>
						</td>
						<td>
							<?php
							$this->set_current_language_color_selector_control_arguments( 'font-current', 'font-current', 'hover', true );
							$this->current_language_color_selector_control();
							?>
						</td>
					</tr>
					<tr>
						<td><?php _e( 'Current language background color', 'sitepress' ) ?></td>
						<td>
							<?php
							$this->set_current_language_color_selector_control_arguments( 'background-current', 'background-current', 'normal', true );
							$this->current_language_color_selector_control();
							?>
						</td>
						<td>
							<?php
							$this->set_current_language_color_selector_control_arguments( 'background-current', 'background-current', 'hover', true );
							$this->current_language_color_selector_control();
							?>
						</td>
					</tr>
					<tr>
						<td><?php _e( 'Other languages font color', 'sitepress' ) ?></td>
						<td>
							<?php
							$this->set_current_language_color_selector_control_arguments( 'font-other', 'font-other', 'normal', true );
							$this->current_language_color_selector_control();
							?>
						</td>
						<td>
							<?php
							$this->set_current_language_color_selector_control_arguments( 'font-other', 'font-other', 'hover', true );
							$this->current_language_color_selector_control();
							?>
						</td>
					</tr>
					<tr>
						<td><?php _e( 'Other languages background color', 'sitepress' ) ?></td>
						<td>
							<?php
							$this->set_current_language_color_selector_control_arguments( 'background-other', 'background-other', 'normal', true );
							$this->current_language_color_selector_control();
							?>
						</td>
						<td>
							<?php
							$this->set_current_language_color_selector_control_arguments( 'background-other', 'background-other', 'hover', true );
							$this->current_language_color_selector_control();
							?>
						</td>
					</tr>

					<tr>
						<td><?php _e( 'Background', 'sitepress' ) ?></td>
						<td>
							<?php
							$this->set_current_language_color_selector_control_arguments( 'background', 'background', '', true );
							$this->current_language_color_selector_control();
							?>
						</td>
						<td>&nbsp;</td>
					</tr>

					<tr>
						<td><?php _e( 'Border', 'sitepress' ) ?></td>
						<td>
							<?php
							$this->set_current_language_color_selector_control_arguments( 'border', 'border', '', true );
							$this->current_language_color_selector_control();
							?>
						</td>
						<td>&nbsp;</td>
					</tr>
					</tbody>

				</table>

				<label for="icl_lang_sel_footer_color_scheme"><?php _e( 'Presets:', 'sitepress' ) ?></label>
				<select id="icl_lang_sel_footer_color_scheme" name="icl_lang_sel_footer_color_scheme">
					<option value=""><?php _e( '--select--', 'sitepress' ) ?>&nbsp;</option>
					<option value="Gray"><?php _e( 'Gray', 'sitepress' ) ?>&nbsp;</option>
					<option value="White"><?php _e( 'White', 'sitepress' ) ?>&nbsp;</option>
					<option value="Blue"><?php _e( 'Blue', 'sitepress' ) ?>&nbsp;</option>
				</select>
				<span style="display:none"><?php _e( "Are you sure? The customization you may have made will be overridden once you click 'Apply'", 'sitepress' ) ?></span>
			</div> <!-- #icl_lang_preview_config_footer_editor_wrapper -->

			</div> <!-- .wpml-section-content-inner -->

			<div class="wpml-section-content-inner">
				<h4><?php _e( 'Show post translation links', 'sitepress' ); ?></h4>
				<ul>
					<li>
						<label>
							<input type="checkbox"
							       name="icl_post_availability"
							       id="js-post-availability"
							       data-target=".js-post-availability-settings"
							       value="1"
							       <?php if ( ! empty( $this->settings[ 'icl_post_availability' ] )): ?>checked<?php endif ?> />
							<?php _e( 'Yes', 'sitepress' ); ?>
						</label>
					</li>
					<li class="js-post-availability-settings <?php if ( empty( $this->settings[ 'icl_post_availability' ] ) ): ?>hidden<?php endif ?>">
						<label>
							<?php _e( 'Position', 'sitepress' ); ?>&nbsp;
							<select name="icl_post_availability_position">
								<option value="above"<?php if ( isset( $this->settings[ 'icl_post_availability_position' ] )
								                                && $this->settings[ 'icl_post_availability_position' ] == 'above'
								): ?> selected="selected"<?php endif ?>><?php _e( 'Above post', 'sitepress' ); ?>&nbsp;&nbsp;</option>
								<option value="below"<?php if ( empty( $this->settings[ 'icl_post_availability_position' ] ) || $this->settings[ 'icl_post_availability_position' ] == 'bellow'
								                                || $this->settings[ 'icl_post_availability_position' ] == 'below'
								): ?> selected="selected"<?php endif ?>><?php _e( 'Below post', 'sitepress' ); ?>&nbsp;&nbsp;</option>
							</select>
						</label>
					</li>
					<li class="js-post-availability-settings <?php if ( empty( $this->settings[ 'icl_post_availability' ] ) ): ?>hidden<?php endif ?>">
						<label>
							<?php _e( 'Text for alternative languages for posts', 'sitepress' ); ?>: <input type="text" name="icl_post_availability_text" value="<?php
							if ( isset( $this->settings[ 'icl_post_availability_text' ] ) ) {
								echo esc_attr( $this->settings[ 'icl_post_availability_text' ] );
							} else {
								_e( 'This post is also available in: %s', 'sitepress' );
							} ?>" size="40"/>
						</label>
					</li>
				</ul>
			</div> <!-- .wpml-section-content-inner -->

			<div class="wpml-section-content-inner">
				<h4><label for="icl_additional_css"><?php _e( 'Additional CSS (optional)', 'sitepress' ); ?></label></h4>

				<p>
					<?php
					if ( ! empty( $this->settings[ 'icl_additional_css' ] ) ) {
						$icl_additional_css = trim( $this->settings[ 'icl_additional_css' ] );
					} else {
						$icl_additional_css = '';
					}
					?>
					<textarea id="icl_additional_css" name="icl_additional_css" rows="4" class="large-text"><?php echo $icl_additional_css; ?></textarea>
				</p>
			</div> <!-- .wpml-section-content-inner -->

		<?php
		}
	}

	function widget_list() {
		global $sitepress;

		$active_languages = icl_get_languages();
		if ( empty( $active_languages ) ) {
			return;
		} ?>

		<div id="lang_sel_list"<?php if ( empty( $this->settings[ 'icl_lang_sel_type' ] ) || $this->settings[ 'icl_lang_sel_type' ] == 'dropdown' ) {
			echo ' style="display:none;"';
		} ?> class="lang_sel_list_<?php echo $this->settings[ 'icl_lang_sel_orientation' ] ?>">
			<ul>
				<?php
				$li_items = '';
				foreach ( $active_languages as $lang ) {
					$language_selected = ' class="lang_sel_';
					$language_selected .= $lang[ 'language_code' ] === $sitepress->get_current_language() ? 'sel"' : 'other"';
					$li_items .= $sitepress->render_ls_li_item( $lang, false, true, $language_selected );
				}
				echo $li_items;
				?>
			</ul>
		</div>
	<?php
	}

	function custom_language_switcher_style() {
		if ( defined( 'ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS' ) && ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS ) {
			return;
		}
		$add = false;
		foreach ( $this->widget_css_defaults as $key => $d ) {
			if ( isset( $this->settings[ 'icl_lang_sel_config' ][ $key ] ) && $this->settings[ 'icl_lang_sel_config' ][ $key ] != $d ) {
				$add = true;
				break;
			}
		}
		if ( $add ) {
			$list = ( $this->settings[ 'icl_lang_sel_type' ] == 'list' ) ? true : false;
			echo "\n<style type=\"text/css\">";
			foreach ( $this->settings[ 'icl_lang_sel_config' ] as $k => $v ) {
				switch ( $k ) {
					case 'font-current-normal':
						//if($v != $this->widget_css_defaults[$k])
						if ( $list ) {
							echo '#lang_sel_list a.lang_sel_sel, #lang_sel_list a.lang_sel_sel:visited{color:' . $v . ';}';
						} else {
							echo '#lang_sel a, #lang_sel a.lang_sel_sel{color:' . $v . ';}';
						}
						break;
					case 'font-current-hover':
						//if($v != $this->widget_css_defaults[$k])
						if ( $list ) {
							echo '#lang_sel_list a:hover, #lang_sel_list a.lang_sel_sel:hover{color:' . $v . ';}';
						} else {
							echo '#lang_sel a:hover, #lang_sel a.lang_sel_sel:hover{color:' . $v . ';}';
						}
						break;
					case 'background-current-normal':
						//if($v != $this->widget_css_defaults[$k])
						if ( $list ) {
							echo '#lang_sel_list a.lang_sel_sel, #lang_sel_list a.lang_sel_sel:visited{background-color:' . $v . ';}';
						} else {
							echo '#lang_sel a.lang_sel_sel, #lang_sel a.lang_sel_sel:visited{background-color:' . $v . ';}';
						}
						break;
					case 'background-current-hover':
						//if($v != $this->widget_css_defaults[$k])
						if ( $list ) {
							echo '#lang_sel_list a.lang_sel_sel:hover{background-color:' . $v . ';}';
						} else {
							echo '#lang_sel a.lang_sel_sel:hover{background-color:' . $v . ';}';
						}
						break;
					case 'font-other-normal':
						//if($v != $this->widget_css_defaults[$k])
						if ( $list ) {
							echo '#lang_sel_list ul a.lang_sel_other, #lang_sel_list ul a.lang_sel_other:visited{color:' . $v . ';}';
						} else {
							echo '#lang_sel li ul a, #lang_sel li ul a:visited{color:' . $v . ';}';
						}
						break;
					case 'font-other-hover':
						//if($v != $this->widget_css_defaults[$k])
						if ( $list ) {
							echo '#lang_sel_list ul a.lang_sel_other:hover{color:' . $v . ';}';
						} else {
							echo '#lang_sel li ul a:hover{color:' . $v . ';}';
						}
						break;
					case 'background-other-normal':
						//if($v != $this->widget_css_defaults[$k])
						if ( $list ) {
							echo '#lang_sel_list ul a.lang_sel_other, #lang_sel li ul a:link, #lang_sel_list ul a.lang_sel_other:visited{background-color:' . $v . ';}';
						} else {
							echo '#lang_sel li ul a, #lang_sel li ul a:link, #lang_sel li ul a:visited{background-color:' . $v . ';}';
						}
						break;
					case 'background-other-hover':
						//if($v != $this->widget_css_defaults[$k])
						if ( $list ) {
							echo '#lang_sel_list ul a.lang_sel_other:hover{background-color:' . $v . ';}';
						} else {
							echo '#lang_sel li ul a:hover{background-color:' . $v . ';}';
						}
						break;
					case 'border':
						//if($v != $this->widget_css_defaults[$k])
						if ( $list ) {
							echo '#lang_sel_list a, #lang_sel_list a:visited{border-color:' . $v . ';} #lang_sel_list  ul{border-top:1px solid ' . $v . ';}';
						} else {
							echo '#lang_sel a, #lang_sel a:visited{border-color:' . $v . ';} #lang_sel ul ul{border-top:1px solid ' . $v . ';}';
						}
						break;
				}
			}
			echo "</style>\n";
		}
		if ( isset( $this->settings[ 'icl_additional_css' ] ) && ! empty( $this->settings[ 'icl_additional_css' ] ) ) {
			echo "\r\n<style type=\"text/css\">";
			//echo implode("\r\n", $this->settings['icl_additional_css']);
			echo $this->settings[ 'icl_additional_css' ];
			echo "\r\n</style>";
		}
	}

	function wp_page_menu_filter( $items, $args ) {
		if($this->must_filter_menus()) {
			$obj_args = new stdClass();
			foreach ( $args as $key => $value ) {
				$obj_args->$key = $value;
			}

			$items = str_replace( "</ul></div>", "", $items );

			$items = apply_filters( 'wp_nav_menu_items', $items, $obj_args );

			$items .= "</ul></div>";
		}
		return $items;
	}

	/**
	 * Filter on the 'wp_nav_menu_items' hook, that potentially adds a language switcher to the item of some menus.
	 *
	 * @param string $items
	 * @param object $args
	 *
	 * @return string
	 */
	function wp_nav_menu_items_filter( $items, $args ) {
		if ( $this->must_filter_menus() && $this->menu_has_ls( $args ) ) {
			$items .= $this->get_menu_ls_html( $args );
		}

		return $items;
	}

	/**
	 * Returns the HTML string of the language switcher for a given menu.
	 *
	 * @param object $args
	 *
	 * @return string
	 */
	private function get_menu_ls_html( $args ) {
		global $sitepress, $wpml_post_translations, $wpml_term_translations;

		$current_language = $sitepress->get_current_language();
		$languages_helper = new WPML_Languages( $wpml_term_translations, $sitepress, $wpml_post_translations );
		$languages        = $sitepress->get_ls_languages();

		$items = '';
		$items .= '<li class="menu-item menu-item-language menu-item-language-current menu-item-has-children">';
		$items .= isset( $args->before ) ? $args->before : '';
		$items .= '<a href="#" onclick="return false">';
		$items .= isset( $args->link_before ) ? $args->link_before : '';

		$lang           = isset( $languages[ $current_language ] )
			? $languages[ $current_language ]
			: $languages_helper->get_ls_language( $current_language, $current_language );
		$native_lang    = $sitepress->get_setting( 'icl_lso_native_lang' );
		$displayed_lang = $sitepress->get_setting( 'icl_lso_display_lang' );
		$language_name  = $this->language_display( $lang[ 'native_name' ],
												   $lang[ 'translated_name' ],
												   $native_lang,
												   $displayed_lang,
												   false );
		$language_name  = $this->maybe_render_flag( $lang, $language_name );

		$items .= $language_name;
		$items .= isset( $args->link_after ) ? $args->link_after : '';
		$items .= '</a>';
		$items .= isset( $args->after ) ? $args->after : '';
		unset( $languages[ $current_language ] );
		$items .= $this->render_ls_sub_items( $languages );

		return $items;
	}

	/**
	 * Checks if a given menu has a language_switcher displayed in it
	 *
	 * @param object $args
	 *
	 * @return bool
	 */
	private function menu_has_ls( $args ) {
		list( $abs_menu_id, $settings_menu_id, $menu_locations ) = $this->get_menu_locations_and_ids( $args );

		return $abs_menu_id == $settings_menu_id
			   || ( (bool) $abs_menu_id === false
					&& isset( $args->theme_location )
					&& in_array( $args->theme_location, $menu_locations ) );
	}

	/**
	 * Gets the menu locations that display a language switcher, the id of the menu to which the switcher is assigned
	 * and translation of this id into the default language.
	 *
	 * @param object $args
	 *
	 * @return array
	 */
	private function get_menu_locations_and_ids( $args ) {
		global $sitepress;

		$abs_menu_id = false;
		$settings_menu_id = false;
		$menu_locations = array();

		if(isset($args->menu)) {
			$default_language = $sitepress->get_default_language();
			$args->menu       = isset( $args->menu->term_id ) ? $args->menu->term_id : $args->menu;
			$abs_menu_id      = apply_filters( 'translate_object_id', $args->menu, 'nav_menu', false, $default_language );
			$settings_menu_id = apply_filters( 'translate_object_id', $sitepress->get_setting( 'menu_for_ls' ), 'nav_menu', false, $default_language );
			$menu_locations   = get_nav_menu_locations();
			if ( ! $abs_menu_id && $settings_menu_id ) {
				foreach ( $menu_locations as $location => $id ) {
					if ( $id != $settings_menu_id ) {
						unset( $menu_locations[ $location ] );
					}
				}
			}
		}

		return array( $abs_menu_id, $settings_menu_id, array_keys( $menu_locations ) );
	}

	private function render_ls_sub_items( $languages ) {
		global $sitepress;

		$ls_type   = $sitepress->get_setting( 'icl_lang_sel_type' );
		$ls_orientation   = ($ls_type == 'list') && $sitepress->get_setting( 'icl_lang_sel_orientation' );
		$menu_is_vertical = ! $ls_orientation || $ls_orientation === 'vertical';

		$sub_items = '';
		foreach ( (array) $languages as $lang ) {
			$sub_items .= '<li class="menu-item menu-item-language">';
			$sub_items .= '<a href="' . $lang[ 'url' ] . '">';

			$native_lang    = $sitepress->get_setting( 'icl_lso_native_lang' );
			$displayed_lang = $sitepress->get_setting( 'icl_lso_display_lang' );
			$language_name = $this->language_display($lang[ 'native_name' ], $lang[ 'translated_name' ], $native_lang, $displayed_lang, false);
			$language_name = $this->maybe_render_flag( $lang, $language_name );

			$sub_items .= $language_name;
			$sub_items .= '</a></li>';
		}

		$sub_items = $sub_items && $menu_is_vertical ? '<ul class="sub-menu submenu-languages">' . $sub_items . '</ul>' : $sub_items;
		$sub_items = $menu_is_vertical ? $sub_items . '</li>' : '</li>' . $sub_items;

		return $sub_items;
	}

	public function language_display( $native_name, $translated_name = false, $show_native_name = false, $show_translate_name = false, $include_html = true ) {
		$result = '';

		if ( ! $show_native_name ) {
			$native_name = '';
		}
		
		if ( ! $show_translate_name ) {
			$translated_name = '';
		}
		
		if ( $native_name && $translated_name ) {
			if ( $native_name != $translated_name ) {
				if ( $show_native_name ) {
					if($include_html) {
						$result .= '<span class="icl_lang_sel_native">';
					}
					$result .= '%1$s';
					if($include_html) {
						$result .= '</span>';
					}
					if($show_translate_name) {
						$result .= ' ';
						if($include_html) {
							$result .= '<span class="icl_lang_sel_translated">';
						}
						$result .= $show_native_name
							? '<span class="icl_lang_sel_bracket">(</span>%2$s<span class="icl_lang_sel_bracket">)</span>'
							: '%2$s';
						if($include_html) {
							$result .= '</span>';
						}
					}
				}elseif($show_translate_name) {
					if($include_html) {
						$result .= '<span class="icl_lang_sel_translated">';
					}
					$result .= '%2$s';
					if($include_html) {
						$result .= '</span>';
					}
				}
			} else {
				if($include_html) {
					$result .= '<span class="icl_lang_sel_current icl_lang_sel_native">';
				}
				$result .= '%1$s';
				if($include_html) {
					$result .= '</span>';
				}
			}
		} elseif ( $native_name ) {
			$result = '%1$s';
		} elseif ( $translated_name ) {
			$result = '%2$s';
		}

		return sprintf($result, $native_name, $translated_name);
	}

	private function maybe_render_flag( $language, $rendered_name ) {
		global $sitepress;

		$html = $rendered_name;
		if ( $sitepress->get_setting( 'icl_lso_flags' ) ) {
			$alt_title_lang = $rendered_name ? esc_attr( $rendered_name ) : esc_attr( $language[ 'native_name' ] );
			$html           = '<img class="iclflag" src="' . $language[ 'country_flag_url' ] . '" width="18" height="12" alt="' . $language[ 'language_code' ] . '" title="' . $alt_title_lang . '" />' . $html;
		}

		return $html;
	}

	function set_current_language_color_selector_control_arguments( $setting_base_key, $input_base_id, $state = '', $in_footer = false ) {
		$input_id    = $input_base_id . ( $state ? '-' . $state : '' );
		$setting_key = $setting_base_key . ( $state ? '-' . $state : '' );

		$input_name_group = 'icl_lang_sel_config';
		if ( $in_footer ) {
			$input_name_group = 'icl_lang_sel_footer_config';
		}

		$this->current_language_color_selector_item[ 'input_name_group' ] = $input_name_group;
		$this->current_language_color_selector_item[ 'input_name_id' ]    = $input_id;

		$this->current_language_color_selector_item[ 'default' ] = $this->footer_css_defaults[ $setting_key ];
		if ( isset( $this->settings[ $input_name_group ][ $setting_key ] ) ) {
			$this->current_language_color_selector_item[ 'value' ] = $this->settings[ $input_name_group ][ $setting_key ];
		}

		return $this->current_language_color_selector_item;
	}

	function current_language_color_selector_control() {
		$wpml_color_picker = new WPML_Color_Picker( $this->current_language_color_selector_item );
		echo $wpml_color_picker->get_current_language_color_selector_control();
	}

	protected function render_admin_language_blocked_preview() {
		?>
		<em><?php printf( __( "%s is defined in your theme. The language switcher can only be customized using the theme's CSS.", 'sitepress' ), 'ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS' ) ?></em>
	<?php
	}

	protected function render_admin_language_preview() {
		?>
		<p>
			<a href="#icl_lang_preview_config_wrapper" class="js-toggle-colors-edit">
				<?php _e( 'Edit the language switcher widget colors', 'sitepress' ) ?>
				<i class="icon-caret-down js-arrow-toggle"></i>
			</a>
		</p>
		<div id="icl_lang_preview_config_wrapper" class="hidden">
			<table id="icl_lang_preview_config" style="width:auto;">
				<thead>
				<tr>
					<th>&nbsp;</th>
					<th><?php _e( 'Normal', 'sitepress' ) ?></th>
					<th><?php _e( 'Hover', 'sitepress' ) ?></th>
				</tr>
				</thead>
				<tbody>
				<tr>
					<td><?php _e( 'Current language font color', 'sitepress' ) ?></td>
					<td>
						<?php
						$this->set_current_language_color_selector_control_arguments( 'font-current', 'font-current', 'normal' );
						$this->current_language_color_selector_control();
						?>
					</td>
					<td>
						<?php
						$this->set_current_language_color_selector_control_arguments( 'font-current', 'font-current', 'hover' );
						$this->current_language_color_selector_control();
						?>
					</td>
				</tr>
				<tr>
					<td><?php _e( 'Current language background color', 'sitepress' ) ?></td>
					<td>
						<?php
						$this->set_current_language_color_selector_control_arguments( 'background-current', 'background-current', 'normal' );
						$this->current_language_color_selector_control();
						?>
					</td>
					<td>
						<?php
						$this->set_current_language_color_selector_control_arguments( 'background-current', 'background-current', 'hover' );
						$this->current_language_color_selector_control();
						?>
					</td>
				</tr>
				<tr>
					<td><?php _e( 'Other languages font color', 'sitepress' ) ?></td>
					<td>
						<?php
						$this->set_current_language_color_selector_control_arguments( 'font-other', 'font-other', 'normal' );
						$this->current_language_color_selector_control();
						?>
					</td>
					<td>
						<?php
						$this->set_current_language_color_selector_control_arguments( 'font-other', 'font-other', 'hover' );
						$this->current_language_color_selector_control();
						?>
					</td>
				</tr>
				<tr>
					<td><?php _e( 'Other languages background color', 'sitepress' ) ?></td>
					<td>
						<?php
						$this->set_current_language_color_selector_control_arguments( 'background-other', 'background-other', 'normal' );
						$this->current_language_color_selector_control();
						?>
					</td>
					<td>
						<?php
						$this->set_current_language_color_selector_control_arguments( 'background-other', 'background-other', 'hover' );
						$this->current_language_color_selector_control();
						?>
					</td>
				</tr>

				<tr>
					<td><?php _e( 'Background', 'sitepress' ) ?></td>
					<td>
						<?php
						$this->set_current_language_color_selector_control_arguments( 'background', 'background' );
						$this->current_language_color_selector_control();
						?>
					</td>
					<td>&nbsp;</td>
				</tr>

				<tr>
					<td><?php _e( 'Border', 'sitepress' ) ?></td>
					<td>
						<?php
						$this->set_current_language_color_selector_control_arguments( 'border', 'border' );
						$this->current_language_color_selector_control();
						?>
					</td>
				</tr>
				</tbody>

			</table>

			<label for="icl_lang_sel_color_scheme"><?php _e( 'Presets:', 'sitepress' ) ?></label>
			<select id="icl_lang_sel_color_scheme" name="icl_lang_sel_color_scheme">
				<option value=""><?php _e( '--select--', 'sitepress' ) ?>&nbsp;</option>
				<option value="Gray"><?php _e( 'Gray', 'sitepress' ) ?>&nbsp;</option>
				<option value="White"><?php _e( 'White', 'sitepress' ) ?>&nbsp;</option>
				<option value="Blue"><?php _e( 'Blue', 'sitepress' ) ?>&nbsp;</option>
			</select>
			<span style="display:none"><?php _e( "Are you sure? The customization you may have made will be overridden once you click 'Apply'", 'sitepress' ) ?></span>
		</div>
	<?php
	}

	/**
	 * @return array
	 */
	protected function get_default_color_schemes() {
		$gray = array(
			'font-current-normal'       => '#222222',
			'font-current-hover'        => '#000000',
			'background-current-normal' => '#eeeeee',
			'background-current-hover'  => '#eeeeee',
			'font-other-normal'         => '#222222',
			'font-other-hover'          => '#000000',
			'background-other-normal'   => '#e5e5e5',
			'background-other-hover'    => '#eeeeee',
			'border'                    => '#cdcdcd',
			'background'                => '#e5e5e5'
		);

		$white = array(
			'font-current-normal'       => '#444444',
			'font-current-hover'        => '#000000',
			'background-current-normal' => '#ffffff',
			'background-current-hover'  => '#eeeeee',
			'font-other-normal'         => '#444444',
			'font-other-hover'          => '#000000',
			'background-other-normal'   => '#ffffff',
			'background-other-hover'    => '#eeeeee',
			'border'                    => '#cdcdcd',
			'background'                => '#ffffff'
		);

		$blue = array(
			'font-current-normal'       => '#ffffff',
			'font-current-hover'        => '#000000',
			'background-current-normal' => '#95bedd',
			'background-current-hover'  => '#95bedd',
			'font-other-normal'         => '#000000',
			'font-other-hover'          => '#ffffff',
			'background-other-normal'   => '#cbddeb',
			'background-other-hover'    => '#95bedd',
			'border'                    => '#0099cc',
			'background'                => '#cbddeb'
		);

		return array(
			'Gray'  => $gray,
			'White' => $white,
			'Blue'  => $blue
		);
	}

	/**
	 * @return bool
	 */
	protected function load_language_selector_css() {
		return ! defined( 'ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS' ) || ! ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS;
	}
} // end class

// language switcher functions
function language_selector_widget( $args ) {
	global $sitepress, $sitepress_settings;
	extract( $args, EXTR_SKIP );
	/** @var $before_widget string */
	echo $before_widget;
	if ( $sitepress_settings[ 'icl_widget_title_show' ] ) {
		echo $args[ 'before_title' ];
		_e( 'Languages', 'sitepress' );
		echo $args[ 'after_title' ];
	}
	$sitepress->language_selector();
	/** @var $after_widget string */
	echo $after_widget;
}

function icl_lang_sel_nav_ob_start() {
	if ( is_feed() ) {
		return;
	}
	ob_start( 'icl_lang_sel_nav_prepend_css' );
}

function icl_lang_sel_nav_ob_end() {
	$ob_handlers    = ob_list_handlers();
	$active_handler = array_pop( $ob_handlers );
	if ( $active_handler == 'icl_lang_sel_nav_prepend_css' ) {
		ob_end_flush();
	}
}

function icl_lang_sel_nav_prepend_css( $buf ) {
	if ( defined( 'ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS' ) && ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS ) {
		return $buf;
	}

	return preg_replace( '#</title>#i', '</title>' . PHP_EOL . PHP_EOL . icl_lang_sel_nav_css( false ), $buf );
}

function icl_lang_sel_nav_css( $show = true ) {
	if ( defined( 'ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS' ) && ICL_DONT_LOAD_LANGUAGE_SELECTOR_CSS ) {
		return '';
	}
	$link_tag = '<link rel="stylesheet" href="' . ICL_PLUGIN_URL . '/res/css/language-selector.css?v=' . ICL_SITEPRESS_VERSION . '" type="text/css" media="all" />';
	if ( ! $show && ( ! isset( $_GET[ 'page' ] ) || $_GET[ 'page' ] != ICL_PLUGIN_FOLDER . '/menu/languages.php' ) ) {
		return $link_tag;
	} else {
		echo $link_tag;
	}

	return $link_tag;
}

global $icl_language_switcher;
$icl_language_switcher = new SitePressLanguageSwitcher;
