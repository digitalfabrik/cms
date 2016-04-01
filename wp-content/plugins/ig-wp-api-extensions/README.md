# WP API Extensions
Collection of extensions to the Wordpress REST API (WP API)
under development as part of a [project for refugees in Germany](http://vmkrcmar21.informatik.tu-muenchen.de/wordpress/)

This plugin has various adjustments for the mentioned project
that probably don't fit to your project.
Until those are refactored out, you might have to adjust the source code
in order to make the plugin work for you.

The following routes are added:
* `extensions/v0/modified_content/pages?since=<datetime>` returns all modified pages (`/posts` for posts)
   since the given datetime (in the [ISO8601 format `Y-m-dTG:i:sZ`](http://php.net/manual/en/class.datetime.php#datetime.constants.atom),
   for instance `2015-09-20T15:37:25Z`).
   Also provides other available languages (through wpml) for the page or post.
* `extensions/v0/languages/wpml` returns the languages available through the WPML plugin
* `extensions/v0/multisites/` returns the multisites of the network
   * exclude sites by adding their id to the exclusion list
   * mark sites as `global` by adding their id to the global list

## Installation
Go to Plugins > Add New > Upload and upload this repository's zip
