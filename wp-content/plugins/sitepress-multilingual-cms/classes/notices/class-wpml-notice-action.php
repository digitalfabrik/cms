<?php

/**
 * @author OnTheGo Systems
 */
class WPML_Notice_Action {
	private $dismiss;
	private $display_as_button;
	private $hide;
	private $text;
	private $url;

	/**
	 * WPML_Admin_Notice_Action constructor.
	 *
	 * @param string      $text
	 * @param string      $url
	 * @param bool        $dismiss
	 * @param bool        $hide
	 * @param bool|string $display_as_button
	 */
	public function __construct( $text, $url = '#', $dismiss = false, $hide = false, $display_as_button = false ) {
		$this->text              = $text;
		$this->url               = $url;
		$this->dismiss           = $dismiss;
		$this->hide              = $hide;
		$this->display_as_button = $display_as_button;
	}

	public function get_text() {
		return $this->text;
	}

	public function get_url() {
		return $this->url;
	}

	public function can_dismiss() {
		return $this->dismiss;
	}

	public function can_hide() {
		return $this->hide;
	}

	public function must_display_as_button() {
		return $this->display_as_button;
	}
}
