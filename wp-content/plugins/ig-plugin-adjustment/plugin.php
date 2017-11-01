<?php

/**
 * Plugin Name: Plugin Adjustment
 * Description: Automatically adjust plugin content (e.g. resources) after an upgrade
 * Version: 0.1
 * Author: Integreat
 * Author URI: https://github.com/Integreat
 * License: MIT
 */

add_action('upgrader_process_complete', function ($upgrader_object, $options) {
	if (strcasecmp($options['type'], 'plugin') != 0) // only adjust plugins
		return true;
	$adjuster = new PluginAdjustment();
	return true;
}, 10, 2);

class PluginAdjustment {

	private function replace_line_in_file($filepath, $search, $replace) {
		$line_found = false;
		$content = file($filepath); // reads an array of lines
		$content = array_map(function ($line) use ($search, $replace, $filepath, &$line_found) {
			if (stristr($line, $search)) {
				if ($line_found) {
					trigger_error("Search '$search' found in more than one line in file '$filepath'", E_USER_WARNING);
				}
				$line_found = true;
				return str_replace($search, $replace, $line);
			} else {
				return $line;
			}
		}, $content);
		file_put_contents($filepath, implode('', $content));
		if (!$line_found) {
			die("Could not find '$search' in file '$filepath'");
		}
	}
}
