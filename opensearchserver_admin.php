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

function opensearchserver_create_schema() {
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
  opensearchserver_setField($schema,$schema_xml,'content','TextAnalyzer','compress','yes','positions_offsets','yes','no');
  opensearchserver_setField($schema,$schema_xml,'contentExact','StandardAnalyzer','no','yes','no','no','no');
  opensearchserver_setField($schema,$schema_xml,'timestamp',NULL,'no','yes','yes','no','no');
  opensearchserver_setField($schema,$schema_xml,'user_name',NULL,'yes','yes','yes','no','no');
  opensearchserver_setField($schema,$schema_xml,'user_email',NULL,'yes','yes','yes','no','no');
  opensearchserver_setField($schema,$schema_xml,'user_url',NULL,'no','yes','yes','no','no');
  opensearchserver_setField($schema,$schema_xml,'allContent','TextAnalyzer','no','yes','no','yes','no');
  opensearchserver_setField($schema,$schema_xml,'categories','TextAnalyzer','yes','yes','no','yes','no');
  opensearchserver_setField($schema,$schema_xml,'categoriesExact',NULL,'yes','yes','no','yes','no');
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
  $query_template->createSearchTemplate('search', $oss_query, 'AND', '10', '2', get_option('oss_language'));
  $query_template->setSnippetField('search','title', 70, 'b');
  $query_template->setSnippetField('search','content', 300, 'b', NULL, 'SentenceFragmenter');
  if (get_option('oss_phonetic')) {
    $query_template->setSnippetField('search','titlePhonetic', 70, 'b');
    $query_template->setSnippetField('search','contentPhonetic', 300, 'b', NULL, 'SentenceFragmenter');
  }
  $query_template->setReturnField('search',"url");
  $query_template->setReturnField('search',"user_url");
  $query_template->setReturnField('search',"type");
  $query_template->setReturnField('search',"user_name");
  $query_template->setReturnField('search',"user_email");
  $query_template->setReturnField('search',"categories");
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

/*
 * Function to reindex the website.
*/
function opensearchserver_reindex_site($id,$type) {
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
  if($id)	{
    $delete='id:'.$type.'_'.$id;
    opensearchserver_delete_document($delete);
    $sql_suffix = 'FROM '.$table_name_posts.' p LEFT JOIN  '.$table_name_users.' u ON p.post_author = u.ID WHERE `post_status` = \'publish\' AND p.ID ='.$id;
    $sql_query = 'SELECT p.ID,post_type,post_title,post_content,guid,post_date_gmt,post_author,user_nicename,user_url,user_email '.$sql_suffix;
    $sql_posts = $wpdb->get_results($sql_query);
    $index = opensearchserver_add_documents_to_index($lang, $sql_posts, $custom_fields);
    opensearchserver_start_indexing($index);
  }else {
    opensearchserver_delete_document('*:*');
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
      $index = opensearchserver_add_documents_to_index($lang, $sql_posts, $custom_fields);
      opensearchserver_start_indexing($index);
      $wpdb->flush();
      wp_cache_flush();
      unset($sql_posts);
      unset($index);
      $current_pos += $row_fetch;
    }
  }
  opensearchserver_optimize();
  opensearchserver_autocompletionBuild();
  $index_status=1;
  return $index_status;
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
  $autocompletion = opensearchserver_getautocomplete_instance();
  $autocompletion->autocompletionSet('contentExact');
  $autocompletion->autocompletionBuild();
}

function opensearchserver_add_documents_to_index($lang, $sql_posts, $customFields) {
  $index = new OSSIndexDocument();
  $lang= substr(get_locale(), 0, 2);
  foreach($sql_posts as $post){
    $content=$post->post_content;
    $content = apply_filters('the_content', $content);
    $content = str_replace(']]>', ']]&gt;', $content);
    $content = opensearchserver_encode(strip_tags($content));
    $document = $index->newDocument($lang);
    $document->newField('id', $post->post_type.'_'.$post->ID);
    $document->newField('type', strip_tags($post->post_type));
    $title = opensearchserver_stripInvalidXml(strip_tags($post->post_title));
    $document->newField('title', $title);
    $document->newField('titleExact', $title);
    $document->newField('titlePhonetic', $title);
    $content = opensearchserver_stripInvalidXml($content);
    $document->newField('content', $content);
    $document->newField('contentExact', $content);
    $document->newField('contentPhonetic', $content);
    $document->newField('url', get_permalink($post->ID));
    $document->newField('urlExact', get_permalink($post->ID));
    $document->newField('timestamp', $post->post_date_gmt);
    $document->newField('user_name', $post->user_nicename);
    $document->newField('user_email', $post->user_email);
    $document->newField('user_email', $post->user_url);
    $categories_data= '';

    // Handling categories
    $categories_data = null;
    $categories = get_the_category($post->ID);
    if ( ! $categories == NULL ) {
      foreach( $categories as $category ) {
        $categories_data .= $category->cat_name.' ';
        $document->newField('categories', $category->cat_name);
        $document->newField('categoriesExact', $category->cat_name);
      }
    }

    // Handling custom fields
    $custom_clean_all='';
    if($customFields) {
      $custom_fields_array=explode(",",$customFields);
      foreach ($custom_fields_array as $field) {
        $custom_content = '';
        $custom_values=get_post_custom_values($field,$post->ID);
        if(is_array($custom_values)) {
          foreach ($custom_values as $values) {
            $custom_content .= $values." ";
          }
        }else {
          $custom_content = $custom_values;
        }
        $content_br = nl2br($custom_content);
        $content_clean=str_replace("<br />", ' ', $content_br);
        $document->newField("custom_".clean_field($field), stripInvalidXml(strip_tags($content_clean)));
        $custom_clean_all .=' '.$content_clean;
      }
    }
    // Build all content field
    $all_content = opensearchserver_stripInvalidXml(strip_tags($post->post_title)). ' '.$content;
    if ($categories_data) {
      $all_content = strip_tags($all_content.' '.$categories_data);
    }
    if ($custom_clean_all) {
      $all_content .= ' ' .$custom_clean_all;
    }
    $document->newField("allContent", strip_tags($all_content.' '.$categories_data));
  }
  return $index;
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
    'categoriesExact' => 'Categories');
}

