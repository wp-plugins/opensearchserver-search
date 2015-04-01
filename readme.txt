=== OpenSearchServer Search ===
Contributors: ekeller,naveenann,AlexandreT
Tags: search,search engine,opensearchserver, full-text, phonetic, filter, facet, indexation, auto-completion
Requires at least: 3.0.1
Tested up to: 4.0
Stable tag: 1.5.6
License: GPLv2 or later

The OpenSearchServer Search Plugin enables OpenSearchServer full-text search in WordPress-based websites.

== Description ==

= OpenSearchServer plugin =

The OpenSearchServer Search Plugin enables [OpenSearchServer](http://www.opensearchserver.com/)  full-text search in WordPress-based websites.
OpenSearchServer is an **high-performance search engine that includes spell-check, facets, filters, phonetic search, and auto-completion**.
This plugin automatically replaces the WordPress built-in search function.

= Key Features =

 * **Full-text search with phonetic support**,
 * Queries can be fully customized and the **relevancy of each field (title, author, ...) can be precisely tuned**,
 * Search results can be filtered using **facets**,
 * Automatic search suggestions through **autocompletion**,
 * **Spell-checking** with automatic substitution,
 * **Search into your files**: .docx, .doc, .pdf, .rtf, etc. The plugin will extract text from your attachments and index it.
 * Automatic indexing of content as soon as it gets published, edited or deleted,
 * Can index and search through **all type of content**,
 * Can index and search **every taxonomies**,
 * Can be easily set up and tweaked through web form page
 * Supports **multi-sites installation**,
 * Supports a **WPML plugin** for translation,
 * Includes **several filters and actions** to allow for more customization via other plugins or themes.

See the screenshots page for more!

== Installation ==

= Requirements =

 * WordPress 3.0.1 or higher
 * A running instance of OpenSearchServer 1.3 or higher (OpenSearchServer 1.5.11 is required for searching into attachments)
 
= Installing an OpenSearchServer instance =

There are two ways to get an OpenSearchServer instance:

 * Deploy it on your own server as explained in the [OpenSearchServer documentation](http://www.opensearchserver.com/documentation/ "OpenSearchServer documentation")
 * Or use an OpenSearchServer SaaS instance [OpenSearchServer Software as a Service instances](http://www.opensearchserver.com/#saas "OpenSearchServer SaaS services")

= Installing the plugin =

   1. Verify that you have a running OpenSearchServer instance
   2. Uncompress the plugin archive in the wp-content/plugins folder for your WordPress installation.
   3. Activate the OpenSearchServer Search plugin via WordPress settings.
   4. Open the OpenSearchServer settings page (in the Plugins menu) and fill the form with your desired settings
   5. Choose the types of content and the taxonomies to index, save your Index Settings.
   6. Create your index by clicking on the "(Re-)Create the index" button
   7. New and modified content from WordPress can be pushed into your newly created index by clicking the "Synchronize / Re-index" button.

== Frequently Asked Questions ==

= What is OpenSearchServer? =

Open Search Server is a search engine developed under the GPL v3 open source licence. Read more on http://www.open-search-server.com

= How to update the search index? =

Use the Reindex-Site button in the OpenSearchServer Settings page. By default the OpenSearchServer plugin automatically indexes any new post or page
   
= When I click the Create-index/Save button or the Reindex button I got an exception saying "Bad credential" = 

Check that the credentials entered in the plugin page match the ones entered in your OpenSearchServer instance, under the Privileges tab.

= I get an error when I install Opensearchserver. "Fatal error: OSS_API won't work without curl extension in "opensearchserver-search\OSS_API.class.php" on line 23" =

Check whether the CURL extension for PHP is enabled on your server, and install it if necessary.
 
= How to customize/style the search page? =

Copy the opensearchserver_search.php file from the wp-content/plugins/opensearchserver-search directory to your current theme folder (for instance wp-content/themes/twentyfourteen). 
Customize the layout as needed.
   
= Will this plugin work with a multi-sites installation? =

Yes, this plugin supports multi-sites installation.

= I already manage my OpenSearchServer index in another way (using web crawler). Can I use this plugin to plug my WordPress search page into my existing index? =

Yes you can: enable the "Search only" mode to stop sending data (chiefly new posts and pages) from Wordpress to OpenSearchServer.

Warning: you may first need to create your index using the OSS WordPress plugin before enabling the "Search only mode". This ensures that the necessary fields are all created in the schema of your index.

= I get the following PHP warning when saving my query settings: "Warning: OSS Returned an error: "Error com.jaeksoft.searchlib.web.ServletException: com.jaeksoft.searchlib.SearchLibException: Returned field: The field: thumbnail_url does not exist" =

You probably updated to a recent version without re-creating your index. You need to re-create your index and re-synchronize data.

= What are the available filters and actions? =

Learn everything about the available filters and actions [in our documentation center](http://www.opensearchserver.com/documentation/plugins/wordpress.md).

= The indexing process crashes before it can send all documents to OpenSearchServer =

Fully re-indexing can hit the memory quite hard. If your server does not allow for that much memory to be used by PHP, try indexing your content in smaller spans. To do so, use the 'From document' and 'to document' input fields located above the 'Re-index / Synchronize' button and determine through trial and error how many documents your server will let you process in one go.

= How can I translate the plugin =

Copy the `lang/opensearchserver-fr_FR.po` file, rename it with your country code and translate its content. Feel free to submit us your translated files!

_Serbian translation provided by Ogi Djuraskovic - [http://firstsiteguide.com](http://firstsiteguide.com)_.

== Screenshots ==

1. Page of results.
2. Administration page: some query settings.
3. Administration page: index settings.

== Changelog ==

= 1.5.6 - 01/04/2015 ==
 * Added some filters and actions when indexing a document

= 1.5.5 - 04/03/2015 ==
 * Added feature to index content from attachments (use OSS's parsers) 

= 1.5.4 - 02/03/2015 =
 * Fixed bug when indexing attachment
 * Forced default query for getting total number of docs in the index

= 1.5.3 - 27/02/2015 =
 * Added a feature to re-index full data using WordPress CRON

= 1.5.2 - 16/02/2015 =
 * Fixed bugs
 * Improved facets
 * Updated FR translation

= 1.5.1 - 05/02/2015 =
 * Added a feature to select an existing query template from OSS rather than using queries configured in WordPress.
 * Improved facets use: added a small search form, hierarchical facets, and some other imrpovements

= 1.5 - 30/12/2014 =
* Improved the indexing of Custom Fields.
* Additional help texts for Query and Facets management.
* ==> **Re-creating and synchronizing the index may be needed if you are indexing Custom Fields.** Please re-configure which Custom Fields to index in the "Index settings" section. 

= 1.4.2 - 16/12/2014 =
* Updated the indexing process

= 1.4.1 - 07/11/2014 =
* Fixed a bug occurring when there only was a single exclusive facet

= 1.4 - 04/11/2014 =
* Added filters and actions, updated the README.

= 1.3.9 - 14/10/2014 =
* Added translation labels for the following strings: Next, Previous, First, Last

= 1.3.8 - 04/09/2014 =
* Added a plugin icon for Wordpress 4.0

= 1.3.7 =
* Added autocompletion on the main search input - it's no longer limited to the search page

= 1.3.6 =
* Changed facet behavior: there is now a standard behavior and an advanced behavior
* The advanced behavior allows for selecting exclusive or multiple facets
* Added configurable URL slugs for facets
* Improved autocompletion suggestions
* Added some WordPress filters
* ==> **Index re-creation and synchronization may be needed upon updating the plugin**

= 1.3.5 =
* Indexing of content thumbnails
* Indexing of multiple taxonomies
* Categories are now indexed like any other taxonomy
* The chosen content type can now be automatically indexed when added / edited / deleted
* Used STYLESHEETPATH instead of TEMPLATEPATH, which makes it possible to override the template in a Child Theme
* Added handling of OpenSearchServer logging
* ==> **Index re-creation and synchronization may be needed upon updating the plugin**

= 1.3.4 =
* UI enhancements
* 'Sort by date' feature. 

= 1.3.3 =
* Added a search only mode 
* Added an option to either send query settings to OSS or to save them locally
* Custom labels and values for facets

= 1.3.2 =
* Search results can now be filtered by year and month.
* Various bug fixes

= 1.3.1 =
* Various bug fixes.
* Added support for multi-sites installation

= 1.3 =
* Improved admin page
* Search result can now also be filtered by tags
* Users can define their own special character to clean the query.
* Fixed issues with multiple categories.

= 1.2.4 =
* Improved the search result display.

= 1.2.3 =
* New autocompletion REST API

= 1.2.2 =
* Added a function to select which type of post will be indexed
* Added an option to display user, type and category in search result

= 1.2.1 =
* Added multiple facets support.
* New feature - facet behavior.
* Fixed the spell check when no field is selected.

= 1.2.0 =
* Upgrade to the latest OpenSearchServer PHP library
* Added the possibility to index a range of documents

= 1.1.1 =
* Now supporting 17 languages
* Now providing spelling corrections

= 1.1.0 =
* Tested with OpenSearchServer 1.3-rc2

= 1.0.9 =
* Added phonetic search
* Added custom fields support
* Added facets and filters on categories
* Various CSS and Javascript improvements

= 1.0.8 =
* Improved the OpenSearchServer settings page
* SpellCheck feature added to the search page
* AutoCompletion feature added to the search page

= 1.0.7 =
* OpenSearchServer client library upgrade (to 1.3)

= 1.0.6 =
* Search results now have a template file. 

= 1.0.5 =
* Fixed a document indexing bug
* Fixed paging issues in the current page.
* Updated the batch-indexing process.

= 1.0.4 =
* OpenSearchServer client library upgrade.
* Fixed a bug that added two blank lines at the top of the HTML page.

= 1.0.3 =
* Fixed images in search results. 

= 1.0.2 =
* Added a deletion feature to the OSS plugin 

= 1.0.1 =
* Implemented the override of WordPress search by OpenSearchServer search
