#2.1.5

##Fix
* [wpmltm-1154] Fixed issues in possible database inconsistencies when choosing to cancel all local translation jobs after activating a translation service

##Performances
* [wpmlcore-2528] Cached calls to `glob()` function when auto loading classes

##Cleanup
* [wpmlcore-2541] Removal of "icon-32" usage

#2.1.4

##Feature
* [wpmlcore-538] Added an informative message to promote WCML when WooCoomerce is installed but WCML is not
  
#2.1.3

##Fixes
* Added backward compatibility for `__DIR__` magic constant not being supported before PHP 5.3.

#2.1.2

##Fixes
* [wpmlga-96] WordPress 4.4 compatibility: pulled all html headings by one (e.g. h2 -> h1, he -> h2, etc.)
* [wpmltm-811] Fixed an UI issue in several admin pages with checkboxes being wrongly aligned
* [wpmltm-966] Fixed some UI issues caused by changes in WordPress 4.4 styles

#2.1.1

##Fixes
* [wpmlst-668] Fix message in dashboard about missing slug translations
* [wpmltm-970] Fix issue with message for missing php settings/extensions not hiding
* [wpmltm-959] Escape html in Post titles under the Translation jobs page
* [wpmltm-1008] Fixed an issue causing users that were translators but did not have administrator capabilities to not be able to access Translation Management functionality on sites that were set to only have hidden secondary languages.

##Performances
* [wpmltm-963] Calculation of words count is now done through multiple AJAX calls and with proper progress feedback

#2.1

#Features
* [wpmlst-505] Add support for sending strings in any language to the translation basket
* [wpmltm-688] Lost connections between translations jobs in WPML and TP due to rolling back a site from a backup are now repaired automatically in many cases
* [wpmltm-783] Added action in Translation Jobs tab, to trigger translation download for batches
* [wpmltm-777] Added words count feature in Translation Dashboard
* [wpmltm-931] Added check for required php ini settings for allow_url_fopen and php_openssl extension

##Fixes
* [wpmlcore-2212] Password-protected posts and private status are properly copied to translations, when this setting is enabled
* [wpmltm-736] Notes to translators are sent again
* [wpmltm-880] Fix so that post format is synchronized as required
* [wpmltm-928] Fixed count of documents in WPML Dashboard widget
* [wpmltm-924] Fixed issue of Translation Jobs listing when String translations is not activated

##API

###Filters:
* [wpmltm-801, wpmltm-797] `wpml_is_translator`
* [wpmltm-801, wpmltm-800] `wpml_translator_languages_pairs`

###Actions
* [wpmltm-801, wpmltm-799] `wpml_edit_translator`

###Performances
* [wpmlcore-1347] Improved multiple posts duplication performances

#2.0.5

##Fixes
* [wpmltm-714] WPML won't activate Translation Service if the project has not been created in TP. This fix is also in preparation of the migration for ICanLocalize users.

#2.0.4

##Features
* [wpmltm-787] Allow to completely disable translation services from appearing in the translators tab by setting the `ICL_HIDE_TRANSLATION_SERVICES` constant

#2.0.3

##Fixes
* Translation Editor now shows existing translated content, if there was a previous translation
* Translation Editor won't changes language pairs for translators anymore
* Titles for packages and posts won't get mixed up in the translation jobs table anymore
* Users set as translators can translate content again, using the translation editor, even if there is not a translation job created for that content
* An editor can translate content if he's set as a translator

#2.0.2

##New
* Updated dependency check module

#2.0.1

##New
* Updated dependency check module

#2.0

##New
* Handle translation jobs in batches/groups
* Select other translation services for professional translation
* Now, shortcodes are not considered in the estimation of the number of words of post content 
* Translation Analytics and XLIFF plugins are now embedded into Translation Management (some features might be disabled until the next version)

##Performances
* Improved performances
* General improvements in the quality of the JavaScript and PHP code

##Fix
* Fixed PHP warning on the Add translator screen when no Translation Service was set yet
* Fixed checkbox validation in Translation Editor
* Fixed issues with translations when switching from Translation editor to WordPress editor
* Fixed SQL error when using Professional translation
* Fixed wrong category assignment when translating via the Translation editor

#1.9.8

##Fix
* Fixed a style issue with the "View Original" link of Translation Jobs table

#1.9.7

##Improvements
* Support for string translation packages 
* Removed PHP warning when in Translation Dashboard and only one language is defined. Replaced with an admin notice

##Fix
* Fixed issue with in proper notices in Translation Editor when user tries to translate document which was assigned to another user before
* Fixed issue with "Copy from" in Translation Editor 
* Fixed multiple issues with translation of hierarchical taxonomies

#1.9.6

##Improvements
* Compatibility with WPML Core

#1.9.5

##Improvements
* New way to define plugin url is now tolerant for different server settings
* Support for different formats of new lines in XLIFF files

##Fix
* Fixed possible SQL injections
* When you preselect posts with status "Translation Complete" on WPML > Translation Management dashboard, it show wrong results. This is fixed now

#1.9.4

##Improvements
* Defining global variables to improve code inspection

##Fixes
* Removed notice after "abort translation"
* Updated links to wpml.org
* Fixed Translation Editor notices in wp_editor()
* Handled case where ICL_PLUGIN_PATH constant is not defined (i.e. when plugin is activated before WPML core)
* Fixed Translation Editor - Notice: wp_editor() and not working editors in WP3.9 (changes for additional fields)
* Fixed not working "Copy from..." links for Gravity forms fields
* Fixed Korean locale in .mo file name

#1.9.3

##Fixes
* Handled dependency from SitePress::get_setting()
* Changed vn to vi in locale files
* Updated translations
* Replace hardcoded references of 'wpml-translation-management' with WPML_TM_FOLDER

#1.9.2

##Performances
* Reduced the number of calls to *$sitepress->get_current_language()*, *$this->get_active_languages()* and *$this->get_default_language()*, to avoid running the same queries more times than needed

##Features
* Added WPML capabilities (see online documentation)

##Fixes
* Improved SSL support for CSS and JavaScript files
