<?php
/**
 * @package wpml-core
 */

/*
 * Thickbox form submit process
 */
global $sitepress, $sitepress_settings, $wpdb;
// @todo: This does nothing at this point it seems, it was calling a method on an interface ...
/**
 * Save for later
 */
if (isset($data['submit-for-later'])) {
	$saved = $sitepress_settings['quote-get'];
	$saved['step'] = 3;
	$sitepress->save_settings(array('quote-get' => $saved));
	echo '<script type="text/javascript">jQuery(\'#TB_closeWindowButton\').trigger(\'click\');</script>';

	/**
	 * Produce quote
	 */
} else if (isset($data['submit-produce'])) {
	$saved = $sitepress_settings['quote-get'];
	if (empty($saved['from'])
	    || empty($saved['to'])
	    || empty($saved['content'])
	) {
		die('data not valid');
	}
	$word_count = 0;
	$wc_description = array();
	foreach ($saved['content'] as $ID => $true) {
		$wc_description[] = $saved['description'][$ID]['num'] . ' '
		                    . $saved['description'][$ID]['title'] . ' with '
		                    . $saved['description'][$ID]['words'] . ' words';
		$word_count += intval($saved['description'][$ID]['words']);
	}
	$wc_description = implode(', ', $wc_description);
	$language_pairs = array($saved['from'] => $saved['to']);

	echo '<script type="text/javascript">iclShowQuoteResult();</script>';
	exit;
}
