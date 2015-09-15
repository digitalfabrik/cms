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

            	cpt_warnings();
            	ICL_AdminNotifier::displayMessages( 'cpt-translation' );
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

                                    <?php if(defined('WPML_ST_VERSION')): ?>
                                        <?php
                                            $_has_slug = isset($custom_post->rewrite['slug']) && $custom_post->rewrite['slug'];
                                            $_on = $sitepress_settings['posts_slug_translation']['on'] &&
                                                   $_has_slug &&
                                                   isset($sitepress_settings['custom_posts_sync_option'][$k]) &&
                                                   $sitepress_settings['custom_posts_sync_option'][$k] == 1;
                                            $is_hidden = $_on ? '' : 'hidden';
                                            $_translate = !empty($sitepress_settings['posts_slug_translation']['types'][$k]);
                                            if ($_has_slug) {
												$string_id = null;
												$_slug_translations = false;
												
												if ( class_exists( 'WPML_Slug_Translation' ) ) {
													list( $string_id, $_slug_translations ) = WPML_Slug_Translation::get_translations( trim( $custom_post->rewrite[ 'slug' ], '/' ) );
												}
												
												if($sitepress_settings['posts_slug_translation']['on'] && $_translate && !$string_id) {
													$message = sprintf( __( "%s slugs are set to be translated, but they are missing their translation", 'sitepress'), $custom_post->labels->name);
													ICL_AdminNotifier::displayInstantMessage( $message, 'error', 'below-h2', false );
												}
                                            } else {
                                                $_slug_translations = false;
                                            }
                                        ?>
                                        <?php if($_has_slug && isset($sitepress_settings['posts_slug_translation']['on']) && $sitepress_settings['posts_slug_translation']['on']): ?>
                                            <div class="icl_slug_translation_choice <?php echo $is_hidden; ?>">
                                                <p>
                                                    <label>
                                                        <input name="translate_slugs[<?php echo $k ?>][on]" type="checkbox" value="1" <?php checked(1, $_translate, true) ?> />
                                                        <?php printf(__('Use different slugs in different languages for %s.', 'sitepress'), $custom_post->labels->name); ?>
                                                    </label>
                                                </p>

                                                <table class="js-cpt-slugs <?php if(empty($_translate)): ?>hidden<?php endif; ?>">


													<?php

													foreach ( $sitepress->get_active_languages() as $language ) {
														if ( $language[ 'code' ] != $sitepress_settings[ 'st' ][ 'strings_language' ] ) {
															$slug_translation_value  = !empty( $_slug_translations[ $language[ 'code' ] ][ 'value' ] ) ? $_slug_translations[ $language[ 'code' ] ][ 'value' ] : '';
															$slug_translation_sample = trim($custom_posts[ $k ]->rewrite[ 'slug' ],'/') . ' @' . $language[ 'code' ];
															?>
															<tr>
																<td>
																	<label for="translate_slugs[<?php echo $k ?>][langs][<?php echo $language[ 'code' ] ?>]"><?php echo $language[ 'display_name' ] ?></label>
																</td>
																<td>
																	<input id="translate_slugs[<?php echo $k ?>][langs][<?php echo $language[ 'code' ] ?>]" name="translate_slugs[<?php echo $k ?>][langs][<?php echo $language[ 'code' ] ?>]" type="text" value="<?php echo $slug_translation_value; ?>"
																		   placeholder="<?php echo $slug_translation_sample; ?>"/>
																	<?php
																	if ( isset( $_slug_translations[ $language[ 'code' ] ] ) && $_slug_translations[ $language[ 'code' ] ][ 'status' ] != ICL_TM_COMPLETE ) {
																		?>
																		<em class="icl_st_slug_tr_warn"><?php _e( "Not marked as 'complete'. Press 'Save' to enable.", 'sitepress' ) ?></em>
																	<?php
																	}
																	?>
																</td>
															</tr>
														<?php
														} else {
															?>
															<tr>
																<td>
																	<label for="translate_slugs[<?php echo $k ?>][langs][<?php echo $language[ 'code' ] ?>]"><?php echo $language[ 'display_name' ] ?> <em><?php _e( "(original)", 'sitepress' ) ?></em></label>
																</td>
																<td><input disabled="disabled" class="disabled" id="translate_slugs[<?php echo $k ?>][langs][<?php echo $language[ 'code' ] ?>]" name="translate_slugs[<?php echo $k ?>][langs][<?php echo $language[ 'code' ] ?>]" type="text"
																								 value="<?php echo trim($custom_posts[ $k ]->rewrite[ 'slug' ],'/'); ?>"/>
																</td>
															</tr>
														<?php
														}
													}
													?>




                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
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

function cpt_warnings()
{
	if(!defined('WPML_ST_PATH')) return;

	global $sitepress_settings;
	ICL_AdminNotifier::removeMessage( 'cpt_default_and_st_language_warning' );
	if ( $sitepress_settings[ 'st' ][ 'strings_language' ] != 'en' ) {
		cpt_default_and_st_language_warning();
	}
}

function cpt_default_and_st_language_warning()
{
	static $called = false;
	if (defined('WPML_ST_FOLDER') && !$called ) {
		global $sitepress, $sitepress_settings;
		$st_language_code = $sitepress_settings[ 'st' ][ 'strings_language' ];
		$st_language      = $sitepress->get_display_language_name( $st_language_code, $sitepress->get_admin_language() );

		$st_page_url = admin_url( 'admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php' );

		$message = __(
			'The strings language in your site is set to %s instead of English.
			This means that the original slug will appear in the URL when displaying content in %s.
			<strong><a href="%s" target="_blank">Read more</a> |  <a href="%s#icl_st_sw_form">Change strings language</a></strong>',
		'wpml-string-translation' );

		$message = sprintf( $message, $st_language, $st_language, 'https://wpml.org/faq/string-translation-default-language-not-english/', $st_page_url );

		$fallback_message = __( '<a href="%s" target="_blank">How to translate strings when default language is not English</a>', 'wpml-string-translation'  );
		$fallback_message = sprintf( $fallback_message, 'https://wpml.org/faq/string-translation-default-language-not-english/' );

		ICL_AdminNotifier::addMessage( 'cpt_default_and_st_language_warning', $message, 'icl-admin-message-warning', true, $fallback_message, false, 'cpt-translation' );
		$called = true;
	}
}
