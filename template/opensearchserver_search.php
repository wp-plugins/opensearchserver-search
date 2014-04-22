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
                id="oss-keyword" style="width:70%;"
                onkeyup="return OpenSearchServer.autosuggest(event)"
                autocomplete="off" /> 
            <input type="submit" id="oss-submit" value="<?php _e("Search", 'opensearchserver'); ?>" />
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
        <?php 
        $facets = get_option('oss_facet');
        $facets_labels = get_option('oss_facets_labels');
        if (isset($facets) && $facets != null) {
        foreach ($facets as $facet) {
          if(!empty($facet)) {
          $facet_results = $oss_result_facets->getFacet($facet);
          ?>
        <div class="oss-filter-title">
            <?php
            if(!empty($facets_labels[$facet])) {
            	print $facets_labels[$facet];	
            } else {
	            $fields = opensearchserver_get_fields();
				if(isset($fields[$facet])) {
	            	print ucfirst($fields[$facet]);
				}else {
					print ucfirst($facet);
				}
            }
            ?>
        </div>
        <ul class="oss-nav">
            <li class="oss-top-nav"><a
                href="<?php print '?s='.urlencode($query).get_multiple_filter_all_parameter($facet);?>"><?php _e("All", 'opensearchserver'); ?></a>
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
            ?> <a class="<?php print $css_class;?>" href="<?php print $link; ?>"><?php print opensearchserver_get_facet_value($facet, $value); ?><?php if(get_option('oss_facet_display_count', 0) == 1) { print ' <span class="oss-facet-number-docs">('.(string)$values.')</span>'; }?>
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
         
        <p><?php _e("No documents containing all your search terms were found.", 'opensearchserver'); ?></p>
        <p><?php printf(__("Your searched keywords <b>'%s'</b> did not match any document.", 'opensearchserver'), $query); ?></p>
        <p><?php _e("Suggestions:"); ?></p>
        <ul>
            <li><?php _e("Make sure all words are spelled correctly.", 'opensearchserver'); ?></li>
            <li><?php _e("Try different keywords.", 'opensearchserver'); ?></li>
            <li><?php _e("Try more general keywords.", 'opensearchserver'); ?></li>
        </ul>
    </div>
    <?php
    }else {
        ?>

    <div id="oss-no-of-doc">
        <?php printf(__('%1$d documents found (in %2$s seconds).', 'opensearchserver'), $oss_results->getResultFound(), $oss_resultTime); ?>
        <div id="oss-did-you-mean">
            <?php 
            	if(isset($spellcheck_query)) { 
					$originalQueryText = '<a href="?s='.$query.'&sp=1"><b>'.$query.'</b></a>';		
            		printf(__('Showing results for <b>%1$s</b> search instead of %2$s.', 'opensearchserver'), $spellcheck_query, $originalQueryText);
            	}
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
			     if($url) :
			     ?>
                    <a href="<?php print $url;?>"><?php print $url;?> </a>
                <?php 
        		endif;
                if ($displayType || $displayUser || $displayCategory) {
                  print '<br/>';
                }
                print '<span class="oss-result-info">';
                //type, user, categories
        		if ( ($type && $displayType) && ($user && $displayUser) && ($categories != null && $categories != '' && $categories != 'Uncategorized' && $displayCategory)) {
                  printf(__('%1$s by %2$s in %3$s', 'opensearchserver'), $type, $user, $categories);
                //type and user
        		} elseif ( ($type && $displayType) && ($user && $displayUser)) {
                  printf(__('%1$s by %2$s', 'opensearchserver'), $type, $user);
                //type and categories
        		} elseif ( ($type && $displayType) && ($categories != null && $categories != '' && $categories != 'Uncategorized' && $displayCategory)) {
                  printf(__('%1$s in %2$s', 'opensearchserver'), $type, $categories);
                //user and categories
        		} elseif ( ($user && $displayUser) && ($categories != null && $categories != '' && $categories != 'Uncategorized' && $displayCategory)) {
                  printf(__('by %1$s in %2$s', 'opensearchserver'), $user, $categories);
                //type only
        		} elseif ( ($type && $displayType)) {
                  printf(__('type: %1$s', 'opensearchserver'), $type);
                //user only
        		}  elseif ( ($user && $displayUser)) {
                  printf(__('by %1$s', 'opensearchserver'), $user);
                //categories only
        		}   elseif ( ($categories != null && $categories != '' && $categories != 'Uncategorized' && $displayCategory)) {
                  printf(__('categories: %1$s', 'opensearchserver'), $categories);
        		} 
        		print '</span>';
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