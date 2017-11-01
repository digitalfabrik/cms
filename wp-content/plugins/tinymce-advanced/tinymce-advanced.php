<?php
/*
Plugin Name: TinyMCE Advanced
Plugin URI: http://www.laptoptips.ca/projects/tinymce-advanced/
Description: Enables advanced features and plugins in TinyMCE, the visual editor in WordPress.
Version: 4.6.3
Author: Andrew Ozz
Author URI: http://www.laptoptips.ca/
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: tinymce-advanced
Domain Path: /langs

	TinyMCE Advanced is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 2 of the License, or
	any later version.

	TinyMCE Advanced is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License along
	with TinyMCE Advanced. If not, see https://www.gnu.org/licenses/gpl-2.0.html.

	Copyright (c) 2007-2016 Andrew Ozz. All rights reserved.
*/

if ( ! class_exists('Tinymce_Advanced') ) :

class Tinymce_Advanced {

	private $required_version = '4.7-beta';
	private $user_settings;
	private $admin_settings;
	private $admin_options;
	private $editor_id;
	private $disabled_for_editor = false;

	private $plugins;
	private $options;
	private $toolbar_1;
	private $toolbar_2;
	private $toolbar_3;
	private $toolbar_4;
	private $used_buttons = array();
	private $all_buttons = array();
	private $buttons_filter = array();
	private $fontsize_formats = '8px 10px 12px 14px 16px 20px 24px 28px 32px 36px 48px 60px 72px 96px';
	

	private function get_default_user_settings() {
		return array(
			'options'	=> 'menubar,advlist',
			'toolbar_1' => 'formatselect,bold,italic,blockquote,bullist,numlist,alignleft,aligncenter,alignright,link,unlink,undo,redo',
			'toolbar_2' => 'fontselect,fontsizeselect,outdent,indent,pastetext,removeformat,charmap,wp_more,forecolor,table,wp_help',
			'toolbar_3' => '',
			'toolbar_4' => '',
			'plugins'   => 'anchor,code,insertdatetime,nonbreaking,print,searchreplace,table,visualblocks,visualchars,advlist,wptadv',
		);
	}

	private function get_default_admin_settings() {
		return array(
			'options' => array(),
		);
	}

	private function get_all_plugins() {
		return array(
			'advlist',
			'anchor',
			'code',
			'contextmenu',
			'emoticons',
			'importcss',
			'insertdatetime',
			'link',
			'nonbreaking',
			'print',
			'searchreplace',
			'table',
			'visualblocks',
			'visualchars',
			'wptadv',
		);
	}

	private function get_all_user_options() {
		return array(
			'advlist',
			'advlink',
			'contextmenu',
			'menubar',
			'fontsize_formats',
		);
	}

	private function get_all_admin_options() {
		return array(
			'importcss',
			'no_autop',
			'paste_images',
		);
	}

	private function get_editor_locations() {
		return array(
			'edit_post_screen',
			'rest_of_wpadmin',
			'on_front_end',
		);
	}

	public function __construct() {
		if ( ! defined('ABSPATH') ) {
			return;
		}

		register_activation_hook( __FILE__, array( $this, 'check_plugin_version' ) );

		add_action( 'plugins_loaded', array( $this, 'set_paths' ), 50 );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
			add_filter( 'plugin_action_links', array( $this, 'add_settings_link' ), 10, 2 );
			add_action( 'before_wp_tiny_mce', array( $this, 'show_version_warning' ) );
		}

		add_filter( 'wp_editor_settings', array( $this, 'disable_for_editor' ), 10, 2 );

		add_filter( 'mce_buttons', array( $this, 'mce_buttons_1' ), 999, 2 );
		add_filter( 'mce_buttons_2', array( $this, 'mce_buttons_2' ), 999 );
		add_filter( 'mce_buttons_3', array( $this, 'mce_buttons_3' ), 999 );
		add_filter( 'mce_buttons_4', array( $this, 'mce_buttons_4' ), 999 );

