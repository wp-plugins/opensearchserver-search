<?php

function opensearchserver_is_plugin_active($plugin_var) {
	$return_var = in_array( $plugin_var. '/' .$plugin_var. '.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
	$return_var2 = in_array( $plugin_var, apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
    return $return_var || $return_var2;
}

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
  $suggestions = opensearchserver_getresult_instance($result)->getSpellSuggestions($spell_field);
  $suggestionToReturn = '';
  $maxFreq = 0;
  foreach($suggestions as $suggestion) {
  	$arraySuggestion = (array)$suggestion;
  	if(!empty($arraySuggestion['@attributes']['freq']) && $arraySuggestion['@attributes']['freq'] > $maxFreq) {
  		$maxFreq = $arraySuggestion['@attributes']['freq'];
  		$suggestionToReturn = (string)$suggestion;
  	}
  }
  return $suggestionToReturn;
}

function is_multiple_filter_enabled() {
  return get_option('oss_multi_filter');
}

function is_separate_filter_query() {
  if(get_option('oss_facet_behavior') == 'separate_query'){
    return TRUE;
  }else {
    return FALSE;
  }
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
/*
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
  if(in_array(urldecode($param), $filters)){
    return TRUE;
  }
  return FALSE;
}
function get_multiple_filter_parameter_string($current_key, $current_value) {
  $current_value = urlencode($current_value);
  $filters_string = '';
  $filters = get_multiple_filter_parameter();
  foreach($filters as $filter) {
    $fqn = explode(':', $filter);
    if($fqn[0]!=$current_key){
      $filters_string .= '&fq='.$filter;
    }
    if($fqn[0]==$current_key && $fqn[1] == $current_value){
      $filters_string .= '&fq='.$filter;
    }
  }
  return $filters_string;
}
*/
function opensearchserver_getsearchresult($query, $spellcheck, $facet) {
  if($query) {
    $start = isset($_REQUEST['pa']) ? $_REQUEST['pa'] : NULL;
    $start = isset($start) ? max(0, $start - 1) * 10 : 0;
    $query = opensearchserver_clean_query($query);
    $search = opensearchserver_getsearch_instance(10, $start);
    if(!$spellcheck) {
      opensearchserver_add_facets_search($search);
      if($facet) {
      	$facets = opensearchserver_get_active_facets();
      	foreach($facets as $field => $value) {
			opensearchserver_add_filter($search, $field, $value);
      	}
      }
      $oss_query = $search->query($query)->template('search');
      if(get_query_var('sort')) {
      	if(get_query_var('sort') == '+date') {
      		$oss_query->sort('+timestamp');
      	} else {
      		$oss_query->sort('-timestamp');
      	}
      }
      
      if(get_option('oss_log_enable')) {
      	$oss_query->setLog(true);
      	if(get_option('oss_log_ip')) {
      		$oss_query->setCustomLog(1,$_SERVER['REMOTE_ADDR']);
      	}
      }
      
	  /*
	   * filter "oss_search"
	   */
	  $oss_query = apply_filters('oss_search', $oss_query);

	  return $oss_query->execute();
    }else {
      $result = $search->query($query)->template('spellcheck')->execute();
    }
    return $result;
  }
}

/**
 * Build an array with every facets values
 * @param unknown_type $query
 * @param unknown_type $oss_result OssResult for query with active facets
 * @param unknown_type $oss_result_facets OssResult for query without any active facets
 */
function opensearchserver_build_facets_to_display($query, $oss_result, $oss_result_facets) {
    //get array with, for each facet, what values would be without this facet active
	$hypotheticalFacets = opensearchserver_getsearchfacet_without_each_facet($query);
	
	//get all available facets for this query, without any active facet.
	//set every frequency to 0
	$allFacets = opensearchserver_build_facets_array($oss_result_facets, 0);
	
	//get real available facets for this query, with every active facets
	$realFacets = opensearchserver_build_facets_array($oss_result);
	
	// erase some values from $allFacets by those from $realFacets
	foreach($realFacets as $field => $values) {
		$allFacets[$field] = array_merge($allFacets[$field], $values);
	}

	// erase some values from $allFacets by those from $hypotheticalFacets
	foreach($hypotheticalFacets as $field => $values) {
		$allFacets[$field] = array_merge($allFacets[$field], $values);
	}
	$facetsFinal = $allFacets;
	
	return $facetsFinal;
}

/**
 * For each active facet run a new search without this facet to get what would 
 * be all values for this facet (for same searched keywords and with all other active facets)  
 **/
function opensearchserver_getsearchfacet_without_each_facet($query) {
	$start = isset($_REQUEST['pa']) ? $_REQUEST['pa'] : NULL;
    $start = isset($start) ? max(0, $start - 1) * 10 : 0;
    $query = opensearchserver_clean_query($query);
    $search = opensearchserver_getsearch_instance(10, $start);
    $oss_query = $search->query($query)->template('search');
      
	/*
	 * filter "oss_search_getsearchfacet_without_each_facet"
	 */
	$oss_query = apply_filters('oss_search_getsearchfacet_without_each_facet', $oss_query);
	
	$hypotheticalFacets = array();
	$facetsActive = opensearchserver_get_active_facets();
	//for each active facet:
	foreach($facetsActive as $field=>$facet) {
		$oss_search_hypothetical = clone $oss_query;
		//ask only for this facet
		$oss_search_hypothetical->facet($field, 1, TRUE);
		//filter on every active facet except the current one
		foreach($facetsActive as $facetField => $facetValue) {
			if($facetField != $field) {
				opensearchserver_add_filter($oss_search_hypothetical, $facetField, $facetValue);	
			}
		}
		//execute search
		try {
			$xmlResult = $oss_search_hypothetical->execute(60);
		} catch (\Exception $e) {
			echo 'An error happened. Message: '. $e->getMessage(); 
		}	
		
		$oss_result = opensearchserver_getresult_instance($xmlResult);
						
		$hypotheticalFacets = opensearchserver_build_facets_array($oss_result);
	}
	
	
	return $hypotheticalFacets;
}

/**
 * Build a proper array from $oss_result->getFacets()
 * @param OssResult $oss_result
 */
function opensearchserver_build_facets_array($oss_result, $force_count_value = null) {
	$facets = array();
	$finalFacets = array();
	foreach($oss_result->getFacets() as $facetName) {
		$facetName = (string)$facetName;
		foreach($oss_result->getFacet($facetName) as $facetDetails) {				
			$facetDetailsArray = (array)$facetDetails;
			$facets[$facetName][$facetDetailsArray['@attributes']['name']] = ($force_count_value !== null) ? $force_count_value : (string)$facetDetails;
		}
		if(!empty($facets[$facetName]))
		{
			$finalFacets[$facetName] = $facets[$facetName];	
		}
	}
	
	return $finalFacets;
}

/**
 * Add filter to an OssSearch instance.
 * Returns string used to filter.
 * @param unknown_type $oss_search
 * @param unknown_type $facetField field on which filter
 * @param unknown_type $facetValue value of the filter
 * @param unknown_type $join type of join. Default is OR
 */
function opensearchserver_add_filter($oss_search, $name, $filter, $join = 'OR') {
	if(is_array($filter)) {
		$filterString = $name . ':"' . implode('" '.$join.' '.$name.':"', $filter ) .'"';
	}
	else {
		$filterString = $name . ':"' . $filter .'"';
	}
	$oss_search->filter($filterString);
	return $filterString;
}

/**
 * Return value for each value of a facet: if original value is found in "custom values" for
 * this facet use it, otherwise return original value.
 * @param string $facet_field field of the facet
 * @param string $value original value
 */
function opensearchserver_get_facet_value($facet_field, $value) {
	$facets_values = get_option('oss_facets_values');
	if(empty($facets_values[$facet_field])) {
		return $value;
	}
	return (!empty($facets_values[$facet_field][(string)$value])) ? $facets_values[$facet_field][(string)$value] : $value;
}

function opensearchserver_clean_query($query) {
  $clean_query_options = get_option('oss_clean_query');
  $clean_query_enable = get_option('oss_clean_query_enable');
  if($clean_query_enable) {
		$escapechars = explode(' ', stripslashes($clean_query_options));
		$query = html_entity_decode($query, ENT_COMPAT);
	  	//defaults to a pre-configured list of escapechars
	  	if(empty($escapechars) || $escapechars[0] == '') {
		  	$escapechars = array('\\', '^', '~', '(', ')', '{', '}', '[', ']' , '&', '||', '!', '*', '?','039;','\'','#');
	  	}
	  	foreach ($escapechars as $escchar)  {
		    $query = str_replace($escchar, ' ', $query);
	  	}
	  	$query = trim($query);
  }
  return $query;
}


function opensearchserver_add_facets_search($search) {
  $facets = get_option('oss_facet');
  if (isset($facets) && $facets != null) {
    foreach ($facets as $facet) {
      if(!empty($facet)) {
      	$search->facet($facet, 1, TRUE);
      }
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

/**
 * Return ID of content in WP.
 * For example:
 *   - id (from OSS) = post_56
 *   - type = post
 *   ==> will return 56
 * @param unknown_type $id string
 * @param unknown_type $type string
 */
function opensearchserver_get_wp_id($id = null, $type = null) {
	if(!empty($id) && !empty($type)) {
		return str_replace($type.'_', '', $id);
	}
	return null;
}

/**
 * Build full URL to search and sort
 * @param string $sort Sort
 */
function opensearchserver_build_sort_url($sort = null) {
	$url = '?s=' . urlencode(get_search_query()) ;
	$facets = opensearchserver_get_active_facets();
	if(!empty($facets)) {
		$url .= '&'.opensearchserver_build_facets_url($facets);
	}
	if(!empty($sort)) {
		$url .= '&sort='.urlencode($sort);
	}
	return $url;
}

/**
 * 
 * Build URL part related to facets for one facet (= one field and one value).
 * Used when displaying each facet value.
 * 
 * @param string $facetField field
 * @param string $facetName value
 */
function opensearchserver_get_facet_url($facetField, $facetValue, $exclusiveFacet = false) {
	$facetsInUrl = opensearchserver_get_active_facets();
	return opensearchserver_build_facets_url(opensearchserver_merge_facets($facetsInUrl, $facetField, $facetValue, $exclusiveFacet));
}

/**
 * Remove from URL part related to the given facet.
 * Used to build the "All" link for each facet.
 * 
 * @param string $facetField field
 */
function opensearchserver_get_facet_url_without_one_facet($facetField) {
	$facets = opensearchserver_get_active_facets();
	if(!empty($facets[$facetField])) {
		unset($facets[$facetField]);
	}
	return opensearchserver_build_facets_url($facets);
}

/**
 * Remove from URL part related to the given facet, and more specifically for the given value, if one is given.
 * Used to build the link set on each active facet (one click will remove this active facet, like with a checkbox)
 * 
 * @param string $facetField field
 * @param string $facetValue optionnal - value
 */
function opensearchserver_get_facet_url_without_one_facet_value($facetName, $value) {
	$facets = opensearchserver_get_active_facets();

	if( $value == null || (!empty($facets[$facetName]) && !is_array($facets[$facetName]))) {
		unset($facets[$facetName]);	
	}	
	elseif (in_array($value, $facets[$facetName])) {
		unset($facets[$facetName][array_search($value, $facets[$facetName])]);
		//if there were previously several values for a facet but there is just one remaining, transform it back to a mere string
		if(sizeof($facets[$facetName]) == 1) {
			$facets[$facetName] = array_shift($facets[$facetName]);
		}
	}
	
	return opensearchserver_build_facets_url($facets);
}

/**
 * Return current facets from URL
 * Replace any URL slug by its real fieldname
 */
function opensearchserver_get_active_facets() {
	if(empty($_REQUEST['f'])) {
		return array();
	}
	$facetsFromUrl = $_REQUEST['f'];
	$facetsSlugs = get_option('oss_facets_slugs', array());
	
	/*
	 * filter "oss_facets_slugs"
	 * Facets slugs can be handled differently
	 */
	$facetsSlugs = apply_filters('oss_facets_slugs', $facetsSlugs);
	
	//build array from $facetsSlugs with slugs as keys and fieldnames as values
	$facetsSlugsReversed = array();
	foreach($facetsSlugs as $fieldname => $slug) {
		$facetsSlugsReversed[$slug] = $fieldname;
	}
	foreach($facetsFromUrl as $facetName => $value) {
		if(isset($facetsSlugsReversed[$facetName])) {
			unset($facetsFromUrl[$facetName]);
			$facetsFromUrl[$facetsSlugsReversed[$facetName]] = $value;
		}
	}
	$facetsWithFieldnamesAsKeys = $facetsFromUrl;
	return $facetsWithFieldnamesAsKeys;
}

/**
 * Build URL for facets
 * @param unknown_type $facetsWithFieldnamesAsKeys
 */
function opensearchserver_build_facets_url($facetsWithFieldnamesAsKeys) {
	return http_build_query(array('f' => opensearchserver_transform_facets_array($facetsWithFieldnamesAsKeys))); 
}

/**
 * Transform array of facets using fieldnames as key 
 * to an array using URL slug as key, if it exist, otherwise
 * still use fieldname
 */
function opensearchserver_transform_facets_array($facetsWithFieldnamesAsKeys) {
	$facets = get_option('oss_facet');
	$facetsSlugs = get_option('oss_facets_slugs');
	
	/*
	 * Facets slugs can be handled differently
	 */
	$facetsSlugs = apply_filters('oss_facets_slugs', $facetsSlugs);
	
	foreach($facetsWithFieldnamesAsKeys as $fieldname => $value) {
		if(isset($facetsSlugs[$fieldname])) {
			unset($facetsWithFieldnamesAsKeys[$fieldname]);
			$facetsWithFieldnamesAsKeys[$facetsSlugs[$fieldname]] = $value;
		}
	}
	$finalFacets = $facetsWithFieldnamesAsKeys;
	return $finalFacets;
}

/**
 * Tell if one particular facet (one field and one value) is currently active (= is currently in URL)
 * @param string $facetField field
 * @param string $facetName value
 */
function opensearchserver_is_facet_active($facetField, $facetName) {
	$facets = opensearchserver_get_active_facets();
	return (!empty($facets) && !empty($facets[$facetField]) && 
			(
			(!is_array($facets[$facetField]) && $facets[$facetField] == $facetName)
			||	
			(is_array($facets[$facetField]) && in_array($facetName, $facets[$facetField]))
			)
		);
}

/**
 * Complete function to work with facets. Allow several values for one facet.
 */
function opensearchserver_merge_facets($existingFilters, $facetName, $facetValue, $exclusiveFacet = false)
{
  if(                                                               // if this facet value already exists, exits
      !empty($existingFilters[$facetName])                                 
      &&    
      (
        (                                       
          is_array($existingFilters[$facetName])                    // there could be several filters set on the field
          && in_array($facetValue, $existingFilters[$facetName])    // and the given value could already be among those
        )
        ||  $existingFilters[$facetName] == $facetValue             // or there could be only one filter for this field
                                                                    // which could be the given one
      )
  ) {
    return $existingFilters;    
  }
                                                                     // given value is not already among the filters, 
                                                                     // it needs to be added
  if(!empty($existingFilters[$facetName]) && !$exclusiveFacet) {
    if(is_array($existingFilters[$facetName])) {                     //    if there is already several values for this
      $existingFilters[$facetName][] = $facetValue;                  //    field then add the given value in the array
    }
    else {                                                           //    if there is already one value for this field   
      $existingFilters[$facetName] =                                 //    transform it into an array and
              array($existingFilters[$facetName], $facetValue);      //    add the given value
    }
  }
  else {                                                             // otherwise if there is no value for this field yet
    $existingFilters[$facetName] = $facetValue;                      // simply add it          
  }
  return $existingFilters;      
}

/**
 * Tell if a facet is exclusive or no: does it allow multiple values to be selected or only one?
 * @param string $facet Name of field for this facet
 */
function opensearchserver_facet_is_exclusive($facet) {
	$facetsExclusive = get_option('oss_facets_exclusive');
	return (in_array($facet, $facetsExclusive));	
}
?>