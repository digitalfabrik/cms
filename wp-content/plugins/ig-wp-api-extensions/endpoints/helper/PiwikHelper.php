<?php

function ig_api_page_tracking () {
    /*
     * Contact Tracking server and save API hit
     */
    $piwikTracker = new PiwikTracker( $idSite = {$IDSITE} );
    $piwikTracker->setTokenAuth('my_token_auth_value_here');
    $piwikTracker->setResolution(1, 1);
    $piwikTracker->setLocalTime($time); //HH:MM:SS
    $piwikTracker->setUrl("https://".$_SERVER[HTTP_HOST].$_SERVER[REQUEST_URI]);
    $piwikTracker->setIp('1.1.1.1');

    $piwikTracker->doTrackPageView('Integreat API');
}