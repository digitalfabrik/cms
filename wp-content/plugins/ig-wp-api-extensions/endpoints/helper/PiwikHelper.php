<?php

function ig_api_page_tracking () {
    /*
     * Contact Tracking server and save API hit
     */
    $idSite = get_blog_option("piwik_site_id");
    $piwikTracker = new PiwikTracker( $idSite = $idSite );
    $piwikTracker->setTokenAuth( PIWIK_AUTH_TOKEN );
    $piwikTracker->setResolution(1, 1);
    $piwikTracker->setLocalTime(date('H:i:s')); //HH:MM:SS
    $piwikTracker->setUrl("https://".$_SERVER[HTTP_HOST].$_SERVER[REQUEST_URI]);
    $piwikTracker->setIp('1.1.1.1');

    $piwikTracker->doTrackPageView('Integreat API');
}