<?php
/*
 * Creating instances for OpenSearchServer library.
*/
function opensearchserver_getapi_instance() {
  return new OssApi(get_option('oss_serverurl'), get_option('oss_indexname'), get_option('oss_login'), get_option('oss_key'));
}
function opensearchserver_getschema_instance() {
  return new OssSchema(get_option('oss_serverurl'), get_option('oss_indexname'), get_option('oss_login'), get_option('oss_key'));
}
function opensearchserver_getsearchtemplate_instance() {
  return new OssSearchTemplate(get_option('oss_serverurl'), get_option('oss_indexname'), get_option('oss_login'), get_option('oss_key'));
}
function opensearchserver_getdelete_instance() {
  return new OssDelete(get_option('oss_serverurl'), get_option('oss_indexname'), get_option('oss_login'), get_option('oss_key'));
}
function opensearchserver_getautocomplete_instance() {
  return new OssAutocompletion(get_option('oss_serverurl'), get_option('oss_indexname'), get_option('oss_login'), get_option('oss_key'));
}
function opensearchserver_getmonitor_instance() {
  return new OssMonitor(get_option('oss_serverurl'), get_option('oss_login'), get_option('oss_key'));
}


/**
 * Create or re-create the index if it already exists
 * @return boolean
 */
function opensearchserver_create_index() {
  $indexName = get_option('oss_indexname');
  $custom_fields = get_option('oss_custom_field');
  $oss_api = opensearchserver_getapi_instance();
  $index_list = $oss_api->indexList();
  $index = in_array($indexName, $index_list);
  if ($index !== FALSE) {
    $oss_api->deleteIndex($indexName);
  }

  $oss_api->createIndex($indexName);
  opensearchserver_create_schema($custom_fields);
  opensearchserver_query_template($custom_fields);
  opensearchserver_spellcheck_query_template();
  $autocompletion = opensearchserver_getautocomplete_instance();
  $autocompletion_name = 'autocomplete';
  $autocompletion->createAutocompletion($autocompletion_name, 'contentExact');
  return TRUE;
}

function opensearchserver_setField($ossSchema, $xmlSchema, $fieldName, $analyzer, $stored, $indexed, $termVector, $default = 'no', $unique = 'no') {
  $xmlField = $xmlSchema->xpath('/response/schema/fields/field[@name="' . $fieldName . '"]');
  // Check if the field already exists.
  if (isset($xmlField[0]) && is_array($xmlField)) {
    $ossSchema->deleteField($fieldName);
  }
  $ossSchema->setField($fieldName, $analyzer, $stored, $indexed, $termVector, $default, $unique);
}

/*
 * Function create schema values for OpenSearchServer
*/

function opensearchserver_create_schema($custom_fields) {
  $schema = opensearchserver_getschema_instance();
  $schema_xml = $schema->getSchema();
  opensearchserver_setField($schema,$schema_xml,'id', NULL, 'yes', 'yes', 'no', 'no', 'yes');
  opensearchserver_setField($schema,$schema_xml,'type', NULL, 'yes', 'yes', 'no', 'no', 'no');
  opensearchserver_setField($schema,$schema_xml,'url',NULL,'no','yes','yes','no','no');
  opensearchserver_setField($schema,$schema_xml,'urlExact','StandardAnalyzer','no','yes','yes','no','no');
  opensearchserver_setField($schema,$schema_xml,'title','TextAnalyzer','yes','yes','positions_offsets','no','no');
  opensearchserver_setField($schema,$schema_xml,'titleExact','StandardAnalyzer','no','yes','no','no','no');
  if (get_option('oss_phonetic')) {
    opensearchserver_setField($schema,$schema_xml,'titlePhonetic','PhoneticAnalyzer','yes','yes','positions_offsets','no','no');
    opensearchserver_setField($schema,$schema_xml,'contentPhonetic','PhoneticAnalyzer','yes','yes','positions_offsets','no','no');
  }
  opensearchserver_setField($schema,$schema_xml,'content','TextAnalyzer','yes','yes','positions_offsets','yes','no');
  opensearchserver_setField($schema,$schema_xml,'contentExact','StandardAnalyzer','no','yes','no','no','no');
  opensearchserver_setField($schema,$schema_xml,'timestamp',NULL,'no','yes','yes','no','no');
  opensearchserver_setField($schema,$schema_xml,'user_name',NULL,'yes','yes','yes','no','no');
  opensearchserver_setField($schema,$schema_xml,'user_email',NULL,'yes','yes','yes','no','no');
  opensearchserver_setField($schema,$schema_xml,'user_url',NULL,'no','yes','yes','no','no');
  opensearchserver_setField($schema,$schema_xml,'allContent','TextAnalyzer','no','yes','no','yes','no');
  opensearchserver_setField($schema,$schema_xml,'categories','TextAnalyzer','yes','yes','no','yes','no');
  opensearchserver_setField($schema,$schema_xml,'categoriesExact',NULL,'yes','yes','no','yes','no');
  opensearchserver_setField($schema,$schema_xml,'tags','TextAnalyzer','yes','yes','no','yes','no');
  opensearchserver_setField($schema,$schema_xml,'tagsExact',NULL,'yes','yes','no','yes','no');
  if (isset($custom_fields) && $custom_fields != null) {
    $custom_fields_array = explode(',', $custom_fields);
    foreach ($custom_fields_array as $field) {
      $field = opensearchserver_clean_field($field);
      if (strlen($field) > 0) {
        opensearchserver_setField($schema,$schema_xml,'custom_'.$field,NULL,'yes','yes','no','yes','no');
      }
    }
  }
}

