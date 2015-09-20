# WP API Modified Content
WordPress Plugin for the WP REST API to retrieve only content that has been modified since a given datetime

## Installation
Go to Plugins > Add New > Upload and upload this repository's zip

## Datetime Parameter
The last part of any url is the GMT datetime after which all modifications are retrieved.
The expected format is `Y-m-d G:i:s` (see the [PHP Documentation](http://php.net/manual/en/function.date.php)),
for instance `2015-09-20 15:37:25`.

## Endpoints
`modified_content/posts_and_pages/<datetime>` gives you a list of all modified posts and pages
