<?php
class WPML_Config {
	static $wpml_config_files = array();
    static $active_plugins = array();

	static function load_config() {
		global $pagenow, $sitepress;

		if ( ! is_admin() || wpml_is_ajax() || ( isset( $_POST['action'] ) && $_POST['action'] === 'heartbeat' ) || ! $sitepress || ! $sitepress->get_default_language() ) {
			return;
		}

		$white_list_pages = array(
			'theme_options',
			'plugins.php',
			'themes.php',
			ICL_PLUGIN_FOLDER . '/menu/languages.php',
			ICL_PLUGIN_FOLDER . '/menu/theme-localization.php',
			ICL_PLUGIN_FOLDER . '/menu/translation-options.php',
		);
		if (defined('WPML_ST_FOLDER')) {
			$white_list_pages[] = WPML_ST_FOLDER . '/menu/string-translation.php';
		}
		if(defined('WPML_TM_FOLDER')) {
			$white_list_pages[] = WPML_TM_FOLDER . '/menu/main.php';
		}

		//Runs the load config process only on specific pages
		$current_page = isset($_GET[ 'page' ]) ? $_GET[ 'page' ] : null;
		if((isset( $current_page ) && in_array( $current_page, $white_list_pages)) || (isset($pagenow) && in_array($pagenow, $white_list_pages))) {
			self::load_config_run();
		}
	}

	static function load_config_run() {
		global $sitepress;
		self::load_config_pre_process();
		self::load_plugins_wpml_config();
		self::load_theme_wpml_config();
		self::parse_wpml_config_files();
		self::load_config_post_process();
		$sitepress->save_settings();
	}

	static function get_custom_fields_translation_settings($translation_actions = array(0)) {
		$iclTranslationManagement = wpml_load_core_tm ();
		$section          = 'custom_fields_translation';

		$result = array();
		$tm_settings = $iclTranslationManagement->settings;
		if(isset( $tm_settings[ $section ])) {
			foreach ( $tm_settings[ $section ] as $meta_key => $translation_type ) {
				if ( in_array($translation_type, $translation_actions) ) {
					$result[] = $meta_key;
				}
			}
		}

		return $result;
	}

	static function parse_wpml_config( $config ) {
		global $sitepress, $sitepress_settings, $iclTranslationManagement;

		// custom fields
		self::parse_custom_fields( $config );
		$settings_helper = wpml_load_settings_helper();
		$tm_settings_tax = new WPML_TM_Settings_Update( 'taxonomy',
		                                                'taxonomies',
		                                                $iclTranslationManagement,
		                                                $sitepress,
		                                                $settings_helper );
		$tm_settings_cpt = new WPML_TM_Settings_Update( 'custom-type',
		                                                'custom-types',
		                                                $iclTranslationManagement,
		                                                $sitepress,
		                                                $settings_helper );
		$tm_settings_tax->update_from_config( $config['wpml-config'] );
		$tm_settings_cpt->update_from_config( $config['wpml-config'] );

		// language-switcher-settings
		if ( empty( $sitepress_settings[ 'language_selector_initialized' ] ) || ( isset( $_GET[ 'restore_ls_settings' ] ) && $_GET[ 'restore_ls_settings' ] == 1 ) ) {
			if ( !empty( $config[ 'wpml-config' ][ 'language-switcher-settings' ] ) ) {

				if ( !is_numeric( key( $config[ 'wpml-config' ][ 'language-switcher-settings' ][ 'key' ] ) ) ) {
					$cfgsettings[ 0 ] = $config[ 'wpml-config' ][ 'language-switcher-settings' ][ 'key' ];
				} else {
					$cfgsettings = $config[ 'wpml-config' ][ 'language-switcher-settings' ][ 'key' ];
				}
				$iclsettings = $iclTranslationManagement->read_settings_recursive( $cfgsettings );
				$iclsettings[ 'language_selector_initialized' ] = 1;
				$sitepress->save_settings( $iclsettings );

				if ( !empty( $sitepress_settings[ 'setup_complete' ] ) && !empty( $_GET[ 'page' ] ) ) {
					wp_redirect( admin_url( 'admin.php?page=' . $_GET[ 'page' ] . '&icl_ls_reset=default#icl_save_language_switcher_options' ) );
				}
			}
		}

		return $config;
	}

