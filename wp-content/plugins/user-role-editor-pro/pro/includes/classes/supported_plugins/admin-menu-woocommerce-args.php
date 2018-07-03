<?php
class URE_Admin_Menu_Woocommerce_Args {
    
    public static function get_for_edit($args) {
        if (!isset($_GET['post_type'])) {
            return $args;
        }
        
        if ($_GET['post_type']=='product') {
            $args[''][] = 'product_cat';
            $args[''][] = 'product_type';
        } elseif ($_GET['post_type']=='shop_order') {
            $args[''][] = '_customer_user';
        } elseif ($_GET['post_type']=='shop_coupon') {
            $args[''][] = 'coupon_type';
        }
        
        return $args;
    }
    // end of get_for_edit()
    

}
// end of URE_Admin_Menu_Woocommerce_Args