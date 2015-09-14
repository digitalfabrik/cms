<?php
/**
 * Fetch the wpml config files for known plugins and themes
 *
 * @package wpml-core
 */

function update_wpml_config_index_event() {
	$wp_http_class = new WP_Http();
	$response      = $wp_http_class->get( ICL_REMOTE_WPML_CONFIG_FILES_INDEX . 'wpml-config/config-index.json' );

	if ( ! is_wp_error( $response ) && $response[ 'response' ][ 'code' ] == 200 ) {
		$arr = json_decode( $response[ 'body' ] );

		if ( isset( $arr->plugins ) && isset( $arr->themes ) ) {
			update_option( 'wpml_config_index', $arr );
			update_option( 'wpml_config_index_updated', time() );

			$config_files = maybe_unserialize( get_option( 'wpml_config_files_arr' ) );

			$config_files_for_themes     = array();
			$deleted_configs_for_themes  = array();
			$config_files_for_plugins    = array();
			$deleted_configs_for_plugins = array();
			if ( $config_files ) {
				if ( isset( $config_files->themes ) ) {
					$config_files_for_themes    = $config_files->themes;
					$deleted_configs_for_themes = $config_files->themes;
				}
				if ( isset( $config_files->plugins ) ) {
					$config_files_for_plugins    = $config_files->plugins;
					$deleted_configs_for_plugins = $config_files->plugins;
				}
			}
			$wp_http_class = new WP_Http();

			$theme_data = wp_get_theme();

			foreach ( $arr->themes as $theme ) {

				if ( $theme_data->get( 'Name' ) == $theme->name && ( ! isset( $config_files_for_themes[ $theme->name ] ) || md5( $config_files_for_themes[ $theme->name ] ) != $theme->hash ) ) {
					$response = $wp_http_class->get( ICL_REMOTE_WPML_CONFIG_FILES_INDEX . $theme->path );
					if ( $response[ 'response' ][ 'code' ] == 200 ) {
						$config_files_for_themes[ $theme->name ] = $response[ 'body' ];
					}
				}

				if ( isset( $deleted_configs_for_themes[ $theme->name ] ) ) {
					unset( $deleted_configs_for_themes[ $theme->name ] );
				}
			}

			foreach ( $deleted_configs_for_themes as $key => $deleted_config ) {
				unset( $config_files_for_themes[ $key ] );
			}

			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$active_plugins = get_plugins();

			$active_plugins_names = array();
			foreach ( $active_plugins as $active_plugin ) {
				$active_plugins_names[ ] = $active_plugin[ 'Name' ];
			}

			foreach ( $arr->plugins as $plugin ) {

				if ( in_array( $plugin->name, $active_plugins_names ) && ( ! isset( $config_files_for_plugins[ $plugin->name ] ) || md5( $config_files_for_plugins[ $plugin->name ] ) != $plugin->hash ) ) {
					$response = $wp_http_class->get( ICL_REMOTE_WPML_CONFIG_FILES_INDEX . $plugin->path );

					if ( ! is_wp_error( $response ) && $response[ 'response' ][ 'code' ] == 200 ) {
						$config_files_for_plugins[ $plugin->name ] = $response[ 'body' ];
					}
				}

				if ( isset( $deleted_configs_for_plugins[ $plugin->name ] ) ) {
					unset( $deleted_configs_for_plugins[ $plugin->name ] );
				}
			}

			foreach ( $deleted_configs_for_plugins as $key => $deleted_config ) {
				unset( $config_files_for_plugins[ $key ] );
			}

			if ( ! isset( $config_files ) || ! $config_files ) {
				$config_files = new stdClass();
			}
			$config_files->themes  = $config_files_for_themes;
			$config_files->plugins = $config_files_for_plugins;

			update_option( 'wpml_config_files_arr', $config_files );

			return true;
		}
	}

	return false;
}