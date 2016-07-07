<?php
/**
 * @package wpml-core
 */

class ICL_WP_Pointers{

    public static function add($callback){
        $dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
        $funcname = is_array ( $callback ) ? $callback[ 1 ] : $callback;
        
        if(!in_array($funcname, $dismissed)){
            add_action( 'admin_print_footer_scripts', $callback );
        }
    }
    
    private static function print_js( $pointer_id, $selector, $args ) {
        if ( empty( $pointer_id ) || empty( $selector ) || empty( $args ) || empty( $args['content'] ) )
            return;

        ?>
        <script type="text/javascript">
        //<![CDATA[
        (function($){
            var options = <?php echo wp_json_encode( $args ); ?>, setup;

            if ( ! options )
                return;

            options = $.extend( options, {
                close: function() {
                    $.post( ajaxurl, {
                        pointer: '<?php echo $pointer_id; ?>',
                        action: 'dismiss-wp-pointer'
                    });
                }
            });

            setup = function() {
                try{
                    $('<?php echo $selector; ?>').pointer( options ).pointer('open');
                }catch(err){;}
            };

            if ( options.position && options.position.defer_loading )
                $(window).bind( 'load.wp-pointers', setup );
            else
                $(document).ready( setup );

        })( jQuery );
        //]]>
        </script>
        <?php
    }
     
    /* The pointers */    
    public static function pointer_mo_auto_download_260() {

        $content  = '<h3>' . __( 'New in WPML 2.6.0', 'sitepress') . '</h3>';
        $content .= '<p>' .  __( 'WPML can automatically download translations for WordPress.', 'sitepress') . '</p>';

        ICL_WP_Pointers::print_js( __FUNCTION__, '#icl_adm_options', array(
            'content'  => $content,
            'position' => array( 'edge' => 'left', 'align' => 'right' ),
        ) );
    }
}
  
?>
