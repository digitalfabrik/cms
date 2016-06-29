<?php

if ( !class_exists( 'TranslationProxy_Com_Log' ) ) {
	class TranslationProxy_Com_Log {
		
		public static function log_call( $url, $params ) {
			if ( isset( $params[ 'accesskey' ] ) ) {
				$params[ 'accesskey' ] = 'UNDISCLOSED';
			}
			if ( isset( $params[ 'job' ] [ 'file' ] ) ) {
				$params[ 'job' ] [ 'file' ] = 'UNDISCLOSED';
			}
			$custom_fields_to_block  = array(
				'api_token',
				'username',
				'api_key',
				'sitekey'
			);
			$params['custom_fields'] = isset( $params['custom_fields'] ) ? (array) $params['custom_fields'] : array();
			foreach ( $custom_fields_to_block as $custom_field ) {
				if ( isset( $params[ 'custom_fields' ] [ $custom_field ] ) ) {
					$params[ 'custom_fields' ] [ $custom_field ] = 'UNDISCLOSED';
				}
				if ( isset( $params[ 'project' ][ $custom_field ] ) ) {
					$params[ 'project' ] [ $custom_field ] = 'UNDISCLOSED';
				}
			}
			
			// strip accesskey from url if required
			$pos = strpos( $url, 'accesskey=' );
			if ( $pos > 0 ) {
				$amp_pos = strpos( $url, '&', $pos );
				
				$accesskey = substr( $url, $pos + 10, $amp_pos - $pos - 10);
				
				$url = str_replace( $accesskey, 'UNDISCLOSED', $url) ;
			}
			
			self::add_to_log( 'call - ' . $url . ' - ' . json_encode( $params ) );
		}
		
		public static function log_response( $response ) {
			self::add_to_log( 'response - ' . $response );
		}

		public static function log_error( $message ) {
			self::add_to_log( 'error - ' . $message );
		}
		
		public static function log_xml_rpc( $data ) {
			self::add_to_log('xml-rpc - ' . json_encode( $data ) );
		}

		public static function get_log( ) {
			return get_option( 'wpml_tp_com_log', '' );
		}
		
		public static function clear_log( ) {
			self::save_log( '' );
		}
		
		public static function is_logging_enabled( ) {
			global $sitepress;
			
			return $sitepress->get_setting( 'tp-com-logging', true );
		}
		
		public static function set_logging_state( $state ) {
			global $sitepress;
				
			$sitepress->set_setting( 'tp-com-logging', $state );
			$sitepress->save_settings( );
		}
		
		public static function add_com_log_link( ) {
			if ( self::get_log() != '' ) {
				$url = esc_attr( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&sm=com-log' );
				?>
				<p style="margin-top: 20px;">
				    <?php printf(__('For retrieving debug information for communication between your site and the translation system, use the <a href="%s">communication log</a> page.', 'sitepress'), $url ); ?>
				</p>
				<?php
			}
		}
		
		private static function now( ) {
			return date( 'm/d/Y h:i:s a', time() );
		}
		
		private static function add_to_log( $string ) {
			
			if ( self::is_logging_enabled( ) ) {
				
				$max_log_length = 10000;
				
				$string = self::now( ) . ' - ' . $string;
				
				$log = self::get_log( );
				$log .= $string;
				$log .= PHP_EOL;
				
				$log_length = strlen( $log );
				if ( $log_length > $max_log_length ) {
					$log = substr( $log, $log_length - $max_log_length );
				}
				
				self::save_log( $log );
			}
		}
		
		private static function save_log( $log ) {
			update_option( 'wpml_tp_com_log', $log );
		}

		
	}
}
