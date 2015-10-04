<?php

/**
 * @medium
 *
 */
class UGL_ScriptTests extends UGL_ScriptTestCase {

	function test_protocol_relative_url() {
		$jquery_tag = $this->ugl->get_jquery_tag();
		$jquery     = $this->scripts->query( $jquery_tag );
		$prefix     = '//';
		if ( ! $this->ugl->get_protocol_relative_supported() ) {
			if ( is_ssl() ) {
				$prefix = 'https://';
			} else {
				$prefix = 'http://';
			}
		}
		$this->assertStringStartsWith( $prefix, $jquery->src );
	}

	function test_scripts_replaced() {
		$scripts = $this->ugl->get_google_scripts();
		foreach ( array_keys( $scripts ) as $handle ) {
			if ( $script = $this->scripts->query( $handle ) ) {
				if ( $script->src && strpos( $script->ver, '-' ) === false ) {
					$this->assertContains(
						'//ajax.googleapis.com/ajax/libs',
						$script->src, $handle + ' should be loading from google'
					);
				}
			}
		}
	}

	function test_nonstandard_ver_not_replaced() {
		$scripts = $this->ugl->get_google_scripts();
		foreach ( array_keys( $scripts ) as $handle ) {
			if ( $script = $this->scripts->query( $handle ) ) {
				if ( $script->src && strpos( $script->ver, '-' ) !== false ) {
					$this->assertNotContains(
						'//ajax.googleapis.com/ajax/libs',
						$script->src, $handle + ' should not be loading from google'
					);
				}
			}
		}
	}

	function test_scriptaculous_1_8_0() {
		$scriptaculous = $this->scripts->query( 'scriptaculous-root' );
		if ( !$scriptaculous || $scriptaculous->ver !== '1.8.0' ||
			strpos( $scriptaculous->src, '//ajax.googleapis.com/ajax/libs' ) === false ) {
			$this->markTestSkipped( 'does not apply, not replacing scriptaculous version 1.8.0' );
		}
		$this->assertContains( '1.8', $scriptaculous->src );
		$this->assertNotContains( '1.8.0', $scriptaculous->src );
	}


	function test_noconfict_next_set() {
		$jquery = $this->scripts->query( $this->ugl->get_jquery_tag() );
		$src    = $jquery->src;
		if ( strpos( $src, '//ajax.googleapis.com/ajax/libs' ) === false ) {
			$this->markTestSkipped( 'jQuery not replaced, noconflict_next unused' );
		}

		$this->ugl->set_noconflict_next( false );

		$this->ugl->remove_ver_query( $src );

		$this->assertTrue(
			$this->ugl->get_noconflict_next(),
			'noconflict_next should be set after remove_ver_query is run for jquery'
		);
	}

	function test_noconflict_injected() {

		$this->ugl->set_noconflict_next( true );
		$this->expectOutputString( $this->ugl->get_noconflict_inject() );
		$this->ugl->remove_ver_query( 'http://example.com/' );

		$this->assertFalse(
			$this->ugl->get_noconflict_next(),
			'noconflict_next should be cleared after remove_ver_query runs'
		);
	}

	function test_all_jquery_ui_replaced() {
		$scripts = $this->ugl->get_google_scripts();
		$known = array_keys( $scripts );
		$missing = array();
		foreach ( array_keys( $this->scripts->registered ) as $handle ) {
			if ( strpos( $handle, 'jquery-ui-') === 0 ) {
				if ( !in_array ( $handle, $known ) ) {
					$missing[] = $handle;
				}
			}
		}
		$this->assertEmpty(
				$missing,
				'Not all jquery-ui components are handled by UGL: ' .
				print_r($missing, true) );
	}

}
