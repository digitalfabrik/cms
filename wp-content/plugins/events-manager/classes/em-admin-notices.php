<?php
/**
 * Handles the registration and display of admin notices, including storage and retrieval of individual EM_Admin_Notice notice objects. 
 * @since 5.8.2.0
 */
class EM_Admin_Notices {
	
	/**
	 * Flag for whether or not to add dismissable notice JS to admin page footer.
	 * @var boolean
	 */
	public static $js_footer = false;
	
	/**
	 * Initialize EM Admin Notices by adding the relevant hooks. 
	 */
	public static function init(){
		add_action('admin_notices', 'EM_Admin_Notices::admin_notices');
		add_action('wp_ajax_em_dismiss_admin_notice', 'EM_Admin_Notices::dismiss_admin_notice');
		if( is_multisite() ){
			add_action('admin_notices', 'EM_Admin_Notices::network_admin_notices');
			add_action('network_admin_notices', 'EM_Admin_Notices::network_admin_notices');
			add_action('wp_ajax_em_dismiss_network_admin_notice', 'EM_Admin_Notices::dismiss_admin_notice');
		}
	}
	
	/**
	 * Adds an admin notice to the site. If $network is set to true, notice will be saved at network level.
	 * If a string is provided as $EM_Admin_Notice, it will be considered as a notice requiring a hook to ouptut anything.
	 * If a notice with an identical key is provided, it will overwrite the previously stored notice.
	 * When adding a notice that all users will see and can dismiss, it's recommended you use a hook to build the EM_Admin_Notice object, to avoid storing unecessary data in the DB
	 * @param EM_Admin_Notice|string $EM_Admin_Notice
	 * @param boolean $network
	 * @return boolean Returns true if added successfully, false if not or if the exact same record exists.
	 */
	public static function add( $EM_Admin_Notice, $network = false ){
		$network = $network && is_multisite(); //make sure we are actually in multisite!
		if( is_string($EM_Admin_Notice) ){
		    $EM_Admin_Notice = new EM_Admin_Notice( $EM_Admin_Notice );
		    $hook_notice = true;
		}
		if( !$EM_Admin_Notice->name ) return false;
		//get options data
		$data = $network ? get_site_option('dbem_data') : get_option('dbem_data');
		$data = empty($data) ? array() : maybe_unserialize($data);
		if( !is_array($data)) $data = array();
		$notices_data = $network ? get_site_option('dbem_admin_notices') : get_option('dbem_admin_notices');
		$notices_data = empty($notices_data) ? array() : maybe_unserialize($notices_data);
		if( !is_array($notices_data)) $notices_data = array(); //we store the data regarldess of whether a message will require a hook, since it contains location and caps considtions
		//start building data
		$notices = !empty($data['admin_notices']) ? $data['admin_notices'] : array();
		$notices[$EM_Admin_Notice->name] = !empty($EM_Admin_Notice->when) ? $EM_Admin_Notice->when : 0;
		if( empty($hook_notice) ){ //we only skip this if simply a key is provided initially in $EM_Admin_Notice
            $notices_data[$EM_Admin_Notice->name] = $EM_Admin_Notice->to_array();
        }
		if( !empty($notices) ){
			$data['admin_notices'] = $notices;
			$update_notices =  $network ? update_site_option('dbem_data', $data) : update_option('dbem_data', $data);
			$update_notices_data = true;
			if( !empty($notices_data) ){
				$update_notices_data =  $network ? update_site_option('dbem_admin_notices', $notices_data) : update_option('dbem_admin_notices', $notices_data, false);
			}
			return $update_notices && $update_notices_data;
		}
		return false;
	}
	
	/**
	 * Remove an admin notice. If $network is true, then a network-level admin notice will be removed.
	 * @param string $notice_key
	 * @param string $network
	 * @return boolean Returns true if successfully deleted, false if there's an error or if there's nothing to delete. 
	 */
	public static function remove( $notice_key, $network = false ){
		$network = $network && is_multisite(); //make sure we are actually in multisite!
		$data = $network ? get_site_option('dbem_data') : get_option('dbem_data');
		if( !empty($data['admin_notices']) && isset($data['admin_notices'][$notice_key])){
			unset($data['admin_notices'][$notice_key]);
			if( empty($data['admin_notices']) ) unset($data['admin_notices']);
			$result = $update_notices_data = $network ? update_site_option('dbem_data', $data) : update_option('dbem_data', $data);
			$notices_data = $network ? get_site_option('dbem_admin_notices') : get_option('dbem_admin_notices');
			if( !empty($notices_data[$notice_key]) ){
				unset($notices_data[$notice_key]);
				if( empty($notices_data) ){
					$update_notices_data =  $network ? delete_site_option('dbem_admin_notices') : delete_option('dbem_admin_notices');
				}else{
					$update_notices_data =  $network ? update_site_option('dbem_admin_notices', $notices_data) : update_option('dbem_admin_notices', $notices_data, false);
				}
			}
			return $result && $update_notices_data;
		}
		return false;
	}
	