/*
 * Function to display messages in admin
*/
function opensearchserver_display_messages($message, $errormsg = FALSE) {
  if ($errormsg) {
    echo '<div id="message" class="error">';
  }
  else {
    echo '<div id="message" class="updated fade">';
  }
  echo "<p><strong>$message</strong></p></div>";
}

/*
 * Function to update the query template
*/
function opensearchserver_query_template($custom_fields) {
  $query_template = opensearchserver_getsearchtemplate_instance();
  $oss_query = stripcslashes(get_option('oss_query'));
  $oss_query = str_replace("\r", "", $oss_query);
  $query_template->createSearchTemplate('search', $oss_query, 'AND', '10', '2', get_option('oss_language'));
  $query_template->setSnippetField('search','title', 70, 'b');
  $query_template->setSnippetField('search','content', 300, 'b', NULL, 'SentenceFragmenter');
  if (get_option('oss_phonetic')) {
    $query_template->setSnippetField('search','titlePhonetic', 70, 'b');
    $query_template->setSnippetField('search','contentPhonetic', 300, 'b', NULL, 'SentenceFragmenter');
  }
  $query_template->setReturnField('search','url');
  $query_template->setReturnField('search','user_url');
  $query_template->setReturnField('search','type');
  $query_template->setReturnField('search','user_name');
  $query_template->setReturnField('search','user_email');
  $query_template->setReturnField('search','categories');
  $query_template->setReturnField('search','tags');
  if (isset($custom_fields) && $custom_fields != null) {
    $custom_fields_array = explode(',', $custom_fields);
    foreach ($custom_fields_array as $field) {
      $field = opensearchserver_clean_field($field);
      if (strlen($field) > 0) {
        $query_template->setReturnField('search','custom_'.$field);
      }
    }
  }
}

/*
 * Function to create the spellcheck query
*/
function opensearchserver_spellcheck_query_template() {
  $spell_field = get_option('oss_spell').'Exact';
  $spell_algo = get_option('oss_spell_algo');
  if($spell_field && $spell_field !='none') {
    $spellcheck_query_template = opensearchserver_getsearchtemplate_instance();
    $spellcheck_query_template->createSpellCheckTemplate('spellcheck', '*:*', '1', $spell_field, '0.5', NULL, $spell_algo);
  }
}

/**
 * Check if the document list should be indexed
 * @param OSSIndexDocument $index
 * @param number $limit
 */
function opensearchserver_checkindex(OSSIndexDocument $index, $limit = 1, $idx = 0, $total = 0) {
  global $wpdb;
  if ($index->count() < $limit) {
    return $index;
  }
  opensearchserver_start_indexing($index);
  $index = null;
  wp_cache_flush();
  $wpdb->flush();
  if (function_exists('gc_enabled')) {
    if (gc_enabled()) {
      gc_collect_cycles();
    }
  }

  if ($idx != 0 && $total != 0) {
    $percent = (floatval($idx) / floatval($total)) * 100;
    $mem = floatval(memory_get_usage()) / 1024 / 1024;
    opensearchserver_display_messages(sprintf('Completed: %.2f - Memory usage: %2f', $percent, $mem));
  }
  return new OSSIndexDocument();
}

