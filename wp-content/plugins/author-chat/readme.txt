=== Author Chat ===
Contributors: Piotr Pesta
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=EEDF5TV3M2WVG&lc=US
Plugin Name: Author Chat
Tags: plugin, chat, author, for authors, admin, messages, internal chat
Author: Piotr Pesta
Requires at least: 2.8.0
Tested up to: 4.7
Stable tag: 1.4.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Author Chat is an internal chat that let your authors (users with access to dashboard) to chat with each other. It is easy to use.

== Description ==

Author Chat is an internal chat that let your authors (users with access to dashboard) to chat with each other. It is easy to use. All chat data is stored in database. You can also configure how many days chat history should be stored in database.

If you wish to translate plugin, just add your translation file to /lang/ folder.

If you would like to show your support for this software, please consider donating: [Donate via PayPal](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=EEDF5TV3M2WVG&lc=US).

== Installation ==

1. Upload the zip to 'plugins' directory
2. Unzip (steps 1 and 2 can also be performed automatically)
3. Activate the plugin
4. Plugin is visible in a dashboard

Or just add .zip file as a new plugin in your Wordpress administration panel.

== Screenshots ==

1. Screenshot 1 - Admin Chat.
2. Screenshot 2 - Admin Chat in a dashboard.

== Changelog ==
= 1.4.3 =
* Updated for Wordpress 4.7 (replaced deprecated get_currentuserinfo function by wp_get_current_user)
= 1.4.1 =
* Support for language files (now you can translate plugin via e.g. Poedit, just add your translation file to /lang/ folder)
* Polish translation included
= 1.4.0 =
* Simple fix - compatibility with custom user roles
* Now you can choose how to display the authors: by Login or by Name
= 1.3.0 =
* Now you can restrict access to Author Chat and exclude Editor, Author, Contributor or Subscriber
= 1.2.0 =
* In settings you can delete chat history
= 1.1.0 =
* Added information about new messages: number of messages in browser tab title and sound signal
= 1.0.0 =
* Bugfix: now plugin supports servers with <5.5 PHP version
= 0.9.9 =
* Initial Release