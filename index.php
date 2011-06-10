<?php
/**
Plugin Name: OpenSearchServer
Plugin URI: http://wordpress.org/extend/plugins/opensearchserver-search/
Description:This Plugin will integrate OpenSearchServer 1.2 as search engine for Wordpress.Go to <a href="plugins.php?page=opensearchserver/index.php">OpenSearchServer Settings</a> for OpenSearchServer Settings,
Author: Naveen.A.N
Requires at least: 3.0.0
Tested up to: 3.1.3
Version:1.0
*/
require 'OSS_API.class.php';
require 'misc.lib.php';
require 'OSS_IndexDocument.class.php';
require 'OSS_Results.class.php';
require 'OSS_Paging.class.php';
require 'OSS_Search.class.php';
require 'OSS_SearchTemplate.class.php';
require 'oss_delete.class.php';
require 'search_result.php';
function opensearchserver_install() {
 global $wpdb;
   $table_name =$wpdb->prefix ."opensearchserver";
   $sql ='
		CREATE TABLE IF NOT EXISTS ' . $table_name . ' (
		  `serverurl` varchar(255) NOT NULL,
		  `indexname` varchar(255) NOT NULL,
		  `username` varchar(255) NOT NULL,
		  `key` varchar(255) NOT NULL,
		  `last_indexed` varchar(255) NOT NULL
		  
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
    add_option("opensearchserver_db_version",'1.2');
}
 function opensearchserver_form($form) {
    $form = '<form role="search" method="get" id="searchform" action="' . home_url( '/' ) . '" >
    <div><label class="screen-reader-text" for="s">' . __('Search for:') . '</label>
    <input type="text" value="' . get_search_query() . '" name="s" id="s" />
    <input type="submit" id="searchsubmit" value="'. esc_attr__('Search') .'" />
    </div>
    </form>';
    return $form;
}
function configure_OSS($url,$indexname,$username,$key)
{
			  $ossAPI = new OSS_API($url);
			  $ossAPI->credential($username,$key);
			  $ossAPI->createIndex($indexname);
		 
	return true;
}
function setFields_OSS($url,$indexname,$username,$key)
{
			  
			  $ossAPI = new OSS_API($url,$indexname);
			  $ossAPI->credential($username,$key);
			  $ossAPI->setField('id','','NO','YES','YES','','NO','YES');
			  $ossAPI->setField('type','','NO','YES','YES','','NO','NO');
			  $ossAPI->setField('url','','NO','YES','YES','','NO','NO');
			  $ossAPI->setField('title','TextAnalyzer','compress','YES','positions_offsets','','NO','NO');
			  $ossAPI->setField('content','TextAnalyzer','compress','YES','positions_offsets','','YES','NO');
			  $ossAPI->setField('timestamp','','NO','YES','YES','','NO','NO');
			  $ossAPI->setField('user_name','TextAnalyzer','compress','YES','positions_offsets','','NO','NO');
			  $ossAPI->setField('user_email','TextAnalyzer','compress','YES','positions_offsets','','NO','NO');
			  $ossAPI->setField('user_url','','NO','YES','YES','','NO','NO');
	  		  $searchTemplate=new OSS_SearchTemplate($url,$indexname);
			  $searchTemplate->credential($username,$key);
			  $searchTemplate->createSearchTemplate("search",'
					title:($$)^10 OR title:("$$")^10
							OR
					content:($$)^10 OR content:("$$")^10
							OR
					user_name:($$)^10 OR user_name:("$$")^10
							OR
					user_email:($$)^10 OR user_email:("$$")^10
					
			
			 ',"AND","10","2","ENGLISH");
  
			   $searchTemplate->setSnippetField("search","title");
			   $searchTemplate->setSnippetField("search","content");
			   $searchTemplate->setReturnField("search","url");
			   $searchTemplate->setReturnField("search","user_url");
			   $searchTemplate->setReturnField("search","type");
			   $searchTemplate->setSnippetField("search","user_name");
			   $searchTemplate->setSnippetField("search","user_email");
			
}
function delete_document($serverurl,$indexname,$username,$password,$query)
{

		$deleteAPI = new oss_delete($serverurl,$indexname);
		$deleteAPI->credential($username,$password);
		$deleteAPI->delete($query);
}
function reindex_site($id,$type)
{
		
		global $wpdb;
		$table_name =$wpdb->prefix ."opensearchserver";
		$table_name_posts =$wpdb->prefix ."posts";
		$table_name_users =$wpdb->prefix ."users";
		$result = $wpdb->get_results('SELECT * FROM '.$table_name);
		
		$ossEnginePath  = configRequestValue('ossEnginePath', $result[0]->serverurl, 'engineURL');
		$ossEngineConnectTimeOut = configRequestValue('ossEngineConnectTimeOut', 5, 'engineConnectTimeOut');
		$ossEngineIndex = configRequestValue('ossEngineIndex', $result[0]->indexname, 'engineIndex');
	
		if($id)
		{
				$delete='id:'.$type.'_'.$id;
				delete_document($result[0]->serverurl,$result[0]->indexname,$result[0]->username,$result[0]->key,$delete);
				$sql='SELECT * FROM `'.$table_name_posts.'` WHERE `post_status` LIKE '."'".'publish'."'" .' AND `ID` ='.$id;
		}else
		{
			delete_document($result[0]->serverurl,$result[0]->indexname,$result[0]->username,$result[0]->key,'*:*');
			$sql='SELECT * FROM `'.$table_name_posts.'` WHERE `post_status` LIKE '."'".'publish'."'";
		}
		$result_posts = $wpdb->get_results($sql);
		$index = new OSS_IndexDocument();
		$lang= substr(get_locale(), 0, 2);
		
		foreach($result_posts as $posts)
		{
								$result_users = $wpdb->get_results('SELECT user_nicename,user_email,user_url FROM `'.$table_name_users.'` WHERE `ID` = '.$posts->post_author);
								$document = $index->newDocument($lang);
								$document->newField('id', $posts->post_type.'_'.$posts->ID);
								$document->newField('type', $posts->post_type);
								$document->newField('title', $posts->post_title);
								$document->newField('content', $posts->post_content);
								$document->newField('url', $posts->guid);
								$document->newField('timestamp', $posts->post_date_gmt);
								$document->newField('user_name',$result_users[0]->user_nicename );
								$document->newField('user_email',$result_users[0]->user_email );
								$document->newField('user_url',$result_users[0]->user_url);
								$server = new OSS_API($ossEnginePath, $ossEngineIndex);
								$server->credential($result[0]->username,$result[0]->key);
							if ($server->update($index,$ossEngineIndex) === false) {
								$errors[] = 'failedToUpdate';
							 }
							 	$server->optimize();
		}
		
		
}
function admin_page()
{
		global $wpdb;
		$table_name =$wpdb->prefix ."opensearchserver";
		echo '<div class="wrap"><h2> OpenSearchServer Settings</h2>';
		 if($_POST['action'] == "Create-index/Save") 
		{
			$wpdb->query('TRUNCATE TABLE `wp_opensearchserver');
			
			$delay=$_POST['delay'];
			$username=$_POST['username'];
			$key=$_POST['key'];
			$serverurl=$_POST['serverurl'];
			$indexname=$_POST['indexname'];
			$last_index=date('YmdHis', time());
			
			$rows_affected = $wpdb->insert( $table_name, array( 'serverurl' =>$serverurl, 'indexname' => $indexname, 'username' => $username, 'key' => $key, 'last_indexed' => $last_index ) );
			configure_OSS($serverurl,$indexname,$username,$key);
			setFields_OSS($serverurl,$indexname,$username,$key);
			echo '<h3 style="color:#3366FF">The Preference saved Successfully.</h3>';
		}
			if($_POST['action'] == "Reindex-Site") 
			{
				reindex_site('','');
				echo '<h3 style="color:#3366FF">Re-index finshed Successfully.</h3>';
			}
		$result = $wpdb->get_results('SELECT * FROM '.$table_name);
		
		echo '<form id="admin" name="admin" method="post" action="">
					  <input type="hidden" name="opensearchserver" value="true"/>
					 
					 OpenSearchServer URL: <br />
					  <input type="text" name="serverurl" id="serverurl" size="50" value="'.$result[0]->serverurl.'"/>
				 
					  <br />
					   IndexName :<br />
					  <input type="text" name="indexname" id="indexname" size="50" value="'.$result[0]->indexname.'"/>
					  <br />
					  Username :<br />
					  <input type="text" name="username" id="username" size="50" value="'.$result[0]->username.'"/>
					  <br />
					  Key :<br />
					  <input type="text" name="key" id="key" size="50" value="'.$result[0]->key.'"/>
					  <br />
					   <br />
					  <input type="submit" name="action" id="action" value="Create-index/Save" />
					  <input type="submit" name="action" id="action" value="Reindex-Site" />
					 
					</form>';
		echo '</div>';

}
function  opensearchserver_search()
{
		if ( stripos($_SERVER['REQUEST_URI'], '/?s=') === FALSE && stripos($_SERVER['REQUEST_URI'], '/search/') === FALSE)
		{
			return;
		}
		get_header();
		echo '
			<form role="search" method="get" id="searchform" action="' . home_url( '/' ) . '" >
				<div><label class="screen-reader-text" for="s">' . __('Search for:') . '</label>
				<input type="text" size=50 value="' . get_search_query() . '" name="s" id="s" />
				<input type="submit" id="searchsubmit" value="'. esc_attr__('Search') .'" />
				</div>
			</form>';
		echo '<div>';
	
		echo get_sidebar();
		echo get_search_result_output(get_search_query());
		get_footer();
		exit;
}
function opensearchserver_admin_actions() {  
	add_submenu_page('plugins.php', 'OpenSearchServer Settings', 'OpenSearchServer', 10, __FILE__, 'admin_page'); 
}  
function opensearchserver_update_db_check() {
      if (get_site_option('opensearchserver_db_version') != '1.2') {
        opensearchserver_install();
    }
}

function do_while_posting($post_id,$post) {

  if ($post->post_type == 'post' || $post->post_type == 'page'
      && $post->post_status == 'publish') {
  reindex_site($post->ID,$post->post_type);
  } 
else
	{
			global $wpdb;
			$table_name =$wpdb->prefix ."opensearchserver";
			$result = $wpdb->get_results('SELECT * FROM '.$table_name);
			$delete='id:'.$post->post_type.'_'.$post->ID;
			delete_document($result[0]->serverurl,$result[0]->indexname,$result[0]->username,$result[0]->key,$delete);
	}
} 
add_action('save_post','do_while_posting',10,2);
add_action('plugins_loaded', 'opensearchserver_update_db_check');
add_action('admin_menu', 'opensearchserver_admin_actions');  
add_action('template_redirect', 'opensearchserver_search');  
add_filter( 'get_search_form', 'opensearchserver_form' );
?>