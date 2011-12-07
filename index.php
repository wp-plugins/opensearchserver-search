<?php
/**
 Plugin Name: OpenSearchServer
 Plugin URI: http://wordpress.org/extend/plugins/opensearchserver-search/
 Description:This Plugin will integrate OpenSearchServer 1.2 as search engine for Wordpress.Go to <a href="plugins.php?page=opensearchserver/index.php">OpenSearchServer Settings</a> for OpenSearchServer Settings,
 Author: Naveen.A.N
 Requires at least: 3.0.0
 Tested up to: 3.1.3
 Version:1.0.5
 */
require_once 'lib/oss_api.class.php';
require_once 'lib/oss_misc.lib.php';
require_once 'lib/oss_indexdocument.class.php';
require_once 'lib/oss_results.class.php';
require_once 'lib/oss_paging.class.php';
require_once 'lib/oss_search.class.php';
require_once 'lib/oss_searchtemplate.class.php';
require_once 'lib/oss_delete.class.php';
require_once 'search_result.php';

function opensearchserver_install() {
  global $wpdb;
  $table_name =$wpdb->prefix ."opensearchserver";
  $sql ='
		CREATE TABLE IF NOT EXISTS ' . $table_name . ' (
		  `serverurl` varchar(255) NOT NULL,
		  `indexname` varchar(255) NOT NULL,
		  `username` varchar(255) NOT NULL,
		  `key` varchar(255) NOT NULL,
		  `indexing_method` varchar(255) NOT NULL,
		  `last_indexed` varchar(255) NOT NULL

		) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  dbDelta($sql);
  add_option("opensearchserver_db_version",'1.2');
}
function opensearchserver_uninstall() {
  global $wpdb;
  $table_name =$wpdb->prefix ."opensearchserver";
  $sql ='DROP TABLE ' . $table_name  ;
  $wpdb->query($sql);
  delete_option("opensearchserver_db_version",'1.2');
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

function configure_OSS($url,$indexname,$username,$key) {
  $ossAPI = new OSSAPI($url);
  $index_available=1;
  $ossAPI->credential($username,$key);
  if (!$ossAPI->isIndexAvailable($indexname)) {
    $ossAPI->createIndex($indexname);
    $index_available=0;
  }
  return $index_available;
}

function setFields_OSS($url,$indexname,$username,$key) {
  $ossAPI = new OSSAPI($url,$indexname);
  $ossAPI->credential($username,$key);
  if (!$ossAPI->isIndexAvailable($indexname)) {
    $ossAPI->setField('id','','NO','YES','YES','','NO','YES');
    $ossAPI->setField('type','','NO','YES','YES','','NO','NO');
    $ossAPI->setField('url','','NO','YES','YES','','NO','NO');
    $ossAPI->setField('title','TextAnalyzer','compress','YES','positions_offsets','','NO','NO');
    $ossAPI->setField('content','TextAnalyzer','compress','YES','positions_offsets','','YES','NO');
    $ossAPI->setField('timestamp','','NO','YES','YES','','NO','NO');
    $ossAPI->setField('user_name','TextAnalyzer','compress','YES','positions_offsets','','NO','NO');
    $ossAPI->setField('user_email','TextAnalyzer','compress','YES','positions_offsets','','NO','NO');
    $ossAPI->setField('user_url','','NO','YES','YES','','NO','NO');
    $searchTemplate=new OSSSearchTemplate($url,$indexname);
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
}

function delete_document($serverurl,$indexname,$username,$password,$query) {
  $deleteAPI = new OssDelete($serverurl,$indexname);
  $deleteAPI->credential($username,$password);
  $deleteAPI->delete($query);
}

function reindex_site($id,$type) {
  global $wpdb;
  $table_name =$wpdb->prefix ."opensearchserver";
  $table_name_posts =$wpdb->prefix ."posts";
  $table_name_users =$wpdb->prefix ."users";
  $result = $wpdb->get_results('SELECT * FROM '.$table_name);
  $index_status=0;
  if($result[0]->indexing_method=='automated_indexation') {
  $ossEnginePath  = config_request_value('ossEnginePath', $result[0]->serverurl, 'engineURL');
  $ossEngineConnectTimeOut = config_request_value('ossEngineConnectTimeOut', 5, 'engineConnectTimeOut');
  $ossEngineIndex = config_request_value('ossEngineIndex', $result[0]->indexname, 'engineIndex');
  if($id)	{
    $delete='id:'.$type.'_'.$id;
    delete_document($result[0]->serverurl,$result[0]->indexname,$result[0]->username,$result[0]->key,$delete);
    $sql='SELECT * FROM `'.$table_name_posts.'` WHERE `post_status` LIKE '."'".'publish'."'" .' AND `ID` ='.$id;
	$index=add_documents_to_index($sql,$table_name_users);
    opensearchserver_start_indexing($index, $result[0], $ossEnginePath, $ossEngineIndex);
  }else {
    delete_document($result[0]->serverurl,$result[0]->indexname,$result[0]->username,$result[0]->key,'*:*');
    $sql='SELECT * FROM `'.$table_name_posts.'` WHERE `post_status` LIKE '."'".'publish'."'";
  $result_posts = $wpdb->get_results($sql);
  $numrows=$wpdb->num_rows;
   for ($i = 0; $i < $numrows; $i++) {
 	if($numrows >100) {
	 	if ($i != 0) {
	  		$i = $j + 1;
	  	}
	  	$j = $i + 100;
	}
	else {
		$i=0;
		$j=$numrows;
		$sql_posts='SELECT * FROM `'.$table_name_posts.'` WHERE `post_status` LIKE '."'".'publish'."'".' LIMIT '.$i.','.$j;
		$index=add_documents_to_index($sql_posts,$table_name_users);
		opensearchserver_start_indexing($index, $result[0], $ossEnginePath, $ossEngineIndex);
		break;
	}
	$sql_posts='SELECT * FROM `'.$table_name_posts.'` WHERE `post_status` LIKE '."'".'publish'."'".' LIMIT '.$i.','.$j;
	$index=add_documents_to_index($sql_posts,$table_name_users);
 	opensearchserver_start_indexing($index, $result[0], $ossEnginePath, $ossEngineIndex);
 }
}
  $index_status=1;
 }
  return $index_status;
}
function add_documents_to_index($sql_posts,$table_name_users) {
	global $wpdb;
	$index = new OSSIndexDocument();
	$batch_result_posts = $wpdb->get_results($sql_posts);
	$lang= substr(get_locale(), 0, 2);
	foreach($batch_result_posts as $posts){
		$result_users = $wpdb->get_results('SELECT user_nicename,user_email,user_url FROM `'.$table_name_users.'` WHERE `ID` = '.$posts->post_author);
		$document = $index->newDocument($lang);
		$document->newField('id', $posts->post_type.'_'.$posts->ID);
		$document->newField('type', strip_tags($posts->post_type));
		$document->newField('title', strip_tags($posts->post_title));
		$document->newField('content', strip_tags($posts->post_content));
		$document->newField('url', $posts->guid);
		$document->newField('timestamp', $posts->post_date_gmt);
		$document->newField('user_name',$result_users[0]->user_nicename );
		$document->newField('user_email',$result_users[0]->user_email );
		$document->newField('user_url',$result_users[0]->user_url);		
	}
return $index;
}
function opensearchserver_start_indexing($index, $serverDetails, $ossEnginePath, $ossEngineIndex) {
	$server = new OssApi($ossEnginePath, $ossEngineIndex);
	$server->credential($serverDetails->username, $serverDetails->key);
	if ($server->update($index, $ossEngineIndex) === FALSE) {
		$errors[] = 'failedToUpdate';
	}
	$server->optimize();
}
function admin_page() {
  global $wpdb;
  $table_name =$wpdb->prefix ."opensearchserver";
  echo '<div class="wrap"><h2> OpenSearchServer Settings</h2>';
  $action=isset($_POST['action']) ? $_POST['action'] :null;
  $delay = isset($_POST['delay']) ? $_POST['delay'] :null;
  $username = isset($_POST['username']) ? $_POST['username'] :null;
  $key = isset($_POST['key']) ? $_POST['key'] :null;
  $serverurl = isset($_POST['serverurl']) ? $_POST['serverurl'] :null;
  $indexname = isset($_POST['indexname']) ? $_POST['indexname'] :null;
  $indexing_method = isset($_POST['indexing_method']) ? $_POST['indexing_method'] :null;
  if($action == "Create-index") {
  	$wpdb->query('TRUNCATE TABLE `wp_opensearchserver');
  	$last_index=date('YmdHis', time());
  	$rows_affected = $wpdb->insert( $table_name, array( 'serverurl' =>$serverurl, 'indexname' => $indexname, 'username' => $username, 'key' => $key,'indexing_method' => $indexing_method, 'last_indexed' => $last_index ) );
     if ($indexing_method=='automated_indexation') {
    	 $is_index=configure_OSS($serverurl,$indexname,$username,$key);
    	 if(!$is_index) {
    	 	setFields_OSS($serverurl,$indexname,$username,$key);
    	 	echo '<h4 style="color:#3366FF">Index created successfully.</h4>';
    	 }
    	 else {
    	 	echo '<h4 style="color:#3366FF">Index already exist.</h4>';
    	 }
    	 
    }
  }
  if($action == "Reindex-Site") {
  	if ($indexing_method=='automated_indexation') {
	    $index_success=reindex_site('','');
	    if($index_success) {
	    echo '<h4 style="color:#3366FF">Re-index finshed Successfully.</h4>';
	    }
  	}
  }
   if($action == "Save") {
  	$wpdb->query('TRUNCATE TABLE `wp_opensearchserver');
  	$last_index=date('YmdHis', time());
  	$rows_affected = $wpdb->insert( $table_name, array( 'serverurl' =>$serverurl, 'indexname' => $indexname, 'username' => $username, 'key' => $key,'indexing_method' => $indexing_method, 'last_indexed' => $last_index ) );
  	echo '<h4 style="color:#3366FF">The Preference saved Successfully.</h4>';
  }
  $result = $wpdb->get_results('SELECT * FROM '.$table_name);
  ?>
<form id="admin" name="admin" method="post" action="">
	<input type="hidden" name="opensearchserver" value="true" />
	OpenSearchServer URL: <br />
	 <input type="text" name="serverurl" id="serverurl" size="50" value="<?php if($result){echo $result[0]->serverurl;}?>" /> <br />
	IndexName :<br /> 
	<input type="text" name="indexname" id="indexname" size="50" value="<?php if($result){echo $result[0]->indexname;}?>" /><br/> 
	Username :<br/> 
	<input type="text" name="username" id="username" size="50" value="<?php if($result){echo $result[0]->username;}?>" /> <br /> 
	Key	:<br/> 
	<input type="text" name="key" id="key" size="50" value="<?php if($result){echo $result[0]->key;}?>" /><br/> 
	Select indexation method :<br/>
	<?php if($result && $result[0]->indexing_method=='manual_indexation') { ?>	
 	<select name="indexing_method">
  	<option value="manual_indexation" selected="selected" >Manual indexation</option>
  	<option value="automated_indexation">Automated indexation</option>
	</select> 
	<?php }else {?>
	<select name="indexing_method">
	<option value="manual_indexation">Manual indexation</option>
	<option value="automated_indexation" selected="selected">Automated indexation</option>
	</select>
	<?php }?>
	<br /><br /> 
	<input type="submit" name="action" id="action" value="Save" />
	<?php if($result && $result[0]->indexing_method=='automated_indexation') { ?>	
	<input type="submit" name="action" id="action" value="Create-index" />
	<input type="submit" name="action" id="action" value="Reindex-Site" />
	<?php }?>
</form>

<?php echo '</div>';

}

function  opensearchserver_search() {
  if ( stripos($_SERVER['REQUEST_URI'], '/?s=') === FALSE && stripos($_SERVER['REQUEST_URI'], '/search/') === FALSE)	{
    return;
  }
  get_header();
  print '<form role="search" method="get" id="searchform" action="' . home_url( '/' ) . '" >
		<div><label class="screen-reader-text" for="s">' . __('Search for:') . '</label>
		<input type="text" size=50 value="' . get_search_query() . '" name="s" id="s" />
		<input type="submit" id="searchsubmit" value="'. esc_attr__('Search') .'" />
		</div>
		</form><hr/>';

  print get_sidebar();
  print get_search_result_output(get_search_query());
  get_footer();
  exit;
}

function opensearchserver_admin_actions() {
	add_submenu_page('plugins.php', 'OpenSearchServer Settings', 'OpenSearchServer', 'edit_plugins', __FILE__, 'admin_page');
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
  else {
    global $wpdb;
    $table_name =$wpdb->prefix ."opensearchserver";
    $result = $wpdb->get_results('SELECT * FROM '.$table_name);
    $delete='id:'.$post->post_type.'_'.$post->ID;
    delete_document($result[0]->serverurl,$result[0]->indexname,$result[0]->username,$result[0]->key,$delete);
  }
}
register_activation_hook(__FILE__,'opensearchserver_install');
register_deactivation_hook( __FILE__, 'opensearchserver_uninstall');
add_action('save_post','do_while_posting',10,2);
add_action('admin_menu', 'opensearchserver_admin_actions');
add_action('template_redirect', 'opensearchserver_search');
add_filter( 'get_search_form', 'opensearchserver_form' );
?>