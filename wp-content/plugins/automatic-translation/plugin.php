<?php
/**
 * Plugin Name: Automatic Translation
 * Description: Automatically translates all kinds of posts on save and links them for WPML
 * Version: 0.1
 * Author: Martin Schrimpf
 * Author URI: https://github.com/Meash
 * License: MIT
 */

require_once __DIR__ . '/activation.php';

require_once __DIR__ . '/TranslationManager.php';

$translator = new TranslationManager();
$translator->add_save_post_hook();
