<?php
/* Author Chat Options v1.7.0 */

function author_chat_settings() {
    ?>
    <div class="wrap">
        <h2><?php _e('Author Chat Options', 'author-chat'); ?></h2>

        <form method="post" action="options.php">
            <?php settings_fields('author_chat_settings_group'); ?>
            <?php do_settings_sections('author_chat_settings_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">
                    </th>
                    <td>
                <center>
                    <p><?php _e('Your PIN code for Android App:', 'author-chat'); ?><br />
                        <b>
                            <font size="6"><?php echo esc_attr(get_option('author_chat_settings_pin')); ?></font>
                        </b><br /> 
                        You can find Author Chat for Android here: <a href="https://play.google.com/store/apps/details?id=pl.ordin.authorchatforwordpress">Google Play</a>
                    </p>
                </center>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="ac_sets_lifetime">
                            <?php _e('Delete chat history older than how many days?', 'author-chat'); ?>
                        </label>
                    </th>
                    <td>
                        <input id="ac_sets_lifetime" type="number" name="author_chat_settings" value="<?php echo esc_attr(get_option('author_chat_settings')); ?>" />
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><?php _e('Who should have access to Author Chat?', 'author-chat'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="author_chat_settings_access_all_users" value="1" <?php checked(get_option('author_chat_settings_access_all_users', '1')); ?>/>
                            <?php _e('All users with access to admin area', 'author-chat'); ?>
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" name="author_chat_settings_access_editor" value="1" <?php checked(get_option('author_chat_settings_access_editor', '1')); ?>/>
                            <?php _e('Editor', 'author-chat'); ?>
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" name="author_chat_settings_access_author" value="1" <?php checked(get_option('author_chat_settings_access_author', '1')); ?>/>
                            <?php _e('Author', 'author-chat'); ?>
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" name="author_chat_settings_access_contributor" value="1" <?php checked(get_option('author_chat_settings_access_contributor', '1')); ?>/>
                            <?php _e('Contributor', 'author-chat'); ?>
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" name="author_chat_settings_access_subscriber" value="1" <?php checked(get_option('author_chat_settings_access_subscriber', '1')); ?>/>
                            <?php _e('Subscriber', 'author-chat'); ?>
                        </label>
                        <br>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><?php _e('Choose how to display the authors: by Name or by Login?', 'author-chat'); ?></th>
                    <td>
                        <label>
                            <input type="radio" name="author_chat_settings_name" value="0" <?php checked(get_option('author_chat_settings_name'), '0'); ?>/>
                            <?php _e('Login (Username)', 'author-chat'); ?>
                        </label>
                        <br>
                        <label>
                            <input type="radio" name="author_chat_settings_name" value="1" <?php checked(get_option('author_chat_settings_name'), '1'); ?>/>
                            <?php _e('Name (Display name)', 'author-chat'); ?>
                        </label>
                        <br>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">
                        <label for="ac_sets_window">
                            <?php _e('Show chat window everywhere (Premium Function)?', 'author-chat'); ?>
                        </label>
                    </th>
                    <td>
                        <input id="ac_sets_window" type="checkbox" name="author_chat_settings_window" value="1" <?php checked(get_option('author_chat_settings_window'), 1); ?>/>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">
                        <label for="ac_show_my_name">
                            <?php _e('Show my name in the messages?', 'author-chat'); ?>
                        </label>
                    </th>
                    <td>
                        <input id="ac_show_my_name" type="checkbox" name="author_chat_settings_show_my_name" value="1" <?php checked(get_option('author_chat_settings_show_my_name'), 1); ?>/>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">
                        <label for="ac_show_url_preview">
                            <?php _e('Show thumb preview of the URLs?', 'author-chat'); ?>
                        </label>
                    </th>
                    <td>
                        <input id="ac_show_url_preview" type="checkbox" name="author_chat_settings_url_preview" value="1" <?php checked(get_option('author_chat_settings_url_preview'), 1); ?>/>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">
                        <label for="ac_show_weekdays">
                            <?php _e('Show weekday names of recent days?', 'author-chat'); ?>
                        </label>
                    </th>
                    <td>
                        <input id="ac_show_weekdays" type="checkbox" name="author_chat_settings_weekdays" value="1" <?php checked(get_option('author_chat_settings_weekdays'), 1); ?>/>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">
                        <label for="ac_sets_interval">
                            <?php _e('Refresh interval to check new messages', 'author-chat'); ?>
                        </label>
                    </th>
                    <td>
                        <select id="ac_sets_interval" name="author_chat_settings_interval">
                            <?php
                            for ($i = 1; $i < 11; $i++) {
                                ?>
                                <option <?php echo selected(get_option('author_chat_settings_interval'), $i); ?> value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php
                            }
                            ?>
                        </select>
                        &nbsp;<?php _e('second(s)', 'author-chat'); ?>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">
                        <label for="ac_sets_delete">
                            <?php _e('Permanently delete chat history? (data will be deleted when you check this box and click "Save Changes")', 'author-chat'); ?>
                        </label>
                    </th>
                    <td>
                        <input id="ac_sets_delete" type="checkbox" name="author_chat_settings_delete" value="1" <?php checked(get_option('author_chat_settings_delete'), 1); ?>/>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>

        </form>
    </div>
<?php }
?>