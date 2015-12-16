<?php

abstract class WPML_Templates_Factory {
	protected $template_paths;
	/**
	 * @var Twig_Environment
	 */
	private $twig;

	public function __construct() {
		$this->init_template_base_dir();
	}

	abstract protected function init_template_base_dir();

	public function show( $template = null, $model = null ) {
		echo $this->get_view( $template, $model );
	}

	/**
	 * @param $template
	 * @param $model
	 *
	 * @return string
	 */
	public function get_view( $template = null, $model = null ) {
		$this->maybe_init_twig();

		if ( $model === null ) {
			$model = $this->get_model();
		}
		if ( $template === null ) {
			$template = $this->get_template();
		}

		$view = $this->twig->render( $template, $model );

		return $view;
	}

	private function maybe_init_twig() {
		if ( ! isset( $this->twig ) ) {
			$loader = new Twig_Loader_Filesystem( $this->template_paths );

			$environment_args = array();
			if ( WP_DEBUG ) {
				$environment_args[ 'debug' ] = true;
			}

			$this->twig = new Twig_Environment( $loader, $environment_args );
		}
	}

	abstract public function get_template();

	abstract public function get_model();

	protected function &get_twig() {
		return $this->twig;
	}
}