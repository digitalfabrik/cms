<?php
/*
 * Plugin Name: Author Chat Plugin
 * Plugin URI: https://github.com/Pantsoffski/Author-Chat-Plugin
 * Description: Plugin that gives your authors an easy way to communicate through back-end UI (admin panel).
 * Author: Piotr Pesta
 * Version: 1.7.0
 * Author URI: https://github.com/Pantsoffski
 * License: GPL12
 * Text Domain: author-chat
 * Domain Path: /lang
 */

include 'pp-process.php';

// Global Vars
global $author_chat_version;
$author_chat_version = '1.7.0';

global $author_chat_db_version;
$author_chat_db_version = '1.1';

add_action('admin_menu', 'pp_author_chat_setup_menu');
add_action('wp_dashboard_setup', 'pp_wp_dashboard_author_chat');
add_action('admin_enqueue_scripts', 'pp_scripts_admin_chat');
register_activation_hook(__FILE__, 'pp_author_chat_activate');
register_deactivation_hook( __FILE__, 'pp_author_chat_deactivate' );
register_uninstall_hook(__FILE__, 'pp_author_chat_uninstall');
add_action('plugins_loaded', 'pp_author_chat_update_db_check');
add_action('plugins_loaded', 'pp_author_chat_load_textdomain');
add_action('in_admin_footer', 'pp_author_chat_chat_on_top');
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'pp_plugin_action_links_ac');
add_action('rest_api_init', 'pp_author_chat_rest_api');

// Load Localization
function pp_author_chat_load_textdomain() {
    load_plugin_textdomain('author-chat', false, dirname(plugin_basename(__FILE__)) . '/lang/');
}

// Check if Database Update
function pp_author_chat_update_db_check() {
    global $author_chat_db_version;
    if (get_site_option('author_chat_db_version') != $author_chat_db_version) {
        pp_author_chat_activate();
    }
}

// Create author_chat table
function pp_author_chat_activate() {
    global $author_chat_db_version;
    global $wpdb;

    $author_chat_table = $wpdb->prefix . 'author_chat';

    // Check if Database Table Exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$author_chat_table'") != $author_chat_table) {
        // table not in database. Create new table
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $author_chat_table (
			id bigint(50) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			nickname tinytext NOT NULL,
			content text NOT NULL,
			date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id)
			) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        dbDelta($sql);

        // set current database version
        add_option('author_chat_db_version', $author_chat_db_version);
    } else {
        // Check for Database Updates
        $updates = $wpdb->get_row("SELECT * FROM $author_chat_table");
        if (!isset($updates->user_id)) {
            //Add user_id column if not present.
            $wpdb->query("ALTER TABLE $author_chat_table ADD user_id BIGINT(20) NOT NULL AFTER id");
        }
    }

    add_option('author_chat_settings', 30);
    add_option('author_chat_settings_access_all_users', 1);
    add_option('author_chat_settings_access_author', 0);
    add_option('author_chat_settings_access_contributor', 0);
    add_option('author_chat_settings_access_editor', 0);
    add_option('author_chat_settings_access_subscriber', 0);
    add_option('author_chat_settings_interval', 2);
    add_option('author_chat_settings_name', 0);
    add_option('author_chat_settings_show_my_name', 0);
    add_option('author_chat_settings_url_preview', 1);
    add_option('author_chat_settings_weekdays', 1);
    add_option('author_chat_settings_val', 0);
    add_option('author_chat_settings_window', 0);
    add_option('author_chat_settings_pin', mt_rand(1000, 9999));
}

// Deactivate Author Chat
function pp_author_chat_deactivate() {
    delete_option('author_chat_settings_val');
}

