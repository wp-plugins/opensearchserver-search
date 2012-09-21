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
/*
 * Check the index is available or create it.
*/
function opensearchserver_create_index() {
	$oss_api = opensearchserver_getapi_instance();
	$index_list = $oss_api->indexList();
	$index = in_array(get_option('oss_indexname'), $index_list);
	if($index === FALSE) {
		$oss_api->createIndex(get_option('oss_indexname'));
		opensearchserver_create_schema();
		opensearchserver_query_template();
		opensearchserver_spellcheck_query_template();
		return TRUE;
	}
	return FALSE;
}
function setField($ossSchema, $xmlSchema, $fieldName, $analyzer, $stored, $indexed, $termVector, $default = 'no', $unique = 'no') {
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
	setField($schema,$schema_xml,'id', NULL, 'yes', 'yes', 'no', 'no', 'yes');
	setField($schema,$schema_xml,'type', NULL, 'yes', 'yes', 'no', 'no', 'no');
	setField($schema,$schema_xml,'url',NULL,'no','yes','yes','no','no');
	setField($schema,$schema_xml,'urlExact','StandardAnalyzer','no','yes','yes','no','no');
	setField($schema,$schema_xml,'title','TextAnalyzer','yes','yes','positions_offsets','no','no');
	setField($schema,$schema_xml,'titleExact','StandardAnalyzer','yes','yes','no','no','no');
	setField($schema,$schema_xml,'content','TextAnalyzer','compress','yes','positions_offsets','yes','no');
	setField($schema,$schema_xml,'contentExact','StandardAnalyzer','compress','yes','no','no','no');
	setField($schema,$schema_xml,'timestamp',NULL,'no','yes','yes','no','no');
	setField($schema,$schema_xml,'user_name',NULL,'yes','yes','yes','no','no');
	setField($schema,$schema_xml,'user_email',NULL,'yes','yes','yes','no','no');
	setField($schema,$schema_xml,'user_url',NULL,'no','yes','yes','no','no');
	setField($schema,$schema_xml,'allContent','TextAnalyzer','compress','yes','no','yes','no');
	setField($schema,$schema_xml,'categories','TextAnalyzer','YES','yes','no','yes','no');
	setField($schema,$schema_xml,'categoriesExact',NULL,'YES','yes','no','yes','no');
}
/*
 * Function to display messages in admin
*/
function opensearchserver_diaplay_messages($message, $errormsg = FALSE) {
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
	$query_template->createSearchTemplate("search",$oss_query,"AND","10","2","ENGLISH");
	$query_template->setSnippetField("search","title");
	$query_template->setSnippetField("search","content");
	$query_template->setReturnField("search","url");
	$query_template->setReturnField("search","user_url");
	$query_template->setReturnField("search","type");
	$query_template->setReturnField("search","user_name");
	$query_template->setReturnField("search","user_email");
	$query_template->setReturnField("search","categories");

}
/*
 * Function to create the spellcheck query
*/
function opensearchserver_spellcheck_query_template() {
	$spell_field = get_option('oss_spell').'Exact';
	if($spell_field && $spell_field !='none')
		$spellcheck_query_template = opensearchserver_getsearchtemplate_instance();
	$spellcheck_query_template->createSpellCheckTemplate("spellcheck","*:*","1",$spell_field,"0.5",NULL,"LevensteinDistance");
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
	$table_name_posts =$wpdb->prefix ."posts";
	$table_name_users =$wpdb->prefix ."users";
	$index_status=0;
	$ossEnginePath  = config_request_value('ossEnginePath', $oss_server_url, 'engineURL');
	$ossEngineConnectTimeOut = config_request_value('ossEngineConnectTimeOut', 5, 'engineConnectTimeOut');
	$ossEngineIndex = config_request_value('ossEngineIndex', $oss_indexname, 'engineIndex');
	if($id)	{
		$delete='id:'.$type.'_'.$id;
		delete_document($delete);
		$sql_suffix = 'FROM '.$table_name_posts.' p LEFT JOIN  '.$table_name_users.' u ON p.post_author = u.ID WHERE `post_status` = \'publish\' AND p.ID ='.$id;
		$sql_query = 'SELECT p.ID,post_type,post_title,post_content,guid,post_date_gmt,post_author,user_nicename,user_url,user_email '.$sql_suffix;
		$sql_posts = $wpdb->get_results($sql_query);
		$index=add_documents_to_index($sql_posts, $custom_fields);
		opensearchserver_start_indexing($index);
	}else {
		delete_document('*:*');
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
			$index = add_documents_to_index($sql_posts,$custom_fields);
			opensearchserver_start_indexing($index);
			$wpdb->flush();
			wp_cache_flush();
			unset($sql_posts);
			unset($index);
			$current_pos += $row_fetch;
		}
	}
	opensearchserver_optimize();
	$index_status=1;
	return $index_status;
}
function delete_document($query) {
	return opensearchserver_getdelete_instance()->delete($query);
}

