=== WP GCM ===
Contributors: pixelart-dev
Plugin  URI: http://wordpress.org/plugins/wp-gcm
Tags: gcm, c2d, android, google, cloud, messaging, google cloud messaging, wp gcm, wp-gcm,
Donation Link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=2YCTDL7AFRHHG
Requires at least: 3.5
Tested up to: 4.1
Stable tag: 1.2.8
License: GPLv3
License URI: http://www.gnu.org/licenses/old-licenses/gpl-3.0.html

A WordPress Plugin for sending GCM messages trough the WordPress backend.

== Description ==

= NO SUPPORT ANYMORE FOR THIS PLUGIN!! GO TO CODECANYON FOR THE LATEST VERSION =
<http://codecanyon.net/item/wp-gcm/9942568?ref=PixelartDev>

With this plugin you can send messages using Google Cloud Messaging to your Android Apps, when they're using GCM. 
You just need your Api-Key!

This Plugin can notify, when you want it, your users with your GCM App when a new post is published or a post was updated.
Of course you can also write them messages, e.g. when a special post is posted, or something like that

You can ONLY use this Plugin if you have an Android App which uses GCM AND is connected to your api-key. 
If you don't know what GCM is then please go to: http://developer.android.com/google/gcm/index.html

Now the Plugin deletes Device IDs if they no longer active and it deletes also the ID of a device if the app was uninstalled.
And now it also deletes multiple IDs from the same device.
So your database is cleaner!


= Demo Android Project =
Now there is finally a Demo Android Project available on GitHub!
<https://github.com/Pixelartdev/GCM-Android-App/> !!OUT DATED!!
Have fun with it!

== Installation ==
1. Upload the folder 'wp-gcm' to the '/wp-content/plugins' directory on your server,
2. Go to 'Plugins' trough the admin dashboard and activate the plugin,
3. After the activation you will be redirected to the settings page where you can setup the plugin. You need your Api-key,
4. When everything is setted up click save, and your finished!,
Now you can write Messages.
___________

In your Android App you need to set the registration url to the following: '{BLOGURL}'
and just include the 'regId' parameter in the url containing the device regId.
A sample url: 'http://www.myblog.com/?regId=AbCdEfG12345'
When you need help, visit: <http://developer.android.com/google/gcm/index.html>

== Frequently Asked Questions ==
= Where to find the Api Key ? =
A: just go to the Api Console: <https://code.google.com/apis/console/> log in and navigate to > your project > to Api Access.


= How to setup a GCM Android App ? =
A: You could go to: http://developer.android.com/google/gcm/index.html , and follow the tutorial, or you can send me an eMail for the sample Android Project I wrote.


= Is there a demo site ? =
A: Yes! Go to <http://px.hj.cx/wp/wp-admin>,you'll find there also a working app!


= Is this plugin translation ready ? =
A: Yes, just copy the px_gcm.pot file from the lang directory of the plugin and translate it to your language. 
If you want to contribute send me your translation and I will add it to the plugin!



== Screenshots ==
1. Writing a message,

2. Settings page,

== Changelog ==
* 1.2.5 Fixeed Error with the debug message (Thanks again to oeoeoeooeo <http://wordpress.org/support/profile/oeoeoeooeo> ),
* 1.2.4 Fixed Error Message on a blank installation (Thanks to oeoeoeooeo <http://wordpress.org/support/profile/oeoeoeooeo> ),
* 1.2.3 Fixed Update Notification Bug (Thanks to fiddelindell <http://wordpress.org/support/profile/fiddelindell> ),
* 1.2.2 Added Debug Response
        and French localization,
* 1.2.1 Bugs fixed,
* 1.2.0 A few little improvements, and added a demo site.,
* 1.1.9 Public Release,