// Delete author_chat table
function pp_author_chat_uninstall() {
    global $wpdb;
    $author_chat_table = $wpdb->prefix . 'author_chat';
    $wpdb->query("DROP TABLE IF EXISTS $author_chat_table");
    delete_option('author_chat_settings');
    delete_option('author_chat_settings_access_all_users');
    delete_option('author_chat_settings_access_author');
    delete_option('author_chat_settings_access_contributor');
    delete_option('author_chat_settings_access_editor');
    delete_option('author_chat_settings_access_subscriber');
    delete_option('author_chat_settings_delete');
    delete_option('author_chat_settings_interval');
    delete_option('author_chat_settings_name');
    delete_option('author_chat_settings_show_my_name');
    delete_option('author_chat_settings_url_preview');
    delete_option('author_chat_settings_weekdays');
    delete_option('author_chat_settings_val');
    delete_option('author_chat_settings_window');
    delete_option('author_chat_settings_pin');
}

// Enqueue JavaScript & CSS files
function pp_scripts_admin_chat() {
    global $author_chat_version;
    wp_enqueue_script('author-chat-script', plugins_url('chat.js', __FILE__), array('jquery'), $author_chat_version, true);
    wp_enqueue_style('author-chat-style', plugins_url('author-chat-style.css', __FILE__), array(), $author_chat_version);
    wp_enqueue_style('wp-jquery-ui-dialog');
    wp_enqueue_script('jquery-ui-dialog');

    // set localize variables for send to the JS
    $current_user = wp_get_current_user();
    $username = str_replace('-', ' ', ( get_option('author_chat_settings_name') == 0 ) ? $current_user->user_login : $current_user->display_name );
    $values = array
        (
        'user_id' => $current_user->ID,
        'nickname' => $username,
        'you_are' => __('You are:', 'author-chat'),
        'today' => __('Today', 'default'),
        'yesterday' => __('Yesterday', 'author-chat'),
        'sunday' => __('Sunday', 'default'),
        'monday' => __('Monday', 'default'),
        'tuesday' => __('Tuesday', 'default'),
        'wednesday' => __('Wednesday', 'default'),
        'thursday' => __('Thursday', 'default'),
        'friday' => __('Friday', 'default'),
        'saturday' => __('Saturday', 'default'),
        'set_interval' => get_option('author_chat_settings_interval'),
        'set_show_my_name' => get_option('author_chat_settings_show_my_name'),
        'set_url_preview' => get_option('author_chat_settings_url_preview'),
        'set_weekdays' => get_option('author_chat_settings_weekdays')
    );
    wp_localize_script('author-chat-script', 'localize', $values);
}

function pp_author_chat_setup_menu() {
    include 'pp-options.php';

    $optionsTitle = __('Author Chat Options', 'author-chat');
    $pluginName = __('Author Chat', 'author-chat');
    add_dashboard_page($pluginName, $pluginName, 'read', 'author-chat', 'pp_author_chat');
    add_menu_page($optionsTitle, $pluginName, 'administrator', 'acset', 'author_chat_settings', 'dashicons-carrot');
    add_action('admin_init', 'register_author_chat_settings');
}

function pp_wp_dashboard_author_chat() {
    $pluginName = __('Author Chat', 'author-chat');
    wp_add_dashboard_widget('author-chat-widget', $pluginName, 'pp_author_chat');
}

function register_author_chat_settings() {
    register_setting('author_chat_settings_group', 'author_chat_settings');
    register_setting('author_chat_settings_group', 'author_chat_settings_access_all_users');
    register_setting('author_chat_settings_group', 'author_chat_settings_access_author');
    register_setting('author_chat_settings_group', 'author_chat_settings_access_contributor');
    register_setting('author_chat_settings_group', 'author_chat_settings_access_editor');
    register_setting('author_chat_settings_group', 'author_chat_settings_access_subscriber');
    register_setting('author_chat_settings_group', 'author_chat_settings_delete');
    register_setting('author_chat_settings_group', 'author_chat_settings_interval');
    register_setting('author_chat_settings_group', 'author_chat_settings_name');
    register_setting('author_chat_settings_group', 'author_chat_settings_show_my_name');
    register_setting('author_chat_settings_group', 'author_chat_settings_url_preview');
    register_setting('author_chat_settings_group', 'author_chat_settings_weekdays');
    register_setting('author_chat_settings_group', 'author_chat_settings_val');
    register_setting('author_chat_settings_group', 'author_chat_settings_window');
    register_setting('author_chat_settings_group', 'author_chat_settings_pin');
}

