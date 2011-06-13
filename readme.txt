=== OpenSearchServer ===
Contributors: ekeller,naveenann
Tags: search,search engine,opensearchserver
Requires at least: 3.0.1
Tested up to: 3.1.3
Stable tag: 1.0.2
License: GPLv2 or later

WordPress OpenSearchServer Search Plugin allows to use OpenSearchServer to enable full text search on WordPress-based websites.

== Description ==
WordPress OpenSearchServer Search Plugin allows to use OpenSearchServer to enable full text search on WordPress-based websites. This plugin replaces WordPress's built-in search functionality.

Key Features

 * Filter search results by Facet Field,
 * Automatically index an post when you publish an post,
 * Search through posts and pages,
 * Easy to set up with just filling a form.
 
== Installation ==

= Requirements =

    * WordPress 3.0.0 or higher
    * OpenSearchServer 1.2.1 or higher

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

1. OpenSearchServer Settings Page.
2. Search provided by OpenSearchServer with facet and filter.


= 1.0.3 =
* Fixed images in search result. 

= 1.0.2 =
* Added deletion feature in OSS plugin 

= 1.0.1 =
* Implemented and overridden wordpress search to OpenSearchServer search

