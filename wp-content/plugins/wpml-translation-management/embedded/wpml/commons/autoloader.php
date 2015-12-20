<?php
if ( version_compare( PHP_VERSION, '5.3', '<' ) ) {
	require_once dirname( __FILE__ ) . '/src/wpml-auto-loader.php';
	if ( ! class_exists( 'Twig_Autoloader' ) ) {
		require_once dirname( __FILE__ ) . '/../../twig/twig/lib/Twig/Autoloader.php';
		Twig_Autoloader::register();
	}
} else {
	require_once __DIR__ . '/../../autoload.php';
}
