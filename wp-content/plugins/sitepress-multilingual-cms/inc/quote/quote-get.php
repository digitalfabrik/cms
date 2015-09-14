<script type="text/javascript">
	/**
	 * @package wpml-core
	 */
	jQuery(document).ready(function(){
		jQuery('.icl-quote-get-next').click(function(){
			jQuery(this).parent().parent().fadeOut('fast', function(){
				jQuery(this).next('div').fadeIn();
			});
		});
		jQuery('.icl-quote-get-back').click(function(){
			jQuery(this).parent().parent().fadeOut('fast', function(){
				jQuery(this).prev('div').fadeIn();
			});
		});
		iclQuoteGetCheckContentCb();
		jQuery('#icl-quote-get-form').ajaxForm({target:'#icl-quote-get-wrap'});
	});
	function iclQuoteGetSetSelectLangs() {
        var quoteGetFrom = jQuery('#icl-quote-get-from').val();
        var quoteGetToggle = jQuery('.icl-quote-get-toggle-to');
        if (quoteGetFrom == 0) {
            quoteGetToggle.fadeOut();
			jQuery('.icl-quote-get-to').removeAttr('checked');
			iclQuoteGetCheckFromTo();
			return false;
		}
        quoteGetToggle.fadeIn();
        jQuery('#icl-quote-get-to-'+quoteGetFrom).removeAttr('checked').parent().hide(0,
			function(){
				iclQuoteGetCheckFromTo();
			}
		);
        quoteGetToggle.not('#icl-quote-get-to-'+quoteGetFrom).parent().show();
	}
	function iclQuoteGetCheckFromTo() {
		var enable = false;
		jQuery('.icl-quote-get-to').each(function(){
			if (jQuery(this).is(':checked')) {
				enable = true;
			}
		});
		if (jQuery('#icl-quote-get-from').val() == 0) {
			enable = false;
		}
		if (enable) {
			jQuery('#icl-quote-next-1').removeAttr('disabled');
		} else {
			jQuery('#icl-quote-next-1').attr('disabled', 'disabled');
		}
	}
	function iclQuoteGetCheckContentCb() {
		var enable = false;
		jQuery('.icl-quote-get-content-checbox').each(function(){
			if (jQuery(this).is(':checked')) {
				enable = true;
			}
		});
		if (enable) {
			jQuery('#icl-quote-next-2').removeAttr('disabled');
		} else {
			jQuery('#icl-quote-next-2').attr('disabled', 'disabled');
		}
	}

	function iclShowQuoteResult() {
		jQuery('#TB_title').after(
			'<iframe frameborder="0" hspace="0" id="TB_iframeContent" name="icl-get-quote-result" onload="tb_showIframe()"></iframe>'
		);
		jQuery('#icl-get-quote-form').submit();
		thickDims();
	}
</script>

<?php

/**
 * Step one (select languages)
 *
 * @global object $sitepress
 * @global array $sitepress_settings
 * @param array $saved
 */
function icl_quote_get_step_one($saved) {
    global $sitepress;
	$active_languages = $sitepress->get_active_languages();

	?>
    <input type="hidden" name="step" value="1" />
    <h1><?php _e('Translation Languages', 'sitepress'); ?></h1>
    <p>
        <label><?php _e('I need translation from', 'sitepress'); ?>
            <select id="icl-quote-get-from" name="from" onchange="iclQuoteGetSetSelectLangs();">
                <option value="0"><?php _e('Select Language', 'sitepress'); ?>&nbsp;</option>
            <?php
	foreach ($active_languages as $code => $lang) {
		$selected = '';

		?>
		<option value="<?php echo $code; ?>"<?php echo $selected; ?>><?php echo $lang['display_name']; ?></option>
	<?php
	}

	?>
        </select>
    </label>
    <br />
    <?php _e('to these languages:', 'sitepress'); ?>
	<?php
	foreach ($active_languages as $code => $lang) {
		$selected = @is_array($saved['to']) && @in_array($code, $saved['to']) ? ' checked="checked"' : '';

		?>
		<div class="icl-quote-get-toggle-to" style="display:none;">
			<label><input type="checkbox" name="to[<?php echo $code; ?>]" onclick="iclQuoteGetCheckFromTo();" value="<?php echo $code; ?>" class="icl-quote-get-to" id="icl-quote-get-to-<?php echo $code; ?>"<?php echo $selected; ?> />&nbsp;<?php echo $lang['display_name']; ?><br /></label>
		</div>
	<?php
	}

	?>
            </p>
            <p>
                <input type="submit" id="icl-quote-next-1" value="<?php _e('Continue', 'sitepress'); ?>" name="next" disabled="disabled" class="button-secondary icl-quote-get-next" />
            </p>
<?php
}
/**
 * Step two (select contents)
 *
 * @global object $sitepress
 * @global <type> $iclTranslationManagement
 * @global <type> $wpdb
 * @param <type> $saved
 */