/*
 * Function to reindex the website.
*/
function opensearchserver_reindex_site($id,$type, $from = 0, $to = 0) {
  global $wpdb;
  $oss_server_url = get_option('oss_serverurl');
  $oss_indexname = get_option('oss_indexname');
  $oss_login =  get_option('oss_login');
  $oss_key = get_option('oss_key');
  $custom_fields = get_option('oss_custom_field');
  $lang = get_option('oss_language');
  $table_name_posts =$wpdb->prefix ."posts";
  $table_name_users =$wpdb->prefix ."users";
  $index_status=0;
  $ossEnginePath  = config_request_value('ossEnginePath', $oss_server_url, 'engineURL');
  $ossEngineConnectTimeOut = config_request_value('ossEngineConnectTimeOut', 5, 'engineConnectTimeOut');
  $ossEngineIndex = config_request_value('ossEngineIndex', $oss_indexname, 'engineIndex');
  if($id) {
    $delete='id:'.$type.'_'.$id;
    opensearchserver_delete_document($delete);
    $index = new OSSIndexDocument();
    opensearchserver_add_documents_to_index($index, $lang, get_post($id), $custom_fields);
    opensearchserver_checkindex($index);
  } else {
    $from = (int) $from;
    $to = (int) $to;
    if ($from == 0 && $to == 0) {
      opensearchserver_delete_document('*:*');
    }
    $limitSuffix = $to != 0 ? ' LIMIT '.$from.','.($to - $from) : '';
    $sql_query = 'SELECT ID FROM '.$wpdb->posts.' WHERE post_status = \'publish\' ORDER BY ID'.$limitSuffix;
    $posts = $wpdb->get_results($sql_query);
    $total_count = count($posts);
    $index = new OSSIndexDocument();
    for ($i = 0; $i < $total_count; $i++) {
      $post = get_post($posts[$i]->ID);
      if (get_option('oss_index_types_'.$post->post_type) == 1) {
        opensearchserver_add_documents_to_index($index, $lang, $post, $custom_fields);
        $index = opensearchserver_checkindex($index, 200, $i, $total_count);
      }
    }
    opensearchserver_checkindex($index, 1, $i, $total_count);
  }
  opensearchserver_optimize();
  opensearchserver_autocompletionBuild();
  return 1;
}

function opensearchserver_delete_document($query) {
  return opensearchserver_getdelete_instance()->delete($query);
}

function opensearchserver_start_indexing($index) {
  $server = opensearchserver_getapi_instance();
  if ($server->update($index, get_option('oss_indexname')) === FALSE) {
    $errors[] = 'failedToUpdate';
  }
}

function opensearchserver_clean_field($field) {
  $field = str_replace(' ', '_', $field);
  $escapechars = array('\\', '^', '~', ':', '(', ')', '{', '}', '[', ']' , '&', '||', '!', '*', '?','039;','\'','#');
  foreach ($escapechars as $escchar)  {
    $field = str_replace($escchar, '_', $field);
  }
  $field = trim($field);
  return strtolower($field);
}

function opensearchserver_stripInvalidXml($value) {
  $ret = "";
  $current;
  if (empty($value)) {
    return $ret;
  }
  $length = strlen($value);
  for ($i=0; $i < $length; $i++) {
    $current = ord($value{$i});
    if (($current == 0x9) ||
      ($current == 0xA) ||
      ($current == 0xD) ||
      (($current >= 0x20) && ($current <= 0xD7FF)) ||
      (($current >= 0xE000) && ($current <= 0xFFFD)) ||
      (($current >= 0x10000) && ($current <= 0x10FFFF))){
      $ret .= chr($current);
    }
    else {
      $ret .= " ";
    }
  }
  return $ret;
}

function opensearchserver_encode($str) {
  $str2 = iconv('UTF-8', 'UTF-8//IGNORE', $str);
  if ($str2 == null) {
    $str2= mb_convert_encoding($str, 'UTF-8', 'UTF-8');
  }
  return opensearchserver_stripInvalidXml($str2);
}

function opensearchserver_optimize() {
  $server = opensearchserver_getapi_instance();
  $server->optimize();
}

function opensearchserver_autocompletionBuild() {
  $oss_monitor = opensearchserver_getmonitor_instance();
  $version = $oss_monitor->get_oss_version();
  $autocompletion = opensearchserver_getautocomplete_instance();
  if($version < 1.5) {	 
    $autocompletion->autocompletionSet('contentExact');
    $autocompletion->autocompletionBuild();
  }else {
  	$autocompletion_name = 'autocomplete';
    $autocompletion->autocompletionBuildREST($autocompletion_name);
  }
}