	/**
	 * Adds admin notice to network rather than specific blog. Equivalent to self::add( $EM_Admin_Notice, true );
	 * @see EM_Admin_Notices::add()
	 */
	public static function network_add( $EM_Admin_Notice ){ return self::add( $EM_Admin_Notice, true ); }
	
	/**
	 * Removes admin notice from network rather than specific blog. Equivalent to self::remove( $EM_Admin_Notice, true );
	 * @see EM_Admin_Notices::remove()
	 */
	public static function network_remove( $notice_key ){  return self::remove( $notice_key, true ); }
	
	/**
	 * Output the admin notices we need to output now. If $network is true, MultiSite network messages will be output.
	 * @param string $network
	 */
	public static function admin_notices( $network = false ){
		$notices = array();
		$data = $network ? get_site_option('dbem_data') : get_option('dbem_data');
		$possible_notices = is_array($data) && !empty($data['admin_notices']) ? $data['admin_notices'] : array();
		//we may have something to show, so we make sure that there's something to show right now
		foreach( $possible_notices as $key => $val ){
			//to avoid extra loading etc. we weed out time-based notices that aren't triggered right now 
			if( empty($val) || ($val > 0 && $val < time()) ){
				//we have a match, so we add this to $notices
				$notices[$key] = self::get_notice($key, $network);
			}
		}
		self::output( $notices, $network );
	}
	
	public static function get_notice( $key, $network = false ){
		//build notice object
		$notice_data = $network ? get_site_option('dbem_admin_notices') : get_option('dbem_admin_notices');
		if( empty($notice_data[$key]) || !is_array($notice_data[$key]) ){
			$notice = array('name'=>$key, 'network'=>$network);
		}else{
			$notice = $notice_data[$key];
			$notice['network'] = $network;
		}
		return new EM_Admin_Notice($notice);
	}
	
	/**
	 * Outputs admin notices at network level, same as EM_Admin_Notices::admin_notices(true)
	 * @see EM_Admin_Notices::admin_notices()
	 */
	public static function network_admin_notices(){ self::admin_notices(true); }
	
	/**
	 * Outputs admin notices and calls the dismissable JS to be output at footer of admin page.
	 * If $network is true, only MultiSite network-level notices will be shown.
	 * @param array $notices
	 * @param boolean $network
	 */
	public static function output( $notices, $network = false ){
		foreach( $notices as $EM_Admin_Notice ){
			//output the notice if meant to
			if( $EM_Admin_Notice->can_show() ){
				if( $EM_Admin_Notice->output() ) self::$js_footer = true;
			}
		}
		if( self::$js_footer ){
			add_action('admin_footer', 'EM_Admin_Notices::admin_footer');
		}
	}
	
	/**
	 * If called via AJAX, the notice will be removed. 
	 */
	public static function dismiss_admin_notice(){
		if( empty($_REQUEST['notice']) ) return;
		$key = $_REQUEST['notice'];
		$network = $_REQUEST['action'] == 'em_dismiss_network_admin_notice';
		//get the notice
		$EM_Admin_Notice = self::get_notice($key, $network);
		if( $EM_Admin_Notice->is_user_notice() ){
			//user-specific notices are flagged on the user-level
			$user_id = get_current_user_id();
			$dismissed_notices = get_user_meta( $user_id, '_em_dismissed_notices', true);
			$dismissed_notices = is_array($dismissed_notices) ? $dismissed_notices : array(); 
			if( !in_array($EM_Admin_Notice->name, $dismissed_notices) ){
				$dismissed_notices[] = $EM_Admin_Notice->name;
				$result = update_user_meta( $user_id, '_em_dismissed_notices', $dismissed_notices);
			}
		}else{
			$result = self::remove($_REQUEST['notice'], $network);
		}
		echo !empty($result) ? 'Thou art dismissed!' : 'Thou shall not pass!';
		exit();
	}
	
	/**
	 * Outputs JS for dismissing notices. 
	 */
	public static function admin_footer(){
		?>
		<script type="text/javascript">
		jQuery(document).ready( function($){
			$('.em-admin-notice').on('click', 'button.notice-dismiss', function(e){
				var the_notice = $(this).closest('.em-admin-notice');
				$.get('<?php echo admin_url('admin-ajax.php'); ?>', {'action' : the_notice.data('dismiss-action'), 'notice' : the_notice.data('dismiss-key') });
			});
		});
		</script>
		<?php
	}
}
EM_Admin_Notices::init();