function icl_quote_get_step_two($saved) {
	global $sitepress, $iclTranslationManagement, $wpdb;
	$iclTranslationManagement->init();
	$cf_settings = $iclTranslationManagement->settings['custom_fields_translation'];
	$rows = array();
	$add = 0;
	$types = get_post_types('', 'objects');
	foreach ($types as $name => $type) {
		if (in_array($name, array('attachment', 'revision', 'nav_menu_item'))) {
			continue;
		}
		$source_code = $saved['from'] == $sitepress->get_default_language() ? 'IS NULL' : "= '" . $saved['from'] . "'";
		$posts_query = "SELECT p.ID, p.post_title, p.post_content
                    FROM {$wpdb->prefix}posts p
                    JOIN {$wpdb->prefix}icl_translations t
                    WHERE p.post_type = %s
                    AND t.element_type = %s
                    AND t.element_id = p.ID
                    AND t.language_code = %s
                    AND p.post_status = 'publish'
                ";
		$posts_query_prepared = $wpdb->prepare($posts_query, array($name, "post_".$name, $saved['from']) );
		$posts = $wpdb->get_results($posts_query_prepared);
		$rows[$name]['ID'] = $name;
		$rows[$name]['title'] = $type->label;
		if (empty($posts)) {
			$rows[$name]['words'] = 0;
			$rows[$name]['num'] = 0;
			continue;
		}
		$rows[$name]['words'] = 0;
		foreach ($posts as $post) {
			$meta_count = 0;
			if (!empty($cf_settings)) {
				foreach ($cf_settings as $meta_key => $translate) {
					if ($translate == 2) {
						$meta = get_post_meta($post->ID, $meta_key);
						if (is_string($meta)) {
							$meta_count += str_word_count(strip_tags($meta));
						} else {
							foreach($meta as $meta_item) {
								$meta_count += str_word_count(strip_tags($meta_item));
							}
						}
					} else {
						unset($cf_settings[$meta_key]);
					}
				}
			}
			$add = $meta_count + str_word_count(strip_tags($post->post_title)) + str_word_count(strip_tags($post->post_content));
			$rows[$name]['words'] += $add;
		}
		$rows[$name]['num'] = count($posts);
	}

	?>
	<h1><?php _e('Content Types', 'sitepress'); ?></h1>
	<p>
		<?php printf(__('Your site includes different kinds of content items. Choose which types to include in the quote. <br /><br />To get the word count of specific documents, use the %sTranslation Dashboard%s.',
		                'sitepress'), '<a href="admin.php?page=' . WPML_TM_FOLDER . '/menu/main.php">', '</a>'); ?>
	</p>
	<input type="hidden" name="step" value="2" />
	<table border="0" cellpadding="5" cellspacing="15" class="widefat" style="margin-top: 15px;">
		<thead>
		<tr>
			<th></th>
			<th><?php _e('Type', 'sitepress'); ?></th>
			<th><?php _e('Number of items', 'sitepress'); ?></th>
			<th><?php _e('Number of words', 'sitepress'); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php
		foreach ($rows as $type => $data) {
			$selected = @is_array($saved['content']) && @array_key_exists($data['ID'], $saved['content']) ? ' checked="checked"' : '';

			?>
			<tr>
				<td>
					<input type="checkbox" name="content[<?php echo $data['ID']; ?>]" value="1" class="icl-quote-get-content-checbox" onclick="iclQuoteGetCheckContentCb();"<?php echo $selected; ?> />
					<input type="hidden" name="description[<?php echo $data['ID']; ?>][title]" value="<?php echo $data['title']; ?>" />
					<input type="hidden" name="description[<?php echo $data['ID']; ?>][num]" value="<?php echo $data['num']; ?>" />
					<input type="hidden" name="description[<?php echo $data['ID']; ?>][words]" value="<?php echo $data['words']; ?>" />
				</td>
				<td><?php echo $data['title']; ?></td>
				<td><?php echo $data['num']; ?></td>
				<td><?php echo $data['words']; ?></td>
			</tr>
		<?php
		}

		?>
		</tbody>
	</table>
	<p>
		<input type="submit" id="icl-quote-back-1" value="<?php _e('Back', 'sitepress'); ?>" name="back" class="button-secondary icl-quote-get-back" />
		<input type="submit" id="icl-quote-next-2" value="<?php _e('Continue', 'sitepress'); ?>" name="next" disabled="disabled" class="button-secondary icl-quote-get-next" />
	</p>
<?php
}