function opensearchserver_add_documents_to_index(OSSIndexDocument $index, $lang, $post, $customFields) {
  $user = get_userdata($post->post_author);
  $content = $post->post_content;
  $content = apply_filters('the_content', $content);
  $content = str_replace(']]>', ']]&gt;', $content);
  $content = opensearchserver_encode(strip_tags($content));
  $content = opensearchserver_stripInvalidXml($content);
  $document = $index->newDocument($lang);
  $document->newField('id', $post->post_type.'_'.$post->ID);
  $document->newField('type', strip_tags($post->post_type));
  $title = opensearchserver_stripInvalidXml(strip_tags($post->post_title));
  $document->newField('title', $title);
  $document->newField('titleExact', $title);
  $document->newField('titlePhonetic', $title);
  $document->newField('content', $content);
  $document->newField('contentExact', $content);
  $document->newField('contentPhonetic', $content);
  $document->newField('url', get_permalink($post->ID));
  $document->newField('urlExact', get_permalink($post->ID));
  $document->newField('timestamp', $post->post_date_gmt);
  $document->newField('user_name', $user->user_nicename);
  $document->newField('user_email', $user->user_email);
  $document->newField('user_email', $user->user_url);
  $categories_data= '';

  // Handling categories
  $categories_data = NULL;
  $categories = get_the_category($post->ID);
  if ($categories != NULL) {
    foreach( $categories as $category ) {
      $categories_data .= $category->cat_name.' , ';
    }
	$document->newField('categories', $categories_data);
    $document->newField('categoriesExact', $categories_data);
    $categories = NULL;
  }
  
  //Handling tags
  $tags_data = NULL;
  $tags = get_the_tags($post->ID);
    if ($tags != NULL) {
      foreach($tags as $tag) {
		$tags_data .= $tag->name.' , ';
      }
      $document->newField('tags', $tags_data);
      $document->newField('tagsExact', $tags_data);
      $tags_data = NULL;
	  $tags = NULL;
    }

  // Handling custom fields
  $custom_clean_all='';
  if($customFields) {
    $custom_fields_array = explode(',',$customFields);
    foreach ($custom_fields_array as $field) {
      $field = trim($field);
      $custom_content = '';
      $custom_values=get_post_custom_values($field, $post->ID);
      if(is_array($custom_values)) {
        foreach ($custom_values as $values) {
          $custom_content .= $values.' ';
        }
      }else {
        $custom_content = $custom_values;
      }
      $content_br = nl2br($custom_content);
      $content_clean=str_replace('<br />', ' ', $content_br);
      $document->newField('custom_'.opensearchserver_clean_field($field), opensearchserver_stripInvalidXml(strip_tags($content_clean)));
      $custom_clean_all .=' '.$content_clean;
    }
    $custom_fields_array = null;
  }
  // Build all content field
  $all_content = opensearchserver_stripInvalidXml(strip_tags($post->post_title)). ' '.$content;
  if ($categories_data) {
    $all_content = strip_tags($all_content.' '.$categories_data);
  }
  if ($custom_clean_all) {
    $all_content .= ' ' .$custom_clean_all;
    $custom_clean_all = null;
  }
  $document->newField("allContent", strip_tags($all_content.' '.$categories_data));
}

function opensearchserver_default_query() {
  $q = 'title:($$)^10 OR title:("$$")^10'."\n";
  $q = $q.'OR titleExact:($$)^10 OR titleExact:("$$")^10'."\n";
  if (get_option('oss_phonetic')) {
    $q = $q.'OR titlePhonetic:($$)^10 OR titlePhonetic:("$$")^10'."\n";
    $q = $q.'OR contentPhonetic:($$) OR contentPhonetic:("$$")'."\n";
  }
  $q = $q.'OR content:($$) OR content:("$$")'."\n";
  $q = $q.'OR contentExact:($$) OR contentExact:("$$")'."\n";
  $q = $q.'OR allContent:($$)^0.1 OR allContent:("$$")^0.1';
  return $q;
}

function opensearchserver_get_fields() {
  return array('none'=>'Select',
    'title' => 'Title',
    'content' =>'Content',
    'url' => 'Url',
    'user_name' => 'User Name',
    'user_email' => 'User Email',
    'user_url' => 'User URL',
    'id' => 'ID',
    'type' => 'Type',
    'timestamp' => 'TimeStamp',
    'tags' => 'Tags',
    'categories' => 'Categories');
}

