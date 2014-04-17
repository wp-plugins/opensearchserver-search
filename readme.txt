=== OpenSearchServer Search ===
Contributors: ekeller,naveenann
Tags: search,search engine,opensearchserver, full-text, phonetic, filter, facet, indexation, auto-completion
Requires at least: 3.0.1
Tested up to: 3.9
Stable tag: 1.3.1
License: GPLv2 or later

The OpenSearchServer Search Plugin allows to use OpenSearchServer to enable full-text search on WordPress-based websites.

== Description ==

The OpenSearchServer Search Plugin allows to use OpenSearchServer to enable full-text search on WordPress-based websites.
OpenSearchServer is an high-performance search engine which includes spell-check, facet, filter, phonetic search, auto-completion.
This plugin replaces automatically the WordPress's built-in search functionnality.

Key Features

 * Full-text search with phonetic support,
 * Filter search results by Facet Field,
 * Automatic suggestion with autocompletion,
 * Spellcheking with automatic substitution,
 * Automatically index an post when you publish an post,
 * Search through posts and pages,
 * Easy to set up with just filling a form.

[youtube http://www.youtube.com/watch?v=_hnUMBLH-aw]
 
== Installation ==

= Requirements =

 * WordPress 3.0.1 or higher
 * A running instance of OpenSearchServer 1.3 or higher
 
= Installing an OpenSearchServer instance =

Two ways to get an OpenSearchServer instance:

 * Deploy it on your own server by reading the [OpenSearchServer documentation](http://www.open-search-server.com/documentation "OpenSearchServer documentation")
 * Or use an OpenSearchServer SaaS instance [OpenSearchServer SaaS service](http://www.open-search-server.com/services/saas_services "OpenSearchServer SaaS services")

= Installing the plugin =

   1. Check you have a running OpenSearchServer instance
   2. Unpack the plugin archive to wp-content/plugins folder of your WordPress installation.
   3. Activate the OpenSearchServer Search plugin via WordPress settings.
   4. Open OpenSearchServer settings page and just fill the form to create and index.

[youtube http://www.youtube.com/watch?v=_hnUMBLH-aw]

== Frequently Asked Questions ==

Q: What is OpenSearchServer?

A: Open Search Server is a search engine software developed under the GPL v3 open source licence. Read more on http://www.open-search-server.com

Q: How to update the search index?

A: Using the Reindex-Site button in the OpenSearchServer Settings page.while posting an page or an post OpenSearchServer plugin automatically indexes the 
   Post or page
   
Q: When i click Create-index/Save button or reindex button i got an exception with Bad credential 

A: Check the credential is correct that you have create in OpenSearchServer instance under the privilages tab.

Q: I get an error when I install opensearchserver "Fatal error: OSS_API won't work without curl extension in "opensearchserver-search\OSS_API.class.php" on line 23"

A: Check that you server is enabled with CURL extension else install it.
 
Q: How to customize/style the search page.

A: Copy the file opensearchserver_search.php from the directory wp-content/plugins/opensearchserver-search to your current theme folder (wp-content/themes/twentyfourteen).
   Customize the layout as per your needs.

== Screenshots ==

1. The admin page.
2. An example of search result.
== Changelog ==

= 1.3.1 =
* Multiple fixes.
* Add support for multisites installation


= 1.3 =
* Improved admin page
* Search result can also be filtered by tags
* User can define their own special character to clean the query.
* Fixed issues with multiple categories.

= 1.2.4 =
* Improved Search result display.

== Changelog ==
= 1.2.3 =
* New Autocompletion REST API

= 1.2.2 =
* Select which type of post will be indexed
* Option to display user, type and category in search result

= 1.2.1 =
* Multiple facet support.
* New feature facet behavior.
* Fix in spell check if no field is selected.

= 1.2.0 =
* Upgrade to last OpenSearchServer PHP library
* Possibility to index a range of documents

= 1.1.1 =
* 17 Language support
* Spelling corrections

= 1.1.0 =
* Tested with OpenSearchServer 1.3-rc2

= 1.0.9 =
* Phonetic search
* Custom fields support
* Facets and filters on categories
* CSS and javascript improvements

= 1.0.8 =
* Improved OpenSearchServer settings page.
* SpellCheck feature in search page.
* AutoCompletion feature in search page.

= 1.0.7 =
* OpenSearchServer client library upgrade (1.3)

= 1.0.6 =
* Search result has template file. 

= 1.0.5 =
* Fixed bug while indexing document
* Fixed paging issues in the current page.
* Updated batch indexing process.

= 1.0.4 =
* OpenSearchServer client library upgrade.
* Fixed a bug which add two blank lines in the top of the HTML page.

= 1.0.3 =
* Fixed images in search result. 

= 1.0.2 =
* Added deletion feature in OSS plugin 

= 1.0.1 =
* Implemented and overridden wordpress search to OpenSearchServer search
