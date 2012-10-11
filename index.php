<?php
/**
 Plugin Name: OpenSearchServer
 Plugin URI: http://wordpress.org/extend/plugins/opensearchserver-search/
 Description: This Plugin will integrate OpenSearchServer as search engine for Wordpress.Go to <a href="plugins.php?page=opensearchserver-search/index.php">OpenSearchServer Settings</a> for OpenSearchServer Settings,
 Author: Emmanuel Keller - Naveen.A.N
 Author URI: http://open-search-server.com
 Tested up to: 3.4.2
 Version:1.1.2
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
require_once 'opensearchserver_admin.php';
require_once 'opensearchserver_search_functions.php';

/*
 * The admin action hook
*/
function opensearchserver_admin_actions() {
  add_submenu_page('plugins.php', 'OpenSearchServer Settings', 'OpenSearchServer', 'edit_plugins', __FILE__, 'opensearchserver_admin_page');
}

function opensearchserver_load_scripts_styles() {
  wp_register_script( 'opensearchserver', plugins_url('opensearchserver-search') .'/js/opensearchserver.js' );
  wp_enqueue_script( 'opensearchserver' );
  wp_register_style( 'opensearchserver-style', plugins_url('opensearchserver-search')  . '/css/oss-style.css');
  wp_enqueue_style('opensearchserver-style');
  wp_enqueue_script( 'jQuery' );
}

function  opensearchserver_search() {

  if (stripos($_SERVER['REQUEST_URI'], '/?s=') === FALSE && stripos($_SERVER['REQUEST_URI'], '/search/') === FALSE)	{
    return;
  }
  if (stripos($_SERVER['REQUEST_URI'], '/?s=') === FALSE && stripos($_SERVER['REQUEST_URI'], '/search/') === FALSE)	{
    return;
  }
  $query=get_search_query();
  if($query == 'ossautointernal') {
    include_once('autocomplete.php');
    exit;
  }else {
    if (file_exists(TEMPLATEPATH . '/oss_search.php')) {
      include_once(TEMPLATEPATH . '/oss_search.php');
    } else if (file_exists(dirname(__FILE__) . '/template/opensearchserver_search.php')) {
      include_once('template/opensearchserver_search.php');
    } else {
      return;
    }
  }
}

function opensearchserver_install() {
}

function opensearchserver_uninstall() {
  delete_option('oss_query');
  delete_option('oss_serverurl');
  delete_option('oss_indexname');
  delete_option('oss_login');
  delete_option('oss_key');
  delete_option('oss_facet');
  delete_option('oss_spell');
  delete_option('oss_spell_algo');
  delete_option('oss_phonetic');
  delete_option('oss_language');
}

function opensearchserver_do_while_posting($post_id,$post) {
  if ($post->post_type == 'post' || $post->post_type == 'page'
    && $post->post_status == 'publish') {
    opensearchserver_reindex_site($post->ID,$post->post_type);
  }
  else {
    $delete='id:'.$post->post_type.'_'.$post->ID;
    opensearchserver_delete_document($delete);
  }
}
register_activation_hook(__FILE__,'opensearchserver_install');
register_deactivation_hook( __FILE__, 'opensearchserver_uninstall');
add_action('save_post','opensearchserver_do_while_posting',10,2);
add_action('wp_enqueue_scripts','opensearchserver_load_scripts_styles' );
add_action('admin_menu', 'opensearchserver_admin_actions');
add_action('template_redirect', 'opensearchserver_search');
?>