<?php
global $wpdb, $current_user;
$active_languages = $this->get_active_languages();
foreach ($active_languages as $lang) {
    if ($this->get_default_language() != $lang['code']) {
        $default = '';
    } else {
        $default = ' (' . __('default', 'sitepress') . ')';
    }
    if(!empty($this->settings['hidden_languages']) && in_array($lang['code'], $this->settings['hidden_languages'])){
        $hidden = '&nbsp<strong style="color:#f00">('.__('hidden', 'sitepress').')</strong>';
    }else{
        $hidden = '';
    }
    $alanguages_links[] = $lang['display_name'] . $hidden . $default;
}
?>
<?php if (empty($this->settings['setup_complete'])): ?>
    <p class="updated" style="text-align: center; padding:4px"><a href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/languages.php"><strong><?php _e('Setup languages', 'sitepress') ?></strong></a></p>
<?php else: ?>
        <p><?php _e('Site languages:', 'sitepress') ?> <b><?php echo join(', ', (array) $alanguages_links) ?></b> (<a href="admin.php?page=<?php echo ICL_PLUGIN_FOLDER ?>/menu/languages.php"><?php _e('edit', 'sitepress'); ?></a>)</p>
        
        <?php do_action('icl_dashboard_widget_content_top'); ?>
        
        <div><a href="javascript:void(0)" onclick="jQuery(this).parent().next('.wrapper').slideToggle();" style="display:block; padding:5px; border: 1px solid #eee; margin-bottom:2px; background-color: #F7F7F7;"><?php _e('Theme and plugins localization', 'sitepress') ?></a></div>
        <div class="wrapper" style="display:none; padding: 5px 10px; border: 1px solid #eee; border-top: 0px; margin:-11px 0 2px 0;"><p>
        <?php
        echo __('Current configuration', 'sitepress');
        echo '<br /><strong>';
        switch ($this->settings['theme_localization_type']) {
            case '1': echo __('Translate by WPML', 'sitepress');
                break;
            case '2': echo __('Using a .mo file in the theme directory', 'sitepress');
                break;
            default: echo __('No localization', 'sitepress');
        }
        echo '</strong>';

        ?>
    </p>
    <p><a class="button secondary" href="<?php echo 'admin.php?page=' . basename(ICL_PLUGIN_PATH) . '/menu/theme-localization.php' ?>"><?php echo __('Manage theme and plugins localization', 'sitepress'); ?></a></p>
</div>

<?php endif; ?>

                                <div><a href="javascript:void(0)" onclick="jQuery(this).parent().next('.wrapper').slideToggle();" style="display:block; padding:5px; border: 1px solid #eee; margin-bottom:2px; background-color: #F7F7F7;"><?php _e('Help resources', 'sitepress'); ?></a></div>
                                <div class="wrapper" style="display:none; padding: 5px 10px; border: 1px solid #eee; border-top: 0px; margin:-11px 0 2px 0;">
                                    <p><img src="<?php echo ICL_PLUGIN_URL; ?>/res/img/question1.png" width="16" height="16" style="position: relative; top: 4px;" alt="<?php _e('WPML home page', 'sitepress'); ?>" />&nbsp;<a href="https://wpml.org/"><?php _e('WPML home page', 'sitepress'); ?></a>
                                        <br /><img src="<?php echo ICL_PLUGIN_URL; ?>/res/img/RO-Mx1-16_tool-wrench.png" width="16" height="16" style="position: relative; top: 4px;" alt="<?php _e('Commercial support', 'sitepress'); ?>" />&nbsp;<a href="<?php echo admin_url('admin.php?page=' . ICL_PLUGIN_FOLDER . '/menu/support.php') ?>"><?php _e('Commercial support', 'sitepress'); ?></a></p>
                                    
                                    </div>
                                    
<?php do_action('icl_dashboard_widget_content'); ?>

<?php
$rss = fetch_feed('https://wpml.org/feed/');
if (!is_wp_error($rss)) { // Checks that the object is created correctly
    // Figure out how many total items there are, but limit it to 2.
    $maxitems = $rss->get_item_quantity(2);
    // Build an array of all the items, starting with element 0 (first element).
    $rss_items = $rss->get_items(0, $maxitems);
}
if (!empty($maxitems)) {
?>
                                                <div class="rss-widget"><p><strong><?php _e('WPML news', 'sitepress'); ?></strong></p>
                                                <ul>
<?php
    // Loop through each feed item and display each item as a hyperlink.
    foreach ($rss_items as $item) {

?>
                                                    <li><a class="rsswidget" href='<?php echo $item->get_permalink(); ?>'><?php echo $item->get_title(); ?></a> <span class="rss-date"><?php echo $item->get_date('j F Y'); ?></span></li>
<?php } ?>
                                                </ul></div>
<?php
}
?>