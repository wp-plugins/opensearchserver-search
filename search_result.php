<?php
function get_search_result_output($query) {
  if($query==''){
    return;
  }
  $result=getSearchResult($query);
  if (isset($result) && $result instanceof SimpleXMLElement) {
    $ossResults = new OSSResults($result);
    if($ossResults->getResultFound()>0)    {
      $resultTime = (float)$result->result['time'] / 1000;
      $cont=$ossResults->getResultFound().' documents found ('.$resultTime.' seconds)';
      $max = ($ossResults->getResultStart() + $ossResults->getResultRows() > $ossResults->getResultFound()) ? $ossResults->getResultFound() : $ossResults->getResultStart() + $ossResults->getResultRows();
      $cont.='<br/><br/>';
      $cont .='<table border="0" style="border:none">
      <tr>
      <td width="120px" height="5%" style="border:none">
      <div>';
      $cont.='<a href="?s='.$query.'&fq=All">ALL</a><br/>';
      foreach ($ossResults->getFacet('type') as $values) {
        $value = $values['name'];
        $cont.='<a href="?s='.$query.'&fq='.$value.'">'.ucfirst($value).'('.$values.')'.'</a><br/>';
      }

      $cont .=' </div>        </td>
        <td rowspan="2" style="border:none">
            <div>';

      for ($i = $ossResults->getResultStart(); $i < $max; $i++) {
        $category	 = stripslashes($ossResults->getField($i, 'type', true));
	    $title	 = stripslashes($ossResults->getField($i, 'title', true));
        $content = stripslashes($ossResults->getField($i, 'content', true));
        $user = stripslashes($ossResults->getField($i, 'user_name', true));
        $user_url = stripslashes($ossResults->getField($i, 'user_url', true));
        $type = stripslashes($ossResults->getField($i, 'type', true));
        $url = stripslashes($ossResults->getField($i, 'url', false));
        if(!$title && !content && !url ) {
        	$cont .='No data found.';
        }else {
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
          		$cont.=$type.' by <a href="'.$user_url.'">'.$user.'</a><br/><br/>';
        	}else {
        		$cont.=$type.' by '.$user.'<br/><br/>';
        	}
        }
        else
        {$cont.="<br/><br/>";
        }
      }
      }
      $ossPaging = new OSSPaging($result, 'r', 'pq');
      $pagingArray = array();
      if (isset($ossPaging) && $ossPaging->getResultTotal() >= 1) {

        if ($ossPaging->getResultLow() > 0) {
          $label = 'First';
          $url = $ossPaging->getPageBaseURI().'1';
          $pagingArray[] = array('label' => $label, 'url' => $url);
        }
        if ($ossPaging->getResultPrev() < $ossPaging->getResultCurrentPage()) {
          $label = 'Previous';
          $url = $ossPaging->getPageBaseURI().($ossPaging->getResultPrev() + 1);
          $pagingArray[] = array('label' => $label, 'url' => $url);
        }
        for ($i = $ossPaging->getResultLow(); $i < $ossPaging->getResultHigh(); $i++) {
          if ($i == $ossPaging->getResultCurrentPage()) {
            $label = $i + 1;
            $url = null;
          } else {
            $label = $i + 1;
            $url = $ossPaging->getPageBaseURI().$label;
          }
          $pagingArray[] = array('label' => $label, 'url' => $url);

        }
        if ($ossPaging->getResultNext() > $ossPaging->getResultCurrentPage()) {
          $label = 'Next';
          $url = $ossPaging->getPageBaseURI().($ossPaging->getResultNext() + 1);
          $pagingArray[] = array('label' => $label, 'url' => $url);

        }
      }

      foreach($pagingArray as $page)
      {
      	if($page['url']) {
        	$cont.='<a href="'.$page['url'].'">'.$page['label'].'</a>'.'&nbsp;&nbsp;&nbsp;';
      	}else {
      		$cont.=$page['label'].'&nbsp;&nbsp;&nbsp;';
      	}
      }

      $cont.='<div align="right">';
      $cont.='<img src="http://www.open-search-server.com/images/oss_logo_62x60.png" /><br/>';
      $cont.='<a href="http://www.open-search-server.com/">Enterprise Search Made Yours</a>';
      $cont.='</div>';
    }
  }
  if($ossResults->getResultFound()<=0) {
    $cont ="No result found for keyword ".$query ;
    $cont.='<div align="right">';
    $cont.='<img src="http://www.open-search-server.com/images/oss_logo_62x60.png" /><br/>';
    $cont.='<a href="http://www.open-search-server.com/">Enterprise Search Made Yours</a>';
    $cont.='</div>';
  }
  $cont.='
               </div>      </td>
    </tr>
    <tr>
      <td style="border:none">&nbsp;</td>
    </tr>
</table>';


  return $cont;
  unset($cont);
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
?>