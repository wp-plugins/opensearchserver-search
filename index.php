<?php
/**
 Plugin Name: OpenSearchServer
 Plugin URI: http://wordpress.org/extend/plugins/opensearchserver-search/
 Description: This Plugin will integrate OpenSearchServer as search engine for Wordpress. Go to <a href="plugins.php?page=opensearchserver-search/index.php">OpenSearchServer Settings</a> for OpenSearchServer Settings,
 Author: Emmanuel Keller - Naveen.A.N - Alexandre Toyer
 Author URI: http://open-search-server.com
 Tested up to: 4.0
 Version: 1.5.7
 */
require_once 'lib/oss_api.class.php';
require_once 'lib/oss_misc.lib.php';
require_once 'lib/oss_indexdocument.class.php';
require_once 'lib/oss_results.class.php';
require_once 'lib/oss_paging.class.php';
require_once 'lib/oss_search.class.php';
require_once 'lib/oss_autocompletion.class.php';
require_once 'lib/oss_delete.class.php';
require_once 'lib/oss_searchtemplate.class.php';
require_once 'lib/oss_monitor.class.php';
require_once 'opensearchserver_admin.php';
require_once 'opensearchserver_search_functions.php';

/*
 * The admin action hook
*/
function opensearchserver_admin_actions() {
  add_submenu_page('plugins.php', 'OpenSearchServer Settings', 'OpenSearchServer', 'edit_plugins', __FILE__, 'opensearchserver_admin_page');
}

function opensearchserver_init() {
 $plugin_dir = basename(dirname(__FILE__));
 load_plugin_textdomain( 'opensearchserver', false, $plugin_dir . '/lang');
}
add_action('plugins_loaded', 'opensearchserver_init');

function opensearchserver_load_scripts_styles() {
  global $wp_version;
  wp_register_script( 'opensearchserver', plugins_url('opensearchserver-search') .'/js/opensearchserver.js', array( 'jquery' ) );
  wp_enqueue_script( 'opensearchserver' );
  wp_register_style( 'opensearchserver-style', plugins_url('opensearchserver-search')  . '/css/oss-style.css');
  wp_enqueue_style('opensearchserver-style');
  if($wp_version >= 3.8 ) {
  	wp_register_style( 'opensearchserver-style-latest', plugins_url('opensearchserver-search')  . '/css/oss-style-latest.css');
  	wp_enqueue_style('opensearchserver-style-latest');
  }
  wp_enqueue_script( 'jQuery' );
  
}

function  opensearchserver_search() {
  if (stripos($_SERVER['REQUEST_URI'], '/?s=') === FALSE && stripos($_SERVER['REQUEST_URI'], '/search/') === FALSE)	{
    return;
  }
  $query=get_search_query();
  if($query == 'ossautointernal') {
    include_once('autocomplete.php');
    exit;
  }else {
    if (file_exists(STYLESHEETPATH . '/opensearchserver_search.php')) {
      include_once(STYLESHEETPATH . '/opensearchserver_search.php');
    } else if (file_exists(dirname(__FILE__) . '/template/opensearchserver_search.php')) {
      include_once('template/opensearchserver_search.php');
    } else {
      return;
    }
  }
}

function opensearchserver_install($networkwide) {
	global $wpdb;
                 
    if (function_exists('is_multisite') && is_multisite()) {
        // check if it is a network activation - if so, run the activation function for each blog id
        if ($networkwide) {
			$old_blog = $wpdb->blogid;
            // Get all blog ids
            $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
            foreach ($blogids as $blog_id) {
                switch_to_blog($blog_id);
                opensearchserver_install_one_site();
            }
            switch_to_blog($old_blog);
            return;
        }   
    } 
    opensearchserver_install_one_site();  
}

function opensearchserver_install_one_site() {
	update_option('oss_clean_query_enable', 1);	
	update_option('oss_query', opensearchserver_default_query());
	update_option('oss_index_types_post', 1);
	update_option('oss_index_types_page', 1);
	update_option('oss_spell', 'title');
	update_option('oss_spell_algo', 'JaroWinklerDistance');
	update_option('oss_display_user', 1);
	update_option('oss_display_type', 1);
	update_option('oss_facet_display_count', 1);
	update_option('oss_facet_behavior','no_separate_query');
	update_option('oss_multi_filter',1);
    update_option('oss_taxonomy_category',1);
    update_option('oss_taxonomy_display',1);
}

function opensearchserver_uninstall($networkwide) {
	global $wpdb;

	if (function_exists('is_multisite') && is_multisite()) {
        // check if it is a network deactivation - if so, run the deactivation function for each blog id
        if ($networkwide) {
			$old_blog = $wpdb->blogid;
            // Get all blog ids
            $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
            foreach ($blogids as $blog_id) {
                switch_to_blog($blog_id);
                opensearchserver_uninstall_one_site();
            }
            switch_to_blog($old_blog);
            return;
        }   
    } 
    opensearchserver_uninstall_one_site();  	
}

