<?php

class WPML_TM_Translation_Basket_Dialog_Hooks implements IWPML_Action {

	const PRIORITY_GREATER_THAN_MEDIA_DIALOG = 5;

	/** @var WPML_TM_Translation_Basket_Dialog_View $dialog_view */
	private $dialog_view;

	public function __construct( WPML_TM_Translation_Basket_Dialog_View $dialog_view ) {
		$this->dialog_view = $dialog_view;
	}

	public function add_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), self::PRIORITY_GREATER_THAN_MEDIA_DIALOG );
		add_action( 'wpml_translation_basket_page_after', array( $this, 'display_dialog_markup' ) );
	}

	public function enqueue_scripts() {
		$handler = 'wpml-tm-translation-basket-dialog';

		wp_register_script( $handler,
			WPML_TM_URL . '/res/js/translation-basket/dialog.js',
			array( 'jquery-ui-dialog' )
		);

		wp_localize_script( $handler, 'wpmlTMBasket', array( 'dialogs' => array(), 'redirection' => '' ) );

		wp_enqueue_script($handler);

		wp_enqueue_script( 'wpml-tm-basket-redirection', WPML_TM_URL . '/res/js/translation-basket/redirection.js', array(), WPML_TM_VERSION, true );
	}

	public function display_dialog_markup() {
		echo $this->dialog_view->render();
	}
}
