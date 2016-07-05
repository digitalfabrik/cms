<?php

if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );

	
function agp_is_ie6() {
	$agent = strtolower($_SERVER['HTTP_USER_AGENT']);
	
	if ( (false !== strpos($agent, 'msie 6.0') || false !== strpos($agent, 'msie 5.5')) 
	&& ( false === strpos($agent, 'opera') &&  false === strpos($agent, 'firefox') && false === strpos($agent, 'safari') ) )
		return true;
}


// Box CSS derived from "Even more rounded corners" by Scott Schiller
//	http://www.schillmania.com/content/projects/even-more-rounded-corners/
echo ('
<ul class="masthead"><li id="masthead-main" title="Agapetry Creations"><a class="agp-toplink" href="http://agapetry.net"> </a></li></ul>
<div id="wrap" style="height: 100%">
');

echo('
<div class="rc-about-dialog">
<p>
<a title="agape" href="http://www.merriam-webster.com/cgi-bin/audio.pl?agape002.wav=agape" target="_blank">a<small><small>&#8226;</small></small>ga<small><small>&#8226;</small></small>p&eacute;</a> (&alpha;&gamma;&alpha;&pi;&eta;): 
');
_e('unselfish, benevolent love, born of the Spirit.', 'revisionary');
echo ('</p><p>');
_e('Agap&eacute; discerns needs and meets them unselfishly and effectively.', 'revisionary');
echo ('</p><p>');

printf(__('This WordPress plugin is part of my agap&eacute; try, a lifelong effort to love God and love people by rightly using the time and abilities He has leant me. As a husband, father, engineer, farmer and/or software developer, I have found this stewardship effort to be often fraught with contradiction. A wise and sustainable balancing of roles has seemed to elude me. Yet I want to keep trying, trusting that if God blesses and multiplies the effort, it will become agapetry, a creative arrangement motivated by benevolent love.  A fleeting childlike sketch of the beautiful %1$s chain-breaking agap&eacute;%2$s which %3$s Jesus Christ unleashed%4$s so %5$s freely%6$s and aptly on an enslaving, enslaved world.', 'revisionary'), '<a href="http://www.biblegateway.com/passage/?search=Isaiah%2059:1-60:3;Matthew%203:1-12;Luke%204:5-8;Matthew%205:1-48;Matthew%206:9-15;&version=50;" target="_blank">', '</a>', '<a href="http://www.biblegateway.com/passage/?search=Matthew%2020:20-28;Matthew%2026:36-49;John%2018:7-12;John%2019:1-30;1%20John%202:1-6;&version=47;" target="_blank">', '</a>', '<a href="http://www.biblegateway.com/passage/?search=Isaiah%2055;John%207:37-51;&version=47;" target="_blank">', '</a>');
echo '</p><p>';
printf(__('Although Role Scoper and Revisionary development was a maniacal hermit-like effort, it was only possible because of the clean and flexible %1$s WordPress code base%2$s.  My PHP programming skills grew immensely by the good examples set forth there and in plugins such as %3$s NextGen Gallery%4$s. I\'m not done learning, and look forward to some real-time cooperation with these and other developers now that my all-consuming quest has reached a stable plateau.', 'revisionary'), "<a href='http://codex.wordpress.org/Developer_Documentation' target='_blank'>", '</a>', "<a href='http://alexrabe.boelinger.com/wordpress-plugins/nextgen-gallery/' target='_blank'>", '</a>');
echo '</p><p>';
printf( __('Revisionary is currently available in the following languages, thanks to volunteer translators:', 'revisionary') );
echo '</p><ul class="rs-notes" style="margin-left: 1em">';
echo '<li>';
printf(__('Belorussian by %1$s', 'revisionary'), "<a href='http://pc.de/' target='_blank'>Marcis G.</a>");
echo '</li><li>';
printf(__('French by %s', 'revisionary'), "<a href='http://marc-olivier.ca/' target='_blank'>Marc-Olivier Ouellet</a>");
echo '</li><li>';
printf(__('Italian by %s', 'revisionary' ), "<a href='http://obertfsp.com' target='_blank'>Alberto Ramacciotti</a>");
echo '</li></ul><p>';
_e( 'I do try to be translator-friendly, but any untranslated captions are likely due to a flurry of recent additions and changes by the plugin developer.  Now there must be someone else who wants Role Scoper in their language...', 'scoper');
echo '</p><p>';
printf(__( 'Revisionary is open source software released under the %1$s General Public License (GPL)%2$s. Due to limitations, obligations and non-technical aspirations common to most human beings, I will probably never again pursue uncommissioned plugin development on the scale these plugins has required. However, I do plan to provide some free support, correct bugs which emerge and revise the plugin for future WordPress versions. If it adds value to your website or saves you time and money, you can express appreciation in several ways:', 'revisionary'), '<a href="http://www.opensource.org/licenses/gpl-license.php">', '</a>');
echo '</p><ul id="agp-thanks" class="rs-notes" style="margin-left: 1em"><li>';
printf(__('%1$s Submit technical feedback%2$s, including improvement requests.', 'revisionary'), '<a href="http://agapetry.net/forum/">', '</a>');
echo '</li><li>';
printf(__('%1$s Submit a case study%2$s, explaining how these plugins help you do something excellent and praiseworthy.', 'revisionary'), '<a href="http://agapetry.net/forum/">', '</a>');
echo '</li><li>';
printf(__('%1$s Localize Revisionary or Role Scoper%2$s to your own language %3$s using poEdit%4$s ', 'revisionary'), '<a href="http://weblogtoolscollection.com/archives/2007/08/27/localizing-a-wordpress-plugin-using-poedit/">', '</a>', '<a href="http://weblogtoolscollection.com/archives/2007/08/27/localizing-a-wordpress-plugin-using-poedit/">', '</a>');
echo '</li><li>';
$paypal_button = '<form action="https://www.paypal.com/cgi-bin/webscr" method="post" class="donate"><input type="hidden" name="cmd" value="_s-xclick" /> <input type="image" style="background:none" src="http://agapetry.net/btn_donate_SM.gif" name="submit" alt="PayPal - The safer, easier way to pay online!" /> <img alt="" border="0" src="http://agapetry.net/pixel.gif" width="1" height="1" style="opacity:0.01" /> <input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
" /></form>';
printf(__('If the plugin has seriously broadened your CMS horizons, %s', 'revisionary'), $paypal_button);
echo '</li><li>';
printf(__('If you are an established web developer, %1$s grant me your professional opinion%2$s on how this work stacks up. Might the skills, work ethic and values I express here fit into a development team near you?', 'revisionary'), '<a href="http://agapetry.net/general-contact-form/">', '</a>');
echo '</li><li>';
printf(__('Hire or refer my services</a> to design, redesign or enhance your site - quality care at reasonable rates.', 'revisionary'), '<a href="http://agapetry.net/service-exploration-form/">', '</a>');
echo '</li></ul>';

//if ( $status = rvy_remote_fopen( 'http://localhost/aglocal/downloads/status.htm', 5) )
if ( $status = rvy_remote_fopen( 'http://agapetry.net/downloads/status.htm', 5) )
	echo $status;

echo '</div>'; //rc-about-dialog

if ( ! agp_is_ie6() )
	echo '<div class="madein alignright" style="margin-right: 1em;">&nbsp;</div>';

echo '<div style="height: 150px;">&nbsp;</div>';
	
echo '</div>'; //wrap
?>