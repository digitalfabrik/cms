<?php

class WPML_Menu_Sync_Display {
    private $menu_id;
    /** @var ICLMenusSync $icl_ms*/
    private $icl_ms;
    private $labels;

    public function __construct( $menu_id, $icl_ms ) {
        $this->menu_id = $menu_id;
        $this->icl_ms = $icl_ms;

        $this->labels = array(
            'del' => array( __ ( 'Remove %s', 'sitepress' ), '' ),
            'label_changed' => array( __ ( 'Rename label to %s', 'sitepress' ), '' ),
            'url_changed' => array( __ ( 'Update URL to %s', 'sitepress' ), '' ),
            'url_missing' => array( __ ( 'Untranslated URL %s', 'sitepress' ), '' ),
            'mov' => array( __ ( 'Change menu order for %s', 'sitepress' ), '' ),
            'add' => array( __ ( 'Add %s', 'sitepress' ), '' ),
            'options_changed' => array( __( 'Update %s menu option to %s', 'sitepress' ), '')
        );

        if ( defined( 'WPML_ST_FOLDER' ) ) {        
            $this->labels['label_missing'] = array(
                __ ( 'Untranslated string %s', 'sitepress' ),
                $this->print_label_missing_text ( $icl_ms, $menu_id )
            );
        }
        
    }

    private function print_label_missing_text( $icl_menus_sync, $menu_id ) {
        $context_menu_name = $icl_menus_sync->menus[ $menu_id ][ "name" ] . " menu";
        $res = '&nbsp;' . sprintf (
                __ (
                    'The selected strings can now be translated using the <a%s>string translation</a> screen',
                    'wpml-string-translation'
                ),
                ' href="admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php&context=' . $context_menu_name . '"'
            );

        return $res;
    }

    function print_sync_field( $index ) {
        global $sitepress;

        $icl_menus_sync = $this->icl_ms;
        $menu_id = $this->menu_id;
        // items translations / del
        if ( isset( $icl_menus_sync->sync_data[ $index ][ $menu_id ] ) ) {
            foreach ( $icl_menus_sync->sync_data[ $index ][ $menu_id ] as $item_id => $languages ) {
                foreach ( $languages as $lang_code => $name ) {
                    $additional_data = $this->get_additional_data ( $index, $name );
                    $item_name = $this->get_item_name ( $index, $name );
                    $lang_details = $sitepress->get_language_details ( $lang_code );
                    ?>
                    <tr>
                        <th scope="row" class="check-column">
                            <input type="checkbox"
                                   name="sync[<?php echo $index ?>][<?php echo $menu_id ?>][<?php echo $lang_code ?>][<?php echo $item_id ?>]<?php echo $additional_data ?>"
                                   value="<?php echo esc_attr ( $item_name ) ?>"/>
                        </th>
                        <td><?php echo $lang_details[ 'display_name' ]; ?></td>
                        <td><?php
                            echo $this->get_action_label($index, $item_name, $item_id);
                            ?> </td>
                    </tr>
                <?php
                }
            }
        }
    }

    private function get_action_label( $index, $item_name, $item_id ) {
        $labels = $this->labels;
        $argument = $index !== 'options_changed'
            ? sprintf (
                $labels[ $index ][ 0 ],
                '<strong>' . $item_name . '</strong>'
            )
            : sprintf (
                $labels[ $index ][ 0 ],
                '<strong>' . $item_id . '</strong>',
                '<strong>' . ($item_name ? $item_name : "0") . '</strong>'
            );

        return $this->hierarchical_prefix ( $index, $item_id )
               . $argument
               . $labels[ $index ][ 1 ];
    }

    private function get_additional_data( $index, $name ) {

        return $index === 'mov' ? '[' . key ( $name ) . ']' : '';
    }

    private function get_item_name( $index, $name ) {

        return $index === 'mov' ? current ( $name ) : $name;
    }

    private function hierarchical_prefix( $index, $item_id ) {
        $prefix = '';
        if ( in_array ( $index, array( 'mov', 'add' ) ) ) {
            $prefix = str_repeat ( ' - ', $this->icl_ms->get_item_depth ( $this->menu_id, $item_id ) );

        }

        return $prefix;
    }
}
