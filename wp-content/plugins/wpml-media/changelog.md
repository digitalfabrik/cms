# 2.1.13

## New
* Updated dependency check module

# 2.1.12

## New
* Updated dependency check module

# 2.1.11

## New
* Updated dependency check module

# 2.1.9

## Fix
* Enabled media translation for files uploaded with XML-RPC

##Improvements
* Plugin dependency to core

# 2.1.7

## Compatibility
* Achieved compatibility with Media Library Assistant plugin

## Fix
* Media Translation now supports wildcard when querying for media by its mime type
* Fixed thumbnails of images in secondary language
* Added support for Trash in Media Library

# 2.1.6

## Compatibility
* Fixed compatibility with Types plugin - execution of save_post hooks respects now other plugins

## Fix
* Fixed issue with hundreds of duplicated images
* Fixed: Language_filter_upload_page() doesn't support multiple mime types and mime types with wildcards

# 2.1.5

## Improvements
* New way to define plugin url is now tolerant for different server settings

## Fix
* Fixed media item list in different languages: if some plugin adds its own parameter to URL, lang parameter was not concatenated correctly
* Removed references to global $wp_query in query filtering functions
* When you import posts, WPML created unnecessary media attachments. It is fixed now

# 2.1.4

## Fix
* Handled case where ICL_PLUGIN_PATH constant is not defined (i.e. when plugin is activated before WPML core)
* Fixed Korean locale in .mo file name

# 2.1.3

## Fix
* Handled dependency from SitePress::get_setting()
* Updated translations
* Several fixes to achieve compatibility with WordPress 3.9
* Updated links to wpml.org

# 2.1.2

## Performances
* Reduced the number of calls to *$sitepress->get_current_language()*, *$this->get_active_languages()* and *$this->get_default_language()*, to avoid running the same queries more times than needed

## Feature
* Added WPML capabilities (see online documentation)

## Fix
* Improved SSL support for included CSS and JavaScript files
