=== OpenSearchServer Search ===
Contributors: ekeller,naveenann
Tags: search,search engine,opensearchserver, full-text, phonetic, filter, facet, indexation, auto-completion
Requires at least: 3.0.1
Tested up to: 3.4.2
Stable tag: 1.0.9
License: GPLv2 or later

WordPress OpenSearchServer Search Plugin allows to use OpenSearchServer to enable full-text search on WordPress-based websites.

== Description ==

WordPress OpenSearchServer Search Plugin allows to use OpenSearchServer to enable full-text search on WordPress-based websites. Including spellcheck, facet, filter, phonetic search, autocompletion. This plugin replaces WordPress's built-in search functionality.

Key Features

 * Full-text search with phonetic support,
 * Filter search results by Facet Field,
 * Automatic suggestion with autocompletion,
 * Spellcheking with automatic substitution,
 * Automatically index an post when you publish an post,
 * Search through posts and pages,
 * Easy to set up with just filling a form.
 
== Installation ==

= Requirements =

    * WordPress 3.0.1 or higher

= Installing the plugin =

   1. Check you have a running OpenSearchServer instance
   2. Unpack the plugin archive to wp-content/plugins folder of your WordPress installation.
   3. Activate OpenSearchServer plugin via WordPress Settings.
   4. Open OpenSearchServer settings page and just fill the form and create and index and re-index it for first time.

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

== Screenshots ==

1. The admin page.
2. An example of search result.

== Changelog ==

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
