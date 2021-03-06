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
 * Return the "handler" to work with V2 API (new PHP client)
 */
function opensearchserver_get_handler_client() {
    global $oss_api_v2;
    if(empty($oss_api_v2)) {
        $key = get_option('oss_key');
        $login = get_option('oss_login');
        //new PHP client can not work with empty key or login, use some dumb values
        $key = (empty($key)) ? 'xx' : $key;
        $login = (empty($login)) ? 'xx' : $login;
        $oss_api_v2 = new OpenSearchServer\Handler(array('url' => get_option('oss_serverurl'), 'key' => $key, 'login' => $login));
    }
    return $oss_api_v2;
}

/**
 * Create or re-create the index if it already exists
 * @return boolean
 */
function opensearchserver_create_index() {
  $indexName = get_option('oss_indexname');
  $oss_api = opensearchserver_getapi_instance();
  $index_list = $oss_api->indexList();
  $index = in_array($indexName, $index_list);
  if ($index !== FALSE) {
    $oss_api->deleteIndex($indexName);
  }

  $oss_api->createIndex($indexName);
  opensearchserver_create_schema();
  opensearchserver_query_template();
  opensearchserver_spellcheck_query_template();
  $autocompletion = opensearchserver_getautocomplete_instance();
  $autocompletion_name = 'autocomplete';
  $autocompletion->createAutocompletion($autocompletion_name, 'autocomplete');
  return TRUE;
}


