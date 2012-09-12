<?php
/*
 Template Name: OpenSearchServer Search Page
*/
get_header();
$query=get_search_query();
if(trim($query)=='') {
	print opensearchserver_form();?>
<div align="left" id="oss_error">Please enter some keyword to search.</div>
<?php }
$result=getSearchResult($query,true);
if (isset($result) && $result instanceof SimpleXMLElement) {
	$ossResults = new OSSResults($result);
	if($ossResults->getResultFound()<=0) {?>
<div align="left" id="oss_error">
	No documents containing all your search terms were found.<br /> Your
	Search Keyword <b><?php print "'	".$query. "	'";?> </b> did not match
	any document<br />Suggestions:<br /> - Make sure all words are spelled
	correctly.<br /> - Try different keywords.<br /> -Try more general
	keywords.<br />
</div>
<?php }
else {
	$resultTime = (float)$result->result['time'] / 1000;
	$max = ($ossResults->getResultStart() + $ossResults->getResultRows() > $ossResults->getResultFound()) ? $ossResults->getResultFound() : $ossResults->getResultStart() + $ossResults->getResultRows();
	?>
<table border="0" style="border: none;">
	<tr>
		<td width="120px" style="border: none">
			<div id="oss_filter">
				<ul id="oss_ul">
					<li><a href="<?php print '?s='.urlencode($query).'&fq=All';?>">All</a>
					</li>
					<?php foreach ($ossResults->getFacet('type') as $values) {
						$value = $values['name'];
						?>
					<li><a id="oss_link" href="?s=<?php print $query.'&fq='.$value ?>"><?php print $value.'('.$values.')';?>
					</a></li>
					<?php } ?>
				</ul>
			</div>
		</td>
		<td rowspan="2" style="border: none">
			<div id="oss-search-form">
				<?php print opensearchserver_form(); ?>
			</div>
			<div id="oss_no_of_doc">
				<?php print $ossResults->getResultFound().' documents found ('.$resultTime.' seconds)';?>
			</div> <br /><?php
			for ($i = $ossResults->getResultStart(); $i < $max; $i++) {
				$category	 = stripslashes($ossResults->getField($i, 'type', true));
				$title	 = stripslashes($ossResults->getField($i, 'title', true));
				$content = stripslashes($ossResults->getField($i, 'content', true));
				$user = stripslashes($ossResults->getField($i, 'user_name', true));
				$user_url = stripslashes($ossResults->getField($i, 'user_url', true));
				$type = stripslashes($ossResults->getField($i, 'type', true));
				$url = stripslashes($ossResults->getField($i, 'url', false));
				?>
			<div id="oss_results">
				<?php
	if($title) {?>
				<a href="<?php print $url;?>"><?php print $title;?> </a><br />
				<?php }else { ?>
				<a href="<?php print $url;?>"><?php print "Un-titled";?> </a><br />
				<?php }
				?>
				<?php if ($content) {
					print $content.'<br/>';
				}
	 if($url) {?>
				<a href="<?php print $url;?>"><?php print $url;?> </a>
				<?php }
				if($type && $user ) {
					if($user_url) {
		print $type;?>
				<a href="<?php print $user_url;?>"><?php print $user;?> </a><br />
				<?php }else {
					print $type."	By	".$user .'<br/>';
				}
	    }?>
			</div> <br /><?php
			}
			$oss_paging=opensearchserver_paging($result);
			foreach($oss_paging as $page)  {
				if($page['url']) {?> <span style="margin-left: 5px;"><a
				href="<?php print $page['url'];?>"><?php print $page['label'];?> </a>
		</span>&nbsp; <?php }
		else { ?> <span style="margin-left: 5px;"><?php print $page['label'];?>
		</span>&nbsp; <?php }
	}?>
			<div align="right">
				<img
					src="http://www.open-search-server.com/images/oss_logo_62x60.png" /><br />
				<a href="http://www.open-search-server.com/">Enterprise Search Made
					Yours</a>
			</div>
	
	</tr>
</table>
<?php }
}
get_footer();
exit;
?>
