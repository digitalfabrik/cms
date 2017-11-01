<?php

class WPML_TM_Post_Edit_Notices {

	const TEMPLATE_TRANSLATION_IN_PROGRESS     = 'translation-in-progress.twig';
	const TEMPLATE_USE_PREFERABLY_TM_DASHBOARD = 'use-preferably-tm-dashboard.twig';
	const TEMPLATE_USE_PREFERABLY_TE           = 'use-preferably-translation-editor.twig';

	/** @var WPML_Post_Status $post_status */
	private $post_status;

	/** @var SitePress $sitepress */
	private $sitepress;

	/** @var IWPML_Template_Service $template_render */
	private $template_render;

	/** @var WPML_Super_Globals_Validation $super_globals */
	private $super_globals;

	/** @var WPML_TM_Translation_Status_Display $status_display */
	private $status_display;

	/** @var bool $use_translation_editor */
	private $use_translation_editor;

	/**
	 * @param WPML_Post_Status              $post_status
	 * @param SitePress                     $sitepress
	 * @param IWPML_Template_Service        $template_render
	 * @param WPML_Super_Globals_Validation $super_globals
	 * @param WPML_TM_Translation_Status_Display $status_display
	 * @param bool                          $use_translation_editor
	 */
	public function __construct(
		WPML_Post_Status $post_status,
		SitePress $sitepress,
		IWPML_Template_Service $template_render,
		WPML_Super_Globals_Validation $super_globals,
		WPML_TM_Translation_Status_Display $status_display,
		$use_translation_editor
	) {
		$this->post_status            = $post_status;
		$this->sitepress              = $sitepress;
		$this->template_render        = $template_render;
		$this->super_globals          = $super_globals;
		$this->status_display         = $status_display;
		$this->use_translation_editor = $use_translation_editor;
	}

	public function add_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_notices', array( $this, 'display_notices' ) );
	}

	public function enqueue_assets() {
		wp_enqueue_script(
			'wpml-tm-post-edit-alert',
			WPML_TM_URL . '/res/js/post-edit-alert.js',
			array( 'jquery', 'jquery-ui-dialog' ),
			WPML_TM_VERSION
		);
	}

	public function display_notices() {
		$trid    = $this->super_globals->get( 'trid', FILTER_SANITIZE_NUMBER_INT, FILTER_NULL_ON_FAILURE );
		$post_id = $this->super_globals->get( 'post', FILTER_SANITIZE_NUMBER_INT, FILTER_NULL_ON_FAILURE );
		$lang    = $this->super_globals->get( 'lang', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_NULL_ON_FAILURE );

		if ( ! $post_id ) {
			return;
		}

		$post_element = new WPML_Post_Element( $post_id, $this->sitepress );
		$is_original  = ! $post_element->get_source_language_code();

		if ( ! $trid ) {
			$trid = $post_element->get_trid();
		}

		if ( $trid ) {
			$translation_status = (int) $this->post_status->get_status( $post_id, $trid, $lang );

			if ( $this->is_translation_in_progress( $translation_status ) ) {
				$model = array(
					'warning' => sprintf(
						__( '%sWarning:%s You are trying to edit a translation that is currently in the process of being added using WPML.', 'wpml-translation-management' ),
						'<strong>',
						'</strong>'
					),
					'check_dashboard' => sprintf(
						__( 'Please refer to the <a href="%s">Translation Management dashboard</a> for the exact status of this translation.', 'wpml-translation-management' ),
						admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php&' )
					),
				);

				echo $this->template_render->show( $model, self::TEMPLATE_TRANSLATION_IN_PROGRESS );

			} elseif ( ! $is_original && $this->use_translation_editor ) {

				$model = array(
					'warning' => sprintf(
						__( '%sWarning:%s You are trying to edit a translation using the standard WordPress editor but your site is configured to use the WPML Translation Editor.', 'wpml-translation-management' ),
						'<strong>',
						'</strong>'
					),
				    'go_back_button'         => __( 'Go back', 'wpml-translation-management' ),
				    'edit_anyway_button'     => __( 'Edit anyway', 'wpml-translation-management' ),
				    'open_in_te_button'      => __( 'Open in Translation Editor', 'wpml-translation-management' ),
				    'translation_editor_url' => $this->get_translation_editor_link( $post_element ),
				);

				echo $this->template_render->show( $model, self::TEMPLATE_USE_PREFERABLY_TE );
			}

		} elseif ( $post_element->is_translatable() && $this->use_translation_editor ){
			$model = array(
				'warning' => sprintf(
					__('%sWarning:%s You are trying to add a translation using the standard WordPress editor but your site is configured to use the WPML Translation Editor.' , 'wpml-translation-management'),
					'<strong>',
					'</strong>'
				),
				'use_tm_dashboard' => sprintf(
					__( 'You should use <a href="%s">Translation management dashboard</a> to send the original document to translation.' , 'wpml-translation-management' ),
					admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php' )
				),
			);

			echo $this->template_render->show( $model, self::TEMPLATE_USE_PREFERABLY_TM_DASHBOARD );
		}
	}

	/**
	 * @param int|null $translation_status
	 *
	 * @return bool
	 */
	private function is_translation_in_progress( $translation_status ) {
		return ! is_null( $translation_status )
		       && $translation_status > 0
		       && $translation_status != ICL_TM_DUPLICATE
		       && $translation_status < ICL_TM_COMPLETE;
	}

	/**
	 * @param WPML_Post_Element $post_element
	 *
	 * @return string
	 */
	private function get_translation_editor_link( WPML_Post_Element $post_element ) {
		$post_id             = $post_element->get_id();
		$source_post_element = $post_element->get_source_element();

		if ( $source_post_element ) {
			$post_id = $source_post_element->get_id();
		}

		$url = $this->status_display->filter_status_link(
			'#', $post_id, $post_element->get_language_code(), $post_element->get_trid()
		);

		return remove_query_arg( 'return_url', $url );
	}
}
