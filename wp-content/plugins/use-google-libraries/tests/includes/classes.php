<?php

if ( class_exists( 'JCP_UseGoogleLibraries' ) ) {
	class JCP_UseGoogleLibraries_Test extends JCP_UseGoogleLibraries {
		public static function get_instance() {
			if ( ! isset( self::$instance ) || ! is_a( self::$instance, 'JCP_UseGoogleLibraries_Test' ) ) {
				self::$instance = new JCP_UseGoogleLibraries_Test();
			}
			return self::$instance;
		}

		public function get_protocol_relative_supported() {
			return $this->protocol_relative_supported;
		}

		public function get_google_scripts() {
			return $this->google_scripts;
		}

		public function get_version() {
			return self::$version;
		}

		public function get_plugin_file() {
			return self::$plugin_file;
		}

		public function get_jquery_tag() {
			return $this->jquery_tag;
		}

		public function newscripts_fix_jquery_core( &$scripts ) {
			parent::newscripts_fix_jquery_core( $scripts );
		}

		public function newscripts_build_url( $name, $lib, $ver, $js, $orig_url ) {
			return parent::newscripts_build_url(
				$name, $lib, $ver, $js, $orig_url
			);
		}

		public function get_noconflict_next( ) {
			return $this->noconflict_next;
		}

		public function get_noconflict_inject() {
			return self::$noconflict_inject;
		}

		public function set_noconflict_next( $value ) {
			$this->noconflict_next = $value;
		}
	}
}

class UGL_UnitTestCase extends WP_UnitTestCase {

	function setUp() {
		parent::setUp();
		$this->ugl = null;
		if ( class_exists( 'JCP_UseGoogleLibraries' ) ) {
			$this->ugl = JCP_UseGoogleLibraries_Test::get_instance();
		} else {
			$this->markTestSkipped( 'Use Google Libraries not loaded' );
		}
	}

}

class UGL_ScriptTestCase extends UGL_UnitTestCase {

	function setUp() {
		parent::setUp();
		$scripts = new WP_Scripts();
		wp_default_scripts( $scripts );
		$this->scripts = $scripts;
	}
}
