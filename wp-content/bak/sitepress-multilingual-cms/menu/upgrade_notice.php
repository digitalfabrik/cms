<?php 
$upgrade_lines =  array(
    '3.2'   => array(
                     'message' => __('This version of WPML includes major updates and improvements.', 'sitepress' ),
                     'link'    => '<a href="https://wpml.org/version/wpml-3-2/">' . __('WPML 3.2 release notes', 'sitepress') . '</a>',
                     'dismiss' => false
                     )
);


$short_v = implode('.', array_slice(explode('.', ICL_SITEPRESS_VERSION), 0, 3));
if(!isset($upgrade_lines[$short_v])) return;

?>
<br clear="all" />
<div id="icl_update_message" class="updated message fade" style="clear:both;margin-top:5px;">
    <p><img src="<?php echo ICL_ICONS_URL . 'icon_adv.png'; ?>" />&nbsp;<?php echo $upgrade_lines[ $short_v ][ 'message' ]; ?></p>
    <p>
        <?php if ( $upgrade_lines[ $short_v ][ 'link' ] ): ?>
            <?php echo $upgrade_lines[ $short_v ][ 'link' ]; ?>
        <?php else: ?>
            <a href="https://wpml.org/?cat=48"><?php _e('Learn more', 'sitepress')?></a>
        <?php endif; ?>
        
        <?php
            if ( $upgrade_lines[ $short_v ][ 'dismiss' ] ) {
                ?>
                    &nbsp;|&nbsp;<a title="<?php _e('Stop showing this message', 'sitepress') ?>" id="icl_dismiss_upgrade_notice" href="#"><?php _e('Dismiss', 'sitepress') ?></a>
                <?php
                wp_nonce_field('dismiss_upgrade_notice_nonce', '_icl_nonce_dun');
            } else {
                // set the hide settings so it's shown only one
                icl_set_setting('hide_upgrade_notice', implode('.', array_slice(explode('.', ICL_SITEPRESS_VERSION), 0, 3)));
                icl_save_settings();
            }
        ?>
    </p>
</div>
