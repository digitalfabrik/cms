<?php
if ( !isset( $wpdb ) ) {
	global $wpdb;
}
if ( !isset( $sitepress_settings ) ) {
	global $sitepress_settings;
}
if ( !isset( $sitepress ) ) {
	global $sitepress;
}
if ( !isset( $iclTranslationManagement ) ) {
	global $iclTranslationManagement;
}
global $wp_taxonomies;

$default_language = $sitepress->get_default_language();

$custom_posts = array();
$icl_post_types = $sitepress->get_translatable_documents( true );

foreach ( $icl_post_types as $k => $v ) {
	if ( !in_array( $k, array( 'post', 'page', 'attachment' ) ) ) {
		$custom_posts[ $k ] = $v;
	}
}

foreach ( $custom_posts as $k => $custom_post ) {
	if ( !isset( $sitepress_settings[ 'custom_posts_sync_option' ][ $k ] ) ) {
		$custom_posts_sync_not_set[ ] = $custom_post->labels->name;
	}
}

$notice = '';
if ( !empty( $custom_posts_sync_not_set ) ) {
	$notice .= '<div class="updated below-h2"><p>';
	$notice .= sprintf( __( "You haven't set your synchronization preferences for these custom posts: %s. Default value was selected.", 'sitepress' ),
						'<i>' . join( '</i>, <i>', $custom_posts_sync_not_set ) . '</i>' );
	$notice .= '</p></div>';
}

$custom_taxonomies = array_diff( array_keys( (array)$wp_taxonomies ), array( 'post_tag', 'category', 'nav_menu', 'link_category', 'post_format' ) );

foreach ( $custom_taxonomies as $custom_tax ) {
	if ( !isset( $sitepress_settings[ 'taxonomies_sync_option' ][ $custom_tax ] ) ) {
		$tax_sync_not_set[ ] = $wp_taxonomies[ $custom_tax ]->label;
	}
}
if ( !empty( $tax_sync_not_set ) ) {
	$notice .= '<div class="updated below-h2"><p>';
	$notice .= sprintf( __( "You haven't set your synchronization preferences for these taxonomies: %s. Default value was selected.", 'sitepress' ),
						'<i>' . join( '</i>, <i>', $tax_sync_not_set ) . '</i>' );
	$notice .= '</p></div>';
}

if(!empty($custom_posts)){
	
	if ( class_exists( 'WPML_Custom_Post_Slug_UI' ) ) {
		$CPT_slug_UI = new WPML_Custom_Post_Slug_UI( $wpdb, $sitepress );
	} else {
		$CPT_slug_UI = null;
	}
	
	?>


    <div class="wpml-section" id="ml-content-setup-sec-7">

        <div class="wpml-section-header">
            <h3><?php _e('Custom posts', 'sitepress');?></h3>
        </div>

        <div class="wpml-section-content">

            <?php
            	if ( isset( $notice ) ) {
            		echo $notice;
            	}

            ?>

            <form id="icl_custom_posts_sync_options" name="icl_custom_posts_sync_options" action="">
				<?php wp_nonce_field('icl_custom_posts_sync_options_nonce', '_icl_nonce') ?>

                <table class="widefat">
                    <thead>
                        <tr>
                            <th colspan="3">
                                <?php _e('Custom post types', 'sitepress');?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($custom_posts as $k=>$custom_post): ?>
                            <?php
                                $rdisabled = isset($iclTranslationManagement->settings['custom-types_readonly_config'][$k]) ? 'disabled="disabled"':'';
                            ?>
                            <tr>
                                <td>

                                    <p>
                                        <?php echo $custom_post->labels->name; ?>
                                    </p>
									
                                </td>
                                <td align="right">
                                    <p>
                                        <label>
                                            <input class="icl_sync_custom_posts" type="radio" name="icl_sync_custom_posts[<?php echo $k ?>]" value="1" <?php echo $rdisabled; ?> <?php if ( @intval($sitepress_settings['custom_posts_sync_option'][$k]) == 1 ): ?>checked<?php endif; ?> />
                                            <?php _e('Translate', 'sitepress') ?>
                                        </label>
                                    </p>
                                </td>
                                <td>
                                   <p>
                                        <label>
                                            <input class="icl_sync_custom_posts" type="radio" name="icl_sync_custom_posts[<?php echo $k ?>]" value="0" <?php echo $rdisabled; ?> <?php if( @intval($sitepress_settings['custom_posts_sync_option'][$k]) == 0 ): ?>checked<?php endif; ?> />
                                            <?php _e('Do nothing', 'sitepress') ?>
                                        </label>
                                   </p>
                                    <?php if ($rdisabled): ?>
                                        <input type="hidden" name="icl_sync_custom_posts[<?php echo $k ?>]" value="<?php echo @intval($sitepress_settings['custom_posts_sync_option'][$k]) ?>" />
                                    <?php endif; ?>
                                </td>
                            </tr>
							<tr>
								<td colspan="3">
									<?php
										if ( $CPT_slug_UI ) {
											$CPT_slug_UI->render( $k, $custom_post );
										}
									?>
								</td>
							</tr>
							
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p class="buttons-wrap">
                    <span class="icl_ajx_response" id="icl_ajx_response_cp"></span>
                    <input type="submit"
						   id="js_custom_posts_sync_button"
						   class="button button-primary"
						   value="<?php _e('Save', 'sitepress') ?>"
						   data-message="<?php echo esc_attr(__("You haven't entered translations for all slugs. Are you sure you want to save these settings?", 'sitepress' ) );?>" />
                </p>

            </form>

        </div> <!-- .wpml-section-content -->

    </div> <!-- wpml-section -->

<?php
}

