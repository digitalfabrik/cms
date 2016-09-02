<?php

/**
 * @author OnTheGo Systems
 */
class WPML_Notice {
	private $id;
	private $text;
	private $group = 'default';

	private $actions            = array();
	/**
	 * @see \WPML_Notice::set_css_class_types
	 * @var array
	 */
	private $css_class_types = array();
	private $css_classes        = array();
	private $dismiss_per_user   = false;
	private $dismissible        = false;
	private $exclude_from_pages = array();
	private $hidden             = false;
	private $hideable           = false;
	private $restrict_to_pages  = array();
	private $users           = array();

	private $default_group_name = 'default';

	/**
	 * WPML_Admin_Notification constructor.
	 *
	 * @param int    $id
	 * @param string $text
	 * @param string $group
	 */
	public function __construct( $id, $text, $group = 'default' ) {
		$this->id    = $id;
		$this->text  = $text;
		$this->group = $group ? $group : $this->default_group_name;
	}

	public function add_action( WPML_Notice_Action $action ) {
		$this->actions[] = $action;

		if ( $action->can_dismiss() ) {
			$this->dismissible = true;
		}
		if ( $action->can_hide() ) {
			$this->hideable = true;
		}
	}

	public function add_exclude_from_page( $page ) {
		$this->exclude_from_pages[] = $page;
	}

	public function add_restrict_to_page( $page ) {
		$this->restrict_to_pages[] = $page;
	}

	public function can_be_dismissed() {
		return $this->dismissible;
	}

	public function can_be_hidden() {
		return $this->hideable;
	}

	public function get_actions() {
		return $this->actions;
	}

	public function get_css_classes() {
		return $this->css_classes;
	}

	/**
	 * @param string|array $css_classes
	 */
	public function set_css_classes( $css_classes ) {
		if ( ! is_array( $css_classes ) ) {
			$css_classes = explode( ' ', $css_classes );
		}
		$this->css_classes = $css_classes;
	}

	public function get_exclude_from_pages() {
		return $this->exclude_from_pages;
	}

	/**
	 * @return string
	 */
	public function get_group() {
		return $this->group;
	}

	/**
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	public function get_restrict_to_pages() {
		return $this->restrict_to_pages;
	}

	/**
	 * @return string
	 */
	public function get_text() {
		return $this->text;
	}

	public function get_css_class_types() {
		return $this->css_class_types;
	}

	/**
	 * Use this to set the look of the notice.
	 * WordPress recognize these values:
	 * - notice-error
	 * - notice-warning
	 * - notice-success
	 * - notice-info
	 * You can use the above values with or without the "notice-" prefix:
	 * the prefix will be added automatically in the HTML, if missing.
	 * @see https://codex.wordpress.org/Plugin_API/Action_Reference/admin_notices for more details
	 *
	 * @param string|array $types Accepts either a space separated values string, or an array of values.
	 */
	public function set_css_class_types( $types ) {
		if ( ! is_array( $types ) ) {
			$types = explode( ' ', $types );
		}
		$this->css_class_types = $types;
	}

	/**
	 * @param bool $dismissible
	 */
	public function set_dismissible( $dismissible ) {
		$this->dismissible = $dismissible;
	}

	public function set_exclude_from_pages( array $pages ) {
		$this->exclude_from_pages = $pages;
	}

	/**
	 * @param bool $hideable
	 */
	public function set_hideable( $hideable ) {
		$this->hideable = $hideable;
	}

	public function set_restrict_to_pages( array $pages ) {
		$this->restrict_to_pages = $pages;
	}
}