function pp_plugin_action_links_ac($links) { //Add settings link to plugins page
    $action_links = array(
        'settings' => '<a href="' . admin_url('admin.php?page=acset') . '">' . esc_html__('Settings', 'author-chat') . '</a>',
        'android' => '<a href="https://play.google.com/store/apps/details?id=pl.ordin.authorchatforwordpress">' . esc_html__('Author Chat for Android', 'author-chat') . '</a>',
    );

    return array_merge($action_links, $links);
}

function pp_author_chat() {
    $resultA = pp_author_chat_sec();
    $current_user = wp_get_current_user();
    $current_screen = get_current_screen();

    if ((get_option('author_chat_settings_access_subscriber') == '1' && $current_user->user_level == '0') || (get_option('author_chat_settings_access_contributor') == '1' && $current_user->user_level == '1') || (get_option('author_chat_settings_access_author') == '1' && $current_user->user_level == '2') || (get_option('author_chat_settings_access_editor') == '1' && $current_user->user_level == '3') || (get_option('author_chat_settings_access_editor') == '1' && $current_user->user_level == '4') || (get_option('author_chat_settings_access_editor') == '1' && $current_user->user_level == '5') || (get_option('author_chat_settings_access_editor') == '1' && $current_user->user_level == '6') || (get_option('author_chat_settings_access_editor') == '1' && $current_user->user_level == '7' || $current_user->user_level == '8' || $current_user->user_level == '9' || $current_user->user_level == '10') || get_option('author_chat_settings_access_all_users') == '1') {
        ?>
        <div id="author-chat">

            <h2 class="ac-title"><?php _e('Author Chat', 'author-chat'); ?></h2>

            <div class="ac-user"></div>

            <div class="ac-wrap">
                <div id="author-chat-area" class="ac-animation">
                    <div class="ac-top-date"></div>
                    <ul></ul>
                    <div class="ac-tobottom ac-animation ac-hidden"><span class="ac-icon-down"></span></div>
                </div>
                <?php if ($current_screen->base == 'dashboard_page_author-chat' || $current_screen->base == 'dashboard' || $resultA === true) { ?>
                </div>
                <form class="ac-text-form">
                    <textarea class="ac-textarea" maxlength = "1000" placeholder="<?php _e('Your message...', 'author-chat'); ?>"></textarea>
                </form>
            <?php } else { ?>
                <div class="ac-overlay">
                    <?php _e('To send text from here you need to buy premium version of that plugin', 'author-chat'); ?>.
                    <br>
                    <b style="color:#b11b1b;">$10.99 <?php _e('for lifetime 1 domain licence', 'author-chat'); ?>.</b>
                    (<i><?php _e('future premium features included', 'author-chat'); ?></i>)
                    <br>
                    <img class="ac-buy" src="https://www.paypalobjects.com/en_US/i/btn/btn_buynowCC_LG.gif" alt="PayPal - The safer, easier way to pay online!" />
                </div>
            </div>
        <?php } ?>
        </div>
        <audio id="author-chat-sound" style="display:none;" controls="controls"><source src="<?php echo plugins_url('notifyauthorchat.ogg', __FILE__); ?>" /></audio>
        <?php
    }

    pp_author_chat_clean_up_chat_history();

    if (get_option('author_chat_settings_delete') == 1) {
        pp_author_chat_clean_up_database();
    }
}

