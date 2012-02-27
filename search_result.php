<?php
function opensearchserver_paging($result) {
		if ($result != NULL) {
			$ossPaging = new OssPaging($result, 'r', 'pq');
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