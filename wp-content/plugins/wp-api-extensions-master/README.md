# WP API Extensions
Collection of extensions to the Wordpress REST API (WP API)

The following routes are added:
* `extensions/v0/modified_content/posts_and_pages/<datetime>` returns all modified posts and pages
   since the given datetime (in the format [`Y-m-d G:i:s`](http://php.net/manual/en/function.date.php), for instance `2015-09-20 15:37:25`)
* `extensions/v0/languages/wpml` returns the languages available through the WPML plugin
* `extensions/v0/multisites/` returns the multisites of the network

## Installation
Go to Plugins > Add New > Upload and upload this repository's zip
