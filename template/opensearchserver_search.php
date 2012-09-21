<?php
/*
 *Template Name: OpenSearchServer Search Page
*/
get_header();
?>
<div id="oss-search">
	<div id="oss-search-form">
		<form method="get" id="searchform" action=<?php print home_url('/');?>>
			<input type="text" value="<?php print get_search_query();?>" name="s"
				id="keyword" size="45" onkeyup="return autosuggest(event)"
				autocomplete="off" /> <input type="submit" id="oss-submit"
				value="Search" />
			<div style="position: absolute">
				<div id="autocomplete"></div>
			</div>
		</form>
	</div>
	<?php
	$query = get_search_query();
	$oss_result = opensearchserver_getsearchresult($query, FALSE,TRUE);
	$oss_result_facet = opensearchserver_getsearchresult($query, FALSE, FALSE);
	$oss_sp= isset($_REQUEST['sp']) ? $_REQUEST['sp'] :NULL;
	if (isset($oss_result) && $oss_result instanceof SimpleXMLElement && isset($oss_result_facet) && $oss_result_facet instanceof SimpleXMLElement) {
		$oss_results = opensearchserver_getresult_instance($oss_result);
		$oss_result_facets = opensearchserver_getresult_instance($oss_result_facet);
		if($oss_results->getResultFound() <= 0 && $oss_sp != 1) {
			$oss_spell_result = opensearchserver_getsearchresult($query, TRUE,NULL);
			$spellcheck_query = opensearchserver_getspellcheck($oss_spell_result);
			$oss_result =  opensearchserver_getsearchresult($spellcheck_query, FALSE, TRUE);
			if (isset($oss_result) && $oss_result instanceof SimpleXMLElement && isset($oss_result_facet) && $oss_result_facet instanceof SimpleXMLElement) {
				$oss_results = opensearchserver_getresult_instance($oss_result);
				$oss_result_facet = opensearchserver_getsearchresult($spellcheck_query, FALSE, FALSE);
				$oss_result_facets = opensearchserver_getresult_instance($oss_result_facet);
			}
		}
		$oss_resultTime = isset($oss_result) ? (float)$oss_result->result['time'] / 1000 : NULL;
		$max = opensearchserver_get_max($oss_results);
		if($oss_sp == 1 || $oss_results->getResultFound() <= 0) {
			?>
	<div align="left" id="oss_error">
		No documents containing all your search terms were found.<br /> Your
		Search Keyword <b><?php print "'	".$query. "	'";?> </b> did not match
		any document<br />Suggestions:<br /> - Make sure all words are spelled
		correctly.<br /> - Try different keywords.<br /> -Try more general
		keywords.<br />
	</div>
	<?php
		}
		else {
			?>
	<div id="oss-filter">
		<?php $facets = get_option('oss_facet');
		foreach ($facets as $facet) {
			$facet_results = $oss_result_facets->getFacet($facet);
			?>
		<div id="oss-filter-title"></div>
		<b><?php
		$fields = opensearchserver_get_fields();
		print ucfirst($fields[$facet]);?> </b>
		<ul id="oss-nav">
			<li class="oss-top-nav"><a
				href="<?php print '?s='.urlencode($query).'&fq=All';?>">All</a>
			</li>
			<?php
			if(count($facet_results) > 0 ) {
				foreach ($facet_results as $values) {
					$value = $values['name'];
					?>
			<li><a id="oss-link"
				href="?s=<?php print $query.'&fq='.$facet. ':' .$value ?>"><?php print $value.'('.$values.')';?>
			</a>
			</li>
			<?php }
			}
			?>
		</ul>
		<?php
}?>
	</div>
	<div id="oss-search-form"></div>

	<div id="oss-no-of-doc">
		<?php print $oss_results->getResultFound().' documents found ('.$oss_resultTime.' seconds)';
		if($oss_sp != 1 && $oss_results->getResultFound() > 0) {
			?>
		<div id="oss-did-you-mean">
			<?php if(isset($spellcheck_query)) { ?>
			Showing results for <b><?php print $spellcheck_query;?> </b> Search
			instead for <a href="?s=<?php print $query.'&sp=1'; ?>"><b><?php print $query;?>
			</b> </a>
			<?php }
			?>
		</div>
		<?php }?>
	</div>
	<div id="oss-results">
		<?php
		for ($i = $oss_results->getResultStart(); $i < $max; $i++) {
			$category	 = stripslashes($oss_results->getField($i, 'type', true));
			$title	 = stripslashes($oss_results->getField($i, 'title', true));
			$content = stripslashes($oss_results->getField($i, 'content', true));
			$user = stripslashes($oss_results->getField($i, 'user_name', true));
			$user_url = stripslashes($oss_results->getField($i, 'user_url', true));
			$type = stripslashes($oss_results->getField($i, 'type', true));
			$url = stripslashes($oss_results->getField($i, 'url', false));
			$categories = stripslashes($oss_results->getField($i, 'categories', false));
			?>

		<div id="oss-result">
			<?php
				if($title) {?>
			<div id="oss-title">
				<a href="<?php print $url;?>"><?php print $title;?> </a><br />
			</div>
			<?php }else { ?>
			<a href="<?php print $url;?>"><?php print "Un-titled";?> </a><br />
			<?php }
			?>
			<div id="oss-content">
				<?php if ($content) {
					print $content.'<br/>';
				}
				$custom_fields_array = get_custom_fields();
				foreach($custom_fields_array as $field) {
					$value = stripslashes($oss_results->getField($i, "custom_".clean_field($field), false));
					if($value) {
						print '<b>'. $field.'</b> : '.$value.'<br/>';
					}
				}
				?>
			</div>
			<div id="oss-url">
				<?php
	 if($url) {?>
				<a href="<?php print $url;?>"><?php print $url;?> </a>
				<?php }
				if($type && $user ) {

					print $type."	By	".$user;
					if($categories != NULL || $categories != '') {
						print ' in '. $categories. '<br/>';
					}else {
						print '<br/>';
					}
				}?>
			</div>
		</div>
		<br />
		<?php
		}
		?>
	</div>
	<?php $oss_paging = opensearchserver_getpaging($oss_result);

	if($oss_paging) { ?>
	<div id="oss-paging">
		<?php	foreach($oss_paging as $page)  {
				if($page['url']) {?>
		<a id="oss-page" href="<?php print $page['url']; ?>"><?php print $page['label']; ?>
		</a>
		<?php }
		else {
			print '<span id="oss-page">'.$page['label'].'</span>';
		}
			} ?>
	</div>
	<?php }?>
	<div align="right">
		<a href="http://www.open-search-server.com/"> <img
			src="http://www.open-search-server.com/images/oss_logo_62x60.png" /><br />
		</a>
	</div>
</div>
<?php
		}
	}

get_footer(); exit; ?>