/*
 * The admin page settings actions
*/
function opensearchserver_admin_page() {
  $fields = opensearchserver_get_fields();
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
  if($action == 'settings') {
    $oss_url = isset($_POST['oss_serverurl']) ? $_POST['oss_serverurl'] :NULL;
    $oss_indexname = isset($_POST['oss_indexname']) ? $_POST['oss_indexname'] :NULL;
    $oss_login = isset($_POST['oss_login']) ? $_POST['oss_login'] :NULL;
    $oss_key = isset($_POST['oss_key']) ? $_POST['oss_key'] :NULL;
    update_option('oss_serverurl', $oss_url);
    update_option('oss_indexname', $oss_indexname);
    update_option('oss_login', $oss_login);
    update_option('oss_key', $oss_key);
    opensearchserver_display_messages('OpenSearchServer Settings has been updated');

  }if($action == 'query_settings') {
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
      update_option('oss_query', $oss_query);
      if($oss_facet != 'none') {
        if(get_option('oss_facet')) {
          $facet = get_option('oss_facet');
        }else {
          $facet = array();
        }
        if (!in_array($oss_facet, $facet)) {
          array_push($facet,$oss_facet);
        }
        update_option('oss_facet', $facet);
      }
      update_option('oss_spell', $oss_spell);
      update_option('oss_spell_algo', $oss_spell_algo);
      $oss_language = isset($_POST['oss_language']) ? $_POST['oss_language'] : NULL;
      update_option('oss_language', $oss_language);
      $oss_phonetic = isset($_POST['oss_phonetic']) ? $_POST['oss_phonetic'] : NULL;
      update_option('oss_phonetic', $oss_phonetic);
      opensearchserver_display_messages('OpenSearchServer Settings has been updated.');
    }

  } if($action == 'index_settings') {
    $is_index_created = opensearchserver_create_index();
    opensearchserver_display_messages('Index '.get_option('oss_indexname').' Created successfully');
     
  }if($action == 'custom_field_settings') {
    $oss_custom_field = isset($_POST['oss_custom_field']) ? $_POST['oss_custom_field'] :NULL;
    update_option('oss_custom_field', $oss_custom_field);
    opensearchserver_display_messages('OpenSearchServer Settings has been updated.');
  }
  if($action == 'opensearchserver_reindex') {
    $is_index_created = opensearchserver_create_index();
    $index_success = opensearchserver_reindex_site(NULL,NULL);
    opensearchserver_display_messages('Re indexing has been finished successfully.');
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
							<input type="hidden" name="oss_submit" id="oss_submit"
								value="settings" />
							<p>
								<input type="submit" name="opensearchserver_submit"
									value="Update Settings »" class="button-primary" />
							</p>
						</div>
					</form>
				</div>
				<div class="postbox closed" id="second">
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
                                  }
                                  ?>
								</textarea>
							</p>
							<p>
								<label for="oss_query">Facet field</label>:<br /> <select
									name="oss_facet">
									<?php
									foreach ($fields as $key => $field) {
									  ?>
									<option value="<?php print $key;?>">
										<?php print $field;?>
									</option>
									<?php }?>
								</select> <input type="submit" name="opensearchserver_add"
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
									<tr>
										<td><?php print $fields[$facet]; ?></td>
										<input type="hidden" name="oss_delete" id="oss_submit"
											value="<?php print $facet; ?>" />
										<td><input type="submit" name="opensearchserver_delete"
											value="Delete" class="button-secondary" /></td>
									</tr>
									<?php }?>

								</tbody>
							</table>
							<?php }?>
							<p>
								<label for="oss_query">SpellCheck field</label>:<br /> <select
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
								<input type="hidden" name="oss_submit" id="oss_submit"
									value="query_settings" /> <input type="submit"
									name="opensearchserver_submit" value="Update Options »"
									class="button-primary" />
							</p>
						</form>
					</div>
				</div>
				<div class="postbox closed" id="third">
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
								<label for="oss_create_index">Create an index</label>: <input
									type="text" name="oss_indexname_create" id="oss_indexname"
									placeholder="opensearchserver_wordpress" size="50"
									value="<?php print get_option('oss_indexname');?>"
									disabled="disabled" /> <input type="hidden" name="oss_submit"
									id="oss_submit" value="index_settings" /><input type="submit"
									name="opensearchserver_submit" value="Create Index"
									class="button-secondary" />
							</p>
						</form>
					</div>
				</div>
				<div class="postbox closed" id="fourth">
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
								<input type="hidden" name="oss_submit" id="oss_submit"
									value="custom_field_settings" /><input type="submit"
									name="opensearchserver_submit" value="Update Settings »"
									class="button-primary" /><br />
							</p>
						</form>
					</div>

				</div>
				<form id="reindex_settings" name="reindex_settings" method="post"
					action="">
					<input type="hidden" name="oss_submit" id="oss_submit"
						value="opensearchserver_reindex" /> <input type="submit"
						name="opensearchserver_submit" value="Synchronize / Re-Index"
						class="button-primary" />
				</form>
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