function pp_author_chat_chat_on_top() {
    $resultA = pp_author_chat_sec();
    $current_screen = get_current_screen();
    ?>
    <script type="text/javascript">
        /* kick off chat */
        jQuery(document).ready(function ()
        {
            /* Init the chat */
            var chat = new authorChat();
            chat.initiate();
    <?php
    if (get_option('author_chat_settings_window') == 1 && $current_screen->base != 'dashboard' && $current_screen->base != 'dashboard_page_author-chat') {
        ?>
                var $_dialogWindow = jQuery('#author-chat-window').dialog(
                        {
                            resizable: false,
                            dragStart: function (event, ui)
                            {
                                $_dialogWindow.removeClass('ac-animation');
                            },
                            drag: function (event, ui)
                            {
                                if (ui.offset.left + $_dialogWindow.outerWidth() >= jQuery(window).width() - 10)
                                {
                                    $_dialogContent.show();
                                    var outside = $_dialogWindow.outerHeight() - $_chatArea.outerHeight();
                                    $_chatArea.css('height', (jQuery(window).height() - $_wpAdminBar.outerHeight() - outside - 10) + 'px');
                                    /* add temporal class */
                                    $_dialogWindow.addClass('ac-snap');
                                    $_dialogWindow.css('opacity', '0.6');
                                } else if ($_dialogWindow.hasClass('ac-snap'))
                                {
                                    $_chatArea.css('height', '300px');
                                    $_dialogWindow.removeClass('ac-snap');
                                    $_dialogWindow.css('opacity', '0.6');
                                }
                            },
                            dragStop: function (event, ui)
                            {
                                $_dialogWindow.css('opacity', '1');
                                $_dialogWindow.addClass('ac-animation');

                                /* Converts the floating window into a SideBar if we approach it to the right margin */
                                if (ui.offset.left + $_dialogWindow.outerWidth() >= jQuery(window).width() - 10)
                                {
                                    setAsSidebar();
                                } else
                                {
                                    /* Re-convert the chat sidebar into a floating window when we move it away from the right margin */
                                    if (jQuery('#wpwrap').css('width') != '100%')
                                    {
                                        setAsWindow();
                                    }

                                    /* 
                                     Change the position of titlebar from top to bottom and bottom to top 
                                     based on the current position of the dialog
                                     */
                                    var doc_scroll_top = jQuery(document).scrollTop();
                                    var content_height = $_dialogWindow.height() + 25;
                                    if ($_dialogContent.is(':hidden'))
                                    {
                                        content_height = 25;
                                    }

                                    if ($_dialogTitleBar.hasClass('ac-bottom-titlebar') == false && ui.offset.top + content_height + $_dialogTitleBar.height() > jQuery(window).height() + doc_scroll_top)
                                    {
                                        $_dialogTitleBar.addClass('ac-bottom-titlebar');
                                        $_dialogWindow.append($_dialogTitleBar);
                                        /* save the state in a local data */
                                        chat.setLocalData('ac_dialog_is_bottom', true);
                                    } else if ($_dialogTitleBar.hasClass('ac-bottom-titlebar') == true && ui.offset.top + content_height + $_dialogTitleBar.height() < jQuery(window).height() + doc_scroll_top)
                                    {
                                        $_dialogTitleBar.removeClass('ac-bottom-titlebar');
                                        $_dialogWindow.append($_dialogContent);
                                        /* save the state in a local data */
                                        chat.setLocalData('ac_dialog_is_bottom', false);
                                    }

                                    fixPosition();
                                }
                            },
                            close: function ()
                            {
                                if (jQuery('#wpwrap').css('width') != '100%')
                                {
                                    jQuery('#wpwrap').css('width', '100%');
                                    $_wpAdminBar.css('width', 'calc(100% + 40px)');
                                }

                                /* we stop the interval */
                                chat.stop();
                            }
                            /* Limit the drag to the window size */
                        }).data('ui-dialog').uiDialog.draggable('option', 'containment', 'window');

                var $_dialogTitleBar = $_dialogWindow.find('.ui-dialog-titlebar');
                var $_dialogContent = $_dialogWindow.find('#author-chat-window');
                var $_chatArea = $_dialogWindow.find('#author-chat-area');
                var dialog_offset = $_dialogWindow.offset();
                var $_wpAdminBar = jQuery('#wpadminbar');

                /* Set Floating Window as Sidebar */
                /*--------------------------------*/
                function setAsSidebar()
                {
                    /* add the class */
                    $_dialogWindow.addClass('ac-sidebar');

                    /* show the content in case of minimized */
                    $_dialogContent.show();

                    /* put the titlebar on top if it's in the bottom */
                    if ($_dialogTitleBar.hasClass('ac-bottom-titlebar') == true)
                    {
                        $_dialogTitleBar.removeClass('ac-bottom-titlebar');
                        $_dialogWindow.append($_dialogContent);
                        /* save the state in a local data */
                        chat.setLocalData('ac_dialog_is_bottom', false);
                    }

                    var max_left = jQuery(window).width() - $_dialogWindow.outerWidth();

                    jQuery('#wpwrap').css('width', 'calc(100% - ' + $_dialogWindow.outerWidth() + 'px)');
                    $_wpAdminBar.css('width', 'calc(100% + ' + ($_dialogWindow.outerWidth() + 40) + 'px)');
                    $_dialogWindow.css('left', 'calc(100% - ' + $_dialogWindow.outerWidth() + 'px)');
                    $_dialogWindow.css('top', $_wpAdminBar.outerHeight() + 'px');

                    var outside = $_dialogWindow.outerHeight() - $_chatArea.outerHeight();
                    $_chatArea.css('height', (jQuery(window).height() - $_wpAdminBar.outerHeight() - outside - 10) + 'px');

                    chat.setLocalData('ac_dialog_is_sidebar', true);
                }

                /* Set Again as a Floating Window */
                /*--------------------------------*/
                function setAsWindow()
                {
                    $_dialogWindow.removeClass('ac-sidebar');

                    jQuery('#wpwrap').css('width', '100%');
                    $_wpAdminBar.css('width', 'calc(100% + 40px)');
                    $_chatArea.css('height', '300px');

                    chat.setLocalData('ac_dialog_is_sidebar', false);
                }

                /* Fix the position of the floating window in case of outside limits */
                function fixPosition()
                {
                    var doc_scroll_top = jQuery(document).scrollTop();
                    var dialog_offset = $_dialogWindow.offset();
                    var content_height = $_dialogContent.outerHeight();

                    if ($_dialogContent.is(':hidden'))
                    {
                        content_height = 0;
                    }

                    /* fix the Top position in case of outside limits */
                    if (dialog_offset.top + $_dialogTitleBar.innerHeight() + content_height > jQuery(window).height() + doc_scroll_top)
                    {
                        dialog_offset.top = jQuery(window).height() - content_height - $_dialogTitleBar.innerHeight() - 1;
                    } else if (dialog_offset.top < 0)
                    {
                        dialog_offset.top = 0;
                    }

                    /* fix the Left position in case of outside limits */
                    if (dialog_offset.left + $_dialogTitleBar.innerWidth() > jQuery(window).width())
                    {
                        dialog_offset.left = jQuery(window).width() - $_dialogTitleBar.innerWidth() - 1;
                    } else if (dialog_offset.left < 0)
                    {
                        dialog_offset.left = 0;
                    }

                    /* set the fixed position */
                    $_dialogWindow.offset(dialog_offset);

                    /* set the top position if the document is scrolled */
                    if (doc_scroll_top > 0)
                    {
                        dialog_offset.top -= doc_scroll_top;
                    }

                    /* save the position in a local data */
                    chat.setLocalData('ac_dialog_offset', JSON.stringify(dialog_offset));
                }

                /* Scroll the chat area to the bottom */
                function scrollToBottom()
                {
                    $_chatArea.scrollTop($_chatArea.prop('scrollHeight'));
                }

                /* for the titlebar counter */
                jQuery('<span id="author-chat-count"></span>').appendTo($_dialogTitleBar.find('span'));

                /* set the dialog in minimize state if it's defined in the local data */
                if (chat.getLocalData('ac_dialog_is_hidden') == 'true')
                {
                    $_dialogContent.hide();
                }

                /* set the titlebar dialog to the bottom if it's defined in the local data */
                if (chat.getLocalData('ac_dialog_is_bottom') == 'true')
                {
                    $_dialogTitleBar.addClass('ac-bottom-titlebar');
                    $_dialogWindow.append($_dialogTitleBar);
                }

                /* init the dialog position values */
                var dialog_offset = {top: "32", left: jQuery(window).width() - $_dialogContent.outerHeight() - 1};

                /* set the position of the dialog based in the local data values */
                var local_dialog_offset = chat.getLocalData('ac_dialog_offset');
                if (local_dialog_offset !== null)
                {
                    var parsed_position = JSON.parse(local_dialog_offset);
                    dialog_offset = {top: parsed_position.top, left: parsed_position.left};
                }

                $_dialogWindow.offset(dialog_offset);

                /* set the floating window into a SideBar if it's defined in the local data */
                if (chat.getLocalData('ac_dialog_is_sidebar') == 'true')
                {
                    setAsSidebar();
                }

                /* TitleBar click event */
                $_dialogTitleBar.mouseup(function ()
                {
                    /* don't do nothing in case of dragging or if it's a sidebar mode */
                    if ($_dialogWindow.hasClass('ui-dialog-dragging') || $_dialogWindow.hasClass('ac-sidebar'))
                        return;

                    var dialog_offset = $_dialogWindow.offset();
                    var content_height = 0;

                    /* Show the chat area if it's hidden */
                    if ($_dialogContent.is(':hidden'))
                    {
                        /* We take into account the height of the chat area in case the titlebar is in the bottom */
                        if ($_dialogTitleBar.hasClass('ac-bottom-titlebar'))
                        {
                            content_height = $_dialogContent.outerHeight();
                        }

                        $_dialogContent.show();
                        $_dialogWindow.offset({top: dialog_offset.top - content_height});

                        /* remove the brinking state and the counter of the titlebar */
                        if ($_dialogTitleBar.hasClass('ac-bg-blink'))
                        {
                            $_dialogTitleBar.removeClass('ac-bg-blink');
                            jQuery('#author-chat-count').text('').hide();
                            chat.clearCount();
                        }

                        /* scroll the chat area to the bottom */
                        scrollToBottom();

                        /* save the position in a local data */
                        chat.setLocalData('ac_dialog_is_hidden', false);
                    }
                    /* Hide the chat area if it's not hidden */
                    else
                    {
                        if ($_dialogTitleBar.hasClass('ac-bottom-titlebar'))
                        {
                            content_height = $_dialogContent.outerHeight();
                        }
                        $_dialogContent.hide();
                        $_dialogWindow.offset({top: dialog_offset.top + content_height});

                        chat.setLocalData('ac_dialog_is_hidden', true);
                    }

                    $_dialogWindow.addClass('ac-animation');

                    /* save the position in a local data */
                    dialog_offset = $_dialogWindow.offset();
                    chat.setLocalData('ac_dialog_offset', JSON.stringify(dialog_offset));

                });

                /* Scroll event */
                jQuery(document).scroll(function ()
                {
                    if ($_dialogWindow.hasClass('ac-sidebar'))
                    {
                        var doc_scroll_top = jQuery(document).scrollTop();
                        var dialog_css_top = parseInt($_dialogWindow.css('top'));
                        var outside = $_dialogWindow.outerHeight() - $_chatArea.outerHeight();

                        if (doc_scroll_top == 0 && dialog_css_top == 0)
                        {
                            $_dialogWindow.css('top', '32px');
                            $_chatArea.css('height', (jQuery(window).height() - $_wpAdminBar.outerHeight() - outside - 10) + 'px');
                        } else if (doc_scroll_top != 0 && dialog_css_top != 0)
                        {
                            $_dialogWindow.css('top', '0');
                            $_chatArea.css('height', (jQuery(window).height() - outside - 10) + 'px');
                        }
                    }
                });

                jQuery(window).load(function ()
                {
                    /* don't do nothing in case of sidebar mode */
                    if ($_dialogWindow.hasClass('ac-sidebar'))
                        return;
                    fixPosition();
                });

        <?php
        if ($resultA === false) {
            ?>
                    jQuery('#author-chat-buy').dialog(
                            {
                                autoOpen: false,
                                modal: true,
                                draggable: false,
                                resizable: false
                            });
                    jQuery('#author-chat-window .ac-overlay').click(function ()
                    {
                        jQuery('#author-chat-buy').dialog('open');
                    });
                });
            </script>
            <div id="author-chat-window" class="ac-animation" title="<?php _e('Author Chat', 'author-chat'); ?>">
                <?php pp_author_chat(); ?>
            </div>
            <div id="author-chat-buy" title="<?php _e('Buy Premium Version', 'author-chat'); ?>">
                <div class="ac-pp">
                    <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
                        <input type="hidden" name="cmd" value="_s-xclick">
                        <input type="hidden" name="hosted_button_id" value="5TGRZ4BSETP9G">
                        <table>
                            <tr><td><input type="hidden" name="on0" value="Domain name"><?php _e('If your domain name is correct, do not change it', 'author-chat'); ?>.</td></tr><tr><td><input type="text" name="os0" maxlength="200" value="<?php echo $_SERVER['HTTP_HOST']; ?>"></td></tr>
                        </table>
                        <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_buynowCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
                        <img alt="" border="0" src="https://www.paypalobjects.com/pl_PL/i/scr/pixel.gif" width="1" height="1">
                    </form>
                </div>
            </div>
            <?php
        } else {
            ?>
            });
            </script>
            <div id="author-chat-window" class="ac-animation" title="<?php _e('Author Chat', 'author-chat'); ?>">
                <?php pp_author_chat(); ?>
            </div>
            <?php
        }
    } else {
        ?>
        });
        </script>
        <?php
    }
}

