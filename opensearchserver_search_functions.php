<?php
function opensearchserver_getsearch_instance($rows, $start) {
  return new OssSearch(get_option('oss_serverurl'), get_option('oss_indexname'), $rows, $start,get_option('oss_login'), get_option('oss_key'));
}

function opensearchserver_getresult_instance($result) {
  if ($result == null) {
    return null;
  }
  return new OssResults($result);
}

function opensearchserver_getpaging_instance($result) {
  return new OssPaging($result, 'r', 'pa');
}

function opensearchserver_getspellcheck($result) {
  if ($result == null)
    return null;
  $spell_field = get_option('oss_spell').'Exact';
  return opensearchserver_getresult_instance($result)->getSpellSuggest($spell_field);
}

function is_multiple_filter_enabled() {
  return get_option('oss_multi_filter');
}

function opensearchserver_getpaging($result) {
  if ($result != NULL) {
    $ossPaging = opensearchserver_getpaging_instance($result);
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
      if($ossPaging->getResultCurrentPage()<$ossPaging->getResultTotal() && $ossPaging->getResultNext() > $ossPaging->getResultCurrentPage()) {
        $style='oss_pager_last';
        $label = 'Last';
        $url = $ossPaging->getPageBaseURI() . ($ossPaging->getResultTotal());
        $pagingArray[] = array('style' => $style, 'label' => $label, 'url' => $url);
      }
    }
    return $pagingArray;
  }
}
function get_multiple_filter_parameter() {
  $parameters = urldecode($_SERVER['QUERY_STRING']);
  $filters = array();
  foreach (explode('&', $parameters) as $param) {
    $filter = explode('=', $param);
    if($filter[0]=='fq') {
      array_push($filters, $filter[1]);
    }
  }
  return array_unique($filters);
}
function get_multiple_filter_all_parameter($facet) {
  $filters_string = '';
  $filters = get_multiple_filter_parameter();
  foreach($filters as $filter) {
    $fqn = explode(':', $filter);
    if($fqn!='' && $fqn[0]!=$facet) {
      $filters_string .= '&fq='.$filter;
    }

  }
  return $filters_string;
}
function search_filter_parameter($param) {
  $filters = get_multiple_filter_parameter();
  if(in_array($param, $filters)){
    return TRUE;
  }
  return FALSE;
}
function get_multiple_filter_parameter_string($current_key) {

  $filters_string = '';
  $filters = get_multiple_filter_parameter();
  foreach($filters as $filter) {
    $fqn = explode(':', $filter);
    if($fqn[0]!=$current_key){
      $filters_string .= '&fq='.$filter;
    }
  }
  return $filters_string;
}
function opensearchserver_getsearchresult($query, $spellcheck, $facet) {
  if($query) {
    $start = isset($_REQUEST['pa']) ? $_REQUEST['pa'] : NULL;
    $start = isset($start) ? max(0, $start - 1) * 10 : 0;
    $query = opensearchserver_clean_query($query);
    $search = opensearchserver_getsearch_instance(10, $start);
    if(!$spellcheck) {
      opensearchserver_add_facets_search($search);
      if($facet) {
        $filters = get_multiple_filter_parameter();
        if(count($filters)>0) {
          foreach ($filters as $filter){
            if($filter != 'All') {
              $filter_split = explode(':', $filter);
              $search->filter($filter_split[0]. ':"' .$filter_split[1]. '"');
            }
          }
        }
      }
      $result = $search->query($query)->template('search')->execute();
    }else {
      $result = $search->query($query)->template('spellcheck')->execute();
    }
    return $result;
  }

}

function opensearchserver_clean_query($query) {
  $escapechars = array('\\', '^', '~', ':', '(', ')', '{', '}', '[', ']' , '&', '||', '!', '*', '?','039;','\'','#');
  foreach ($escapechars as $escchar)  {
    $query = str_replace($escchar, ' ', $query);
  }
  $query = trim($query);
  return $query;
}

function opensearchserver_add_facets_search($search) {
  $facets = get_option('oss_facet');
  if (isset($facets) && $facets != null) {
    foreach ($facets as $facet) {
      $search->facet($facet, 1);
    }
  }
  return $search;
}

function opensearchserver_get_max($oss_results) {
  return ($oss_results->getResultStart() + $oss_results->getResultRows() > $oss_results->getResultFound()) ? $oss_results->getResultFound() : $oss_results->getResultStart() + $oss_results->getResultRows();
}

function opensearchserver_get_custom_fields() {
  return explode(",",get_option('oss_custom_field'));
}
?>