function opensearchserver_admin_set_instance_settings() {
  $oss_url = isset($_POST['oss_serverurl']) ? $_POST['oss_serverurl'] :NULL;
  $oss_indexname = isset($_POST['oss_indexname']) ? $_POST['oss_indexname'] :NULL;
  $oss_login = isset($_POST['oss_login']) ? $_POST['oss_login'] :NULL;
  $oss_key = isset($_POST['oss_key']) ? $_POST['oss_key'] :NULL;
  update_option('oss_serverurl', $oss_url);
  update_option('oss_indexname', $oss_indexname);
  update_option('oss_login', $oss_login);
  update_option('oss_key', $oss_key);
  opensearchserver_display_messages('OpenSearchServer Instance Settings has been updated');
}
function opensearchserver_update_facet_settings($facet_field) {
  if($facet_field != 'none' || $facet_field != NULL) {
    if(get_option('oss_facet')) {
      $facet = get_option('oss_facet');
    }else {
      $facet = array();
    }
    if (!in_array($facet_field, $facet)) {
      array_push($facet,$facet_field);
    }
    update_option('oss_facet', $facet);
  }
}
function opensearchserver_admin_set_query_settings() {
  $delete = isset($_POST['oss_delete']) ? $_POST['oss_delete'] :NULL;
  $delete_action = isset($_POST['opensearchserver_delete']) ? $_POST['opensearchserver_delete'] :NULL;
  if($delete !=NULL && $delete_action === 'Delete') {
    $facets = get_option('oss_facet');
    foreach ($facets as $key => $facet) {
      if(trim($facet) == trim($delete)) {
        unset($facets[$key]);
      }
    }
    update_option('oss_facet', $facets);
  }else {
    $oss_query = isset($_POST['oss_query']) ? $_POST['oss_query'] : NULL;
    if (isset($oss_query)) {
      $oss_query = trim($oss_query);
      if (strlen($oss_query) == 0) {
        $oss_query = opensearchserver_default_query();
      }
    }
    $oss_facet = isset($_POST['oss_facet']) ? $_POST['oss_facet'] : NULL;
    $oss_spell = isset($_POST['oss_spell']) ? $_POST['oss_spell'] : NULL;
    $oss_spell_algo = isset($_POST['oss_spell_algo']) ? $_POST['oss_spell_algo'] : NULL;
	$oss_custom_facet = isset($_POST['oss_custom_facet']) ? $_POST['oss_custom_facet'] : NULL;
    update_option('oss_query', $oss_query);
    if($oss_facet != 'none') {
      opensearchserver_update_facet_settings($oss_facet);
    }else{
    	opensearchserver_update_facet_settings($oss_custom_facet);
    }
    $oss_multi_filter = isset($_POST['oss_multi_filter']) ? $_POST['oss_multi_filter'] : NULL;
    update_option('oss_multi_filter', $oss_multi_filter);
    $oss_facet_behavior = isset($_POST['oss_facet_behavior']) ? $_POST['oss_facet_behavior'] : NULL;
    update_option('oss_facet_behavior', $oss_facet_behavior);
    update_option('oss_spell', $oss_spell);
    update_option('oss_spell_algo', $oss_spell_algo);
    $oss_language = isset($_POST['oss_language']) ? $_POST['oss_language'] : NULL;
    update_option('oss_language', $oss_language);
    $oss_phonetic = isset($_POST['oss_phonetic']) ? $_POST['oss_phonetic'] : NULL;
    update_option('oss_phonetic', $oss_phonetic);
    $oss_display_user = isset($_POST['oss_display_user']) ? $_POST['oss_display_user'] : NULL;
    update_option('oss_display_user', $oss_display_user);
    $oss_display_category = isset($_POST['oss_display_category']) ? $_POST['oss_display_category'] : NULL;
    update_option('oss_display_category', $oss_display_category);
    $oss_display_type = isset($_POST['oss_display_type']) ? $_POST['oss_display_type'] : NULL;
    update_option('oss_display_type', $oss_display_type);
	$oss_clean_query = isset($_POST['oss_clean_query']) ? $_POST['oss_clean_query'] : NULL;
	update_option('oss_clean_query', $oss_clean_query);
    opensearchserver_display_messages('OpenSearchServer Query Settings has been updated.');
  }
}

function opensearchserver_admin_set_index_settings() {
  $post_oss_submit = $_POST['opensearchserver_submit'];
  if ($post_oss_submit == 'Update Index Settings') {
    foreach (get_post_types() as $post_type) {
      $post_form_type = (int)$_POST['oss_index_types_'.$post_type];
      update_option('oss_index_types_'.$post_type, $post_form_type);
    }
    opensearchserver_display_messages('OpenSearchServer Index Settings has been updated.');
  } else {
    $is_index_created = opensearchserver_create_index();
    opensearchserver_display_messages('Index '.get_option('oss_indexname').' Created successfully');
  }
}

function opensearchserver_admin_set_custom_fields_settings() {
  $oss_custom_field = isset($_POST['oss_custom_field']) ? $_POST['oss_custom_field'] :NULL;
  update_option('oss_custom_field', $oss_custom_field);
  opensearchserver_display_messages('OpenSearchServer Custom Fields Settings has been updated.');
}

