<?php
global $sitepress, $sitepress_settings;
?>
<div class="wpml-section" id="ml-content-setup-sec-2">

    <div class="wpml-section-header">
        <h3><?php _e('Posts and pages synchronization', 'sitepress');?></h3>
    </div>

    <div class="wpml-section-content">

        <form id="icl_page_sync_options" name="icl_page_sync_options" action="">
            <?php wp_nonce_field('icl_page_sync_options_nonce', '_icl_nonce'); ?>

            <div class="wpml-section-content-inner">
                <p>
                    <label><input type="checkbox" id="icl_sync_page_ordering" name="icl_sync_page_ordering" <?php if($sitepress_settings['sync_page_ordering']): ?>checked<?php endif; ?> value="1" />
                    <?php _e('Synchronize page order for translations', 'sitepress') ?></label>
                </p>
                <p>
                    <label><input type="checkbox" id="icl_sync_page_parent" name="icl_sync_page_parent" <?php if($sitepress_settings['sync_page_parent']): ?>checked<?php endif; ?> value="1" />
                    <?php _e('Set page parent for translation according to page parent of the original language', 'sitepress') ?></label>
                </p>
                <p>
                    <label><input type="checkbox" name="icl_sync_page_template" <?php if($sitepress_settings['sync_page_template']): ?>checked<?php endif; ?> value="1" />
                    <?php _e('Synchronize page template', 'sitepress') ?></label>
                </p>
                <p>
                    <label><input type="checkbox" name="icl_sync_comment_status" <?php if($sitepress_settings['sync_comment_status']): ?>checked<?php endif; ?> value="1" />
                    <?php _e('Synchronize comment status', 'sitepress') ?></label>
                </p>
                <p>
                    <label><input type="checkbox" name="icl_sync_ping_status" <?php if($sitepress_settings['sync_ping_status']): ?>checked<?php endif; ?> value="1" />
                    <?php _e('Synchronize ping status', 'sitepress') ?></label>
                </p>
                <p>
                    <label><input type="checkbox" name="icl_sync_sticky_flag" <?php if($sitepress_settings['sync_sticky_flag']): ?>checked<?php endif; ?> value="1" />
                    <?php _e('Synchronize sticky flag', 'sitepress') ?></label>
                </p>
                <p>
                    <label><input type="checkbox" name="icl_sync_password" <?php if($sitepress_settings['sync_password']): ?>checked<?php endif; ?> value="1" />
                    <?php _e('Synchronize password for password protected posts', 'sitepress') ?></label>
                </p>
                <p>
                    <label><input type="checkbox" name="icl_sync_private_flag" <?php if($sitepress_settings['sync_private_flag']): ?>checked<?php endif; ?> value="1" />
                    <?php _e('Synchronize private flag', 'sitepress') ?></label>
                </p>
                <p>
                    <label><input type="checkbox" name="icl_sync_post_format" <?php if($sitepress_settings['sync_post_format']): ?>checked<?php endif; ?> value="1" />
                    <?php _e('Synchronize posts format', 'sitepress') ?></label>
                </p>
            </div>

            <div class="wpml-section-content-inner">
                <p>
                    <label><input type="checkbox" name="icl_sync_delete" <?php if($sitepress_settings['sync_delete']): ?>checked<?php endif; ?> value="1" />
                    <?php _e('When deleting a post, delete translations as well', 'sitepress') ?></label>
                </p>
                <p>
                    <label><input type="checkbox" name="icl_sync_delete_tax" <?php if($sitepress_settings['sync_delete_tax']): ?>checked<?php endif; ?> value="1" />
                    <?php _e('When deleting a taxonomy (category, tag or custom), delete translations as well', 'sitepress') ?></label>
                </p>
            </div>

            <div class="wpml-section-content-inner">
                <p>
                    <label><input type="checkbox" name="icl_sync_post_taxonomies" <?php if($sitepress_settings['sync_post_taxonomies']): ?>checked<?php endif; ?> value="1" />
                    <?php _e('Copy taxonomy to translations', 'sitepress') ?></label>
                </p>
                <p>
                    <label><input type="checkbox" name="icl_sync_post_date" <?php if($sitepress_settings['sync_post_date']): ?>checked<?php endif; ?> value="1" />
                    <?php _e('Copy publishing date to translations', 'sitepress') ?></label>
                </p>
            </div>

            <?php if( defined('WPML_TM_VERSION') ): ?>
            <div class="wpml-section-content-inner">
                <p>
                    <label><input type="checkbox" name="icl_sync_comments_on_duplicates" <?php if($sitepress->get_setting('sync_comments_on_duplicates')): ?>checked<?php endif; ?> value="1" />
                    <?php _e('Synchronize comments on duplicate content', 'sitepress') ?></label>
                </p>
            </div>
            <?php endif; ?>

            <div class="wpml-section-content-inner">
                <p class="buttons-wrap">
                    <span class="icl_ajx_response" id="icl_ajx_response_mo"></span>
                    <input class="button button-primary" name="save" value="<?php _e('Save','sitepress') ?>" type="submit" />
                </p>
            </div>

        </form>

    </div> <!-- wpml-section-content -->

</div> <!-- .wpml-section -->