	static function load_config_post_process() {
		global $iclTranslationManagement;

		$changed = false;
		if ( isset( $iclTranslationManagement->settings[ '__custom_types_readonly_config_prev' ] ) ) {
			foreach ( $iclTranslationManagement->settings[ '__custom_types_readonly_config_prev' ] as $pk => $pv ) {
				if ( !isset( $iclTranslationManagement->settings[ 'custom_types_readonly_config' ][ $pk ] ) || $iclTranslationManagement->settings[ 'custom_types_readonly_config' ][ $pk ] != $pv ) {
					$changed = true;
					break;
				}
			}
		}
		if ( isset( $iclTranslationManagement->settings[ 'custom_types_readonly_config' ] ) ) {
			foreach ( $iclTranslationManagement->settings[ 'custom_types_readonly_config' ] as $pk => $pv ) {
				if ( !isset( $iclTranslationManagement->settings[ '__custom_types_readonly_config_prev' ][ $pk ] ) || $iclTranslationManagement->settings[ '__custom_types_readonly_config_prev' ][ $pk ] != $pv ) {
					$changed = true;
					break;
				}
			}
		}
		if ( isset( $iclTranslationManagement->settings[ 'custom_fields_readonly_config' ]  ) && isset($iclTranslationManagement->settings[ '__custom_fields_readonly_config_prev' ]) ) {
			foreach ( $iclTranslationManagement->settings[ '__custom_fields_readonly_config_prev' ] as $cf ) {
				if ( !in_array( $cf, $iclTranslationManagement->settings[ 'custom_fields_readonly_config' ] ) ) {
					$changed = true;
					break;
				}
			}

			foreach ( $iclTranslationManagement->settings[ 'custom_fields_readonly_config' ] as $cf ) {
				if ( !in_array( $cf, $iclTranslationManagement->settings[ '__custom_fields_readonly_config_prev' ] ) ) {
					$changed = true;
					break;
				}
			}
		}

		if ( $changed ) {
			$iclTranslationManagement->save_settings();
		}
	}

	static function load_config_pre_process() {
		global $iclTranslationManagement;
		$tm_settings = $iclTranslationManagement->settings;

		if ( ( isset( $tm_settings[ 'custom_types_readonly_config' ] ) && is_array( $tm_settings[ 'custom_types_readonly_config' ] ) ) ) {
			$iclTranslationManagement->settings[ '__custom_types_readonly_config_prev' ] = $tm_settings[ 'custom_types_readonly_config' ];
		} else {
			$iclTranslationManagement->settings[ '__custom_types_readonly_config_prev' ] = array();
		}
		$iclTranslationManagement->settings[ 'custom_types_readonly_config' ] = array();

		if ( ( isset( $tm_settings[ 'custom_fields_readonly_config' ] ) && is_array( $tm_settings[ 'custom_fields_readonly_config' ] ) ) ) {
			$iclTranslationManagement->settings[ '__custom_fields_readonly_config_prev' ] = $tm_settings[ 'custom_fields_readonly_config' ];
		} else {
			$iclTranslationManagement->settings[ '__custom_fields_readonly_config_prev' ] = array();
		}
		$iclTranslationManagement->settings[ 'custom_fields_readonly_config' ] = array();
	}

