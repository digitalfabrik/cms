<?php

function ig_api_page_tracking () {
    /*
     * Contact Tracking server and save API hit
     */
    $token = get_blog_option(get_current_blog_id(), "wp-piwik_global-piwik_token");
    var_dump($token);
    if ( !$token ) {
        echo "Fallback<br>";
        $token = PIWIK_DEFAULT_AUTH_TOKEN;
        $idSite = PIWIK_DEFAULT_SITE_ID;
    } else {
        $idSite = get_blog_option(get_current_blog_id(), "wp-piwik-site_id");
    }
    var_dump($token);
    var_dump($idSite);
    $piwikTracker = new PiwikTracker( $idSite = $idSite );
    $piwikTracker->setTokenAuth( $token );
    $piwikTracker->setResolution(1, 1);
    $piwikTracker->setLocalTime(date('H:i:s')); //HH:MM:SS
    $piwikTracker->setUrl("https://".$_SERVER[HTTP_HOST].$_SERVER[REQUEST_URI]);
    $piwikTracker->setIp('1.1.1.1');

    $piwikTracker->doTrackPageView( 'Integreat API '. ICL_LANGUAGE_CODE );
}
?>