<?php

class WPML_TM_Dashboard_Display_Filter {

    private $active_languages = array();
    private $translation_filter;
    private $post_types;
    private $post_statuses;
    private $source_language_code;

    public function __construct(
        $active_languages,
        $source_language_code,
        $translation_filter,
        $post_types,
        $post_statuses
    ) {
        $this->active_languages     = $active_languages;
        $this->translation_filter   = $translation_filter;
        $this->source_language_code = $source_language_code;
        $this->post_types           = $post_types;
        $this->post_statuses        = $post_statuses;
    }

    private function from_lang_select() {
        ?>
        <label for="icl_language_selector">
            <?php esc_html_e( 'in', 'wpml-translation-management' ) ?>
        </label>
        <select id="icl_language_selector"
                name="filter[from_lang]" <?php if ( $this->source_language_code ): echo "disabled"; endif; ?> >
            <?php
            foreach ( $this->active_languages as $lang ) {
                $selected = '';
                if ( !$this->source_language_code && $lang[ 'code' ] == $this->translation_filter[ 'from_lang' ] ) {
                    $selected = 'selected="selected"';
                } elseif ( $this->source_language_code && $lang[ 'code' ] == $this->source_language_code ) {
                    $selected = 'selected="selected"';
                }
                ?>
                <option value="<?php echo $lang[ 'code' ] ?>" <?php echo $selected; ?>>
                    <?php
                    echo $lang[ 'display_name' ]; ?>
                </option>
            <?php
            }
            ?>
        </select>
    <?php
    }

    private function to_lang_select() {
        ?>
        <label for="filter_to_lang">
            <?php esc_html_e( 'translated to', 'wpml-translation-management' ); ?>
        </label>
        <select id="filter_to_lang" name="filter[to_lang]">
            <option value=""><?php esc_html_e( 'Any language', 'wpml-translation-management' ) ?></option>
            <?php
            foreach ( $this->active_languages as $lang ) {
                $selected = selected( $this->translation_filter[ 'to_lang' ], $lang[ 'code' ], false );
                ?>
                <option value="<?php echo $lang[ 'code' ] ?>" <?php echo $selected; ?>>
                    <?php echo $lang[ 'display_name' ] ?>
                </option>
            <?php
            }
            ?>
        </select>
    <?php
    }

    private function translation_status_select() {
        ?>
        <select id="filter_tstatus" name="filter[tstatus]">
            <?php
            $option_status = array(
                - 1 => esc_html__( 'All translation statuses', 'wpml-translation-management' ),
                ICL_TM_NOT_TRANSLATED => esc_html__(
                    'Not translated or needs updating',
                    'wpml-translation-management'
                ),
                ICL_TM_NEEDS_UPDATE => esc_html__( 'Needs updating', 'wpml-translation-management' ),
                ICL_TM_IN_PROGRESS => esc_html__( 'Translation in progress', 'wpml-translation-management' ),
                ICL_TM_COMPLETE => esc_html__( 'Translation complete', 'wpml-translation-management' )
            );
            foreach ( $option_status as $status_key => $status_value ) {
                $selected = selected( $this->translation_filter[ 'tstatus' ], $status_key, false );

                ?>
                <option value="<?php echo $status_key ?>" <?php echo $selected; ?>><?php echo $status_value ?></option>
            <?php
            }
            ?>
        </select>
    <?php
    }

    private function display_source_lang_locked_message_if_required() {
        if ( $this->source_language_code && isset( $this->active_languages[ $this->source_language_code ] ) ) {
            $language_name        = $this->active_languages[ $this->source_language_code ][ 'display_name' ];
            $basket_locked_string = '<p>';
            $basket_locked_string .= sprintf(
	            esc_html__(
                    'Language filtering has been disabled because you already have items in %s in the basket.',
                    'wpml-translation-management'
                ),
                $language_name
            );
            $basket_locked_string .= '<br/>';
            $basket_locked_string .= esc_html__(
                'To re-enable it, please empty the basket or send it for translation.',
                'wpml-translation-management'
            );
            $basket_locked_string .= '</p>';

            ICL_AdminNotifier::display_instant_message( $basket_locked_string, 'information-inline' );
        }
    }

    private function display_basic_filters() {
        ?>
        <tr valign="top">
            <td colspan="2">
                <img id="icl_dashboard_ajax_working" align="right"
                     src="<?php echo ICL_PLUGIN_URL ?>/res/img/ajax-loader.gif" style="display: none;" width="16"
                     height="16" alt="loading..."/>
                <br/>

	            <?php $this->number_of_ducuments_select() ?>
            </td>
        </tr>
    <?php
    }

    private function display_post_type_select() {
        $selected_type = isset( $this->translation_filter[ 'type' ] ) ? $this->translation_filter[ 'type' ] : false;
        ?>
        <select id="filter_type" name="filter[type]">
            <option value=""><?php esc_html_e( 'All types', 'wpml-translation-management' ) ?></option>
            <?php
            foreach ( $this->post_types as $post_type_key => $post_type ) {
                $filter_type_selected = selected( $selected_type, $post_type_key, false );
                ?>
                <option value="<?php echo $post_type_key ?>" <?php echo $filter_type_selected; ?>>
                    <?php echo $post_type->labels->singular_name != "" ? $post_type->labels->singular_name
                        : $post_type->labels->name; ?>
                </option>
            <?php
            }
            ?>
        </select>
    <?php
    }

    private function filter_title_textbox() {
	    ?>
        <input type="text" id="filter_title" name="filter[title]"
               value="<?php echo isset( $this->translation_filter['title'] ) ? $this->translation_filter['title'] : '' ?>"
               placeholder="<?php esc_attr_e( 'Title', 'wpml-translation-management' ); ?>"
        />
	    <?php
    }

    private function display_post_statuses_select() {
        $filter_post_status = isset( $this->translation_filter[ 'status' ] ) ? $this->translation_filter[ 'status' ]
            : false;

        ?>
        <select id="filter_status" name="filter[status]">
            <option value=""><?php esc_html_e( 'All statuses', 'wpml-translation-management' ) ?></option>
            <?php
            foreach ( $this->post_statuses as $post_status_k => $post_status ) {
                $post_status_selected = selected( $filter_post_status, $post_status_k, false );
                ?>
                <option value="<?php echo $post_status_k ?>" <?php echo $post_status_selected; ?>>
                    <?php echo $post_status ?>
                </option>
            <?php
            }
            ?>
        </select>
    <?php
    }

    private function display_button() {
        ?>
	    <input id="translation_dashboard_filter" name="translation_dashboard_filter"
                               class="button-secondary" type="submit"
                               value="<?php esc_attr_e( 'Filter', 'wpml-translation-management' ) ?>"/>

        <?php
    }
    public function display() {
        ?>
        <form method="post" name="translation-dashboard-filter" class="wpml-tm-dashboard-filter"
              action="admin.php?page=<?php echo WPML_TM_FOLDER ?>/menu/main.php&amp;sm=dashboard">
            <input type="hidden" name="icl_tm_action" value="dashboard_filter"/>

            <?php

                do_action( 'display_basket_notification', 'tm_dashboard_top' );
                $this->display_source_lang_locked_message_if_required();

                $this->display_post_type_select();
                $this->from_lang_select();
                $this->to_lang_select();
                $this->translation_status_select();
                $this->display_post_statuses_select();
                $this->filter_title_textbox();
                $this->display_button();

            ?>
        </form>
    <?php
    }

}