	static function load_plugins_wpml_config() {
		if ( is_multisite() ) {
			// Get multi site plugins
			$plugins = get_site_option( 'active_sitewide_plugins' );
			if ( !empty( $plugins ) ) {
				foreach ( $plugins as $p => $dummy ) {
                    if(!self::check_on_config_file($dummy)){
                        continue;
                    }
					$plugin_slug = dirname( $p );
					$config_file = WP_PLUGIN_DIR . '/' . $plugin_slug . '/wpml-config.xml';
					if ( trim( $plugin_slug, '\/.' ) && file_exists( $config_file ) ) {
						self::$wpml_config_files[ ] = $config_file;
					}
				}
			}
		}

		// Get single site or current blog active plugins
		$plugins = get_option( 'active_plugins' );
		if ( !empty( $plugins ) ) {
			foreach ( $plugins as $p ) {
                if(!self::check_on_config_file($p)){
                    continue;
                }

				$plugin_slug = dirname( $p );
				$config_file = WP_PLUGIN_DIR . '/' . $plugin_slug . '/wpml-config.xml';
				if ( trim( $plugin_slug, '\/.' ) && file_exists( $config_file ) ) {
					self::$wpml_config_files[ ] = $config_file;
				}
			}
		}

		// Get the must-use plugins
		$mu_plugins = wp_get_mu_plugins();

		if ( !empty( $mu_plugins ) ) {
			foreach ( $mu_plugins as $mup ) {
                if(!self::check_on_config_file($mup)){
                    continue;
                }

				$plugin_dir_name  = dirname( $mup );
				$plugin_base_name = basename( $mup, ".php" );
				$plugin_sub_dir   = $plugin_dir_name . '/' . $plugin_base_name;
				if ( file_exists( $plugin_sub_dir . '/wpml-config.xml' ) ) {
					$config_file                = $plugin_sub_dir . '/wpml-config.xml';
					self::$wpml_config_files[ ] = $config_file;
				}
			}
		}

		return self::$wpml_config_files;
	}

