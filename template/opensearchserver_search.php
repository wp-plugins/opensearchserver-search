<?php
/*
 *Template Name: OpenSearchServer Search Page
*/
get_header(); ?>
<section id="primary" class="content-area">
<div id="content" class="site-content" role="main">
    <div id="oss-search-form">
        <form method="get" id="oss-searchform" action=<?php print home_url('/');?> >
            <input type="text" value="<?php print get_search_query();?>" name="s"
                id="oss-keyword" size="55"
                onkeyup="return OpenSearchServer.autosuggest(event)"
                autocomplete="off" /> 
            <input type="submit" id="oss-submit" value="Search" />
            <div style="position: absolute">
                <div id="oss-autocomplete"></div>
            </div>
        </form>
    </div>
            
    <?php
    $displayUser = get_option('oss_display_user') == 1;
    $displayCategory = get_option('oss_display_category') == 1;
    $displayType = get_option('oss_display_type') == 1;
    $query_fq_parm = isset($_REQUEST['fq']) ? $_REQUEST['fq'] : NULL;
    $query = get_search_query();
    $oss_result = opensearchserver_getsearchresult($query, FALSE, TRUE);
    $oss_result_facet = is_separate_filter_query() ? opensearchserver_getsearchresult($query, FALSE, FALSE) : $oss_result;
    $oss_sp = isset($_REQUEST['sp']) ? $_REQUEST['sp'] :NULL;
    if (isset($oss_result) && $oss_result instanceof SimpleXMLElement && isset($oss_result_facet) && $oss_result_facet instanceof SimpleXMLElement) {
      $oss_results = opensearchserver_getresult_instance($oss_result);
      $oss_result_facets = opensearchserver_getresult_instance($oss_result_facet);
      if($oss_results->getResultFound() <= 0 && $oss_sp != 1 && get_option('oss_spell')!='none') {
        $oss_spell_result = opensearchserver_getsearchresult($query, TRUE,NULL);
        $spellcheck_query = opensearchserver_getspellcheck($oss_spell_result);
        $oss_result =  opensearchserver_getsearchresult($spellcheck_query, FALSE, TRUE);
        if (isset($oss_result) && $oss_result instanceof SimpleXMLElement && isset($oss_result_facet) && $oss_result_facet instanceof SimpleXMLElement) {
          $oss_results = opensearchserver_getresult_instance($oss_result);
          $oss_result_facet = is_separate_filter_query() ? opensearchserver_getsearchresult($spellcheck_query, FALSE, FALSE) : $oss_result;
          $oss_result_facets = opensearchserver_getresult_instance($oss_result_facet);
        }
      }
      $oss_resultTime = isset($oss_result) ? (float)$oss_result->result['time'] / 1000 : NULL;
      $max = opensearchserver_get_max($oss_results);
      ?>
    <div class="oss-search">
    <?php if($oss_result_facets->getResultFound()>0) {?>
    <div id="oss-filter">
        <?php $facets = get_option('oss_facet');
        if (isset($facets) && $facets != null) {
        foreach ($facets as $facet) {
          if(!empty($facet)) {
          $facet_results = $oss_result_facets->getFacet($facet);
          ?>
        <div class="oss-filter-title">
            <?php
            $fields = opensearchserver_get_fields();
			if(isset($fields[$facet])) {
            	print ucfirst($fields[$facet]);
			}else {
				print ucfirst($facet);
			}
            ?>
        </div>
        <ul class="oss-nav">
            <li class="oss-top-nav"><a
                href="<?php print '?s='.urlencode($query).get_multiple_filter_all_parameter($facet);?>">All</a>
            </li>
            <?php
            if(count($facet_results) > 0 ) {
              foreach ($facet_results as $values) {
                $value = $values['name'];
                $fqParm = $facet. ':' .urlencode($value);
                $css_class = 'oss-link';
                $link = "?s=".$query;
                if(is_multiple_filter_enabled()) {
                    $link .= get_multiple_filter_parameter_string($facet,$value);
                }
                ?>
            <li><?php if (search_filter_parameter($fqParm)) {
              $css_class .= ' oss-bold';
            }else {
                $link .= '&fq='. $fqParm;
            }
            ?> <a class="<?php print $css_class;?>" href="<?php print $link; ?>"><?php print $value.'('.$values.')';?>
            </a>
            </li>
            <?php }
			}
            ?>
        </ul>
        <?php
    }
	}
}?>
    </div>
    <?php }?>
    <?php if($oss_sp == 1 || $oss_results->getResultFound() <= 0) {
      ?>
    <div>
        <p>No documents containing all your search terms were found.</p>
        <p>Your searched keywords <b><?php print "'    ".$query. " '";?> </b> did not match
        any document.</p>
        <p>Suggestions:</p>
        <ul>
            <li>Make sure all words are spelled correctly.</li>
            <li>Try different keywords.</li>
            <li>Try more general keywords.</li>
        </ul>
    </div>
    <?php
    }else {
        ?>

    <div id="oss-no-of-doc">
        <?php print $oss_results->getResultFound().' documents found ('.$oss_resultTime.' seconds)';
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
          $category  = stripslashes($oss_results->getField($i, 'type', TRUE));
          $title     = stripslashes($oss_results->getField($i, 'title', TRUE));
          $content = stripslashes($oss_results->getField($i, 'content', TRUE, TRUE));
          if ($content == null) {
            $content = stripslashes($oss_results->getField($i, 'contentPhonetic', TRUE, TRUE));
            if ($content == null) {
              $content = stripslashes($oss_results->getField($i, 'content', TRUE, FALSE));
            }
          }
          $user = stripslashes($oss_results->getField($i, 'user_name', TRUE));
          $user_url = stripslashes($oss_results->getField($i, 'user_url', TRUE));
          $type = stripslashes($oss_results->getField($i, 'type', TRUE));
          $url = stripslashes($oss_results->getField($i, 'url', FALSE));
          $categories = stripslashes($oss_results->getField($i, 'categories', FALSE));
          ?>

        <div class="oss-result">
            <?php
                if ($title) {?>
            <div class="oss-title">
                <a href="<?php print $url;?>"><?php print $title;?> </a><br />
            </div>
            <?php }else { ?>
            <a href="<?php print $url;?>"><?php print "Un-titled";?> </a><br />
            <?php }
            ?>
            <div class="oss-content">
                <?php if ($content) {
                  print $content.'<br/>';
                }
                $custom_fields_array = opensearchserver_get_custom_fields();
                foreach($custom_fields_array as $field) {
                  $value = stripslashes($oss_results->getField($i, "custom_".opensearchserver_clean_field($field), FALSE));
                  if($value) {
                    print '<b>'. $field.'</b> : '.$value.'<br/>';
                  }
                }
                ?>
            </div>
            <div class="oss-url">
                <?php
     if($url) {?>
                <a href="<?php print $url;?>"><?php print $url;?> </a>
                <?php }
                if ($displayType || $displayUser || $displayCategory) {
                  print '<br/>';
                }
                if ($type && $displayType) {
                  print $type;
                }
                if ($user && $displayUser) {
                  print ' by '.$user;
                }
                if ($categories != null && $categories != '' && $displayCategory) {
                  print ' in '.$categories;
                }
                ?>
            </div>
        </div>
        <?php
        }
        ?>
    </div>
    <?php $oss_paging = opensearchserver_getpaging($oss_result);

    if($oss_paging) { ?>
    <div id="oss-paging">
        <?php   foreach($oss_paging as $page)  {
                if($page['url']) {?>
        <a id="oss-page" href="<?php print $page['url']; ?>"><?php print $page['label']; ?>
        </a>
        <?php }
        else {
          print '<span id="oss-page">'.$page['label'].'</span>';
        }
            } ?>
    </div>
    <?php }
      }
    ?>
</div><!-- #content -->
</section><!-- #primary -->
<?php
get_sidebar( 'content' );
get_sidebar();
get_footer();
exit();