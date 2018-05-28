<?php

function ig_api_page_tracking ( $call_name ) {
    /*
     * Contact Tracking server and save API hit
     */
    $token = get_blog_option(get_current_blog_id(), "wp-piwik_global-piwik_token");
    if ( !$token ) {
        $token = PIWIK_DEFAULT_AUTH_TOKEN;
        $idSite = PIWIK_DEFAULT_SITE_ID;
    } else {
        $idSite = get_blog_option(get_current_blog_id(), "wp-piwik-site_id");
    }
    $piwikTracker = new PiwikTracker( $idSite = $idSite );
    $piwikTracker->setTokenAuth( $token );

    // Privay overrides
    $piwikTracker->setBrowserLanguage('en');
    $piwikTracker->setUserAgent('');
    $piwikTracker->setResolution(1, 1);
    $piwikTracker->setIp(mt_rand(0,255).".".mt_rand(0,255).".0.0");

    $piwikTracker->doTrackPageView( $call_name );
}
?>