    static function check_on_config_file( $name ){

        if(empty(self::$active_plugins)){
            if ( ! function_exists( 'get_plugins' ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            self::$active_plugins = get_plugins();
        }
        $config_index_file_data = maybe_unserialize(get_option('wpml_config_index'));
        $config_files_arr = maybe_unserialize(get_option('wpml_config_files_arr'));

        if(!$config_index_file_data || !$config_files_arr){
            return true;
        }


        if(isset(self::$active_plugins[$name])){
            $plugin_info = self::$active_plugins[$name];
            $plugin_slug = dirname( $name );
            $name = $plugin_info['Name'];
            $config_data = $config_index_file_data->plugins;
            $config_files_arr = $config_files_arr->plugins;
            $config_file = WP_PLUGIN_DIR . '/' . $plugin_slug . '/wpml-config.xml';
            $type = 'plugin';

        }else{
            $config_data = $config_index_file_data->themes;
            $config_files_arr = $config_files_arr->themes;
            $config_file = get_template_directory() . '/wpml-config.xml';
            $type = 'theme';
        }

        foreach($config_data as $item){
            if($name == $item->name && isset($config_files_arr[$item->name])){
                if($item->override_local || !file_exists( $config_file )){
                    end(self::$wpml_config_files);
                    $key = key(self::$wpml_config_files)+1;
                    self::$wpml_config_files[$key] = new stdClass();
                    self::$wpml_config_files[$key]->config = icl_xml2array($config_files_arr[$item->name]);
                    self::$wpml_config_files[$key]->type = $type;
                    self::$wpml_config_files[$key]->admin_text_context = basename( dirname( $config_file ) );
                    return false;
                }else{
                    return true;
                }
            }
        }

        return true;

    }

	static function load_theme_wpml_config() {
        $theme_data = wp_get_theme();
        if(!self::check_on_config_file($theme_data->get('Name'))){
            return self::$wpml_config_files;
        }

		if ( get_template_directory() != get_stylesheet_directory() ) {
			$config_file = get_stylesheet_directory() . '/wpml-config.xml';
			if ( file_exists( $config_file ) ) {
				self::$wpml_config_files[ ] = $config_file;
			}
		}

		$config_file = get_template_directory() . '/wpml-config.xml';
		if ( file_exists( $config_file ) ) {
			self::$wpml_config_files[ ] = $config_file;
		}

		return self::$wpml_config_files;
	}
	
	static function get_theme_wpml_config_file() {
		if ( get_template_directory() != get_stylesheet_directory() ) {
			$config_file = get_stylesheet_directory() . '/wpml-config.xml';
			if ( file_exists( $config_file ) ) {
				return $config_file;
			}
		}

		$config_file = get_template_directory() . '/wpml-config.xml';
		if ( file_exists( $config_file ) ) {
			return $config_file;
		}
		
		return false;
		
	}

	static function parse_wpml_config_files() {
		$config_all[ 'wpml-config' ] = array(
			'custom-fields'              => array(),
			'custom-types'               => array(),
			'taxonomies'                 => array(),
			'admin-texts'                => array(),
			'language-switcher-settings' => array()
		);

		if ( !empty( self::$wpml_config_files ) ) {
			foreach ( self::$wpml_config_files as $file ) {
				$config = is_object( $file ) ? $file->config : icl_xml2array( file_get_contents( $file ) );
				do_action( 'wpml_parse_config_file', $file );
				if ( isset( $config[ 'wpml-config' ] ) ) {
                    $wpml_config     = $config[ 'wpml-config' ];
                    $wpml_config_all = $config_all[ 'wpml-config' ];
                    $wpml_config_all = self::parse_config_index($wpml_config_all, $wpml_config, 'custom-field', 'custom-fields');
					$wpml_config_all = self::parse_config_index($wpml_config_all, $wpml_config, 'custom-type', 'custom-types');
                    $wpml_config_all = self::parse_config_index($wpml_config_all, $wpml_config, 'taxonomy', 'taxonomies');

					//language-switcher-settings
					if ( isset( $wpml_config[ 'language-switcher-settings' ][ 'key' ] ) ) {
						if ( !is_numeric( key( $wpml_config[ 'language-switcher-settings' ][ 'key' ] ) ) ) { //single
							$wpml_config_all[ 'language-switcher-settings' ][ 'key' ][ ] = $wpml_config[ 'language-switcher-settings' ][ 'key' ];
						} else {
							foreach ( $wpml_config[ 'language-switcher-settings' ][ 'key' ] as $cf ) {
								$wpml_config_all[ 'language-switcher-settings' ][ 'key' ][ ] = $cf;
							}
						}
					}
                    $config_all[ 'wpml-config' ] = $wpml_config_all;
				}
			}

			$config_all = apply_filters( 'icl_wpml_config_array', $config_all );
			$config_all = apply_filters( 'wpml_config_array', $config_all );
		}

		self::parse_wpml_config( $config_all );
	}

	/**
	 * @param $config
	 *
	 * @return mixed
	 */
	protected static function parse_custom_fields( $config ) {
		global $iclTranslationManagement;
		if ( ! empty( $config['wpml-config']['custom-fields'] ) ) {
			$field = $config['wpml-config']['custom-fields']['custom-field'];
			$cf    = ! is_numeric( key( current( $config['wpml-config']['custom-fields'] ) ) ) ? array( $field ) : $field;
			foreach ( $cf as $c ) {
				if ( $c['attr']['action'] == 'translate' ) {
					$action = 2;
				} elseif ( $c['attr']['action'] == 'copy' ) {
					$action = 1;
				} else {
					$action = 0;
				}
				$iclTranslationManagement->settings['custom_fields_translation'][ $c['value'] ] = $action;
				$custom_fields_readonly_config_set                                              = isset( $iclTranslationManagement->settings['custom_fields_readonly_config'] ) && is_array( $iclTranslationManagement->settings['custom_fields_readonly_config'] );
				if ( ! $custom_fields_readonly_config_set ) {
					$iclTranslationManagement->settings['custom_fields_readonly_config'] = array();
				}
				if ( ! in_array( $c['value'], $iclTranslationManagement->settings['custom_fields_readonly_config'] ) ) {
					$iclTranslationManagement->settings['custom_fields_readonly_config'][] = $c['value'];
				}
			}
		}
	}

	private static function parse_config_index( $config_all, $wpml_config, $index_sing, $index_plur ) {
		if ( isset( $wpml_config[ $index_plur ][ $index_sing ] ) ) {
			if ( isset( $wpml_config[ $index_plur ][ $index_sing ]['value'] ) ) { //single
				$config_all[ $index_plur ][ $index_sing ][] = $wpml_config[ $index_plur ][ $index_sing ];
			} else {
				foreach ( (array) $wpml_config[ $index_plur ][ $index_sing ] as $cf ) {
					$config_all[ $index_plur ][ $index_sing ][] = $cf;
				}
			}
		}

		return $config_all;
	}
}
