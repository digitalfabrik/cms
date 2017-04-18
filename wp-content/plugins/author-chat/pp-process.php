<?php

if (!function_exists('array_column')) {

    function array_column(array $input, $columnKey, $indexKey = null) {
        $array = array();
        foreach ($input as $value) {
            if (!isset($value[$columnKey])) {
                trigger_error("Key \"$columnKey\" does not exist in array");
                return false;
            }
            if (is_null($indexKey)) {
                $array[] = $value[$columnKey];
            } else {
                if (!isset($value[$indexKey])) {
                    trigger_error("Key \"$indexKey\" does not exist in array");
                    return false;
                }
                if (!is_scalar($value[$indexKey])) {
                    trigger_error("Key \"$indexKey\" does not contain scalar value");
                    return false;
                }
                $array[$value[$indexKey]] = $value[$columnKey];
            }
        }
        return $array;
    }

}

if (isset($_POST['function'])) {
    $function = filter_var($_POST['function'], FILTER_SANITIZE_STRING);
    $log = array();
    global $wpdb;

    switch ($function) {

        case('updateCount'):
            $mydb = new wpdb(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);
            $author_chat_table = $wpdb->base_prefix.'author_chat';
            $linesCount = $mydb->get_var("SELECT COUNT(*) FROM $author_chat_table");
            $log = $linesCount;
            break;

        case('getState'):
            $mydb = new wpdb(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);
            $author_chat_table = $wpdb->base_prefix.'author_chat';
            $newLinesCount = $mydb->get_var("SELECT COUNT(*) FROM $author_chat_table");
            $log = $newLinesCount;
            break;

        case('send'):
	    global $current_user;
	    $mail = strip_tags(filter_var($_POST['email'], FILTER_SANITIZE_STRING));
	    $site = strip_tags(filter_var($_POST['site'], FILTER_SANITIZE_STRING));
            $nickname = strip_tags(filter_var($_POST['nickname'], FILTER_SANITIZE_STRING));
            $reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
            $message = strip_tags(filter_var($_POST['message'], FILTER_SANITIZE_STRING));
	    $mydb = new wpdb(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);
	    $author_chat_table = $wpdb->base_prefix.'author_chat';
	    $author_chat_color = $wpdb->base_prefix.'author_chat_color';
	    $site = "'"+$site+"'";
	    $lines = $mydb->get_results("SELECT tag, color FROM $author_chat_color where site=$site", ARRAY_A);
	    $tag = "";
	    $color = "";
	    foreach ($lines as $line){
		$tag = $line['tag'];
		$color = $line['color'];
	    }
            if (($message) != "\n") {
                if (preg_match($reg_exUrl, $message, $url)) {
                    $message = preg_replace($reg_exUrl, '<a href="' . $url[0] . '" target="_blank">' . $url[0] . '</a>', $message);
                }
                $mydb->query($mydb->prepare(
                                "INSERT INTO $author_chat_table (nickname, content, date, email, tag, color) VALUES (%s, %s, NOW(), %s, %s, %s)", $nickname, $message, $mail, $tag, $color
                ));
            }
            break;

        case('update'):
            $mydb = new wpdb(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);
            $author_chat_table = $wpdb->base_prefix.'author_chat';
            $lines = $mydb->get_results("SELECT nickname, content, date, email, tag, color FROM $author_chat_table ORDER BY id ASC", ARRAY_A);
            $text = array();
            foreach ($lines as $line) {
                $text[] = $line;
            }
            $log = array_column($text, 'nickname');
            $log2 = array_column($text, 'content');
            $log3 = array_column($text, 'date');
	    $log4 = array_column($text, 'email');
	    $log5 = array_column($text, 'tag');
	    $log6 = array_column($text, 'color');
            array_walk_recursive($log3, function(&$element) {
                $element = strtotime($element);
                $element = date('j. M y </\s\p\a\n> <\s\p\a\n \i\d="\t\i\m\e">G:i', $element);
            });
            break;

        case('initiate'):
            $mydb = new wpdb(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);
            $author_chat_table = $wpdb->base_prefix.'author_chat';
            $lines = $mydb->get_results("SELECT nickname, content, date, email, tag, color FROM $author_chat_table ORDER BY id ASC", ARRAY_A);
            $text = array();
            foreach ($lines as $line) {
                $text[] = $line;
            }
            $log = array_column($text, 'nickname');
            $log2 = array_column($text, 'content');
            $log3 = array_column($text, 'date');
	    $log4 = array_column($text, 'email');
	    $log5 = array_column($text, 'tag');
	    $log6 = array_column($text, 'color');
            array_walk_recursive($log3, function(&$element) {
                $element = strtotime($element);
                $element = date('j. M y </\s\p\a\n> <\s\p\a\n \i\d="\t\i\m\e">G:i', $element);
            });
            break;
    }
    if (isset($log2)) {
	//$user = wp_get_current_user();
        global $current_user;
	echo wp_send_json(array('result1' => $log, 'result2' => $log2, 'result3' => $log3, 'result4' => $log4, 'result5' => $log5, 'result6' => $log6));
    } else {
        echo wp_send_json($log);
    }
}
?>
