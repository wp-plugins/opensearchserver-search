=== OpenSearchServer Search ===
Contributors: ekeller,naveenann
Tags: search,search engine,opensearchserver, full-text, phonetic, filter, facet, indexation, auto-completion
Requires at least: 3.0.1
Tested up to: 4.0
Stable tag: 1.4.2
License: GPLv2 or later

The OpenSearchServer Search Plugin allows to use OpenSearchServer to enable full-text search on WordPress-based websites.

== Description ==

= OpenSearchServer plugin =

The OpenSearchServer Search Plugin allows to use [OpenSearchServer](http://www.opensearchserver.com/) to enable full-text search on WordPress-based websites.
OpenSearchServer is an **high-performance search engine which includes spell-check, facet, filter, phonetic search, auto-completion**.
This plugin automatically replaces WordPress built-in search functionnality.

= Key Features =

 * **Full-text search with phonetic support**,
 * Query can be fully customized and **relevancy of each field (title, author, ...) can be precisely tuned**,
 * Filter search results by **facets**,
 * Automatic suggestion with **autocompletion**,
 * **Spellchecking** with automatic substitution,
 * Automatic indexing of content when published, edited or deleted,
 * Can index and search through **all type of content**,
 * Can index and search **every taxonomies**,
 * Easy to set up and customize with just filling a form,
 * Supports **multisites installation**,
 * Supports **WPML plugin** for translation,
 * Includes **several filters and actions** to allow for more customization from other plugins or themes.

See screenshots page to learn more!

== Installation ==

= Requirements =

 * WordPress 3.0.1 or higher
 * A running instance of OpenSearchServer 1.3 or higher
 
= Installing an OpenSearchServer instance =

Two ways to get an OpenSearchServer instance:

 * Deploy it on your own server by reading the [OpenSearchServer documentation](http://www.opensearchserver.com/documentation/ "OpenSearchServer documentation")
 * Or use an OpenSearchServer SaaS instance [OpenSearchServer SaaS service](http://www.opensearchserver.com/#saas "OpenSearchServer SaaS services")

= Installing the plugin =

   1. Check you have a running OpenSearchServer instance
   2. Uncompress the plugin archive to wp-content/plugins folder of your WordPress installation.
   3. Activate the OpenSearchServer Search plugin via WordPress settings.
   4. Open OpenSearchServer settings page and fill the form with instance settings.
   5. Choose type of content and taxonomies to index, save Index Settings.
   6. Create index by clicking on "(Re-)Create the index".
   7. Content from WordPress can be pushed to newly created index by clicking "Synchronize / Re-index".

== Frequently Asked Questions ==

= What is OpenSearchServer? =

Open Search Server is a search engine software developed under the GPL v3 open source licence. Read more on http://www.open-search-server.com

= How to update the search index? =

Using the Reindex-Site button in the OpenSearchServer Settings page. While posting a page or a post OpenSearchServer plugin automatically indexes the post or page
   
= When I click Create-index/Save button or reindex button I got an exception saying "Bad credential" = 

Check that the credentials used in the plugin page is correct against the ones created in OpenSearchServer instance, under the privilages tab.

= I get an error when I install opensearchserver "Fatal error: OSS_API won't work without curl extension in "opensearchserver-search\OSS_API.class.php" on line 23" =

Check that CURL extension for PHP is enabled on your server, else install it.
 
= How to customize/style the search page? =

Copy the file opensearchserver_search.php from the directory wp-content/plugins/opensearchserver-search to your current theme folder (wp-content/themes/twentyfourteen). 
Customize the layout as per your needs.
   
= Will this plugin work with a multisites installation? =

Yes, this plugin supports multisites installation.

= I already manage my OpenSearchServer index in another way (web crawler). Can I use this plugin to plug my Wordress search page to my existing index? =

Yes you can: enable the "Search only" mode to switch off sending of data (new posts and pages) from Wordpress to OpenSearchServer. Warning: you may however need to first create your index with this plugin before enabling "Search only mode" to ensure creation of all needed schema's fields.

= I get this PHP warning when saving query settings: "Warning: OSS Returned an error: "Error com.jaeksoft.searchlib.web.ServletException: com.jaeksoft.searchlib.SearchLibException: Returned field: The field: thumbnail_url does not exist" =

You probably updated to a recent version without re-creating your index. You need to re-create your index and re-synchronize data.

= What are the available filters and actions? =

Learn everything about available filters and actions [at our documentation center](http://www.opensearchserver.com/documentation/plugins/wordpress.md).

= Indexing crashes before sending all documents to OpenSearchServer =

Full re-indexing can really be a memory-consuming task. If your server does not allow for such memory to be used by PHP try indexing your content by range. To do so, use the `From document` and `to document` text fields located above the `Re-index / Synchronize` button.

= How can I translate the plugin =

Copy file `lang/opensearchserver-fr_FR.po`, rename it with your country code and translate its content. Feel free to submit us your translated files!

_Serbian translation provided by Ogi Djuraskovic – [http://firstsiteguide.com](http://firstsiteguide.com)_.

== Screenshots ==

1. Page of results.
2. Administration page: some query settings.
3. Administration page: index settings.

== Changelog ==
= 1.4.2 - 16/12/2014 =
* Update indexing process

= 1.4.1 - 07/11/2014 =
* Fix bug whith exclusive facet when there is only one.

= 1.4 - 04/11/2014 =
* Add filter and actions, update README.

= 1.3.9 - 14/10/2014 =
* Add translation labels for some strings: Next, Previous, First, Last

= 1.3.8 - 04/09/2014 =
* Add plugin icon for Wordpress 4.0

= 1.3.7 =
* Add autocompletion on main search input, not only in search page anymore

= 1.3.6 =
* Change facet behavior: there is now a standard behavior and an advanced behavior
* Advanced behavior allow to choose exclusive or multiple facets
* Add configurable URL slug for facets
* Improve autocompletion suggestion
* Add some WordPress filters
* ==> **Index re-creation and synchronization may be needed**

= 1.3.5 =
* Indexation of content's thumbnail
* Indexation of multiple taxonomies
* Categorie is now indexed like any other taxonomy
* Chosen content type can now be automatically indexed when added / edited / deleted
* Use STYLESHEETPATH instead of TEMPLATEPATH to be able to override template in a Child Theme
* Add handling of OpenSearchServer logging
* ==> **Index re-creation and synchronization may be needed**

= 1.3.4 =
* UI Enhancements
* Sort by date feature. 

= 1.3.3 =
* Add search only mode 
* Add option to send query settings to OSS or save them locally
* Custom label and values for facets

= 1.3.2 =
* Search results can be filtered by year and month.
* Bug Fixes

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