function get_list_of_search_templates() {
  $search_template_instance = opensearchserver_getsearchtemplate_instance();
  return $search_template_instance->get_search_template_list();
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

function opensearchserver_create_schema() {
  $schema = opensearchserver_getschema_instance();
  $schema_xml = $schema->getSchema();
  opensearchserver_setField($schema,$schema_xml,'id', NULL, 'yes', 'yes', 'no', 'no', 'yes');
  opensearchserver_setField($schema,$schema_xml,'type', NULL, 'yes', 'yes', 'no', 'no', 'no');
  opensearchserver_setField($schema,$schema_xml,'url',NULL,'no','yes','yes','no','no');
  opensearchserver_setField($schema,$schema_xml,'urlExact','StandardAnalyzer','no','yes','yes','no','no');
  opensearchserver_setField($schema,$schema_xml,'title','TextAnalyzer','yes','yes','positions_offsets','no','no');
  opensearchserver_setField($schema,$schema_xml,'titleExact','StandardAnalyzer','no','yes','no','no','no');
  opensearchserver_setField($schema,$schema_xml,'autocomplete','AutoCompletionAnalyzer','yes','yes','no','no','no');
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
  opensearchserver_setField($schema,$schema_xml,'tags','TextAnalyzer','yes','yes','no','yes','no');
  opensearchserver_setField($schema,$schema_xml,'tagsExact',NULL,'yes','yes','no','yes','no');
  
  opensearchserver_setField($schema,$schema_xml,'thumbnail_url',NULL,'no','yes','no','no','no');
  opensearchserver_setField($schema,$schema_xml,'thumbnail_width',NULL,'no','yes','no','no','no');
  opensearchserver_setField($schema,$schema_xml,'thumbnail_height',NULL,'no','yes','no','no','no');
  
  if (opensearchserver_is_wpml_usable()) {
    opensearchserver_setField($schema,$schema_xml,'language',NULL,'no','yes','no','no','no');
  }
  opensearchserver_setField($schema,$schema_xml,'year',NULL,'no','yes','no','no','no');
  opensearchserver_setField($schema,$schema_xml,'year_month',NULL,'no','yes','no','no','no');
  //Add taxonomies schema
  $taxonomies = get_taxonomies('','names');
  foreach ($taxonomies as $taxonomy ) {
  	$check_taxonomy_name = 'oss_taxonomy_'.$taxonomy;
  	if(get_option($check_taxonomy_name)==1) {
  		opensearchserver_setField($schema,$schema_xml,'taxonomy_'.$taxonomy,'TextAnalyzer','yes','yes','no','yes','no');
      opensearchserver_setField($schema,$schema_xml,'taxonomy_'.$taxonomy.'_notAnalyzed',NULL,'no','yes','yes','yes','no');
  	}
  }
  //Add custom fields schema
  $custom_field_labels = opensearchserver_get_selected_custom_fields();
  foreach($custom_field_labels as $key => $custom_field_label) {
      $schemaFieldName = opensearchserver_format_custom_field_name($custom_field_label);
      opensearchserver_setField($schema,$schema_xml,$schemaFieldName,'TextAnalyzer','yes','yes','no','yes','no');
      opensearchserver_setField($schema,$schema_xml,$schemaFieldName.'_notAnalyzed',NULL,'no','yes','yes','yes','no');
    }
  /*
   * action "oss_create_schema"
   */
  $oss_query = do_action('oss_create_schema', $schema, $schema_xml);
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
function opensearchserver_query_template() {
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
  $query_template->setReturnField('search','id');
  $query_template->setReturnField('search','user_url');
  $query_template->setReturnField('search','type');
  $query_template->setReturnField('search','user_name');
  $query_template->setReturnField('search','user_email');
  $query_template->setReturnField('search','tags');
  $query_template->setReturnField('search','timestamp');
  $query_template->setReturnField('search','thumbnail_url');
  $query_template->setReturnField('search','thumbnail_width');
  $query_template->setReturnField('search','thumbnail_height');
  $taxonomies = get_taxonomies('','names');
  foreach ($taxonomies as $taxonomy ) {
  	$check_taxonomy_name = 'oss_taxonomy_'.$taxonomy;
  	if(get_option($check_taxonomy_name)==1) {
  		$query_template->setReturnField('search','taxonomy_'.$taxonomy);
      $query_template->setReturnField('search','taxonomy_'.$taxonomy.'_notAnalyzed');
  	}
  }
  $custom_field_labels = opensearchserver_get_selected_custom_fields();
  foreach($custom_field_labels as $key => $custom_field_label) {  
      $schemaFieldName = opensearchserver_format_custom_field_name($custom_field_label);  
      $query_template->setReturnField('search', $schemaFieldName);
  }
}

/*
 * Function to create the spellcheck query
*/
function opensearchserver_spellcheck_query_template() {
  $spell_algo = get_option('oss_spell_algo');
  $field = get_option('oss_spell');
  if($field && $field !='none') {
    $spell_field = get_option('oss_spell').'Exact';
  	$spellcheck_query_template = opensearchserver_getsearchtemplate_instance();
    $spellcheck_query_template->createSpellCheckTemplate('spellcheck', '*:*', '1', $spell_field, '0.5', NULL, $spell_algo);
  }
}

/**
 * Check if the document list should be indexed
 * @param OSSIndexDocument $index
 * @param number $limit
 */
function opensearchserver_checkindex(OSSIndexDocument $index, $limit = 1, $idx = 0, $total = 0 ,$is_not_cron = TRUE) {
  global $wpdb;
  if ($index->count() < $limit) {
    return $index;
  }
  opensearchserver_start_indexing($index);
  $index = null;
  if($is_not_cron) {
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
      opensearchserver_display_messages(sprintf('Completed: %.2f %% (%d/%d)- Memory usage: %2f', $percent, $idx, $total, $mem));
    }
  }
  return new OSSIndexDocument();
}

function opensearchserver_get_number_to_index() {
    global $wpdb;
    $docs_count = $wpdb->get_var( opensearchserver_get_sql_query_count_posts_toindex() );
    return $docs_count;
}

/*
 * Function to reindex the website with cron.
*/
function opensearchserver_reindex_site_with_cron() {
  global $wpdb;
  $lang = get_option('oss_language', '');  
  
  update_option('oss_cron_reset', false);
  $numberOfPostsToIndexByJob = get_option('oss_cron_number_by_job', 200);
  
  // Get boundaries to use for getting the posts to index
  // Those limits are set by previous CRON jobs
  $from = (int) get_option('oss_cron_from', null);
  // Start by deleting all documents from the index if the OSS CRON job runs
  // for the first time.
  if (empty($from)) {
    opensearchserver_delete_document('*:*');
  }
  $limitSuffix = ' LIMIT '.$from.','.$numberOfPostsToIndexByJob;
  
  $sql_query = opensearchserver_get_sql_query_get_posts_reindex($limitSuffix);
  $posts = $wpdb->get_results($sql_query);
  $total_count = count($posts);
  $index = new OSSIndexDocument();
  for ($i = 0; $i < $total_count; $i++) {
    $post = get_post($posts[$i]->ID);
    opensearchserver_add_documents_to_index($index, $lang, $post);
    $index = opensearchserver_checkindex($index, 200, $i, $total_count, FALSE);
  }
  opensearchserver_checkindex($index, 1, $i, $total_count,FALSE);
  opensearchserver_optimize();
  opensearchserver_autocompletionBuild();
  
  wp_cache_flush();
  $wpdb->flush();
  
  // See if indexing is completed
  $to = $from + $numberOfPostsToIndexByJob;
  if($to > opensearchserver_get_number_to_index()) {
      opensearchserver_reset_cron();
  } else {
      // CRON may have been reset since the beginning of this batch. 
      // If it's the case, do not update the oss_cron_from option  and 
      // do not program a new batch
      if(get_option('oss_cron_reset')) {
          update_option('oss_cron_from', 0);
      } else {
          update_option('oss_cron_from', $to);
          //schedule a next CRON job
          wp_schedule_single_event(time() + 2, 'synchronize_with_cron');
      }
  }
  return 1;
}

/**
 * Return the SQL query used to get posts to re index
 */
function opensearchserver_get_sql_query_get_posts_reindex($suffix = '') {
    return opensearchserver_get_sql_query_toindex('ID', $suffix);
}

/**
 * Return the SQL query used to count posts to re index
 */
function opensearchserver_get_sql_query_count_posts_toindex($suffix = '') {
    return opensearchserver_get_sql_query_toindex('count(ID)', $suffix);
}

function opensearchserver_get_sql_query_toindex($prefix = 'ID', $suffix = '') {
  global $wpdb;
  $contentTypesToKeep = array();
  foreach (get_post_types() as $post_type) {
    if (get_option('oss_index_types_'.$post_type) == 1) {
        $contentTypesToKeep[] = $post_type;  
    }
  }
  $postStatus = array('publish');
    if(in_array('attachment', $contentTypesToKeep)) {
        $postStatus[] = 'inherit';
    }
  $sql_query = 'SELECT '.$prefix.' FROM '.$wpdb->posts.' WHERE post_status IN ("'.implode('","', $postStatus).'") AND post_type IN ("'.implode('","', $contentTypesToKeep).'") ORDER BY ID'.$suffix;
  return $sql_query;
}

function opensearchserver_reset_cron() {
    wp_clear_scheduled_hook( 'synchronize_with_cron' );
    update_option('oss_cron_from', 0);
    update_option('oss_cron_reset', true);
    update_option('oss_cron_running', false);
}

/*
 * Function to reindex the website.
*/
function opensearchserver_reindex_site($id, $type, $from = 0, $to = 0) {
  global $wpdb;
  $lang = get_option('oss_language', '');
  if($id) {
    $index = new OSSIndexDocument();
      
    //If current post has some attached media, process them too if needed
    //This is only useful here, for full re indexing attachments will be processed
    //directly in opensearchserver_add_documents_to_index because they will be results
    //from the SQL Query
    $deleteSuffix;
    if(is_content_type_allowed('attachment')) {
        $attachments = get_attached_media(null, $id);
        foreach($attachments as $attachmentId => $attachment) {
            $deleteSuffix .= ' id:attachment_'.$attachmentId;
            //index this attachment
            opensearchserver_add_documents_to_index($index, $lang, get_post($attachmentId));
        }
    }
   
    //process POST
    $delete='id:'.$type.'_'.$id . $deleteSuffix;
    opensearchserver_delete_document($delete);
    opensearchserver_add_documents_to_index($index, $lang, get_post($id));
    opensearchserver_checkindex($index);
  } else {
    $from = (int) $from;
    $to = (int) $to;
    if ($from == 0 && $to == 0) {
      opensearchserver_delete_document('*:*');
    }
    $limitSuffix = $to != 0 ? ' LIMIT '.$from.','.($to - $from) : '';
    
    $sql_query = opensearchserver_get_sql_query_get_posts_reindex($limitSuffix);
    $posts = $wpdb->get_results($sql_query);
    $total_count = count($posts);
    $index = new OSSIndexDocument();
    for ($i = 0; $i < $total_count; $i++) {
      $post = get_post($posts[$i]->ID);
      opensearchserver_add_documents_to_index($index, $lang, $post);
      $index = opensearchserver_checkindex($index, 200, $i, $total_count);
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


/**
 * Cache user objects in an array to avoid querying database for each post
 * when indexing content 
 */
$cacheUserObjects = array();
function opensearchserver_get_user_cache($author) {
    if(!isset($cacheUserObjects[$author])) {
        $cacheUserObjects[$author] = get_userdata($author);
    }
    return $cacheUserObjects[$author];
}

function opensearchserver_add_documents_to_index(OSSIndexDocument $index, $lang, $post) {
  /*
   * filter "oss_abort_index_document"
   */
  $abortIndexing = apply_filters('oss_abort_index_document', $document, $index, $lang, $post);
  if($abortIndexing === true) {
      return;
  }
  
  //Handling attached files
  $contentFromParsing = '';
  if(get_option('oss_parse_file', 0) && $post->post_type == 'attachment' ) {
      //get path to the file
      $path = get_attached_file($post->ID);
      if(!empty($path)) {
          $oss_api2 = opensearchserver_get_handler_client();
          $request = new OpenSearchServer\Parser\Parse\DetectType();
          $request->file($path);
          if(!empty($post->post_mime_type)) {
              //add Mime Type if known
              $request->type($post->post_mime_type);
          }
          //send the file to OpenSearchSerfver for automatic parsing
          $response = $oss_api2->submit($request);
          //parse available fields: looking for fields "content", "title"
          if(!empty($response->getJsonValues()->documents[0]->fields)) {
              $fieldsToKeep = array('content', 'title');
              foreach($response->getJsonValues()->documents[0]->fields as $field) {
                  if(!empty($field->values)) {
                      if(in_array($field->fieldName, $fieldsToKeep) ) {
                          $contentFromParsing .= implode(' ', $field->values);
                      }
                  }
              }
          }          
      }
  }
    
  $user = opensearchserver_get_user_cache($post->post_author);
  $content = $post->post_content;
  if(!empty($contentFromParsing)) {
      $content .= ' ' . $contentFromParsing;
  }
  $content = apply_filters('the_content', $content);
  $content = str_replace(']]>', ']]&gt;', $content);
  $content = opensearchserver_encode(strip_tags($content));
  $content = opensearchserver_stripInvalidXml($content);
  $document = $index->newDocument($lang);

  /*
   * action "oss_index_document_begin"
   */
  do_action('oss_index_document_begin', $document, $index, $lang, $post);
  
  $document->newField('id', $post->post_type.'_'.$post->ID);
  $document->newField('type', strip_tags($post->post_type));
  $title = opensearchserver_stripInvalidXml(strip_tags($post->post_title));
  $document->newField('title', $title);
  /*
   * filter "oss_autocomplete_value"
   */
  $document->newField('autocomplete', apply_filters('oss_autocomplete_value', $title, $post));
  $document->newField('titleExact', $title);
  $document->newField('titlePhonetic', $title);
  $document->newField('content', $content);
  $document->newField('contentExact', $content);
  $document->newField('contentPhonetic', $content);
  $document->newField('url', get_permalink($post->ID));
  $document->newField('urlExact', get_permalink($post->ID));
  $document->newField('timestamp', $post->post_date_gmt);
  $document->newField('year',  substr($post->post_date_gmt, 0, 4));
  $document->newField('year_month', substr($post->post_date_gmt, 0, 7));
  $document->newField('user_name', $user->user_nicename);
  $document->newField('user_email', $user->user_email);
  $document->newField('user_email', $user->user_url);
  
  //Handling post's thumbnail
  $post_thumbnail_id = get_post_thumbnail_id( $post->ID );
  if(!empty($post_thumbnail_id)) {
  	$image = wp_get_attachment_image_src($post_thumbnail_id, 'large');
  	if(!empty($image)) {
  		list($src, $width, $height) = $image;
  	  	$document->newField('thumbnail_url', $src);
  		$document->newField('thumbnail_width', $width);
  		$document->newField('thumbnail_height', $height);
  	}
  }
  
  //Handling tranlsations
  if (opensearchserver_is_wpml_usable()) {
    $post_language_information = wpml_get_language_information($post->ID);
    //split locale on "_" to save only language info
    if(!empty($post_language_information['locale'])) {
        $chuncks = explode('_', $post_language_information['locale']);
        if(!empty($chuncks[0])) {
  	        $document->newField('language', $chuncks[0]);
        }
    }
  }
  
  //Handling taxonomies
  $taxonomies=get_taxonomies('','names'); 
    foreach ($taxonomies as $taxonomy ) {
      $check_taxonomy_name = 'oss_taxonomy_'.$taxonomy;
      $taxonomy_data = array();
       if(get_option($check_taxonomy_name)==1) {
          $terms = get_the_terms( $post->ID, $taxonomy);
          if ( $terms && ! is_wp_error( $terms ) ) {
                foreach ( $terms as $term ) {
                  $taxonomy_data[] = $term->name;
                }
          }
           $document->newField('taxonomy_'.$taxonomy, $taxonomy_data);
           $document->newField('taxonomy_'.$taxonomy.'_notAnalyzed', $taxonomy_data);
       }
    }
  
  //Handling tags
  $tags_data = NULL;
  $tags = get_the_tags($post->ID);
    if ($tags != NULL) {
      $tags_data = array();
      foreach($tags as $tag) {
	  	$tags_data[] = $tag->name;
      }
      $document->newField('tags', $tags_data);
      $document->newField('tagsExact', $tags_data);
      $tags_data = NULL;
	  $tags = NULL;
    }

  // Handling custom fields
  $custom_field_labels = opensearchserver_get_selected_custom_fields();
  foreach($custom_field_labels as $key => $field) {
        $schemaFieldName = opensearchserver_format_custom_field_name($field);
        $custom_content = '';
        $custom_values = get_post_custom_values($field, $post->ID);
        if(is_array($custom_values)) {
          foreach ($custom_values as $values) {
            $custom_content .= $values.' ';
          }
        } else {
          $custom_content = $custom_values;
        }
        $document->newField($schemaFieldName, $custom_content);
        $document->newField($schemaFieldName.'_notAnalyzed', $custom_content);
  }

  // Build all content field
  $all_content = opensearchserver_stripInvalidXml(strip_tags($post->post_title)). ' '.$content;
  
  if ($custom_clean_all) {
    $all_content .= ' ' .$custom_clean_all;
    $custom_clean_all = null;
  }
  $document->newField("allContent", strip_tags($all_content));
  
  /*
   * action "oss_index_document"
   */
  do_action('oss_index_document', $document, $index, $lang, $post);
  
  //free memory
  $user = null;
  $content = null;
  $all_content = null;
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
  $fields = array(
  		'none'=>'Select',
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
  		'year' => 'Year',
  		'year_month' => 'Month'
  	);
  if (opensearchserver_is_wpml_usable()) {
  	$fields['language'] = 'Language';
  }
  return $fields;  
}

function opensearchserver_is_wpml_usable() {
	return (opensearchserver_is_plugin_active('sitepress-multilingual-cms/sitepress.php') && get_option('oss_enable_translation_wpml'));
}

function opensearchserver_admin_set_instance_settings() {
  $oss_url = isset($_POST['oss_serverurl']) ? $_POST['oss_serverurl'] :NULL;
  $oss_indexname = isset($_POST['oss_indexname']) ? $_POST['oss_indexname'] :NULL;
  $oss_login = isset($_POST['oss_login']) ? $_POST['oss_login'] :NULL;
  $oss_key = isset($_POST['oss_key']) ? $_POST['oss_key'] :NULL;
  //delete trailing slash if any
  if(substr($oss_url, strlen($oss_url) - 1, 1) == '/') {
  	$oss_url = substr($oss_url, 0, strlen($oss_url) - 1);
  }
  update_option('oss_serverurl', $oss_url);
  update_option('oss_indexname', $oss_indexname);
  update_option('oss_login', $oss_login);
  update_option('oss_key', $oss_key);
  opensearchserver_display_messages('OpenSearchServer Instance Settings have been updated');
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

function opensearchserver_update_facet_label_settings($facet_field, $facet_label) {
	if($facet_field != NULL) {
    	$facets_labels = get_option('oss_facets_labels', array()); 
      	$facets_labels[$facet_field] = $facet_label;
    	update_option('oss_facets_labels', $facets_labels);
  	}
}

function opensearchserver_update_facet_slug_settings($facet_field, $facet_slug) {
	if($facet_field != NULL) {
    	$facets_slugs = get_option('oss_facets_slugs', array()); 
      	$facets_slugs[$facet_field] = $facet_slug;
    	update_option('oss_facets_slugs', $facets_slugs);
  	}
}

/**
 * Save custom values for one facet
 * @param $facet_field string name of the facet
 * @param $facet_values string custom values, separated by a pipe, one replacement by line 
 */
function opensearchserver_update_facet_values_settings($facet_field, $facet_values) {
	if($facet_field != NULL) {
    	$facets_values = get_option('oss_facets_values', array()); 
    	$facets_values_details = array();
    	//explode on new line
    	$lines = explode("\n", $facet_values);
    	if(!empty($lines)) {
    		foreach($lines as $line) {
    			//explode on pipe to get real value and replacement value
    			$details = explode("|", $line);
    			if(count($details) == 2) {
    				$facets_values_details[$details[0]] = $details[1];
    			}
    		}
    	}
    	$facets_values[$facet_field] = $facets_values_details;
    	update_option('oss_facets_values', $facets_values);
  	}
}

/**
 * Returns an array of custom values for one facets. Each array value is: <original value>|<replacement value>
 * @param unknown_type $facet_field
 */
function opensearchserver_get_facet_values_string($facet_field) {
	$facets_values = get_option('oss_facets_values', array()); 
    $values = array();
	if(!empty($facets_values[$facet_field])) {
    	foreach($facets_values[$facet_field] as $original => $replacement) {
    		$values[] = $original.'|'.stripslashes($replacement);
    	}
    }
    return $values;
}

function opensearchserver_admin_delete_facet() {
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
	}
}
function opensearchserver_admin_set_query_settings() {
	$oss_facet = isset($_POST['oss_facet']) ? $_POST['oss_facet'] : NULL;
    $oss_custom_facet = isset($_POST['oss_custom_facet']) ? $_POST['oss_custom_facet'] : NULL;
    if((!empty($oss_facet) && $oss_facet!='none') || !empty($oss_custom_facet)) {
	    if($oss_facet != 'none') {
	      	opensearchserver_update_facet_settings($oss_facet);
	    } elseif(!empty($oss_custom_facet)){
	    	opensearchserver_update_facet_settings($oss_custom_facet);
	    }
    	opensearchserver_display_messages('Field added.');
	    return;
    }
	
	$oss_query = isset($_POST['oss_query']) ? $_POST['oss_query'] : NULL;
    if (isset($oss_query)) {
      $oss_query = trim($oss_query);
      if (strlen($oss_query) == 0) {
        $oss_query = opensearchserver_default_query();
      }
    }
    update_option('oss_query', $oss_query);
    
    $oss_spell = isset($_POST['oss_spell']) ? $_POST['oss_spell'] : NULL;
    $oss_spell_algo = isset($_POST['oss_spell_algo']) ? $_POST['oss_spell_algo'] : NULL;

    //custom facets slugs
    $oss_facet_slugs = isset($_POST['oss_facet_edit_slugs']) ? $_POST['oss_facet_edit_slugs'] : array();
    foreach($oss_facet_slugs as $field => $slug) {
    	if(!empty($slug)) {
      		opensearchserver_update_facet_slug_settings($field, $slug);
    	}
    }
    
    //custom facets labels
    $oss_facet_labels = isset($_POST['oss_facet_edit_labels']) ? $_POST['oss_facet_edit_labels'] : array();
    foreach($oss_facet_labels as $field => $custom_label) {
    	if(!empty($custom_label)) {
      		opensearchserver_update_facet_label_settings($field, $custom_label);
    	}
    }

    //custom values
    $oss_facet_values = isset($_POST['oss_facet_edit_values']) ? $_POST['oss_facet_edit_values'] : array();
    foreach($oss_facet_values as $field => $values) {
    	opensearchserver_update_facet_values_settings($field, $values);
    }

    //option "search form" for each facet
    if(!empty($_POST['oss_facets_option_searchform'])) {
    	update_option('oss_facets_option_searchform', array_keys($_POST['oss_facets_option_searchform']));
    } else {
        update_option('oss_facets_option_searchform', null);
    }
    
    //option "hierarchical" for each facet
    if(!empty($_POST['oss_facets_option_hierarchical'])) {
    	update_option('oss_facets_option_hierarchical', array_keys($_POST['oss_facets_option_hierarchical']));
    } else {
        update_option('oss_facets_option_hierarchical', null);
    }
    
    
    //taxonomy "hierarchical" for each facet
    $oss_facets_option_hierarchical_taxonomy = isset($_POST['oss_facets_option_hierarchical_taxonomy']) ? $_POST['oss_facets_option_hierarchical_taxonomy'] : array();
	update_option('oss_facets_option_hierarchical_taxonomy', $oss_facets_option_hierarchical_taxonomy);
    
    
    //option "all" for each facet
    if(!empty($_POST['oss_facets_option_all'])) {
    	update_option('oss_facets_option_all', array_keys($_POST['oss_facets_option_all']));
    } else {
        update_option('oss_facets_option_all', null);
    }
    
    //option "facet exclusive" for each facet
    if(!empty($_POST['oss_facets_exclusive'])) {
    	update_option('oss_facets_exclusive', array_keys($_POST['oss_facets_exclusive']));
    } else {
        update_option('oss_facets_exclusive', null);
    }
    
    //delete facets
	$oss_facet_delete = isset($_POST['oss_facet_delete']) ? $_POST['oss_facet_delete'] : array();
    $facets = get_option('oss_facet', array());
    foreach($facets as $key => $facet) {
    	if(in_array(trim($facet), $oss_facet_delete)) {
			unset($facets[$key]);
		}
    }
	if(empty($facets)) {
		$facets = array();
	}
	update_option('oss_facet', $facets);
    
    $oss_write_query = isset($_POST['oss_write_query']) ? $_POST['oss_write_query'] : 1;
    update_option('oss_query_behaviour', $oss_write_query);
    $oss_query_template = isset($_POST['oss_query_template']) ? $_POST['oss_query_template'] : NULL;
    update_option('oss_query_template', $oss_query_template);
    $oss_multi_filter = isset($_POST['oss_multi_filter']) ? $_POST['oss_multi_filter'] : NULL;
    update_option('oss_multi_filter', $oss_multi_filter);
    $oss_facet_display_count = isset($_POST['oss_facet_display_count']) ? $_POST['oss_facet_display_count'] : NULL;
    update_option('oss_facet_display_count', $oss_facet_display_count);
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
    $oss_display_date = isset($_POST['oss_display_date']) ? $_POST['oss_display_date'] : NULL;
    update_option('oss_display_date', $oss_display_date);
    $oss_advanced_facets = isset($_POST['oss_advanced_facets']) ? $_POST['oss_advanced_facets'] : NULL;
    update_option('oss_advanced_facets', $oss_advanced_facets);
	  $oss_facet_max_display = isset($_POST['oss_facet_max_display']) ? $_POST['oss_facet_max_display'] : NULL;
  	update_option('oss_facet_max_display', $oss_facet_max_display);
    $oss_sort_timestamp = isset($_POST['oss_sort_timestamp']) ? $_POST['oss_sort_timestamp'] : NULL;
    update_option('oss_sort_timestamp', $oss_sort_timestamp);
    $oss_clean_query = isset($_POST['oss_clean_query']) ? $_POST['oss_clean_query'] : NULL;
  	update_option('oss_clean_query', $oss_clean_query);
  	$oss_clean_query_enable = isset($_POST['oss_clean_query_enable']) ? $_POST['oss_clean_query_enable'] : NULL;
  	update_option('oss_clean_query_enable', $oss_clean_query_enable);
  	$oss_log_enable = isset($_POST['oss_log_enable']) ? $_POST['oss_log_enable'] : NULL;
  	update_option('oss_log_enable', $oss_log_enable);
  	$oss_log_ip = isset($_POST['oss_log_ip']) ? $_POST['oss_log_ip'] : NULL;
  	update_option('oss_log_ip', $oss_log_ip);
    $oss_taxonomy_display = isset($_POST['oss_taxonomy_display']) ? $_POST['oss_taxonomy_display'] : NULL;
    update_option('oss_taxonomy_display', $oss_taxonomy_display);
    $oss_autocomplete_number = isset($_POST['oss_autocomplete_number']) ? $_POST['oss_autocomplete_number'] : NULL;
    update_option('oss_autocomplete_number', $oss_autocomplete_number);
    $oss_filter_language_wpml = isset($_POST['oss_filter_language_wpml']) ? $_POST['oss_filter_language_wpml'] : NULL;
    update_option('oss_filter_language_wpml', $oss_filter_language_wpml);
    $oss_filter_language_field_wpml = isset($_POST['oss_filter_language_field_wpml']) ? $_POST['oss_filter_language_field_wpml'] : NULL;
    update_option('oss_filter_language_field_wpml', $oss_filter_language_field_wpml);
    //some options needs to post changes to OSS
    if(!opensearchserver_is_query_settings_not_automatic() || (isset($_POST['oss_query_settings_post_to_oss']) && $_POST['oss_query_settings_post_to_oss'] == 1)) {
      opensearchserver_query_template();
    }
    opensearchserver_display_messages('OpenSearchServer Query Settings have been updated.');
}

function opensearchserver_admin_set_advanced_settings() {
    $oss_advanced_search_only = isset($_POST['oss_advanced_search_only']) ? $_POST['oss_advanced_search_only'] : NULL;
    update_option('oss_advanced_search_only', $oss_advanced_search_only);
    $oss_advanced_query_settings_not_automatic = isset($_POST['oss_advanced_query_settings_not_automatic']) ? $_POST['oss_advanced_query_settings_not_automatic'] : NULL;
    update_option('oss_advanced_query_settings_not_automatic', $oss_advanced_query_settings_not_automatic);
    $oss_advanced_query_template_choice = isset($_POST['oss_advanced_query_template_choice']) ? $_POST['oss_advanced_query_template_choice'] : NULL;
    update_option('oss_advanced_query_template_choice', $oss_advanced_query_template_choice);
    //need to reset "oss_query_template" if option query_template_choice is disabled
    if(empty($oss_advanced_query_template_choice)) {
        update_option('oss_query_behaviour', 1);
    }
    opensearchserver_display_messages('OpenSearchServer advanced settings have been updated.');
}

function opensearchserver_admin_set_index_settings() {
  $post_oss_submit = $_POST['opensearchserver_submit'];
  if ($post_oss_submit == 'Update Index Settings') {
    foreach (get_post_types() as $post_type) {
      $post_form_type = (int)$_POST['oss_index_types_'.$post_type];
      update_option('oss_index_types_'.$post_type, $post_form_type);
    }
    $taxonomies=get_taxonomies('','names');
    foreach ($taxonomies as $taxonomy ) {
    	$check_taxonomy_name = (int)$_POST['oss_taxonomy_'.$taxonomy];
    	update_option('oss_taxonomy_'.$taxonomy, $check_taxonomy_name);
    }
    
    $customFields = ($_POST['oss_custom_fields']) ? $_POST['oss_custom_fields'] : array();
    update_option('oss_custom_fields', array_keys($customFields));
    
    $oss_parse_file = isset($_POST['oss_parse_file']) ? $_POST['oss_parse_file'] : NULL;
    update_option('oss_parse_file', (int)$oss_parse_file);
    
    $oss_enable_autoindexation = isset($_POST['oss_enable_autoindexation']) ? $_POST['oss_enable_autoindexation'] : NULL;
    update_option('oss_enable_autoindexation', (int)$oss_enable_autoindexation);
    opensearchserver_display_messages('OpenSearchServer Index Settings have been updated.');
  } else {
    $is_index_created = opensearchserver_create_index();
    opensearchserver_display_messages('Index '.get_option('oss_indexname').' Created successfully');
  }
  
  $oss_enable_translation_wpml = isset($_POST['oss_enable_translation_wpml']) ? $_POST['oss_enable_translation_wpml'] : NULL;
  update_option('oss_enable_translation_wpml', $oss_enable_translation_wpml);
}


/**
 * Format name of a custom field in machine readable string
 */
function opensearchserver_format_custom_field_name($name) {
  return strtolower('custom_field_'.str_replace(' ', '_', $name));
}

/**
 * Return the selected custom fields to index
 */
function opensearchserver_get_selected_custom_fields() {
  return get_option('oss_custom_fields', array());
}

/** 
 * Get all available custom fields for the whole website
 */
function opensearchserver_get_all_custom_fields() {
  global $wpdb;
  $meta_keys = array();
  $custom_fields = array();
  $meta_keys = $wpdb->get_col("SELECT meta_key FROM $wpdb->postmeta GROUP BY meta_key HAVING meta_key NOT LIKE '\_%' ORDER BY meta_key");
  if ($meta_keys){
    natcasesort($meta_keys);
  }
  foreach ($meta_keys as $key => $value) {
    $custom_fields[$value] = isset($custom_fields[$value]) ? $custom_fields[$value] + 1 : 1;
  }
  return $custom_fields;
}


function opensearchserver_is_search_only() {
	$search_only = get_option('oss_advanced_search_only', null);
	return ($search_only == 1);
}

function opensearchserver_is_query_settings_not_automatic() {
	$oss_advanced_query_settings_not_automatic = get_option('oss_advanced_query_settings_not_automatic', null);
	return ($oss_advanced_query_settings_not_automatic == 1);
}
function opensearchserver_is_query_template_choice() {
	$oss_advanced_query_template_choice = get_option('oss_advanced_query_template_choice', null);
	return ($oss_advanced_query_template_choice == 1);
}


function opensearchserver_admin_set_reindex() {
    //reset CRON    
    opensearchserver_reset_cron();
    
    $oss_index_from = isset($_POST['oss_index_from']) ? $_POST['oss_index_from'] : NULL;
    $oss_index_to = isset($_POST['oss_index_to']) ? $_POST['oss_index_to'] : NULL;
    update_option('oss_index_from', $oss_index_from);
    update_option('oss_index_to', $oss_index_to);
    $index_success = opensearchserver_reindex_site(NULL,NULL, $oss_index_from, $oss_index_to);
    $suffix = ($oss_index_from !== null && $oss_index_from != '' && $oss_index_to !== null && $oss_index_to != '') ? '(indexed documents from #'.$oss_index_from.' to #'.$oss_index_to.')' : '';
    opensearchserver_display_messages('Re indexing has been finished successfully '. $suffix .'.');
}

function opensearchserver_admin_set_reindex_cron() {
    opensearchserver_reset_cron();
    wp_schedule_single_event(time() + 2, 'synchronize_with_cron');
    update_option('oss_cron_running', true);
    $oss_cron_number_by_job = isset($_POST['oss_cron_number_by_job']) ? $_POST['oss_cron_number_by_job'] : NULL;
    update_option('oss_cron_number_by_job', $oss_cron_number_by_job);
    opensearchserver_display_messages('Re indexing has been successfully scheduled with CRON.');
}

/*
 * Re-index using cron
*/
function opensearchserver_synchronize_with_cron() {
    ini_set('memory_limit', '2048M');
    set_time_limit(0);
    opensearchserver_reindex_site_with_cron();
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
  } elseif ($action == 'query_settings') {
    opensearchserver_admin_set_query_settings();
  } elseif ($action == 'index_settings') {
    opensearchserver_admin_set_index_settings();
  } elseif ($action == 'opensearchserver_reindex') {
    opensearchserver_admin_set_reindex();
  } elseif ($action == 'opensearchserver_reindex_cron') {
    opensearchserver_admin_set_reindex_cron();
  }elseif ($action == 'opensearchserver_advanced_settings') {
  	opensearchserver_admin_set_advanced_settings();
  }
  
  
    $taxonomies_list = get_taxonomies('','names');
  ?>
<div class="wrap">
	<h2>OpenSearchServer</h2>
	<div class="postbox-container" id="opensearchserver_admin" style="width: 100%">
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
									placeholder="admin" size="50"
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
                    
                        
                        <form id="query_settings" name="query_settings" method="post"
							action="">
                        
                            <?php if(opensearchserver_is_query_template_choice()):?>
                            <fieldset><legend>Query Behaviour</legend>
                            
                            <p>
                                  <input type="radio" id="oss_write_query" 
                                        value="1" name="oss_write_query"
                                        <?php 
                                        $oss_write_query = isset($_POST['oss_write_query']) ? $_POST['oss_write_query'] : 1;
                                        checked( 1 == $oss_write_query); ?>  />
                                    <label for="oss_write_query">Write query pattern</label>&nbsp;
                                    <input type="radio" id="oss_existing_query_template" 
                                        value="2" name="oss_write_query"
                                        <?php checked( 2 == get_option('oss_query_behaviour')); ?>  />
                                    <label for="oss_existing_query_template">Use an existing search template</label>
                              </p>
                              
                            </fieldset>
                            <?php endif; ?>
                            
                            <fieldset><legend>Query template</legend>
    						<div class="oss_visibleByQueryPattern" id="oss-query-write-template">
                                <p>Enter your template query in this box, or leave it empty to use the default one.</p>
    							<p>	<label for="oss_query">OpenSearchServer query template</label>:<br />
    								<textarea rows="7" cols="80" name="oss_query" wrap="off"><?php
    									if (trim(get_option('oss_query'))) {
                                        print stripslashes(get_option('oss_query'));
                                      }else {
    									print opensearchserver_default_query();
    								  }?></textarea>
    							</p>
                                <div class="help">
                                    <p>The Taxonomies and Custom Fields selected in the "Index Settings" section below automatically create fields in the schema. They can thus be used in your template query.</p>
                                    <ul>
                                        <li>OSS fields for Taxonomies are generated using this format: <code>taxonomy_&lt;taxonomy_name&gt;</code>, for example <code>taxonomy_mytags</code></li>
                                        <li>OSS fields for Custom Fields are generated using this format: <code>custom_field_&lt;custom_field_name&gt;</code>, for example <code>custom_field_favorite_fruits</code></li> 
                                    </ul>
                                    <p>To allow search into a new field, simply add it to the query, for example <code>OR custom_field_favorite_fruits:($$)^5</code>. <code>^5</code> which also gives a weight of 5 to this field.
                                </div>
                          </div>
                          <div class="oss_visibleByQueryTemplate" id="oss-query-select-template">
                              <p>
                                <strong>Select an existing search template</strong>
                                <select name="oss_query_template" id="oss_query_template">
                                <?php
                                $templates = get_list_of_search_templates();
                                foreach($templates as $template) :
                                  $checked = (get_option('oss_query_template') == $template->name) ? 'selected="selected"' : '';
                                  echo '<option value="'. $template->name.'" '. $checked . '>'. $template->name .' (Type '.$template->type.')</option>';
                                endforeach;
                                ?>
                                
                                </select>
                              </p>
                          </div>
                            </fieldset>
                            <fieldset><legend>Facets</legend>
                                <strong>Add a facet:</strong> 
								<label for="oss_facet">facet field </label>: 
                                <select name="oss_facet" id="oss_facet">
									<?php
									$facets = get_option('oss_facet');
									foreach ($fields as $key => $field):
										if(!in_array($key, $facets)):
									  ?>
									   <option value="<?php print $key;?>">
											<?php print $field;?>
									   </option>
									<?php 
										endif;
									endforeach;
									?>
								</select>
								<label for="oss_custom_facet">or write the name of an existing field of the schema: </label>
								<input type="text" name="oss_custom_facet" id="oss_custom_facet" placeholder="fieldname" size="20" /> 
								<input type="submit" name="opensearchserver_add" value="Add" class="button-secondary" /><br />
                                
                                <div class="help">
                                    <p><strong>How do I create facets on Taxonomies or Custom Fields?</strong>
                                    <p>Those Taxonomies and Custom Fields selected in the "Index Settings" section below automatically create 
                                        fields in the schema. These can then be used as facets. The field name formats are 
                                        <code>taxonomy_&lt;taxonomy_name&gt;_notAnalyzed</code> and 
                                        <code>custom_field_&lt;custom_field_name&gt;_notAnalyzed</code>. For instance :  
                                        <code>custom_field_favorite_fruits_notAnalyzed</code>.
                                    </p>
                                    <p>If you want to create a facet on a hierarchical Taxonomy you will have to add it first, using its field name, then
                                    choose the corresponding taxonomy in the "hierarchical" select list.</p>
                                </div>
                                <br/>
							<?php 
								$facets_labels = get_option('oss_facets_labels');
								$facets_slugs=  get_option('oss_facets_slugs');
								$facets_option_searchform = get_option('oss_facets_option_searchform');
								if(!is_array($facets_option_searchform)) {
									$facets_option_searchform = array();
								}
								$facets_option_hierarchical = get_option('oss_facets_option_hierarchical');
								if(!is_array($facets_option_hierarchical)) {
									$facets_option_hierarchical = array();
								}
								$facets_option_hierarchical_taxonomy = get_option('oss_facets_option_hierarchical_taxonomy');
								if(!is_array($facets_option_hierarchical_taxonomy)) {
									$facets_option_hierarchical_taxonomy = array();
								}
								$facets_option_all = get_option('oss_facets_option_all');
								if(!is_array($facets_option_all)) {
									$facets_option_all = array();
								}
								$facets_exclusive = get_option('oss_facets_exclusive');
								if(!is_array($facets_exclusive)) {
									$facets_exclusive = array();
								}
							if($facets):?>
							<table class="widefat" style="width: 100% !important; min-width:600px;">
								<thead>
                                    <tr>
                                        <th colspan="<?php if(1 == get_option(oss_advanced_facets)) echo '8'; else echo '4'; ?>">
                                            <span class="help">
	                                            <h4>Help on facets management:</h4>
	                                            <ul>
	                                                <li><strong>URL slug: </strong>Name of the parameter to use in the URL for this facet. If left empty, the name of the field will be used.</li>
	                                                <li><strong>Custom label: </strong>You can enter a custom name for this facet that will be displayed on the results page.</li>
	                                                <li><strong>Custom values: </strong>Write one replacement by line, with this format: &lt;original value&gt;|&lt;value to display&gt;. 
	                                            For example "2014-02|February 2014" would replace "2014-02" with "February 2014" when displaying and "post|Blog post" would replace "post" with "Blog post".</li>
                                                    <?php if(1 == get_option(oss_advanced_facets)):?>
                                                        <li><strong>Search form</strong>: displays a small search form on top of the facet. This form can be used to filter the displayed values.</li>
                                                        <li><strong>Hierarchical</strong>: indicates a hierarchical facet. If checked, you will have to choose which Taxonomy should be used to get hierarchical values.</li>                                                        
                                                        <li><strong>Link "All"</strong>: adds an "All" link which resets the facet when clicked.</li>
	                                                    <li><strong>Exclusive: </strong>only one value can be selected by the user for this facet.</li>
                                                   <?php endif;?>
	                                            </ul>
                                            </span>
                                        </th>
                                    </tr>
                                    
                                    <tr>
                                        <th width="15%;">Facet field list</th>
                                        <th width="20%;">URL slug</th>
                                        <th width="40%;">Custom label and values</th>
                                        <?php if(1 == get_option(oss_advanced_facets)):?>
                                            <th>Search form</th>
                                            <th width="40%">Hierarchical</th>
                                            <th>Link "All"</th>
                                            <th>Exclusive</th>
                                        <?php endif;?>
                                        <th class="warning" width="10%">Delete facet</th>
                                    </tr>
								</thead>
								<tbody>
									<?php
									foreach($facets as $facet) :
										if (!empty($facet)):
									  ?>
										<tr>
										<td><?php  if($fields[$facet]) { print $fields[$facet]; } else { print $facet; } ?></td>
										<td>
	                                        <input type="text" name="oss_facet_edit_slugs[<?php echo $facet?>]" placeholder="URL slug" value="<?php if(!empty($facets_slugs[$facet])) { echo $facets_slugs[$facet]; }?>"  style="min-width:80px;"/>
                                        </td>
                                        <td>
                                            <input type="text" name="oss_facet_edit_labels[<?php echo $facet?>]" placeholder="Custom label" value="<?php if(!empty($facets_labels[$facet])) { echo $facets_labels[$facet]; }?>"  style="min-width:140px;"/>
                                            <?php if(1 == get_option(oss_advanced_facets)) echo '<br/>'; ?><em>&nbsp;&nbsp;<a href="#" onclick="jQuery('#oss_facet_edit_values_wrapper_<?php echo $facet?>').fadeIn(); jQuery(this).toggle(); return false;">Edit custom values</a></em>
                                            <div style="display:none;" id="oss_facet_edit_values_wrapper_<?php echo $facet?>">
                                                <label for="oss_facet_edit_values[<?php echo $facet?>]">Custom values:</label><br/>
                                                <textarea name="oss_facet_edit_values[<?php echo $facet?>]" id="oss_facet_edit_values[<?php echo $facet?>]" cols="28" rows="5"><?php print implode("\n", opensearchserver_get_facet_values_string($facet)); ?></textarea> 
                                            </div>
                                        </td>
                                        
                                        <?php if(1 == get_option(oss_advanced_facets)):?>
                                            <td>
                                                <input type="checkbox" value="1" name="oss_facets_option_searchform[<?php echo $facet?>]" id="oss_facets_option_searchform[<?php echo $facet?>]" <?php if(in_array($facet, $facets_option_searchform)) echo 'checked="checked"'; ?>/>
                                            </td>
                                            <td>
                                                <input type="checkbox" value="1" name="oss_facets_option_hierarchical[<?php echo $facet?>]" 
                                                        id="oss_facets_option_hierarchical[<?php echo $facet?>]" 
                                                        <?php if(in_array($facet, $facets_option_hierarchical)) echo 'checked="checked"'; ?>
                                                         onclick="jQuery('#oss_facets_option_hierarchical_taxonomy_<?php echo $facet?>').toggle(jQuery(this).prop('checked'));"
                                                        />
                                                <select style="display:<?php echo (in_array($facet, $facets_option_hierarchical)) ? 'block' : 'none'; ?>;" id="oss_facets_option_hierarchical_taxonomy_<?php echo $facet?>" name="oss_facets_option_hierarchical_taxonomy[<?php echo $facet?>]">
    									          <?php
                                                    foreach($taxonomies_list as $id => $taxonomy) {
                                                        $checked = ($id == $facets_option_hierarchical_taxonomy[$facet]) ? 'selected="selected"' : '';
                                                        echo '<option value="'.$id.'" '.$checked.' >'.$taxonomy.'</option>';
                                                    }
                                                  ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="checkbox" value="1" name="oss_facets_option_all[<?php echo $facet?>]" id="oss_facets_option_all[<?php echo $facet?>]" <?php if(in_array($facet, $facets_option_all)) echo 'checked="checked"'; ?>/>
                                            </td>
                                            
                                            <td>
                                                <input type="checkbox" value="1" name="oss_facets_exclusive[<?php echo $facet?>]" id="oss_facets_exclusive[<?php echo $facet?>]" <?php if(in_array($facet, $facets_exclusive)) echo 'checked="checked"'; ?>/>
                                            </td>
                                        <?php endif;?>
                                
                                        <td class="warning">
                                            <input type="checkbox" name="oss_facet_delete[]" value="<?php print $facet; ?>" />
                                        </td>
									</tr>
									<?php 
										endif;
									endforeach;
									?>
								</tbody>
							</table>
							<?php endif;?>
                                <?php if(1 == get_option(oss_advanced_facets)):?>
                                    <p>
                                        <label for="oss_facet_max_display">
                                           Maximum number of values to display before displaying a "See more" link:
                                        </label> 
                                        <input type="text" name="oss_facet_max_display" id="oss_facet_max_display" placeholder="5" size="7" value="<?php print get_option('oss_facet_max_display');?>" />
                                        <br/><span class="help">This can be useful if facets have lots of values. Leave this box empty to always display all values.</span>                                
                                    </p>
                                <?php endif; ?>
                                <br/>
                                <input type="checkbox" value="1" name="oss_advanced_facets" id="oss_advanced_facets" <?php checked( 1 == get_option('oss_advanced_facets')); ?>/>
                                <label for="oss_advanced_facets"><strong>Enable advanced facets behaviour</strong></label>
                                <br/><span class="help">This option allows for choosing the type of facets (exclusive ones or multiple choices ones), using hierarchical facets and some other options, displaying an "Active filters" section on top of the facets on the search page, and provide more values for facets in search results.</span>
                                <br/>
                                <input type="checkbox" id="oss_facet_display_count" name="oss_facet_display_count" value="1" <?php checked( 1 == get_option('oss_facet_display_count')); ?> />
                                <label for="oss_facet_display_count">Display the number of results for each facet</label>
                                <br/>
                                
							</p>
                            </fieldset>
                            
                            <fieldset><legend>SpellCheck</legend>
							<p>
								<label for="oss_spell">SpellCheck field</label>: <select
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
								<label for="oss_spell_algo">SpellCheck algorithm</label>: 
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
                            </fieldset>
                            
                            
							<fieldset><legend>Clean query</legend>
                                <p>
	                                <input type="checkbox" id="oss_clean_query_enable" 
                                        value="1" name="oss_clean_query_enable"
                                        <?php checked( 1 == get_option('oss_clean_query_enable')); ?>  />
                                    <label for="oss_clean_query_enable">Enable escaping of special characters</label>
	                            </p>
                                <p>
                                    <label for="oss_clean_query">
										Special characters to remove <span class="help">(separated by space)</span> :
									</label> 
                                    <br/><input type="text" name="oss_clean_query" id="oss_clean_query" placeholder="# $ ! @" size="50" value="<?php print htmlspecialchars(stripslashes(get_option('oss_clean_query')));?>" />
                                    <br/><span class="help">If the "enable escaping" option is ticked but nothing is written in the box, OSS will default to the following list: \\ ^ ~ ( ) { } [ ] & || ! * ? 039; ' #</span>
    							</p>
                            </fieldset>
                            
                            
                            <fieldset><legend>Logs</legend>
                                <p>
                                    <input type="checkbox" id="oss_log_enable" 
                                        value="1" name="oss_log_enable"
                                        <?php checked( 1 == get_option('oss_log_enable')); ?> />
                                    <label for="oss_log_enable">Enable logging of queries in OpenSearchServer</label>
                                    <br/><span class="help">Reports can be viewed in OpenSearchServer in the "Report" tab.</span>
                                </p>
                                
                                <div id="oss_logs_custom" style="<?php if(get_option('oss_log_enable') != 1) { echo 'display:none'; }?>">
                                    <p><strong>Custom logs:</strong></p>
	                                <p>
	                                    <input type="checkbox" id="oss_log_ip" 
	                                        value="1" name="oss_log_ip"
	                                        <?php checked( 1 == get_option('oss_log_ip')); ?>  />
	                                    <label for="oss_log_ip">Log IP (<code>$_SERVER['REMOTE_ADDR']</code>)</label>
	                                </p>
                                </div>
                            </fieldset>
                            
                            <?php if(opensearchserver_is_plugin_active('sitepress-multilingual-cms/sitepress.php')) : ?>
                                <fieldset>
                                    <legend>Filter results based on language, with plugin WPML</legend>
                                    <p>
                                        <input type="checkbox" value="1" 
                                            name="oss_filter_language_wpml" id="oss_filter_language_wpml" 
                                            <?php checked( 1 == get_option('oss_filter_language_wpml')); ?>
                                            />
                                        <label for="oss_filter_language_wpml">Filter search results based on current website's language</label>
                                    </p>
                                    <p>
                                        <span class="help">If enabled, search results will automatically be filtered by language, based on the language of the currently used website.
                                        <br/><strong>The "Index language information" option must be checked in order to use this feature.</strong>
                                        </span>
                                        
                                    </p>
                                    <div id="oss_filter_language_field" style="<?php if(get_option('oss_filter_language_wpml') != 1) { echo 'display:none;'; }?> padding-left:10px; margin-left:30px; border-left:1px solid #ebebeb;">
                                        <p><strong>Advanced option:</strong></p>
                                        <p>                                            
                                            <label for="oss_filter_language_field_wpml">Field to filter on: </label>
                                            <input type="text" id="oss_filter_language_field_wpml" placeholder="Leave empty to use default" size="40" value="<?php print get_option('oss_filter_language_field_wpml'); ?>" name="oss_filter_language_field_wpml" />
                                        </p>
                                        <p>
                                            <span class="help"><strong>Leave this field empty if you enabled the "Index language information" option and wish to filter on the field used for this information.</strong><br/>
                                            If you want to filter the language on a different field (because, say, your index is managed in a different way) please enter the field's name. The filter value will be the ICL_LANGUAGE_CODE one, such as <code>fr</code>, <code>en</code>, <code>es</code>, etc. You must ensure that values indexed in your field match those codes. See <a href="http://wpml.org/documentation/support/wpml-coding-api/" target="_blank">this page</a> for more information.
                                            </span>
                                        </p>
                                    </div>
                                </fieldset>
                             <?php endif;?>
                            
                            <fieldset><legend>Other options</legend>
                            <p>
                                <label for="oss_language">Default language</label>: <select
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
                            <p class="oss_visibleByQueryPattern">
                                <input
                                    type="checkbox" name="oss_phonetic" value="1"
                                    <?php checked( 1 == get_option('oss_phonetic')); ?> />
                                     <label for="oss_phonetic">Enable phonetic approximation</label>
                            </p>
                            <p>
                                Display:&nbsp;
                                    <input type="checkbox" name="oss_display_date" id="oss_display_date" value="1" <?php checked( 1 == get_option('oss_display_date')); ?> />&nbsp;
                                    <label for="oss_display_date">date</label>
                                    <input type="checkbox" name="oss_display_type" id="oss_display_type" value="1" <?php checked( 1 == get_option('oss_display_type')); ?> />&nbsp;
                                    <label for="oss_display_type">type</label>
                                    <input type="checkbox" name="oss_display_user" id="oss_display_user" value="1" <?php checked( 1 == get_option('oss_display_user')); ?> />
                                    <label for="oss_display_user">user</label>&nbsp;&nbsp;
                                    <input type="checkbox" name="oss_display_category" id="oss_display_category" value="1" <?php checked( 1 == get_option('oss_display_category')); ?> />
                                    <label for="oss_display_category">Chosen taxonomy (see list below)</label>
                                     <br/><span class="help">Choose what kind of information should be displayed under each result.</span>
                            </p>
                            <p>
                                <input type="checkbox" name="oss_sort_timestamp" id="oss_sort_timestamp" value="1" <?php checked( 1 == get_option('oss_sort_timestamp')); ?> />&nbsp;
                                <label for="oss_sort_timestamp">Display a link to sort results by date</label>
                            </p>
                             <p>
                                <label for="oss_taxonomy_display">Taxonomy to be displayed on Result</label>: <select
                                    name="oss_taxonomy_display"><?php
                                    $taxonomies=get_taxonomies('','names'); 
                                     $opt = get_option('oss_taxonomy_display');
                                      foreach ($taxonomies as $taxonomy ) {
                                          $selected = '';
                                        $check_taxonomy_name = 'oss_taxonomy_'.$taxonomy;
                                          if(get_option($check_taxonomy_name)==1) {
                                            if($opt == $taxonomy) {
                                                $selected = 'selected="selected"';
                                            }
                                      ?>
                                    <option value="<?php print $taxonomy;?>" <?php print $selected;?>>
                                        <?php print $taxonomy;?>
                                    </option>
                                    <?php }
                                    }?>
                                </select>
                            </p>
                            <p>
                                <label for="oss_autocomplete_number">Number of suggestions for autocompletion: </label>
                                <input type="text" id="oss_autocomplete_number" name="oss_autocomplete_number" 
                                value="<?php echo get_option('oss_autocomplete_number'); ?>" placeholder="10" style="width:40px;"/>
                            </p>
                            </fieldset>
                            
							<p>
								<input type="hidden" name="oss_submit" value="query_settings" />
                                <?php if(opensearchserver_is_query_settings_not_automatic()): ?>
                                    <div class="oss_visibleByQueryPattern" id="oss_query_settings_post_to_oss_wrapper">
	                                   <input type="checkbox" name="oss_query_settings_post_to_oss" id="oss_query_settings_post_to_oss" value="1" <?php checked(!opensearchserver_is_search_only()); ?> />&nbsp;
	                                   <label for="oss_query_settings_post_to_oss">Post query settings to OpenSearchServer instance.</label>
	                                   <br/><span class="help">If not checked, settings will only be saved locally and nothing will be posted to OpenSearchServer instance. Useful for example to edit the custom label of a facet.</span>
                                    </div>
                                <?php endif; ?>
                                <input type="submit" name="opensearchserver_submit" value="Update query settings" class="button-primary" />
							</p>
						</form>
					</div>
				</div>
                
				<?php 
                if(!opensearchserver_is_search_only()) :
                ?>                
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
						<fieldset>
                  			<legend>Type of content to index</legend>
                            <div class="oss_scrollable_list">
								<?php
								foreach (get_post_types() as $post_type) {
                                  $checkTypeName = 'oss_index_types_'.$post_type;
                                  ?>
								<input type="checkbox" name="<?php print $checkTypeName;?>"
									value="1" <?php checked( 1 == get_option($checkTypeName)); ?> id="<?php print $checkTypeName;?>"/>&nbsp;<label
									for="<?php print $checkTypeName;?>"><?php print $post_type;?> </label><br />
								<?php } ?>
                            </div>
						</fieldset>
                        <fieldset>
                            <legend>Extract text from attached files</legend>
                                <p>If you chose <code>attachment</code> among the types of content to index (above) you can decide to use OpenSearchServer's parsers
                                 to automatically extract text from your files - and index it alongside the attachment.<br/>
                                 <strong>This feature requires OpenSearchServer version 1.5.11 or better.</strong></p>
                                 <p>
                                <input type="checkbox" name="oss_parse_file"
                                    value="1" <?php checked( 1 == get_option('oss_parse_file')); ?> id="oss_parse_file"/>&nbsp;<label
                                    for="oss_parse_file">Extract test data from the attachments</label><br />
                                    <span class="help">This could slow down the indexing if there are lots of attachments.</span>
                                </p>
                        </fieldset>
						 <fieldset>
			                    <legend>Taxonomies to index</legend>
                                <div class="oss_scrollable_list">
				                  <?php 
				                    $taxonomies=get_taxonomies('','names'); 
				                    foreach ($taxonomies as $taxonomy ) {
				                      $check_taxonomy_name = 'oss_taxonomy_'.$taxonomy;
				                      ?>
				                      <input type="checkbox" name="<?php print $check_taxonomy_name;?>"
				                    value="1" <?php checked( 1 == get_option($check_taxonomy_name)); ?> id="<?php print $check_taxonomy_name;?>"/>&nbsp;<label
				                    for="<?php print $check_taxonomy_name;?>"><?php print $taxonomy;?> </label><br />
				                  <?php 
				                    }
				                  ?>
                                </div>
			              </fieldset>
                       <fieldset>
                          <legend>Custom Fields to index</legend>
                          <div class="oss_scrollable_list">
                          <?php 
                          $custom_field_labels = opensearchserver_get_all_custom_fields();
                          $selected_custom_fields = opensearchserver_get_selected_custom_fields();
                          foreach($custom_field_labels as $custom_field_label => $key) {
                          ?>
                            <input type="checkbox" name="oss_custom_fields[<?php print $custom_field_label;?>]"
                            value="1" <?php checked( in_array($custom_field_label, $selected_custom_fields)); ?> id="<?php print urlencode($custom_field_label);?>"/>&nbsp;<label
                            for="<?php print urlencode($custom_field_label) ;?>"><?php print $custom_field_label;?> </label><br />
                            <?php 
                            }
                            ?>
                            </div>
                      </fieldset>
                      <fieldset>
                         <legend>Auto Indexation </legend>
                         <input type="checkbox" name="oss_enable_autoindexation" id="oss_enable_autoindexation" value="1" <?php checked( 1 == get_option('oss_enable_autoindexation')); ?> />
                         <label for="oss_enable_autoindexation">Enable automatic indexation whenever content is added, edited or deleted.</label><br>
                         <span class="help"> If this option is disabled, the content will only be indexed when the 'Synchronize / Re-index' button is pressed.</span>
                      </fieldset>
							</p>
                            
                            <?php if(opensearchserver_is_plugin_active('sitepress-multilingual-cms/sitepress.php')) : ?>
                                <fieldset>
                                    <legend>Translation with WPML</legend>
                                    <p>
                                        <input type="checkbox" value="1" 
                                            name="oss_enable_translation_wpml" id="oss_enable_translation_wpml" 
                                            <?php checked( 1 == get_option('oss_enable_translation_wpml')); ?>
                                            />
                                        <label for="oss_enable_translation_wpml">Index language information</label>
                                    </p>
                                    <p>
                                        <span class="help">This will index language information for each content. Index re-creation and synchronization will be needed.</span>
                                        <br/><span class="help">The "Language" facet must be added to the list of facets to make use of this information.</span>
                                    </p>
                                </fieldset>
                             <?php endif;?>
                             <p>
                                <input type="hidden" name="oss_submit" value="index_settings" />
                                
                                 <br/>
                                 <span class="help"><strong>Which button should I click?</strong></span>
                                 <br/><br/>
                                 <span class="help">If you changed your "Content types to index" list or your "Auto indexation" settings you will only need to click "Update Index Settings".</span>
                                 <br/>
                                 <span class="help">However, if you updated your "Taxonomies to index" list or your "Custom Fields to index" list settings &mdash; you will *first* need to update your settings and *then* press the "(Re-)Create index" button. This is because specific fields need to be created in index's schema.</span>
                                 <br/><br/><span class="help">If you did not create your index yet or wish to completely re-create it, you need to press the "(Re-)Create index" button.</span> 
                                 <br/><br/>
                                 <input type="submit" name="opensearchserver_submit"
                                    value="Update Index Settings" class="button-primary" />
                                   
                                <input
                                    onclick="return confirm('This will erase current indexed data and totally re-create your index, are you sure?');"
                                    type="submit" name="opensearchserver_submit"
                                    value="(Re-)Create index" class="button-secondary" />
                                <br/><span class="help">When clicking "(Re-)Create index" you may want to click "Synchronize / Re-index" afterwards to re-populate the index.</span>

                            </p>
						</form>
					</div>
				</div>
                <?php 
                endif;
                ?>
				  <?php
                if(!opensearchserver_is_search_only()) :
                ?>
				<div class="postbox" id="fifth">
					<div class="handlediv" title="Click to toggle">
						<br />
					</div>
					<h3 class="hndle">
						<span>Indexing</span>
					</h3>
					<div class="inside">
						<form id="reindex_settings" name="reindex_settings" method="post"
							action="">
                            
                            <p>
                                With your current "Index settings", the total number of documents to index is <strong><?php echo opensearchserver_get_number_to_index(); ?>.</strong>
                            </p>
                            
                            <?php 
                                 $indexName = get_option('oss_indexname');
                                 if(!empty($indexName)) :
                                     $oss_result = opensearchserver_getsearchresult('*', false, false, true);
                                     if (isset($oss_result) && $oss_result instanceof SimpleXMLElement) :
                                         $oss_results = opensearchserver_getresult_instance($oss_result);
                                         //if first query does not return results try a spellcheck query to get a new suggestion
                                         $numberOfDocsInIndex = $oss_results->getResultFound();
                            ?>
                                    <p>
                                        There are currently <strong><?php echo $numberOfDocsInIndex; ?></strong> documents in the 
                                        <strong><?php echo get_option('oss_indexname'); ?></strong> index. 
                                    </p>
                            <?php     
                                    endif;
                                 endif; 
                            ?>
                            <p><strong>Re-indexing starts by deleting all content from the index. Thus, while the indexing process runs, the search engine will provide fewer or no results.</strong>
                             <hr/>
                              <p><strong>Re-indexing manually</strong><br/>
                              This re-indexes the data as soon as the button is clicked, as a synchronous process.</p>
                            <p><span class="help">If indexing is taking too long and the process ends up crashing, try running in several chunks,
                            each with a small number of documents to index. For instance you could index chunks of 1000 documents.<br/>
                            <strong>Leave both fields empty to index all documents.</strong></span>
							<p>
								<label for="oss_index_from">From document </label> <input
									type="text" name="oss_index_from" id="oss_index_from" size="7" placeholder="0"
									value="<?php print get_option('oss_index_from');?>" />
								<label for="oss_index_to">to document </label> <input
									type="text" name="oss_index_to" id="oss_index_to" size="7" placeholder="1000"
									value="<?php print get_option('oss_index_to');?>" /> 
							</p>
							<p>
								<input type="hidden" name="oss_submit" value="opensearchserver_reindex" /> 
                                <input type="submit"
									name="opensearchserver_submit" value="Synchronize / Re-Index"
									class="button-primary" />
                          </form>
                          <form id="reindex_settings_cron" name="reindex_settings_cron" method="post" action="">
                          <hr/>
                          <p><strong>Re-indexing with CRON</strong><br/>
                          This schedules a CRON job to re-index your full content. This job will be executed on the next time that WordPress' CRON runs.</p>
                          
                          <label for="oss_cron_number_by_job">Number of documents to index with each job : </label> <input
                                    type="text" name="oss_cron_number_by_job" id="oss_cron_number_by_job" size="7" placeholder="200"
                                    value="<?php print get_option('oss_cron_number_by_job');?>" /> 
                          <br/><span class="help">Full re-indexing may need the CRON to run automatically several times, depending on the <strong>Number of documents to index with each job</strong> value and the total number of documents to index.</span>
                          <?php 
                              //If a CRON indexing is running, check the current percentage of job done
                              $cronIsRunning = get_option('oss_cron_running');
                              if($cronIsRunning) {
                                  $numberIndexed = get_option('oss_cron_from');
                                  $totalToIndex = opensearchserver_get_number_to_index();
                                  $percentDone = round($numberIndexed * 100 / $totalToIndex);
                              }
                          ?>
                          <?php if($cronIsRunning) : ?>
                          <div id="oss-cron-running">
                              <p style="margin-bottom:0;">
                                <strong><em>CRON is currently running</em></strong>
                                <br/>
                                Progress (refresh the page to update it):                  
                              </p>
                              <div id="oss-cron-wrapper">
                                <div id="oss-cron-loading">
                                    <div id="oss-cron-loading-inner" style="width:<?php echo $percentDone; ?>%"></div>
                                    <div id="oss-cron-loading-percent"><?php echo $percentDone ?>%</div>
                                </div>
                                <div id="oss-cron-stats"><?php echo $numberIndexed ?> / <?php echo $totalToIndex ?></div>
                              </div>
                          </div>
                          <p>CRON is running. You can choose to restart it from 0:</p>
                          <?php endif; ?>
                          <p>
                            <input type="hidden" name="oss_submit" value="opensearchserver_reindex_cron" />
                            <input type="submit"
                                name="opensearchserver_submit" value="Re-index with CRON"
                                class="button-primary" />
                          </p>
						</form>
					</div>
				</div>
                <?php 
                endif;
                ?>
                <div class="postbox <?php if(!opensearchserver_is_search_only()) {print "closed";} ?>" id="sixth">
                    <div class="handlediv" title="Click to toggle">
                        <br />
                    </div>
                    <h3 class="hndle">
                        <span>Advanced settings </span>
                    </h3>
                    <div class="inside">
                        <form id="advanced_settings" name="advanced_settings" method="post" action="">
                            <p>
                                <input type="checkbox" value="1" name="oss_advanced_search_only" id="oss_advanced_search_only" <?php checked( 1 == get_option('oss_advanced_search_only')); ?>/>
                                    <label for="oss_advanced_search_only"><em>Search only</em> mode</label>
                                <br/>
                                <span class="help">In this mode, data is not sent from Wordpress to your OpenSearchServer instance. The OSS plugin will thus be only used
                                for the search page. This mode can be used if the data is indexed in another way (for example, a web crawler).</span>
                            </p>
                            <p>
                                <input type="checkbox" value="1" name="oss_advanced_query_settings_not_automatic" id="oss_advanced_query_settings_not_automatic" <?php checked( 1 == get_option('oss_advanced_query_settings_not_automatic')); ?>/>
                                    <label for="oss_advanced_query_settings_not_automatic">Do not immediately post "Query Settings" to OpenSearchServer</label>
                                <br/>
                                <span class="help">If enabled, this option will display a checkbox at the bottom of the "Query settings" section allowing to choose whether
                                OpenSearchServer related settings (pattern, spell-check, ...) should be sent to your OpenSearchServer instance. <br/>If not checked, settings will only be
                                saved locally. This may be useful if the query is managed on OpenSearchServer's side, and the Wordpress plugin is only meant to be used to manage local options (like
                                labels and custom values for facets, types of information to display for each document on the results page, etc.).<br/>
                                If the "Allow for choosing a different search template" option and the "Do not immediately post "Query Settings" to OpenSearchServer" option are both enabled then the
                                checkbox at the bottom of the "Query Settings" section will be hidden.</span>
                            </p>
                            <p>
                                <input type="checkbox" value="1" name="oss_advanced_query_template_choice" id="oss_advanced_query_template_choice" <?php checked( 1 == get_option('oss_advanced_query_template_choice')); ?>/>
                                    <label for="oss_advanced_query_template_choice">Allow for choosing a different search template</label>
                                <br/>
                                <span class="help">If enabled, this option will display an option on top of the "Query settings" section that allows for choosing to use a specific search 
                                template created in OpenSearchserver directly. If this option is chosen, your search template will have to be fully managed in OpenSearchServer: searched fields (or pattern),
                                needed returned fields, snippets, etc. Only keywords and facets will be dynamically configured in the search template.</span>
                            </p>
                            <p>
                                <input type="hidden" name="oss_submit" value="opensearchserver_advanced_settings" /> 
                                <input type="submit" name="opensearchserver_submit" value="Save advanced settings" class="button-primary" />
                            </p>
                        </form>
                    </div>
                </div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
    jQuery('#oss_log_enable').click(function(e) { jQuery('#oss_logs_custom').toggle();});
    jQuery('#oss_filter_language_wpml').click(function(e) { jQuery('#oss_filter_language_field').toggle();});
    jQuery('#oss_write_query').click(function(e) {jQuery('.oss_visibleByQueryTemplate').hide();jQuery('.oss_visibleByQueryPattern').show();});
    jQuery('#oss_existing_query_template').click(function(e) {jQuery('.oss_visibleByQueryPattern').hide();jQuery('.oss_visibleByQueryTemplate').show();});
    if(jQuery('#oss_existing_query_template').is(":checked")) {jQuery('.oss_visibleByQueryPattern').hide();jQuery('.oss_visibleByQueryTemplate').show();}
    else {jQuery('#oss-query-select-template').hide();jQuery('#oss-query-write-template').show();}
</script>
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