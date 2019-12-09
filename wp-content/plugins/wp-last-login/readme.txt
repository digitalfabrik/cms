=== WP Last Login ===
Contributors: obenland
Tags: admin, user, login, last login, plugin
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=K32M878XHREQC
Requires at least: 3.1
Tested up to: 5.3
Stable tag: 3

Make the last login for each user visibile in the user overview.

== Description ==

This plugin adds an extra column to the users overview with the date of the last login for each user.
Additionally, users can be sorted by the date of their last login.

= Translations =
I will be more than happy to update the plugin with new locales, as soon as I receive them!
Currently available in:

* Arabic
* Chinese
* French
* English
* German
* Italian
* Japanese
* Nederlands
* Polish
* Portuguese
* Rumanian
* Russian
* Spanish


== Installation ==

1. Download WP Last Login.
2. Unzip the folder into the `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.


== Frequently Asked Questions ==

None asked yet.


== Screenshots ==

1. Last login column in the user table.


== Changelog ==

= 3 =
* Fixed a bug where users who haven't logged in disappear from user lists when ordering by last login. See https://wordpress.org/support/topic/new-users-dont-get-the-meta-field/

= 2 =
* Maintenance release.
* Updated code to adhere to WordPress Coding Standards.
* Tested with WordPress 5.0.

= 1.4.0 =
* Fixed a long standing bug, where sorting users by last login didn't work.
* Tested with WordPress 4.3.

= 1.3.0 =
* Maintenance release.
* Tested with WordPress 4.0.

= 1.2.1 =
* Reverts changes to wp_login() as the second argument seems not to be set at all times.

= 1.2.0 =
* Users are now sortable by last login!
* Updated utility class.
* Added Danish translation. Props thomasclausen.

= 1.1.2 =
* Fixed a bug where content of other custom columns were not displayed.

= 1.1.1 =
* Updated utility class.

= 1.1.0 =
* Made the display of the column filterable.
* Widened the column a bit to accomodate for large date strings.

= 1.0 =
* Initial Release.


== Upgrade Notice ==


== Plugin Filter Hooks ==

**wpll_current_user_can** (*boolean*)
> Whether the column is supposed to be shown.
> Default: true


**wpll_date_format** (*string*)
> The date format string for the date output.