function pp_author_chat_clean_up_chat_history() {
    global $wpdb;
    $daystoclear = get_option('author_chat_settings');
    $author_chat_table = $wpdb->prefix . 'author_chat';
    $wpdb->query("DELETE FROM $author_chat_table WHERE date <= NOW() - INTERVAL $daystoclear DAY");
}

function pp_author_chat_clean_up_database() {
    global $wpdb;
    $author_chat_table = $wpdb->prefix . 'author_chat';
    $wpdb->query("TRUNCATE TABLE $author_chat_table");
    $update_options = get_option('author_chat_settings_delete');
    $update_options = '';
    update_option('author_chat_settings_delete', $update_options);
}

function pp_author_chat_sec() {
    $valOption = explode(",", get_option('author_chat_settings_val'));
    if ($valOption[0] == 0 || $valOption[0] <= time() - (1 * 24 * 60 * 60 ) && get_option('author_chat_settings_window') == 1) {
        $checkFile = file_get_contents(aURL);
        if ($checkFile === false) {
            return true;
        }
        $dmCompare = stripos($checkFile, $_SERVER['HTTP_HOST']);
        if ($dmCompare !== false) {
            $toUpdate = time() . ',1';
            update_option('author_chat_settings_val', $toUpdate);
            $result = true;
        } else {
            $toUpdate = time() . ',0';
            update_option('author_chat_settings_val', $toUpdate);
            $result = false;
        }
    } elseif ($valOption[1] == 1) {
        $result = true;
    } elseif ($valOption[1] == 0) {
        $result = false;
    } elseif (get_option('author_chat_settings_window') == 0) {
        update_option('author_chat_settings_val', 0);
    }
    $checkFile = file_get_contents(aURL);
    return $result;
}

function pp_author_chat_rest_api() {
    register_rest_route('author-chat/v2', '/chat/', array(
        'methods' => 'GET',
        'callback' => 'pp_get_chat_rest',
    ));
}

function pp_get_chat_rest() {
    global $wpdb;
    $author_chat_table = $wpdb->prefix . 'author_chat';
    $lines = $wpdb->get_results("SELECT id, user_id, nickname, content, date FROM $author_chat_table ORDER BY id ASC", ARRAY_A);
            $text = array();
            foreach ($lines as $line) {
                $text[] = $line;
            }
            $date = array_column($text, 'date');
            array_walk_recursive($date, function( &$element ) {
                $element = strtotime($element);
                $element = date('Y-m-d,H:i:s', $element);
            });
            $result = array(
                'id' => array_column($text, 'id'),
                'uid' => array_column($text, 'user_id'),
                'nick' => array_column($text, 'nickname'),
                'msg' => array_column($text, 'content'),
                'date' => $date,
                'pin' => get_option('author_chat_settings_pin')
            );
    return $result;
}