function opensearchserver_uninstall_one_site() {
  delete_option('oss_query');
  delete_option('oss_serverurl');
  delete_option('oss_indexname');
  delete_option('oss_login');
  delete_option('oss_key');
  delete_option('oss_facet');
  delete_option('oss_facets_labels');
  delete_option('oss_facets_values');
  delete_option('oss_facet_display_count');
  delete_option('oss_spell');
  delete_option('oss_spell_algo');
  delete_option('oss_phonetic');
  delete_option('oss_multi_filter');
  delete_option('oss_language');
  delete_option('oss_advanced_facets');
  delete_option('oss_clean_query');
  delete_option('oss_clean_query_enable');
  delete_option('oss_display_user');
  delete_option('oss_display_type');
  delete_option('oss_display_date');
  delete_option('oss_index_from');
  delete_option('oss_index_to');
  delete_option('oss_enable_translation_wpml');
  delete_option('oss_advanced_query_settings_not_automatic');
  delete_option('oss_advanced_search_only');
  delete_option('oss_sort_timestamp');
  delete_option('oss_log_enable');
  delete_option('oss_log_ip');
  delete_option('oss_display_category');
  delete_option('oss_enable_autoindexation');
  delete_option('oss_custom_fields');
  delete_option('oss_facet_behavior');
  delete_option('oss_taxonomy_display');
  delete_option('oss_facets_slugs');
  delete_option('oss_facets_exclusive');
  delete_option('oss_filter_language_wpml');
  delete_option('oss_filter_language_field_wpml');
  delete_option('oss_facets_option_all');
  delete_option('oss_facets_option_all');
  delete_option('oss_facets_labels');
  delete_option('oss_facets_values');
  delete_option('oss_facets_option_searchform');
  delete_option('oss_facets_option_hierarchical');
  delete_option('oss_facets_option_hierarchical_taxonomy');
  delete_option('oss_query_behaviour');
  delete_option('oss_query_template');
  delete_option('oss_autocomplete_number');
  
  wp_clear_scheduled_hook( 'synchronize_with_cron' );
  delete_option('oss_cron_from');
  delete_option('oss_cron_reset');
  delete_option('oss_cron_running');
  delete_option('oss_cron_number_by_job');
  
  $taxonomies=get_taxonomies('','names'); 
    foreach ($taxonomies as $taxonomy ) {
      $check_taxonomy_name = 'oss_taxonomy_'.$taxonomy;
          delete_option($check_taxonomy_name);
    }
  
  //Delete all the options starting with oss_index_types.
  $all_options = wp_load_alloptions();
  foreach( $all_options as $name => $value ) {
    if(strrpos($name, 'oss_index_types', - strlen($name)) !== FALSE) {
        delete_option($name); 
    }
  }
}

function add_query_vars_filter( $vars ){
  $vars[] = "sort";
  $vars[] = "f";
  return $vars;
}


// Add settings link on plugin page
function opensearchserver_settings_link($links) { 
  $settings_link = '<a href="plugins.php?page=opensearchserver-search/index.php">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}
$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'opensearchserver_settings_link');

function is_content_type_allowed($post_type) {
    foreach (get_post_types() as $post_type) {
      $content_type = 'oss_index_types_'.$post_type;
        if(get_option($content_type) == 1) {
          return TRUE;
        }
    }
   return FALSE;
}

function opensearchserver_do_while_posting($post_id,$post) {
  if(opensearchserver_is_search_only()) {
    return;
  }
  if (is_content_type_allowed($post->post_type) && get_option('oss_enable_autoindexation') == 1 && $post->post_status == 'publish') {
    opensearchserver_reindex_site($post->ID,$post->post_type);
  }
  else {
    $delete='id:'.$post->post_type.'_'.$post->ID;
    opensearchserver_delete_document($delete);
  }
}
function opensearchserver_admin_register_head() {
    $siteurl = get_option('siteurl');
    $url = $siteurl . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/css/admin-style.css';
    echo "<link rel='stylesheet' type='text/css' href='$url' />\n";
}
add_action('admin_head', 'opensearchserver_admin_register_head');

/**
 * This function autoloads class for the newest OpenSearchServer client 
 * and for Buzz library (used by OSS client)
 */
function opensearchserver_autoload($class_name) {
    if(substr($class_name, 0, 16) == 'OpenSearchServer') {
        include __DIR__.'/lib/opensearchserver-php-client/src/'.str_replace('\\','/', $class_name) . '.php';
    } else if(substr($class_name, 0, 4) == 'Buzz') {
        include __DIR__.'/lib/Buzz/lib/'.str_replace('\\', '/', $class_name) . '.php';
    }
}
spl_autoload_register('opensearchserver_autoload');

register_activation_hook(__FILE__,'opensearchserver_install');
register_deactivation_hook( __FILE__, 'opensearchserver_uninstall');
add_action('save_post','opensearchserver_do_while_posting',10,2);
add_action('wp_enqueue_scripts','opensearchserver_load_scripts_styles' );
add_action('admin_menu', 'opensearchserver_admin_actions');
add_action('template_redirect', 'opensearchserver_search');
add_action('synchronize_with_cron','opensearchserver_synchronize_with_cron' );
add_filter( 'query_vars', 'add_query_vars_filter' );
?>