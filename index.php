<?php
/**
 Plugin Name: OpenSearchServer
 Plugin URI: http://wordpress.org/extend/plugins/opensearchserver-search/
 Description: This Plugin will integrate OpenSearchServer 1.2 as search engine for Wordpress. Go to <a href="plugins.php?page=opensearchserver-search/index.php">OpenSearchServer Settings</a> for OpenSearchServer Settings. More information <a href="http://www.open-search-server.com">about OpenSearchServer</a>.
 Author: Naveen.A.N
 Requires at least: 3.0.1
 Tested up to: 3.4.2
 Version: 1.0.7
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

function opensearchserver_form() {
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
	$searchTemplate=new OSSSearchTemplate($url,$indexname);
	$searchTemplate->credential($username,$key);
	if (!$ossAPI->isIndexAvailable($indexname)) {
		global $wpdb;
		$table_name =$wpdb->prefix ."opensearchserver";
		$result = $wpdb->get_results('SELECT * FROM '.$table_name);
		$ossAPI->setField('id','','NO','YES','YES','NO','YES');
		$ossAPI->setField('type','','NO','YES','YES','NO','NO');
		$ossAPI->setField('url','','NO','YES','YES','NO','NO');
		$ossAPI->setField('title','TextAnalyzer','compress','YES','positions_offsets','NO','NO');
		$ossAPI->setField('content','TextAnalyzer','compress','YES','positions_offsets','YES','NO');
		$ossAPI->setField('timestamp','','NO','YES','YES','NO','NO');
		$ossAPI->setField('user_name','','compress','YES','YES','NO','NO');
		$ossAPI->setField('user_email','','compress','YES','YES','NO','NO');
		$ossAPI->setField('user_url','','NO','YES','YES','NO','NO');
		$searchTemplate->createSearchTemplate("search",'	title:($$)^10 OR title:("$$")^10
				OR
				content:($$)^5 OR content:("$$")^5
				',"AND","10","2","ENGLISH");
		$searchTemplate->setSnippetField("search","title");
		$searchTemplate->setSnippetField("search","content");
		$searchTemplate->setReturnField("search","url");
		$searchTemplate->setReturnField("search","user_url");
		$searchTemplate->setReturnField("search","type");
		$searchTemplate->setReturnField("search","user_name");
		$searchTemplate->setReturnField("search","user_email");
	}
}

function delete_document($serverurl,$indexname,$username,$password,$query) {
	$deleteAPI = new OssDelete($serverurl,$indexname);
	$deleteAPI->credential($username,$password);
	$deleteAPI->delete($query);
}

function reindex_site($id,$type) {
	global $wpdb,$blog_id;
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
			$sql_suffix = 'FROM '.$table_name_posts.' p LEFT JOIN  '.$table_name_users.' u ON p.post_author = u.ID WHERE `post_status` = \'publish\' AND p.ID ='.$id;
			$sql_query = 'SELECT p.ID,post_type,post_title,post_content,guid,post_date_gmt,post_author,user_nicename,user_url,user_email '.$sql_suffix;
			$sql_posts = $wpdb->get_results($sql_query);
			$index=add_documents_to_index($sql_posts);
			opensearchserver_start_indexing($index, $result[0], $ossEnginePath, $ossEngineIndex);
		}else {
			delete_document($result[0]->serverurl,$result[0]->indexname,$result[0]->username,$result[0]->key,'*:*');
			wp_cache_flush();
			$batch = 200;
			$sql_suffix = 'FROM '.$table_name_posts.' p LEFT JOIN  '.$table_name_users.' u ON p.post_author = u.ID WHERE `post_status` = \'publish\'';
			$total_count = $wpdb->get_var($wpdb->prepare( 'SELECT COUNT(*) '.$sql_suffix));
			$wpdb->flush();
			$sql_query = 'SELECT p.ID,post_type,post_title,post_content,guid,post_date_gmt,post_author,user_nicename,user_url,user_email '.$sql_suffix;
			$current_pos = 0;
			while ($current_pos < $total_count) {
				$row_fetch = $total_count - $current_pos;
				if ($row_fetch > $batch) {
					$row_fetch = $batch;
				}
				$sql_posts = $wpdb->get_results($sql_query.' LIMIT '.$current_pos.','.$batch);
				$index = add_documents_to_index($sql_posts);
				opensearchserver_start_indexing($index, $result[0], $ossEnginePath, $ossEngineIndex);
				$wpdb->flush();
				wp_cache_flush();
				unset($sql_posts);
				unset($index);
				$current_pos += $row_fetch;
			}
		}
		opensearchserver_optimize($result[0],$ossEnginePath, $ossEngineIndex);
		$index_status=1;
	}
	return $index_status;
}


function add_documents_to_index($sql_posts) {
	$index = new OSSIndexDocument();
	$lang= substr(get_locale(), 0, 2);
	foreach($sql_posts as $post){
		$content=opensearchserver_encode(strip_tags($post->post_content));
		$document = $index->newDocument($lang);
		$document->newField('id', $post->post_type.'_'.$post->ID);
		$document->newField('type', strip_tags($post->post_type));
		$document->newField('title', stripInvalidXml(strip_tags($post->post_title)));
		$document->newField('content',stripInvalidXml($content));
		$document->newField('url', get_permalink($post->ID));
		$document->newField('timestamp', $post->post_date_gmt);
		$document->newField('user_name', $post->user_nicename);
		$document->newField('user_email', $post->user_email);
	}
	return $index;
}

function opensearchserver_encode($str) {
	$str2 = iconv('UTF-8', 'UTF-8//IGNORE', $str);
	if ($str2 == null) {
		$str2= mb_convert_encoding($str, 'UTF-8', 'UTF-8');
	}
	return stripInvalidXml($str2);
}

function stripInvalidXml($value)
{
	$ret = "";
	$current;
	if (empty($value))
	{
		return $ret;
	}

	$length = strlen($value);
	for ($i=0; $i < $length; $i++)
	{
		$current = ord($value{$i});
		if (($current == 0x9) ||
				($current == 0xA) ||
				($current == 0xD) ||
				(($current >= 0x20) && ($current <= 0xD7FF)) ||
				(($current >= 0xE000) && ($current <= 0xFFFD)) ||
				(($current >= 0x10000) && ($current <= 0x10FFFF)))
		{
			$ret .= chr($current);
		}
		else
		{
			$ret .= " ";
		}
	}
	return $ret;
}

function opensearchserver_start_indexing($index, $serverDetails, $ossEnginePath, $ossEngineIndex) {
	$server = new OssApi($ossEnginePath, $ossEngineIndex);
	$server->credential($serverDetails->username, $serverDetails->key);
	if ($server->update($index, $ossEngineIndex) === FALSE) {
		$errors[] = 'failedToUpdate';
	}
}

function opensearchserver_optimize($serverDetails, $ossEnginePath, $ossEngineIndex) {
	$server = new OssApi($ossEnginePath, $ossEngineIndex);
	$server->credential($serverDetails->username, $serverDetails->key);
	$server->optimize();
}

function admin_page() {
	global $wpdb;
	$table_name =$wpdb->prefix ."opensearchserver";
	echo '<div cla$indexing_methodss="wrap"><h2> OpenSearchServer Settings</h2>';
	$action=isset($_POST['action']) ? $_POST['action'] :null;
	$delay = isset($_POST['delay']) ? $_POST['delay'] :null;
	$username = isset($_POST['username']) ? $_POST['username'] :null;
	$key = isset($_POST['key']) ? $_POST['key'] :null;
	$serverurl = isset($_POST['serverurl']) ? $_POST['serverurl'] :null;
	$indexname = isset($_POST['indexname']) ? $_POST['indexname'] :null;
	$indexing_method = isset($_POST['indexing_method']) ? $_POST['indexing_method'] :null;
	if($action == "Create-index") {
		$wpdb->query('TRUNCATE TABLE '.'`'.$table_name.'`');
		$last_index=date('YmdHis', time());
		$rows_affected = $wpdb->insert( $table_name, array( 'serverurl' =>$serverurl, 'indexname' => $indexname, 'username' => $username, 'key' => $key,'indexing_method' => $indexing_method,'last_indexed' => $last_index ) );
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
		$wpdb->query('TRUNCATE TABLE '.'`'.$table_name.'`');
		$last_index=date('YmdHis', time());
		$rows_affected = $wpdb->insert( $table_name, array( 'serverurl' =>$serverurl, 'indexname' => $indexname, 'username' => $username, 'key' => $key,'indexing_method' => $indexing_method,'last_indexed' => $last_index ) );
		echo '<h4 style="color:#3366FF">The Preference saved Successfully.</h4>';
	}
	$result = $wpdb->get_results('SELECT * FROM '.$table_name);
	?>
<form id="admin" name="admin" method="post" action="">
	<input type="hidden" name="opensearchserver" value="true" />
	OpenSearchServer URL: <br /> <input type="text" name="serverurl"
		id="serverurl" size="50"
		value="<?php if($result){echo $result[0]->serverurl;}?>" /> <br />
	IndexName :<br /> <input type="text" name="indexname" id="indexname"
		size="50" value="<?php if($result){echo $result[0]->indexname;}?>" /><br />
	Username :<br /> <input type="text" name="username" id="username"
		size="50" value="<?php if($result){echo $result[0]->username;}?>" /> <br />
	Key :<br /> <input type="text" name="key" id="key" size="50"
		value="<?php if($result){echo $result[0]->key;}?>" /><br /> <br />
	Select indexation method :<br />
	<?php if($result && $result[0]->indexing_method=='manual_indexation') { ?>
	<select name="indexing_method">
		<option value="manual_indexation" selected="selected">Manual
			indexation</option>
		<option value="automated_indexation">Automated indexation</option>
	</select>
	<?php }else {?>
	<select name="indexing_method">
		<option value="manual_indexation">Manual indexation</option>
		<option value="automated_indexation" selected="selected">Automated
			indexation</option>
	</select>
	<?php }?>
	<br /> <br /> <input type="submit" name="action" id="action"
		value="Save" />
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
	if (stripos($_SERVER['REQUEST_URI'], '/?s=') === FALSE && stripos($_SERVER['REQUEST_URI'], '/search/') === FALSE)	{
		return;
	}
	if (file_exists(TEMPLATEPATH . '/oss_search.php')) {
		include_once(TEMPLATEPATH . '/oss_search.php');
	} else if (file_exists(dirname(__FILE__) . '/template/opensearchserver_search.php')) {
		include_once(dirname(__FILE__) . '/template/opensearchserver_search.php');
	} else {
		return;
	}
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