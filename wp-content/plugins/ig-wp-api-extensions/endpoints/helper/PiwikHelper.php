<?php

function ig_api_page_tracking ( $call_name ) {
    /*
     * Contact Tracking server and save API hit
     */
    if ( PIWIK_ENABLE_TRACKING !== "yes" ) {
        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        syslog(LOG_NOTICE, "INTEGREAT CMS - X-Integreat-Development $url");
        return;
    }
    $token = get_blog_option(get_current_blog_id(), "wp-piwik_global-piwik_token");
    if ( !$token or isset( $_SERVER['HTTP_X_INTEGREAT_DEVELOPMENT'] ) ) {
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