function opensearchserver_admin_set_reindex() {
  $oss_index_from = isset($_POST['oss_index_from']) ? $_POST['oss_index_from'] : NULL;
  $oss_index_to = isset($_POST['oss_index_to']) ? $_POST['oss_index_to'] : NULL;
  update_option('oss_index_from', $oss_index_from);
  update_option('oss_index_to', $oss_index_to);
  $index_success = opensearchserver_reindex_site(NULL,NULL, $oss_index_from, $oss_index_to);
  opensearchserver_display_messages('Re indexing has been finished successfully.');
}
/*
 * The admin page settings actions
*/
function opensearchserver_admin_page() {
  $fields = opensearchserver_get_fields();
  $facet_behaviour = array('separate_query'=>'Separate query','no_separate_query'=>'No separate query');
  $spellcheck_fields = array(
    'none'=>'Select',
    'title' => 'Title',
    'content' =>'Content');
  $spellcheck_algo = array(
    'JaroWinklerDistance' => 'JaroWinklerDistance',
    'LevensteinDistance' => 'LevensteinDistance',
    'NGramDistance' => 'NGramDistance');
  $languages = array(
    ''   => 'Undefined',
    'ar' => 'Arabic',
    'zh' => 'Chinese',
    'da' => 'Danish',
    'nl' => 'Dutch',
    'en' => 'English',
    'fi' => 'Finnish',
    'fr' => 'French',
    'de' => 'German',
    'hu' => 'Hungarian',
    'it' => 'Italian',
    'no' => 'Norwegian',
    'pt' => 'Portuguese',
    'ro' => 'Romanian',
    'ru' => 'Russian',
    'es' => 'Spanish',
    'sv' => 'Swedish',
    'tr' => 'Turkish'
  );


  $action = isset($_POST['oss_submit']) ? $_POST['oss_submit'] :NULL;
  if ($action == 'settings') {
    opensearchserver_admin_set_instance_settings();
  } else if ($action == 'query_settings') {
    opensearchserver_admin_set_query_settings();
  } else if ($action == 'index_settings') {
    opensearchserver_admin_set_index_settings();
  }else  if ($action == 'custom_field_settings') {
    opensearchserver_admin_set_custom_fields_settings();
  } if ($action == 'opensearchserver_reindex') {
    opensearchserver_admin_set_reindex();
  }
  ?>
<div class="wrap">
	<?php screen_icon( 'options-general' ); ?>
	<h2>
		<?php print 'OpenSearchServer'; ?>
	</h2>
	<div class="postbox-container" style="width: 100%">
		<div class="metabox-holder">
			<div class="meta-box-sortables">
				<div class="postbox" id="first">
					<div class="handlediv" title="Click to toggle">
						<br />
					</div>
					<h3 class="hndle">
						<span><?php print 'Instance settings'; ?> </span>
					</h3>
					<form id="oss_settings" name="oss_settings" method="post" action="">
						<div class="inside">
							<p>
								<label for="opensearchserver_location">OpenSearchServer instance
									location</label>:<br /> <input type="text" name="oss_serverurl"
									id="oss_serverurl" placeholder="http://localhost:8080"
									size="80" value="<?php print get_option('oss_serverurl');?>" />
								<br />
							</p>
							<p>
								<label for="opensearchserver_index_name">OpenSearchServer index
									name</label>:<br /> <input type="text" name="oss_indexname"
									id="oss_indexname" placeholder="opensearchserver_wordpress"
									size="50" value="<?php print get_option('oss_indexname');?>" />
								<br />
							</p>
							<p>
								<label for="opensearchserver_login">OpenSearchServer login name</label>:<br />
								<input type="text" name="oss_login" id="oss_login"
									placeholder="admin" size="30"
									value="<?php print get_option('oss_login');?>" /> <br />
							</p>


							<p>
								<label for="opensearchserver_key">OpenSearchServer API key</label>:<br />
								<input type="text" name="oss_key" id="oss_key"
									placeholder="9bc6aceeb43965b02b1d28a5201924e2" size="50"
									value="<?php print get_option('oss_key');?>" /><br />
							</p>
							<input type="hidden" name="oss_submit" value="settings" />
							<p>
								<input type="submit" name="opensearchserver_submit"
									value="Update Instance Settings" class="button-primary" />
							</p>
						</div>
					</form>
				</div>
				<div class="postbox" id="second">
					<div class="handlediv" title="Click to toggle">
						<br />
					</div>
					<h3 class="hndle">
						<span>Query settings </span>
					</h3>
					<div class="inside">
						<p>Enter the template query, or leave empty to use the default one</p>
						<form id="query_settings" name="query_settings" method="post"
							action="">
							<p>
								<label for="oss_query">OpenSearchServer query template</label>:<br />
								<textarea rows="10" cols="100" name="oss_query" wrap="off">
									<?php
									if (trim(get_option('oss_query'))) {
                                    print stripslashes(get_option('oss_query'));
                                  }else {
									print opensearchserver_default_query();
								  }?>
								</textarea>
							</p>
							<p>
								<label for="oss_facet">Facet field </label>:<br /><select
									name="oss_facet">
									<?php
									foreach ($fields as $key => $field) {
									  ?>
									<option value="<?php print $key;?>">
										<?php print $field;?>
									</option>
									<?php }?>
								</select>
								<label for="oss_facet_field">or write a fieldname : </label>
								<input type="text" name="oss_custom_facet"
									id="oss_custom_facet" placeholder="fieldname"
									size="10" /> 
								<input type="submit" name="opensearchserver_add"
									value="Add" class="button-secondary" /><br />
							</p>
							<?php $facets = get_option('oss_facet');
							if($facets) {?>
							<table class="widefat" style="width: 40% !important">
								<thead>
									<tr>
										<th>Facet field list</th>
										<th>Action</th>
									</tr>
								</thead>
								<tbody>
									<?php
									foreach($facets as $facet) {
									  ?>
									<?php if($fields[$facet])  { ?>
										<tr>
										<td><?php print $fields[$facet]; ?></td>
										<td><input type="hidden" name="oss_delete"
											value="<?php print $facet; ?>" /> <input type="submit"
											name="opensearchserver_delete" value="Delete"
											class="button-secondary" /></td>
									</tr>
									<?php }else {?>
										<tr>
										<td><?php print $facet; ?></td>
										<td><input type="hidden" name="oss_delete"
											value="<?php print $facet; ?>" /> <input type="submit"
											name="opensearchserver_delete" value="Delete"
											class="button-secondary" /></td>
									</tr>
									<?php }?>
									<?php }?>
								</tbody>
							</table>
							<?php }?>
							<p>
								<label for="oss_facet_behavior">Facet behavior </label>:<br /> <select
									name="oss_facet_behavior"><?php
									$facet_option = get_option('oss_facet_behavior');
									foreach ($facet_behaviour as $key => $field) {
								  $selected = '';
								  if($facet_behaviour[$facet_option] == $field) {
								    $selected = 'selected="selected"';
								  }
								  ?>
									<option value="<?php print $key;?>" <?php print $selected;?>>
										<?php print $field;?>
									</option>
									<?php }?>
								</select>
							</p>
							<p>
								<label for="oss_enable_multi_filter">Enable multiple filter</label>:
								<input type="checkbox" name="oss_multi_filter" value="1"
								<?php checked( 1 == get_option('oss_multi_filter')); ?> />
							</p>
							<p>
								<label for="oss_spell">SpellCheck field</label>:<br /> <select
									name="oss_spell"><?php
									$facet = get_option('oss_spell');
									foreach ($spellcheck_fields as $key => $field) {
									  $selected = '';
									  if($spellcheck_fields[$facet] == $field) {
									    $selected = 'selected="selected"';
									  }
									  ?>
									<option value="<?php print $key;?>" <?php print $selected;?>>
										<?php print $field;?>
									</option>
									<?php }?>
								</select>
							
							
							<p>
								<label for="oss_spell_algo">SpellCheck algorithm</label>:<br />
								<select name="oss_spell_algo"><?php
								$facet = get_option('oss_spell_algo');
								foreach ($spellcheck_algo as $key => $field) {
								  $selected = '';
								  if($spellcheck_algo[$facet] == $field) {
								    $selected = 'selected="selected"';
								  }
								  ?>
									<option value="<?php print $key;?>" <?php print $selected;?>>
										<?php print $field;?>
									</option>
									<?php }?>
								</select>
							</p>
							<p>
								<label for="oss_language">Default language</label>:<br /> <select
									name="oss_language"><?php
									$opt = get_option('oss_language');
									foreach ($languages as $key => $field) {
									  $selected = '';
									  if($opt == $key) {
									    $selected = 'selected="selected"';
									  }
									  ?>
									<option value="<?php print $key;?>" <?php print $selected;?>>
										<?php print $field;?>
									</option>
									<?php }?>
								</select>
							</p>
							<p>
								<label for="oss_phonetic">Enable phonetic</label>: <input
									type="checkbox" name="oss_phonetic" value="1"
									<?php checked( 1 == get_option('oss_phonetic')); ?> />
							</p>
							<p>
								Display:&nbsp;<input type="checkbox" name="oss_display_user"
									value="1"
									<?php checked( 1 == get_option('oss_display_user')); ?> />&nbsp;<label
									for="oss_display_user">user</label>&nbsp;&nbsp;<input
									type="checkbox" name="oss_display_category" value="1"
									<?php checked( 1 == get_option('oss_display_category')); ?> />&nbsp;<label
									for="oss_display_category">category</label>&nbsp;&nbsp;<input
									type="checkbox" name="oss_display_type" value="1"
									<?php checked( 1 == get_option('oss_display_type')); ?> />&nbsp;<label
									for="oss_display_type">type</label>
							</p>
							<p>
								<label for="opensearchserver_clean_query">
									Special characters to remove (Special characters are delimited by space)
								</label> :<br /> <input type="text" name="oss_clean_query"
									id="oss_clean_query" placeholder="# $ ! @"
									size="50" value="<?php print get_option('oss_clean_query');?>" />
								<br />
							</p>
							<p>
								<input type="hidden" name="oss_submit" value="query_settings" />
								<input type="submit" name="opensearchserver_submit"
									value="Update Query Settings" class="button-primary" />
							</p>
						</form>
					</div>
				</div>
				<div class="postbox" id="third">
					<div class="handlediv" title="Click to toggle">
						<br />
					</div>
					<h3 class="hndle">
						<span>Index settings </span>
					</h3>
					<div class="inside">
						<form id="index_settings" name="index_settings" method="post"
							action="">
							<p>
								<label for="oss_index_types">Check which type will be indexed:</label><br />
								<?php
								foreach (get_post_types() as $post_type) {
                                  $checkTypeName = 'oss_index_types_'.$post_type;
                                  ?>
								<input type="checkbox" name="<?php print $checkTypeName;?>"
									value="1" <?php checked( 1 == get_option($checkTypeName)); ?> />&nbsp;<label
									for="oss_index_types"><?php print $post_type;?> </label><br />
								<?php } ?>
							</p>
							<p>
								<input type="hidden" name="oss_submit" value="index_settings" />
								<input type="submit" name="opensearchserver_submit"
									value="Update Index Settings" class="button-primary" /> <input
									type="submit" name="opensearchserver_submit"
									value="(Re-)Create the index" class="button-secondary" />

							</p>
						</form>
					</div>
				</div>
				<div class="postbox" id="fourth">
					<div class="handlediv" title="Click to toggle">
						<br />
					</div>
					<h3 class="hndle">
						<span>Custom fields settings </span>
					</h3>
					<div class="inside">
						<form id="custom_field_settings" name="custom_field_settings"
							method="post" action="">
							<p>
								<label for="custom_fields_oss">Enter the fields from the Custom
									Field Template. Get useful information <a target="_blank"
									href="http://wordpress.org/extend/plugins/custom-field-template/">here</a>
								</label>:<br />
								<textarea rows="10" cols="100" name="oss_custom_field"
									wrap="off">
									<?php
									print stripslashes(get_option('oss_custom_field'));
									?>
								</textarea>
							</p>
							<p>
								<input type="hidden" name="oss_submit"
									value="custom_field_settings" /><input type="submit"
									name="opensearchserver_submit"
									value="Update Custom Fields Settings" class="button-primary" /><br />
							</p>
						</form>
					</div>

				</div>
				<div class="postbox" id="fifth">
					<div class="handlediv" title="Click to toggle">
						<br />
					</div>
					<h3 class="hndle">
						<span>Indexation </span>
					</h3>
					<div class="inside">
						<form id="reindex_settings" name="reindex_settings" method="post"
							action="">
							<p>
								<label for="oss_index_from">From index</label>:<br /> <input
									type="text" name="oss_index_from" id="oss_index_from" size="15"
									value="<?php print get_option('oss_index_from');?>" /> <br />
							</p>
							<p>
								<label for="oss_index_to">To index</label>:<br /> <input
									type="text" name="oss_index_to" id="oss_index_to" size="15"
									value="<?php print get_option('oss_index_to');?>" /> <br />
							</p>
							<p>
								<input type="hidden" name="oss_submit"
									value="opensearchserver_reindex" /> <input type="submit"
									name="opensearchserver_submit" value="Synchronize / Re-Index"
									class="button-primary" />
							</p>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
opensearchserver_add_toogle();
}
/*
 * Adding toogle script for collapsing
*/
function opensearchserver_add_toogle() {
  ?>
<script type="text/javascript">
	// <![CDATA[
	jQuery('.postbox h3').prepend('<a class="togbox">+</a> ');
	jQuery('.postbox div.handlediv').click( function() { jQuery(jQuery(this).parent().get(0)).toggleClass('closed'); } );
	jQuery('.postbox h3').click( function() { jQuery(jQuery(this).parent().get(0)).toggleClass('closed'); } );
	jQuery('.postbox.close-me').each(function(){
	jQuery(this).addClass("closed");
	});
	//-->
	</script>

<?php }?>