<?php

/* Author Chat Process v1.6.0 */

define('aURL', 'https://ordin.pl/auth/author_chat/author_chat.csv');

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
    global $wpdb;
    $author_chat_table = $wpdb->prefix . 'author_chat';
    $function = filter_var($_POST['function'], FILTER_SANITIZE_STRING);
    $result = array();

    switch ($function) {
        case( 'getState' ):
            $result = $wpdb->get_var("SELECT COUNT(*) FROM $author_chat_table");
            break;

        case( 'send' ):
            $user_id = strip_tags(filter_var($_POST['user_id'], FILTER_SANITIZE_STRING));
            $nickname = strip_tags(filter_var($_POST['nickname'], FILTER_SANITIZE_STRING));
            $message = strip_tags(filter_var($_POST['message'], FILTER_SANITIZE_STRING));
            if (( $message ) != '\n') {
                $result = array(
                    'user_id' => $user_id,
                    'nickname' => $nickname,
                    'content' => $message,
                    'date' => date('Y-m-d H:i:s')
                );

                $wpdb->insert($author_chat_table, $result, array('%d', '%s', '%s', '%s'));
            }
            break;

        case( 'update' ):
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
                'date' => $date
            );
            break;

        case( 'initiate' ):
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
                'date' => $date
            );
            break;
    }
    echo wp_send_json($result);
}
?>