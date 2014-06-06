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
    $displayDate = get_option('oss_display_date') == 1;
    
    $query_fq_parm = isset($_REQUEST['fq']) ? $_REQUEST['fq'] : null;
    $query = get_search_query();
    $oss_result = opensearchserver_getsearchresult($query, false, true);
    $oss_result_facet = opensearchserver_getsearchresult($query, false, false);
    
    $oss_sp = isset($_REQUEST['sp']) ? $_REQUEST['sp'] : null;
    if (isset($oss_result) && $oss_result instanceof SimpleXMLElement && isset($oss_result_facet) && $oss_result_facet instanceof SimpleXMLElement) {
      $oss_results = opensearchserver_getresult_instance($oss_result);
      $oss_result_facets = opensearchserver_getresult_instance($oss_result_facet);
      //if first query does not return results try a spellcheck query to get a new suggestion
      if($oss_results->getResultFound() <= 0 && $oss_sp != 1 && get_option('oss_spell')!='none') {
        $oss_spell_result = opensearchserver_getsearchresult($query, true, null);
        $first_query = $query;
        $query = $spellcheck_query = opensearchserver_getspellcheck($oss_spell_result);
        $oss_result = opensearchserver_getsearchresult($query, false, true);
        if (isset($oss_result) && $oss_result instanceof SimpleXMLElement && isset($oss_result_facet) && $oss_result_facet instanceof SimpleXMLElement) {
          $oss_results = opensearchserver_getresult_instance($oss_result);
          $oss_result_facet = opensearchserver_getsearchresult($query, false, false);
          $oss_result_facets = opensearchserver_getresult_instance($oss_result_facet);
        }
      }
      
      $oss_resultTime = isset($oss_result) ? (float)$oss_result->result['time'] / 1000 : null;
      $max = opensearchserver_get_max($oss_results);
      ?>
    <div class="oss-search">
    <?php if($oss_result_facets->getResultFound()>0) {?>
    <div id="oss-filter">
 
        <?php 
        
        $facets_labels = get_option('oss_facets_labels');
        $fields = opensearchserver_get_fields();
        
        //advanced facets
        if(get_option('oss_advanced_facets')) :
            $facets = opensearchserver_build_facets_to_display($query, $oss_results, $oss_result_facets);
	        foreach ($facets as $facet => $facet_results) :
	        ?>
               <div class="oss-filter-title">
                    <?php
                    if(!empty($facets_labels[$facet])) {
                        print $facets_labels[$facet];   
                    } else {
                        if(isset($fields[$facet])) {
                            print ucfirst($fields[$facet]);
                        } else {
                            print ucfirst($facet);
                        }
                    }
                    ?>
               </div>
               <ul class="oss-nav oss-nav-radio">
                    <li class="oss-top-nav">
                        <a href="<?php print '?s='.urlencode($query).'&'.opensearchserver_get_facet_url_without_one_facet($facet);?>">
                            <?php _e("All", 'opensearchserver'); ?>
                        </a>
                    </li>
                    <?php
                    if(count($facet_results) > 0 ) :
                      foreach ($facet_results as $value => $count) :
                        $css_class = 'oss-link';
                        $link = "?s=".$query.'&'. opensearchserver_get_facet_url($facet, $value, opensearchserver_facet_is_exclusive($facet))
                        ?>
                        <li>
                            <?php 
                            if (opensearchserver_is_facet_active($facet, $value)) {
                                $css_class .= ' oss-bold';
                                $link = "?s=".$query.'&'. opensearchserver_get_facet_url_without_one_facet_value($facet, $value);
                            }
                            ?>
                            <?php if($count > 0) : ?>
	                            <input onclick='window.location.href = "<?php print $link; ?>";' <?php if (opensearchserver_is_facet_active($facet, $value)) { print 'checked="checked"'; } ?> type="<?php if(opensearchserver_facet_is_exclusive($facet)) echo 'radio'; else echo 'checkbox'; ?>" id="<?php print urlencode($facet.'_'.$value); ?>"/>
	                            <label for="<?php print urlencode($facet.'_'.$value); ?>">
	                                <a class="opensearchserver_display_use_radio_buttons <?php echo $css_class; ?>" href="<?php print $link; ?>">
	                                    <?php print opensearchserver_get_facet_value($facet, $value); ?><?php if(get_option('oss_facet_display_count', 0) == 1) { print ' <span class="oss-facet-number-docs">('.$count.')</span>'; }?>
	                                </a>
	                            </label> 
                            <?php else: ?>
                                <input disabled="disabled" <?php if (opensearchserver_is_facet_active($facet, $value)) { print 'checked="checked"'; } ?> type="<?php if(opensearchserver_facet_is_exclusive($facet)) echo 'radio'; else echo 'checkbox'; ?>" id="<?php print urlencode($facet.'_'.$value); ?>"/>
                                <label for="<?php print urlencode($facet.'_'.$value); ?>" class="unavailable_facet">
                                    <?php print opensearchserver_get_facet_value($facet, $value); ?>
                                </label> 
                            <?php endif; ?>
                        </li>
                    <?php 
                        endforeach;
                    endif;
                    ?>
            </ul>
            <?php
    		endforeach;
    	?>
        
        <?php 
        	//standard facets
        	else: 
	        	$facets = opensearchserver_build_facets_array($oss_results);
		        foreach ($facets as $facet => $facet_results) :
		        ?>
               <div class="oss-filter-title">
                    <?php
                    if(!empty($facets_labels[$facet])) {
                        print $facets_labels[$facet];   
                    } else {
                        if(isset($fields[$facet])) {
                            print ucfirst($fields[$facet]);
                        } else {
                            print ucfirst($facet);
                        }
                    }
                    ?>
               </div>
               <ul class="oss-nav">
                    <li class="oss-top-nav">
                        <a href="<?php print '?s='.urlencode($query).'&'.opensearchserver_get_facet_url_without_one_facet($facet);?>">
                            <?php _e("All", 'opensearchserver'); ?>
                        </a>
                    </li>
                    <?php
                    if(count($facet_results) > 0 ) :
                      foreach ($facet_results as $value => $count) :
                        $css_class = 'oss-link';
                        $link = "?s=".$query.'&'. opensearchserver_get_facet_url($facet, $value)
                        ?>
                        <li>
                            <?php 
                            if (opensearchserver_is_facet_active($facet, $value)) {
                                $css_class .= ' oss-bold';
                            }
                            ?>
                            <label for="<?php print urlencode($facet.'_'.$value); ?>">
                                <a class="<?php echo $css_class; ?>" href="<?php print $link; ?>">
                                    <?php print opensearchserver_get_facet_value($facet, $value); ?><?php if(get_option('oss_facet_display_count', 0) == 1) { print ' <span class="oss-facet-number-docs">('.$count.')</span>'; }?>
                                </a>
                            </label> 
                        </li>
                    <?php 
                        endforeach;
                    endif;
                    ?>
            </ul>
            <?php
    		endforeach;
	    	?>
        	
        <?php 
        	endif;
        ?>
    
    </div>
    <?php }?>
    <?php if($oss_sp == 1 || $oss_results->getResultFound() <= 0) {
      ?>
    <div>
         
        <p><?php _e("No documents containing all your search terms were found.", 'opensearchserver'); ?></p>
        <p><?php printf(__("Your searched keywords <b>'%s'</b> did not match any document.", 'opensearchserver'), $first_query); ?></p>
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
    <div id="oss-no-of-doc" class="oss-<?php echo $oss_results->getResultFound() ?>-results">
        <?php printf(__('%1$d documents found (in %2$s seconds).', 'opensearchserver'), $oss_results->getResultFound(), $oss_resultTime); ?>
        <?php if(get_option('oss_sort_timestamp') == 1 ) :?>
            <div id="oss-sort">
	            <span class="oss-sort-label"><?php _e('Sort results by: ', 'opensearchserver'); ?>
	            <a class="<?php if(!get_query_var('sort')) { print 'oss-bold'; } ?>" href="<?php print opensearchserver_build_sort_url(''); ?>"><?php _e('relevancy', 'opensearchserver'); ?></a> -
	            <a class="<?php if(get_query_var('sort') == '-date') { print 'oss-bold'; } ?>" href="<?php print opensearchserver_build_sort_url('-date'); ?>"><?php _e('date (desc)', 'opensearchserver'); ?></a> -
	            <a class="<?php if(get_query_var('sort') == '+date') { print 'oss-bold'; } ?>" href="<?php print opensearchserver_build_sort_url('+date'); ?>"><?php _e('date (asc)', 'opensearchserver'); ?></a>
	        </div>
        <?php endif;?>
        <div id="oss-did-you-mean">
            <?php 
            	if(isset($spellcheck_query)) { 
					$originalQueryText = '<a href="?s='.$first_query.'&sp=1"><b>'.$first_query.'</b></a>';		
            		printf(__('Showing results for <b>%1$s</b> search instead of %2$s.', 'opensearchserver'), $spellcheck_query, $originalQueryText);
            	}
            ?>
        </div>
        <?php }?>
    </div>
    <div id="oss-results">
        <?php
        for ($i = $oss_results->getResultStart(); $i < $max; $i++) {
          $category  = stripslashes($oss_results->getField($i, 'type', true));
          $title     = stripslashes($oss_results->getField($i, 'title', true));
          $content = stripslashes($oss_results->getField($i, 'content', true, true));
          if ($content == null) {
            $content = stripslashes($oss_results->getField($i, 'contentPhonetic', true, true));
            if ($content == null) {
              $content = stripslashes($oss_results->getField($i, 'content', true, false));
            }
          }
          $user = stripslashes($oss_results->getField($i, 'user_name', true));
          $user_url = stripslashes($oss_results->getField($i, 'user_url', true));
          $type = stripslashes($oss_results->getField($i, 'type', true));
          $url = stripslashes($oss_results->getField($i, 'url', false));
          $date = stripslashes($oss_results->getField($i, 'timestamp', false));
          $categories = array();
          $taxonomy_field = get_option('oss_taxonomy_display');
          $taxonomy_data = array();
          $taxonomies = $oss_results->getField($i, 'taxonomy_'.$taxonomy_field, false, false, null, true);
          if(!is_array($taxonomies)) {
              $categories = (string)$taxonomies[0];
          }else {
            foreach ($taxonomies as $taxonomy) {
  			       $taxonomy_data[] = (string)$taxonomy;
  		      }
            $categories = implode(', ', $taxonomy_data);
         }
        
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
                  $value = stripslashes($oss_results->getField($i, "custom_".opensearchserver_clean_field($field), false));
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
                if ($displayDate || $displayType || $displayUser || $displayCategory) {
                  print '<br/>';
                }
                print '<span class="oss-result-info">';
        		
                if($displayDate) {
					print '<span class="entry-date"><time datetime="'.$date.'">'.date(get_option('date_format'), strtotime($date)).'<time>';
					if (($displayType && !empty($type)) || ($displayUser && !empty($user)) || ($displayCategory && !empty($categories))) {
						print ', ';
					}
					print '</span>';
        		}
        		
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
    <div class="clearer">&nbsp;</div>
    </div>
</div><!-- #content -->
</section><!-- #primary -->
<?php
get_sidebar( 'content' );
get_sidebar();
get_footer();
exit();