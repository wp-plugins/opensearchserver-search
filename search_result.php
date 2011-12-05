<?php
function get_search_result_output($query) {
	$result=getSearchResult($query,true);
	$result_facet=getSearchResult($query,false);
	if (isset($result) && $result instanceof SimpleXMLElement) {
		$ossResults = new OSSResults($result);
		$ossResults_facet = new OSSResults($result_facet);
		$display_error='<div align="left" id="oss_error">
					No documents containing all your search terms were found.<br/>
					Your Search Keyword <b>'.$query.'</b> did not match any document<br/>Suggestions:<br/>
					- Make sure all words are spelled correctly.<br/>
					- Try different keywords.<br/>
					-Try more general keywords.<br/>
					</div>';
if($ossResults->getResultFound()<=0) {
	$cont = $display_error;
}
else {
	$resultTime = (float)$result->result['time'] / 1000;
	$max = ($ossResults->getResultStart() + $ossResults->getResultRows() > $ossResults->getResultFound()) ? $ossResults->getResultFound() : $ossResults->getResultStart() + $ossResults->getResultRows();
	$cont ='<table border="0" style="border:none;">
			<tr>
			<td width="120px" style="border:none">
			<div id="oss_filter">';
	$cont .='<ul id="oss_ul">';
	$cont.='<li><a href="?s='.urlencode($query).'&fq=All">All</a></li>';
	 foreach ($ossResults->getFacet('type') as $values) {
		$value = $values['name'];
		$cont.='<li><a id="oss_link" href="?s='.$query.'&fq='.$value.'">'.$value.'('.$values.')'.'</a></li>';
	}
	$cont .='</ul></div>';
	$cont .='</td><td rowspan="2" style="border:none"><div>';
	$cont.='<div id="oss_no_of_doc">'.$ossResults->getResultFound().' documents found ('.$resultTime.' seconds)<br/><br/></div>';
	for ($i = $ossResults->getResultStart(); $i < $max; $i++) {
 $category	 = stripslashes($ossResults->getField($i, 'type', true));
	    $title	 = stripslashes($ossResults->getField($i, 'title', true));
        $content = stripslashes($ossResults->getField($i, 'content', true));
        $user = stripslashes($ossResults->getField($i, 'user_name', true));
        $user_url = stripslashes($ossResults->getField($i, 'user_url', true));
        $type = stripslashes($ossResults->getField($i, 'type', true));
        $url = stripslashes($ossResults->getField($i, 'url', false));
       		$cont.='<div id="oss_results">';
	        	if($title && $url) {
        	$cont.='<a href="'.$url.'">'.$title.'</a><br/>';
        	}else if(!$url) {
        		$cont.=$title.'<br/>';
        	}else if(!$title){
        		$cont.='<a href="'.$url.'">Un-titled</a><br/>';
        	}
        	if($content) {
        		$cont.=$content.'<br/>';
        	}
        	if($url) {
        		$cont.='<a href='.$url.'>'.$url.'</a>&nbsp;&nbsp;&nbsp;&nbsp;';
        	}
        if($type && $user ) {
        	if($user_url) {
          		$cont.=$type.' by <a href="'.$user_url.'">'.$user.'</a><br/>';
        	}else {
        		$cont.=$type.' by '.$user.'<br/>';
        	}
        }		
			$cont.="<br/></div>";
}
$oss_paging=opensearchserver_paging($result);
	foreach($oss_paging as $page)  {
		if($page['url']) {
			$cont.= '<span align="center" style="margin-left:5px;"><a href='.$page['url'].'>'.$page['label'].'</a></span>&nbsp;&nbsp;&nbsp;&nbsp;';
		}
		else {
			$cont.= '<span align="center" style="margin-left:5px;">'.$page['label'].'</span>&nbsp;&nbsp;&nbsp;&nbsp;';
		}
	}
	$cont.='<div align="right">';
	$cont.='<img src="http://www.open-search-server.com/images/oss_logo_62x60.png" /><br/>';
	$cont.='<a href="http://www.open-search-server.com/">Enterprise Search Made Yours</a>';
	$cont.='</div>';
	
$cont.='</tr> </table>';
}
return $cont;
unset($cont);
}
}

function opensearchserver_paging($result) {
		if ($result != NULL) {
			$ossPaging = new OssPaging($result, 'r', 'pa');
			$pagingArray = array();
			if (isset($ossPaging) && $ossPaging->getResultTotal() >= 1) {
				if ($ossPaging->getResultLow() > 0) {
					$style='oss_pager_first';
					$label = 'First';
					$url = $ossPaging->getPageBaseURI() . '1';
					$pagingArray[] = array('style' => $style, 'label' => $label, 'url' => $url);
				}
				if ($ossPaging->getResultPrev() < $ossPaging->getResultCurrentPage()) {
					$style='oss_pager_prev';
					$label = 'Previous';
					$url = $ossPaging->getPageBaseURI() . ($ossPaging->getResultPrev() + 1);
					$pagingArray[] = array('style' => $style, 'label' => $label, 'url' => $url);
				}
				for ($i = $ossPaging->getResultLow(); $i < $ossPaging->getResultHigh(); $i++) {
					$style='oss_pager_number';
					if ($i == $ossPaging->getResultCurrentPage()) {
						$label = $i + 1;
						$url = NULL;
					}
					else {
						$label = $i + 1;
						$url = $ossPaging->getPageBaseURI() . $label;
					}
					$pagingArray[] = array('style' => $style, 'label' => $label, 'url' => $url);
				}
				if ($ossPaging->getResultNext() > $ossPaging->getResultCurrentPage()) {
					$style='oss_pager_next';
					$label = 'Next';
					$url = $ossPaging->getPageBaseURI() . ($ossPaging->getResultNext() + 1);
					$pagingArray[] = array('style' => $style, 'label' => $label, 'url' => $url);
				}
			}
			return $pagingArray;
		}
	}
function getSearchResult($query) {
  global $wpdb;
  $table_name =$wpdb->prefix ."opensearchserver";
  if($query) {
    $result = $wpdb->get_results('SELECT * FROM '.$table_name);
    $start = isset($_REQUEST['pq']) ? $_REQUEST['pq'] : null;
    $start = isset($start) ? max(0, $start - 1) * 10 : 0;
    $escapechars = array('\\', '^', '~', ':', '(', ')', '{', '}', '[', ']' , '&&', '||', '!', '*', '?');
    foreach ($escapechars as $escchar) $query = str_replace($escchar, ' ', $query);
    $query = trim($query);
    $search = new OSSSearch($result[0]->serverurl, $result[0]->indexname, 10, $start);
    $search->credential($result[0]->username, $result[0]->key);
    $search->facet('type',1);
    $filter=isset($_REQUEST['fq']) ? $_REQUEST['fq'] : null;
    if($filter)
    {
      if($filter!='All')
      {
        $search->filter('type:'.$filter);
      }
    }
    $result = $search->query($query)->template('search')->execute(5);


  }
  return $result;
}
function opensearchserver_create_url_snippet($url, $end) {
  if (strlen($url)>$end) {
    $snippet_url=substr($url, 0, $end);
    return $snippet_url . '...';
  }
  else {
    return $url;
  }
}
?>