if(!empty($custom_taxonomies)) {

?>
	<div class="wpml-section" id="ml-content-setup-sec-8">

	    <div class="wpml-section-header">
	        <h3><?php _e('Custom taxonomies', 'sitepress');?></h3>
	    </div>

	    <div class="wpml-section-content">
	        <form id="icl_custom_tax_sync_options" name="icl_custom_tax_sync_options" action="">
	            <?php wp_nonce_field('icl_custom_tax_sync_options_nonce', '_icl_nonce') ?>
	            <table class="widefat">
	                <thead>
	                    <tr>
	                        <th colspan="3">
	                            <?php _e('Custom taxonomies', 'sitepress');?>
	                        </th>
	                    </tr>
	                </thead>
	                <tbody>
	                    <?php foreach($custom_taxonomies as $ctax): ?>
	                    <?php $rdisabled = isset($iclTranslationManagement->settings['taxonomies_readonly_config'][$ctax]) ? 'disabled':''; ?>
	                    <tr>
	                        <td>
	                            <p><?php echo $wp_taxonomies[$ctax]->label; ?> (<i><?php echo $ctax; ?></i>)</p>
	                        </td>
	                        <td align="right">
	                            <p>
	                                <label>
	                                    <input type="radio" name="icl_sync_tax[<?php echo $ctax ?>]" value="1" <?php echo $rdisabled; ?> <?php if ( @$sitepress_settings['taxonomies_sync_option'][$ctax] == 1 ): ?> checked<?php endif; ?> />
	                                    <?php _e('Translate', 'sitepress') ?>
	                                </label>
	                            </p>
	                        </td>
	                        <td>
	                            <p>
	                                <label>
	                                    <input type="radio" name="icl_sync_tax[<?php echo $ctax ?>]" value="0" <?php echo $rdisabled; if ( @$sitepress_settings['taxonomies_sync_option'][$ctax] == 0 ): ?> checked<?php endif; ?> />
	                                    <?php _e('Do nothing', 'sitepress') ?>
	                                </label>
	                            </p>
	                        </td>
	                    </tr>
	                    <?php endforeach; ?>
	                </tbody>
	            </table>
	            <p class="buttons-wrap">
	                <span class="icl_ajx_response" id="icl_ajx_response_ct"></span>
	                <input type="submit" class="button-primary" value="<?php _e('Save', 'sitepress') ?>" />
	            </p>
	        </form>
	    </div> <!-- .wpml-section-content -->

	</div> <!-- wpml-section -->
<?php
}

