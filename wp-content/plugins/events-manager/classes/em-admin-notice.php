<?php
/**
 * A single admin notice which contains information about who to display it to, what to dispaly, when and where to display it.
 * @since 5.8.2.0
 */
class EM_Admin_Notice {
	
	/**
	 * Notice key
	 * @var string
	 */
	public $name = '';
	/**
	 * Which user should see this message. Can be 'admin', 'all' (or false), or a specific capability.
	 * Note that 'admin' in MultiSite context is considered a super admin, use the 'manage_options' cap instead.
	 * @var string
	 */
	public $who = 'admin';
	/**
	 * What kind of notices this is, which can be 'success','info','warning' or 'error'
	 * @var string
	 */
	public $what = 'info';
	/**
	 * Timestamp indicating when a notice should be shown. If empty, message will show immediately.
	 * @var int
	 */
	public $when;
	/**
	 * Where a message should be shown. Values accepted are 'all' (all pages), 'network_admin', 'plugin' (plugin-specific pages), 'settings'
	 * or any value representing an admin page in the events admin menu, e.g. 'events-manager-bookings' would be the bookings admin page.
	 *
	 * @var string
	 */
	public $where = 'settings';
	/**
	 * The actual message that will be displayed. If left blank, a filter will be applied upon output with format
	 * em_admin_notice_output_{$this->name}
	 * @var string
	 */
	public $message = false; //the message
	/**
	 * Whether a message is dismissable
	 * @var boolean
	 */
	public $dismissible = true;
	/**
	 * If a message is dismissable and this is set to true, it will be shown to every user matching the who property until dismissed.
	 * This is also set to true by default if the user type is not 'admin' and not previously set to true or false by the em_admin_notice_ hook.
	 * @var boolean
	 */
	protected $user_notice = null;
	/**
	 * If set to true, this is treated as a network-level notice, meaning it can apply to all sites on the network or the network admin in MultiSite mode.
	 * @var bool
	 */
	public $network = false;
	
	public function __construct( $key, $type = false, $message = false ){
		//process the supplied data
		if( empty($message) ){
			if( empty($type) && is_array($key) ){
				$notice = $key;
			}elseif( is_array($type) ){
				$this->name = $key;
				$notice = $type;
			}elseif( is_array($key) ){
				$notice = $key;
			}else{
				//we may even have simply a key/name for this notice, for hooking later on
				if( is_string($key) ) $this->name = $key;
				$notice = array();
			}
		}else{
			//here we expect a string for eveything
			$notice = array('name'=> (string) $key, 'what' => (string) $type, 'message' => (string) $message) ;
		}
		//we should have an array to process at this point
		foreach( $notice as $key => $value ){
			$this->$key = $value;
		}
		//call a hook
		do_action('em_admin_notice_'.$this->name, $this);
		if( !is_multisite() && $this->where == 'network_admin' ) $this->where = 'settings';
	}
	
	public function __set( $prop, $val ){
		$this->$prop = $val;
	}
	
	public function __get( $prop ){
		if( $prop == 'user_notice' ){
			return $this->is_user_notice();
		}
	}
	
	/**
	 * Returns whether or not this object should be dismissed on a per-user basis.
	 * @return boolean
	 */
	public function is_user_notice(){
		if( $this->who != 'admin' && $this->user_notice === null ){
			//user_notice was not specifically set, so if notice is dismissible and not targetted at admins we assume it's dismissed at per-user basis
			return $this->dismissible;
		}
		return $this->user_notice;
	}
	
	/**
	 * Returns notice as an array with non-default values.
	 * @return array
	 */
	public function to_array(){
		$default = new EM_Admin_Notice('default');
		$notice = array();
		foreach( get_class_vars('EM_Admin_Notice') as $var => $val ){
			if( $this->$var != $default->$var ) $notice[$var] = $this->$var;
		}
		return $notice;
	}
	
	public function can_show(){
		//check that we have at least a notice to show
		if( empty($this->name) ) return false;
		//can we display due to time?
		$return = ( empty($this->when) || $this->when <= time() );
		//who to display it to
		if( $return && !empty($this->who) && $this->who != 'all' ){
			$return = false; //unless this test passes, don't show it
			if( $this->who == 'all' ) $return = true;
			elseif ( $this->who == 'admin' ){
				if( $this->network && em_wp_is_super_admin() ) $return = true;
				elseif( current_user_can('manage_options') ) $return = true;
			}
			elseif( $this->who == 'blog_admin' && current_user_can('manage_options') ) $return = true;
			elseif( !$return && current_user_can($this->who) ) $return = true;
		}
		//can we display due to location?
		if( $return ){
			$return = false; //unless this test passes, don't show it
			if( empty($this->where) || $this->where == 'all' ){
				$return = true;
			}elseif( !empty($_REQUEST['post_type']) && in_array($_REQUEST['post_type'], array(EM_POST_TYPE_EVENT, EM_POST_TYPE_LOCATION, 'event-recurring')) ){
				if( $this->where == 'plugin' ) $return = true;
				elseif( empty($_REQUEST['page']) && in_array($this->where, array(EM_POST_TYPE_EVENT, EM_POST_TYPE_LOCATION, 'event-recurring')) ) $return = true;
				elseif( $this->where == 'settings' && !empty($_REQUEST['page']) && $_REQUEST['page'] == 'events-manager-options' ) $return = true;
				elseif( !empty($_REQUEST['page']) && $this->where == $_REQUEST['page'] ) $return = true;
			}elseif( is_network_admin() && !empty($_REQUEST['page']) && preg_match('/^events\-manager\-/', $_REQUEST['page']) ){
				$return = $this->where == 'plugin' || $this->where == 'settings' || $this->where == 'network_admin';
			}
		}
		//does this even have a message we can display?
		if( $return && empty($this->message)){
			$this->message = apply_filters('em_admin_notice_'.$this->name .'_message', false, $this);
			$return = !empty($this->message);
		}
		//is this user-dismissable, and if so, did this user dismiss it?
		if( $return && $this->is_user_notice() ){
			$user_id = get_current_user_id();
			$dismissed_notices = get_user_meta( $user_id, '_em_dismissed_notices', true);
			$return = empty($dismissed_notices) || !in_array($this->name, $dismissed_notices);
		}
		return $return;
	}
	
	public function output(){
		if( empty($this->message) ) return false;
		$action = $this->network ? 'em_dismiss_network_admin_notice':'em_dismiss_admin_notice';
		?>
		<div class="em-admin-notice notice notice-<?php echo esc_attr($this->what); ?> <?php if($this->dismissible) echo 'is-dismissible'?>" data-dismiss-action="<?php echo $action; ?>" data-dismiss-key="<?php echo esc_attr($this->name); ?>">
			<p><?php echo $this->message; ?></p>
		</div>
		<?php
		return true;
	}
}