		add_filter( 'tiny_mce_before_init', array( $this, 'mce_options' ) );
		add_filter( 'mce_external_plugins', array( $this, 'mce_external_plugins' ), 999 );
		add_filter( 'tiny_mce_plugins', array( $this, 'tiny_mce_plugins' ), 999 );
		add_action( 'after_wp_tiny_mce', array( $this, 'after_wp_tiny_mce' ) );
	}

	public function disable_for_editor( $settings, $editor_id ) {
		static $editor_style_added = false;

		if ( empty( $this->admin_settings ) ) {
			$this->load_settings();
		}

		$this->disabled_for_editor = false;
		$this->editor_id = $editor_id;

		if ( ! empty( $this->admin_settings['disabled_editors'] ) ) {
			$disabled_editors = explode( ',', $this->admin_settings['disabled_editors'] );
			$current_screen = isset( $GLOBALS['current_screen'] ) ? $GLOBALS['current_screen'] : new stdClass;

			if ( is_admin() ) {
				if ( $editor_id === 'content' && ( $current_screen->id === 'post' || $current_screen->id === 'page' ) ) {
					if ( in_array( 'edit_post_screen', $disabled_editors, true ) ) {
						$this->disabled_for_editor = true;
					}
				} elseif ( in_array( 'rest_of_wpadmin', $disabled_editors, true ) ) {
					$this->disabled_for_editor = true;
				}
			} elseif ( in_array( 'on_front_end', $disabled_editors, true ) ) {
				$this->disabled_for_editor = true;
			}
		}

		if ( ! $this->disabled_for_editor && ! $editor_style_added ) {
			if ( $this->check_admin_setting( 'importcss' ) && $this->has_editor_style() !== 'present' ) {
				add_editor_style();
			}

			$editor_style_added = true;
		}

		return $settings;
	}

	private function is_disabled() {
		return $this->disabled_for_editor;
	}

	private function has_editor_style() {
		if ( ! current_theme_supports( 'editor-style' ) ) {
			return 'not-supporetd';
		}

		$editor_stylesheets = get_editor_stylesheets();

		if ( is_array( $editor_stylesheets ) ) {
			foreach ( $editor_stylesheets as $url ) {
				if ( strpos( $url, 'editor-style.css' ) !== false ) {
					return 'present';
				}
			}
		}

		return 'not-present';
	}

	// When using a plugin that changes the paths dinamically, set these earlier than 'plugins_loaded' 50.
	public function set_paths() {
		if ( ! defined( 'TADV_URL' ) )
			define( 'TADV_URL', plugin_dir_url( __FILE__ ) );

		if ( ! defined( 'TADV_PATH' ) )
			define( 'TADV_PATH', plugin_dir_path( __FILE__ ) );
	}

	public function load_textdomain() {
	    load_plugin_textdomain( 'tinymce-advanced', false, 'tinymce-advanced/langs' );
	}

	public function enqueue_scripts( $page ) {
		if ( 'settings_page_tinymce-advanced' == $page ) {
			wp_enqueue_script( 'tadv-js', TADV_URL . 'js/tadv.js', array( 'jquery-ui-sortable' ), '4.0', true );
			wp_enqueue_style( 'tadv-mce-skin', includes_url( 'js/tinymce/skins/lightgray/skin.min.css' ), array(), '4.0' );
			wp_enqueue_style( 'tadv-css', TADV_URL . 'css/tadv-styles.css', array( 'editor-buttons' ), '4.0' );

			add_action( 'admin_footer', array( $this, 'load_mce_translation' ) );
		}
	}

	public function load_mce_translation() {
		if ( ! class_exists( '_WP_Editors' ) ) {
			require( ABSPATH . WPINC . '/class-wp-editor.php' );
		}

		?>
		<script>var tadvTranslation = <?php echo _WP_Editors::wp_mce_translation( '', true ); ?>;</script>
		<?php
	}

	public function load_settings() {
		if ( empty( $this->admin_settings ) ) {
			$this->admin_settings = get_option( 'tadv_admin_settings', false );
		}

		if ( empty( $this->user_settings ) ) {
			$this->user_settings = get_option( 'tadv_settings', false );
		}

		// load defaults if the options don't exist...
		if ( $this->admin_settings === false ) {
			$this->admin_settings = $this->get_default_admin_settings();
		}

		$this->admin_options = ! empty( $this->admin_settings['options'] ) ? explode( ',', $this->admin_settings['options'] ) : array();

		if ( $this->user_settings === false ) {
			$this->user_settings = $this->get_default_user_settings();
		}

		$this->options   = ! empty( $this->user_settings['options'] )   ? explode( ',', $this->user_settings['options'] )   : array();
		$this->plugins   = ! empty( $this->user_settings['plugins'] )   ? explode( ',', $this->user_settings['plugins'] )   : array();
		$this->toolbar_1 = ! empty( $this->user_settings['toolbar_1'] ) ? explode( ',', $this->user_settings['toolbar_1'] ) : array();
		$this->toolbar_2 = ! empty( $this->user_settings['toolbar_2'] ) ? explode( ',', $this->user_settings['toolbar_2'] ) : array();
		$this->toolbar_3 = ! empty( $this->user_settings['toolbar_3'] ) ? explode( ',', $this->user_settings['toolbar_3'] ) : array();
		$this->toolbar_4 = ! empty( $this->user_settings['toolbar_4'] ) ? explode( ',', $this->user_settings['toolbar_4'] ) : array();

		$this->used_buttons = array_merge( $this->toolbar_1, $this->toolbar_2, $this->toolbar_3, $this->toolbar_4 );
		$this->get_all_buttons();
	}

	public function show_version_warning() {
		if ( is_admin() && current_user_can( 'update_plugins' ) && get_current_screen()->base === 'post' ) {
			$this->warn_if_unsupported();
		}
	}

	public function warn_if_unsupported() {
		if ( ! $this->check_minimum_supported_version() ) {
			$wp_ver = ! empty( $GLOBALS['wp_version'] ) ? $GLOBALS['wp_version'] : '(undefined)';

			?>
			<div class="error notice is-dismissible"><p>
			<?php

			printf( __( 'TinyMCE Advanced requires WordPress version %1$s or newer. It appears that you are running %2$s. This can make the editor unstable.', 'tinymce-advanced' ),
				$this->required_version,
				esc_html( $wp_ver )
			);

			echo '<br>';

			printf( __( 'Please upgrade your WordPress installation or download an <a href="%s">older version of the plugin</a>.', 'tinymce-advanced' ),
				'https://wordpress.org/plugins/tinymce-advanced/download/'
			);

			?>
			</p></div>
			<?php
		}
	}

	// Min version
	private function check_minimum_supported_version() {
		include( ABSPATH . WPINC . '/version.php' ); // get an unmodified $wp_version
		$wp_version = str_replace( '-src', '', $wp_version );

		return ( version_compare( $wp_version, $this->required_version, '>=' ) );
	}

	public function check_plugin_version() {
		$version = get_option( 'tadv_version', 0 );

		if ( ! $version || $version < 4000 ) {
			// First install or upgrade to TinyMCE 4.0
			$this->user_settings = $this->get_default_user_settings();
			$this->admin_settings = $this->get_default_admin_settings();

			update_option( 'tadv_settings', $this->user_settings );
			update_option( 'tadv_admin_settings', $this->admin_settings );
			update_option( 'tadv_version', 4000 );
		}

		if ( $version < 4000 ) {
			// Upgrade to TinyMCE 4.0, clean options
			delete_option('tadv_options');
			delete_option('tadv_toolbars');
			delete_option('tadv_plugins');
			delete_option('tadv_btns1');
			delete_option('tadv_btns2');
			delete_option('tadv_btns3');
			delete_option('tadv_btns4');
			delete_option('tadv_allbtns');
		}
	}

	public function get_all_buttons() {
		if ( ! empty( $this->all_buttons ) )
			return $this->all_buttons;

		$buttons = array(
			// Core
			'bold' => 'Bold',
			'italic' => 'Italic',
			'underline' => 'Underline',
			'strikethrough' => 'Strikethrough',
			'alignleft' => 'Align left',
			'aligncenter' => 'Align center',
			'alignright' => 'Align right',
			'alignjustify' => 'Justify',
			'styleselect' => 'Formats',
			'formatselect' => 'Paragraph',
			'fontselect' => 'Font Family',
			'fontsizeselect' => 'Font Sizes',
			'cut' => 'Cut',
			'copy' => 'Copy',
			'paste' => 'Paste',
			'bullist' => 'Bulleted list',
			'numlist' => 'Numbered list',
			'outdent' => 'Decrease indent',
			'indent' => 'Increase indent',
			'blockquote' => 'Blockquote',
			'undo' => 'Undo',
			'redo' => 'Redo',
			'removeformat' => 'Clear formatting',
			'subscript' => 'Subscript',
			'superscript' => 'Superscript',

			// From plugins
			'hr' => 'Horizontal line',
			'link' => 'Insert/edit link',
			'unlink' => 'Remove link',
			'image' => 'Insert/edit image',
			'charmap' => 'Special character',
			'pastetext' => 'Paste as text',
			'print' => 'Print',
			'anchor' => 'Anchor',
			'searchreplace' => 'Find and replace',
			'visualblocks' => 'Show blocks',
			'visualchars' => 'Show invisible characters',
			'code' => 'Source code',
			'wp_code' => 'Code',
			'fullscreen' => 'Fullscreen',
			'insertdatetime' => 'Insert date/time',
			'media' => 'Insert/edit video',
			'nonbreaking' => 'Nonbreaking space',
			'table' => 'Table',
			'ltr' => 'Left to right',
			'rtl' => 'Right to left',
			'emoticons' => 'Emoticons',
			'forecolor' => 'Text color',
			'backcolor' => 'Background color',

			// Layer plugin ?
		//	'insertlayer' => 'Layer',

			// WP
			'wp_adv'		=> 'Toolbar Toggle',
			'wp_help'		=> 'Keyboard Shortcuts',
			'wp_more'		=> 'Read more...',
			'wp_page'		=> 'Page break',

			'tadv_mark'     => 'Mark',
		);

		// add/remove allowed buttons
		$buttons = apply_filters( 'tadv_allowed_buttons', $buttons );

		$this->all_buttons = $buttons;
		$this->buttons_filter = array_keys( $buttons );
		return $buttons;
	}

	public function get_plugins( $plugins = array() ) {

		if ( ! is_array( $this->used_buttons ) ) {
			$this->load_settings();
		}

		if ( in_array( 'anchor', $this->used_buttons, true ) )
			$plugins[] = 'anchor';

		if ( in_array( 'visualchars', $this->used_buttons, true ) )
			$plugins[] = 'visualchars';

		if ( in_array( 'visualblocks', $this->used_buttons, true ) )
			$plugins[] = 'visualblocks';

		if ( in_array( 'nonbreaking', $this->used_buttons, true ) )
			$plugins[] = 'nonbreaking';

		if ( in_array( 'emoticons', $this->used_buttons, true ) )
			$plugins[] = 'emoticons';

		if ( in_array( 'insertdatetime', $this->used_buttons, true ) )
			$plugins[] = 'insertdatetime';

		if ( in_array( 'table', $this->used_buttons, true ) )
			$plugins[] = 'table';

		if ( in_array( 'print', $this->used_buttons, true ) )
			$plugins[] = 'print';

		if ( in_array( 'searchreplace', $this->used_buttons, true ) )
			$plugins[] = 'searchreplace';

		if ( in_array( 'code', $this->used_buttons, true ) )
			$plugins[] = 'code';

	//	if ( in_array( 'insertlayer', $this->used_buttons, true ) )
	//		$plugins[] = 'layer';

		// From options
		if ( $this->check_user_setting( 'advlist' ) )
			$plugins[] = 'advlist';

		if ( $this->check_user_setting( 'advlink' ) )
			$plugins[] = 'link';

		if ( $this->check_admin_setting( 'importcss' ) )
			$plugins[] = 'importcss';

		if ( $this->check_user_setting( 'contextmenu' ) )
			$plugins[] = 'contextmenu';

		// add/remove used plugins
		$plugins = apply_filters( 'tadv_used_plugins', $plugins, $this->used_buttons );

		return array_unique( $plugins );
	}

	private function check_user_setting( $setting ) {
		if ( ! is_array( $this->options ) ) {
			$this->load_settings();
		}

		// Back-compat for 'fontsize_formats'
		if ( $setting === 'fontsize_formats' && $this->check_admin_setting( 'fontsize_formats' ) ) {
			return true;
		}

		return in_array( $setting, $this->options, true );
	}

	private function check_admin_setting( $setting ) {
		if ( ! is_array( $this->admin_options ) ) {
			$this->load_settings();
		}

		if ( strpos( $setting, 'enable_' ) === 0 ) {
			$disabled_editors = ! empty( $this->admin_settings['disabled_editors'] ) ? explode( ',', $this->admin_settings['disabled_editors'] ) : array();
			return ! in_array( str_replace( 'enable_', '', $setting ), $disabled_editors );
		}

		return in_array( $setting, $this->admin_options, true );
	}

	public function mce_buttons_1( $original, $editor_id ) {
		if ( $this->is_disabled() ) {
			return $original;
		}

		if ( ! is_array( $this->options ) ) {
			$this->load_settings();
		}

		$buttons_1 = $this->toolbar_1;

		if ( is_array( $original ) && ! empty( $original ) ) {
			$original = array_diff( $original, $this->buttons_filter );
			$buttons_1 = array_merge( $buttons_1, $original );
		}

		return $buttons_1;
	}

	public function mce_buttons_2( $original ) {
		if ( $this->is_disabled() ) {
			return $original;
		}

		if ( ! is_array( $this->options ) ) {
			$this->load_settings();
		}

		$buttons_2 = $this->toolbar_2;

		if ( is_array( $original ) && ! empty( $original ) ) {
			$original = array_diff( $original, $this->buttons_filter );
			$buttons_2 = array_merge( $buttons_2, $original );
		}

		return $buttons_2;
	}

	public function mce_buttons_3( $original ) {
		if ( $this->is_disabled() ) {
			return $original;
		}

		if ( ! is_array( $this->options ) ) {
			$this->load_settings();
		}

		$buttons_3 = $this->toolbar_3;

		if ( is_array( $original ) && ! empty( $original ) ) {
			$original = array_diff( $original, $this->buttons_filter );
			$buttons_3 = array_merge( $buttons_3, $original );
		}

		return $buttons_3;
	}

	public function mce_buttons_4( $original ) {
		if ( $this->is_disabled() ) {
			return $original;
		}

		if ( ! is_array( $this->options ) ) {
			$this->load_settings();
		}

		$buttons_4 = $this->toolbar_4;

		if ( is_array( $original ) && ! empty( $original ) ) {
			$original = array_diff( $original, $this->buttons_filter );
			$buttons_4 = array_merge( $buttons_4, $original );
		}

		return $buttons_4;
	}

	public function mce_options( $init ) {
		if ( $this->is_disabled() ) {
			return $init;
		}

		$init['image_advtab'] = true;
		$init['rel_list'] = '[{text: "None", value: ""}, {text: "Nofollow", value: "nofollow"}]';

		if ( $this->check_admin_setting( 'no_autop' ) ) {
			$init['wpautop'] = false;
			$init['indent'] = true;
			$init['tadv_noautop'] = true;
		}

		if ( $this->check_user_setting('menubar') ) {
			$init['menubar'] = true;
		}

		if ( ! in_array( 'wp_adv', $this->toolbar_1, true ) ) {
			$init['wordpress_adv_hidden'] = false;
		}

		if ( $this->check_admin_setting( 'importcss' ) ) {
	//		$init['importcss_selector_filter'] = 'function(sel){return /^\.[a-z0-9]+$/i.test(sel);}';
			$init['importcss_file_filter'] = 'editor-style.css';
		}

		if ( $this->check_user_setting( 'fontsize_formats' ) ) {
			$init['fontsize_formats'] =  $this->fontsize_formats;
		}

		if ( $this->check_user_setting( 'paste_images' ) ) {
			$init['paste_data_images'] = true;
		}

		if ( in_array( 'table', $this->plugins, true ) ) {
			$init['table_toolbar'] = false;
		}

		return $init;
	}

	public function after_wp_tiny_mce() {
		if ( $this->is_disabled() ) {
			return;
		}

		?>
		<script>
		!function(a,b){"undefined"!=typeof a&&"undefined"!=typeof b&&a(function(){b.addButton("sofbg-axcell","&para;",function(b,c){
		var d=a(c),e=a.trim(d.val()),f="table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|form|map|area|blockquote|" +
		"address|math|style|p|h[1-6]|hr|fieldset|legend|tmadv|section|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary";
		-1!==e.indexOf("</p>")&&-1===e.indexOf("\n\n")&&(e=e.replace(new RegExp("<(?:"+f+")(?: [^>]*)?>","gi"),"\n$&"),
		e=e.replace(new RegExp("</(?:"+f+")>","gi"),"$&\n"),e=e.replace(/(<br(?: [^>]*)?>)[\r\n\t]*/gi,"$1\n"),
		e=e.replace(/>\n[\r\n\t]+</g,">\n<"),e=e.replace(/^<li/gm,"	<li"),e=e.replace(/<td>\u00a0<\/td>/g,"<td>&nbsp;</td>"),
		d.val(a.trim(e)))},"","","Fix line breaks")})}(window.jQuery,window.QTags);
		</script>
		<?php
	}

	public function htmledit( $content ) {
		return $content;
	}

	public function mce_external_plugins( $mce_plugins ) {
		if ( $this->is_disabled() ) {
			return $mce_plugins;
		}

		if ( ! is_array( $this->plugins ) ) {
			$this->plugins = array();
		}

		$this->plugins[] = 'wptadv';

		$this->plugins = array_intersect( $this->plugins, $this->get_all_plugins() );

		$plugpath = TADV_URL . 'mce/';
		$mce_plugins = (array) $mce_plugins;
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		foreach ( $this->plugins as $plugin ) {
			$mce_plugins["$plugin"] = $plugpath . $plugin . "/plugin{$suffix}.js";
		}

		return $mce_plugins;
	}

	public function tiny_mce_plugins( $plugins ) {
		if ( $this->is_disabled() ) {
			return $plugins;
		}

		if ( in_array( 'image', $this->used_buttons, true ) && ! in_array( 'image', $plugins, true ) ) {
			$plugins[] = 'image';
		}

		if ( ( in_array( 'rtl', $this->used_buttons, true ) || in_array( 'ltr', $this->used_buttons, true ) ) &&
			! in_array( 'directionality', (array) $plugins, true ) ) {

			$plugins[] = 'directionality';
		}

		return $plugins;
	}

	private function parse_buttons( $toolbar_id = false, $buttons = false ) {
		if ( $toolbar_id && ! $buttons && ! empty( $_POST[$toolbar_id] ) )
			$buttons = $_POST[$toolbar_id];

		if ( is_array( $buttons ) ) {
			$_buttons = array_map( array( @$this, 'filter_name' ), $buttons );
			return implode( ',', array_filter( $_buttons ) );
		}

		return '';
	}

	private function filter_name( $str ) {
		if ( empty( $str ) || ! is_string( $str ) )
			return '';
		// Button names
		return preg_replace( '/[^a-z0-9_]/i', '', $str );
	}

	private function sanitize_settings( $settings ) {
		$_settings = array();

		if ( ! is_array( $settings ) ) {
			return $_settings;
		}

		foreach( $settings as $name => $value ) {
			$name = preg_replace( '/[^a-z0-9_]+/', '', $name );

			if ( strpos( $name, 'toolbar_' ) === 0 ) {
				$_settings[$name] = $this->parse_buttons( false, explode( ',', $value ) );
			} else if ( 'options' === $name || 'plugins' === $name || 'disabled_plugins' === $name ) {
				$_settings[$name] = preg_replace( '/[^a-z0-9_,]+/', '', $value );
			}
		}

		return $_settings;
	}

	private function validate_settings( $settings, $checklist ) {
		if ( empty( $settings ) ) {
			return '';
		} elseif ( is_string( $settings ) ) {
			$settings = explode( ',', $settings );
		} elseif ( ! is_array( $settings ) ) {
			return '';
		}

		$_settings = array();

		foreach ( $settings as $value ) {
			if ( in_array( $value, $checklist, true ) ) {
				$_settings[] = $value;
			}
		}

		return implode( ',', $_settings );
	}

	private function save_settings( $all_settings = null ) {
		$settings = $user_settings = array();

		if ( empty( $this->buttons_filter ) ) {
			$this->get_all_buttons();
		}

		if ( ! empty( $all_settings['settings'] ) ) {
			$user_settings = $all_settings['settings'];
		}

		for ( $i = 1; $i < 5; $i++ ) {
			$toolbar_name = 'toolbar_' . $i;

			if ( ! empty( $user_settings[ $toolbar_name ] ) ) {
				$toolbar = explode( ',', $user_settings[ $toolbar_name ] );
			} elseif ( ! empty( $_POST[ $toolbar_name ] ) && is_array( $_POST[ $toolbar_name ] ) ) {
				$toolbar = $_POST[ $toolbar_name ];
			} else {
				$toolbar = array();
			}

			if ( $i > 1 && ( $wp_adv = array_search( 'wp_adv', $toolbar ) ) !== false ) {
				unset( $toolbar[ $wp_adv ] );
			}

			$settings[ $toolbar_name ] = $this->validate_settings( $toolbar, $this->buttons_filter );
		}

		if ( ! empty( $user_settings['options'] ) ) {
			$options = explode( ',', $user_settings['options'] );
		} elseif ( ! empty( $_POST['options'] ) && is_array( $_POST['options'] ) ) {
			$options = $_POST['options'];
		} else {
			$options = array();
		}

		$settings['options'] = $this->validate_settings( $options, $this->get_all_user_options() );

		if ( ! empty( $user_settings['plugins'] ) ) {
			$plugins = explode( ',', $user_settings['plugins'] );
		} elseif ( ! empty( $_POST['options']['menubar'] ) ) {
			$plugins = array( 'anchor', 'code', 'insertdatetime', 'nonbreaking', 'print', 'searchreplace', 'table', 'visualblocks', 'visualchars' );
		} else {
			$plugins = array();
		}

		// Merge the submitted plugins with plugins needed for the buttons.
		$this->user_settings = $settings;
		$this->load_settings();
		$plugins = $this->get_plugins( $plugins );

		$settings['plugins'] = $this->validate_settings( $plugins, $this->get_all_plugins() );

		$this->user_settings = $settings;
		$this->load_settings();

		// Save the new settings.
		update_option( 'tadv_settings', $settings );

		if ( ! is_multisite() || current_user_can( 'manage_sites' ) ) {
			$this->save_admin_settings( $all_settings );
		}
	}

	private function save_admin_settings( $all_settings = null ) {
		$admin_settings = $save_admin_settings = array();

		if ( ! empty( $all_settings['admin_settings'] ) ) {
			$admin_settings = $all_settings['admin_settings'];
		}

		if ( ! empty( $admin_settings ) ) {
			if ( ! empty( $admin_settings['options'] ) ) {
				$save_admin_settings['options'] = $this->validate_settings( $admin_settings['options'], $this->get_all_admin_options() );
			} else {
				$save_admin_settings['options'] = '';
			}

			$disabled_editors = array_intersect( $this->get_editor_locations(), explode( ',', $admin_settings['disabled_editors'] ) );
		} elseif ( isset( $_POST['tadv-save'] ) ) {
			if ( ! empty( $_POST['admin_options'] ) && is_array( $_POST['admin_options'] ) ) {
				$save_admin_settings['options'] = $this->validate_settings( $_POST['admin_options'], $this->get_all_admin_options() );
			}

			if ( ! empty( $_POST['tadv_enable_at'] ) && is_array( $_POST['tadv_enable_at'] ) ) {
				$tadv_enable_at = $_POST['tadv_enable_at'];
			} else {
				$tadv_enable_at = array();
			}

			$disabled_editors = array_diff( $this->get_editor_locations(), $tadv_enable_at );
		} else {
			return;
		}

		$save_admin_settings['disabled_editors'] = implode( ',', $disabled_editors );

		$this->admin_settings = $save_admin_settings;
		update_option( 'tadv_admin_settings', $save_admin_settings );
	}

	public function settings_page() {
		if ( ! defined( 'TADV_ADMIN_PAGE' ) ) {
			define( 'TADV_ADMIN_PAGE', true );
		}

		include_once( TADV_PATH . 'tadv_admin.php' );
	}

	public function add_menu() {
		add_options_page( 'TinyMCE Advanced', 'TinyMCE Advanced', 'manage_options', 'tinymce-advanced', array( $this, 'settings_page' ) );
	}

	/**
	 * Add a link to the settings page
	 */
	public function add_settings_link( $links, $file ) {
		if ( $file === 'tinymce-advanced/tinymce-advanced.php' && current_user_can( 'manage_options' ) ) {
			$settings_link = sprintf( '<a href="%s">%s</a>', admin_url( 'options-general.php?page=tinymce-advanced' ), __( 'Settings', 'tinymce-advanced' ) );
			array_unshift( $links, $settings_link );
		}

		return $links;
	}
}

new Tinymce_Advanced;
endif;