function opensearchserver_start_indexing($index) {
	$server = opensearchserver_getapi_instance();
	if ($server->update($index, get_option('oss_indexname')) === FALSE) {
		$errors[] = 'failedToUpdate';
	}
}
function clean_field($field) {
	$field = str_replace(' ', '_', $field);
	$escapechars = array('\\', '^', '~', ':', '(', ')', '{', '}', '[', ']' , '&', '||', '!', '*', '?','039;','\'','#');
	foreach ($escapechars as $escchar)  {
		$field = str_replace($escchar, '_', $field);
	}
	$field = trim($field);
	return strtolower($field);
}
function stripInvalidXml($value) {
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
	return stripInvalidXml($str2);
}

function opensearchserver_optimize() {
	$server = opensearchserver_getapi_instance();
	$server->optimize();
}

function add_documents_to_index($sql_posts,$customFields) {
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
		$document->newField('title', stripInvalidXml(strip_tags($post->post_title)));
		$document->newField('titleExact', stripInvalidXml(strip_tags($post->post_title)));
		$document->newField('content',stripInvalidXml($content));
		$document->newField('contentExact',stripInvalidXml($content));
		$document->newField('url', get_permalink($post->ID));
		$document->newField('urlExact', get_permalink($post->ID));
		$document->newField('timestamp', $post->post_date_gmt);
		$document->newField('user_name', $post->user_nicename);
		$document->newField('user_email', $post->user_email);
		$document->newField('user_email', $post->user_url);
		$custom_clean_all='';
		$categories_data= '';
		if($customFields) {
			$custom_fields_array=explode(",",$customFields);
			foreach($custom_fields_array as $field) {
				$custom_content = '';
				$custom_values=get_post_custom_values($field,$post->ID);
				if(is_array($custom_values)) {
					foreach ($custom_values as $values) {
						$custom_content .= $values." ";
					}
				}else {
					$custom_content = $custom_values;
				}
				$categories = get_the_category($post->ID);
				if ( ! $categories == NULL ) {
					foreach( $categories as $category ) {
						$categories_data .= $category->cat_name.' ';
					}
				}
				if($categories_data != '') {
					$document->newField('categories', $categories_data);
				}
				$content_br = nl2br($custom_content);
				$content_clean=str_replace("<br />", ' ', $content_br);
				$document->newField("custom_".clean_field($field), stripInvalidXml(strip_tags($content_clean)));
				$custom_clean_all .=' '.$content_clean;
			}
		}
		$all_content = stripInvalidXml(strip_tags($post->post_title)). ' '.$content;
		if($custom_clean_all) {
			$all_content .= ' ' .$custom_clean_all;
		}
		if($categories_data) {
			$document->newField("allContent", strip_tags($all_content.' '.$categories_data));
		}
	}
	return $index;
}
function opensearchserver_get_fields() {
	return array("none"=>"Select","title" => "Title","content" =>"Content","url" => "Url","user_name" => "User Name","user_email" => "User Email","user_url" => "User URL","id" => "ID","type" => "Type","timestamp" => "TimeStamp");
}
/*
 * The admin page settings actions
*/
function opensearchserver_admin_page() {
	$fields = opensearchserver_get_fields();
	$spellcheck_fields = array("none"=>"Select","title" => "Title","content" =>"Content");
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
		opensearchserver_diaplay_messages('OpenSearchServer Settings has been updated');

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
			$oss_query = isset($_POST['oss_query']) ? $_POST['oss_query'] :NULL;
			$oss_facet = isset($_POST['oss_facet']) ? $_POST['oss_facet'] :NULL;
			$oss_spell = isset($_POST['oss_spell']) ? $_POST['oss_spell'] :NULL;
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
			opensearchserver_diaplay_messages('OpenSearchServer Settings has been updated.');
		}

	}if($action == 'index_settings') {
		$is_index_created = opensearchserver_create_index();
		if($is_index_created === TRUE) {
			opensearchserver_diaplay_messages('Index '.get_option('oss_indexname').' Created successfully');
		}else {
			opensearchserver_diaplay_messages('Error:10001 Index '.get_option('oss_indexname').' already exist');
		}
	}if($action == 'custom_field_settings') {
		$oss_custom_field = isset($_POST['oss_custom_field']) ? $_POST['oss_custom_field'] :NULL;
		update_option('oss_custom_field', $oss_custom_field);
		opensearchserver_diaplay_messages('OpenSearchServer Settings has been updated.');
	}
	if($action == 'opensearchserver_reindex') {
		$is_index_created = opensearchserver_create_index();
		$index_success = opensearchserver_reindex_site(NULL,NULL);
		opensearchserver_diaplay_messages('Re indexing has been finished successfully.');
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
						<span><?php print 'Server Settings'; ?> </span>
					</h3>
					<form id="oss_settings" name="oss_settings" method="post" action="">
						<div class="inside">
							<p>
								<label for="opensearchserver_location">OpenSearchServer
									Installation Location</label>:<br /> <input type="text"
									name="oss_serverurl" id="oss_serverurl"
									placeholder="http://localhost:8080" size="50"
									value="<?php print get_option('oss_serverurl');?>" /> <br />
							</p>
							<p>
								<label for="opensearchserver_index_name">OpenSearchServer Index
									Name</label>:<br /> <input type="text" name="oss_indexname"
									id="oss_indexname" placeholder="opensearchserver_wordpress"
									size="50" value="<?php print get_option('oss_indexname');?>" />
								<br />
							</p>
							<p>
								<label for="opensearchserver_login">OpenSearchServer Login Name</label>:<br />
								<input type="text" name="oss_login" id="oss_login"
									placeholder="admin" size="50"
									value="<?php print get_option('oss_login');?>" /> <br />
							</p>


							<p>
								<label for="opensearchserver_key">OpenSearchServer Access key</label>:<br />
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
						<span>Query Settings </span>
					</h3>
					<div class="inside">
						<p>Available for querying,facet and spell check are title, posts,
							category, user,user_email, post_type.</p>
						<form id="query_settings" name="query_settings" method="post"
							action="">
							<p>
								<label for="oss_query">OpenSearchServer Query</label>:<br />
								<textarea rows="10" cols="100" name="oss_query" wrap="off"><?php
									if(trim(get_option('oss_query'))) {
										print stripslashes(get_option('oss_query'));
									}
									?></textarea>
							</p>
							<p>
								<label for="oss_query">Facet Field</label>:<br /> <select
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
										<th>Facet Field</th>
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
								<label for="oss_query">SpellCheck Field</label>:<br /> <select
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
						<span>Index Settings </span>
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
									class="button-secondary" /><br />
							</p>
						</form>
					</div>
				</div>
				<div class="postbox closed" id="fourth">
					<div class="handlediv" title="Click to toggle">
						<br />
					</div>
					<h3 class="hndle">
						<span>Custom Fields Settings </span>
					</h3>
					<div class="inside">
						<form id="custom_field_settings" name="custom_field_settings"
							method="post" action="">
							<p>
								<label for="custom_fields_oss">Enter the fields from the Custom
									Field Template more information at
									http://wordpress.org/extend/plugins/custom-field-template/</label>:
								<textarea rows="10" cols="100" name="oss_custom_field"
									wrap="off"><?php
									print stripslashes(get_option('oss_custom_field'));
									?></textarea>
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