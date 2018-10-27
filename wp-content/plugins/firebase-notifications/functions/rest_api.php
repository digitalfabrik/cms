<?php
/**
 * Get messages for API. Contains only content that is intended for the public:
 * message, title, timestamp
 *
 * @param string $to filter by topic
 * @return array
 */
function fcm_rest_api_messages ( $language, $to = Null ) {
    $fcmdb = New FirebaseNotificationsDatabase();
    $newer_than = date('Y-m-d G:i:s', time() - ( 2 * 7 * 24 * 3600 ));
    $messages = $fcmdb->messages_by_language( $lang = $language, $amount = 0, $timestamp = $newer_than );
    $return = array();
    $n = 0;
    foreach ( $messages as $message ) {
        if ( $message['answer'] != Null && ( $to === Null || $message['request']['to'] == "/topics/" . $to ) ) {
            $return[$n]['timestap'] = $message['timestamp'];
            $return[$n]['title'] = $message['request']['notification']['title'];
            $return[$n]['message'] = $message['request']['notification']['body'];
            $n++;
        }
    }
    return $return;
}