/**
 * Step three (create or save)
 *
 * @param array $saved
 */
function icl_quote_get_step_three($saved) {
	if ($saved['content']) {
		$wc_description = array();
		foreach ($saved['content'] as $ID => $true) {
			$wc_description[] = $saved['description'][$ID]['num'] . ' '
			                    . $saved['description'][$ID]['title'] . ' with '
			                    . $saved['description'][$ID]['words'] . ' words';
		}

		?>
		<h1><?php _e('Summary', 'sitepress'); ?></h1>
		<?php _e('You have selected the following content:', 'sitepress'); ?>
		<br /><br />
		<ul style="list-style: square; margin-left: 15px;">
			<li><?php echo implode('</li><li>', $wc_description); ?></li>
		</ul>
		<input type="hidden" name="step" value="3" />
		<p>
			<input type="submit" id="icl-quote-back-2" value="<?php _e('Back', 'sitepress'); ?>" name="back" class="button-secondary icl-quote-get-back" />
			<input type="submit" value="<?php _e('Produce Quote', 'sitepress'); ?>" name="submit-produce" id="icl-quote-get-submit-produce" class="button-primary" />
			<input type="submit" value="<?php _e('Save for later', 'sitepress'); ?>" name="submit-for-later" id="icl-quote-get-submit-for-later" class="button-secondary" />
		</p>
	<?php
	}
}

?>
<div id="icl-quote-get-wrap" style="margin: 25px 0 0 0">
	<form id="icl-quote-get-form" action="" method="post">
		<input type="hidden" name="icl_ajx_action" value="quote-get-submit" />
		<?php wp_nonce_field('quote-get-submit_nonce', '_icl_nonce'); ?>
		<?php
		global $sitepress, $sitepress_settings;
		$continue = FALSE;
		$saved = array();

		if (isset($sitepress_settings['quote-get'])) {
			if ($sitepress_settings['quote-get']['step'] == 3) {
				$continue = TRUE;
			}
			$saved = $sitepress_settings['quote-get'];
		}

		if (isset($data['back'])) {
			$data['step'] -= 1;
		} else if (isset($data['next'])) {
			$data['step'] += 1;
		}

		if ($continue && (!isset($data['next']) && !isset($data['back']))) {
			icl_quote_get_step_three($saved);
		} else if (!isset($data['step']) || $data['step'] == 1) {
			if (isset($data['back'])) {
				$saved['content'] = empty($data['content'])?'':$data['content'];
				$saved['description'] = $data['description'];
			}
			$saved['step'] = 1;
			$sitepress->save_settings(array('quote-get' => $saved));
			icl_quote_get_step_one($saved);
		} else if ($data['step'] == 2) {
			if (isset($data['next'])) {
				$saved['from'] = $data['from'];
				$saved['to'] = $data['to'];
			}
			$saved['step'] = 2;
			$sitepress->save_settings(array('quote-get' => $saved));
			icl_quote_get_step_two($saved);
		} else if ($data['step'] == 3) {
			if (isset($data['next'])) {
				$saved['content'] = $data['content'];
				$saved['description'] = $data['description'];
			}
			$saved['step'] = 3;
			$sitepress->save_settings(array('quote-get' => $saved));
			icl_quote_get_step_three($saved);
		}

		?>
	</form>
</div>
