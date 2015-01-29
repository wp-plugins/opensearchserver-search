<?php
/*
 *  This file is part of OpenSearchServer PHP Client.
*
*  Copyright (C) 2008-2013 Emmanuel Keller / Jaeksoft
*
*  http://www.open-search-server.com
*
*  OpenSearchServer PHP Client is free software: you can redistribute it and/or modify
*  it under the terms of the GNU Lesser General Public License as published by
*  the Free Software Foundation, either version 3 of the License, or
*  (at your option) any later version.
*
*  OpenSearchServer PHP Client is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU Lesser General Public License for more details.
*
*  You should have received a copy of the GNU Lesser General Public License
*  along with OpenSearchServer PHP Client.  If not, see <http://www.gnu.org/licenses/>.
*/


/**
 * @file
 * Class to access OpenSearchServer API
 */

require_once(dirname(__FILE__).'/oss_abstract.class.php');

class OssSearchTemplate extends OssAbstract {

  const API_SEARCH_TEMPLATE='searchtemplate';
  const API_SEARCH_TEMPLATE_CREATE='create';
  const API_SEARCH_TEMPLATE_SETRETURNFIELD='setreturnfield';
  const API_SEARCH_TEMPLATE_SETSNIPPETFIELD='setsnippetfield';

  protected $query;
  protected $template;

  public function __construct($enginePath, $index = NULL, $login = NULL, $apiKey = NULL) {
    $this->init($enginePath, $index, $login, $apiKey);
  }

  /**
   * Create a query template
   * @param string $qtname Name of the template
   * @param string $qtquery Query
   * @param string $qtoperator Default operator
   * @param int $qtrows Number of rows
   * @param int $qtslop Phrase slop
   * @param string $qtlang Default language
   */
  public function createSearchTemplate($qtname, $qtquery = NULL, $qtoperator = NULL, $qtrows = NULL, $qtslop = NULL, $qtlang = NULL) {
    $params = array("qt.name" => $qtname);
    if ($qtquery) {
      $params['qt.query'] = $qtquery;
    }
    if ($qtoperator) {
      $params['qt.operator'] = $qtoperator;
    }
    if ($qtrows) {
      $params['qt.rows'] = $qtrows;
    }
    if ($qtslop) {
      $params['qt.slop'] = $qtslop;
    }
    if ($qtlang) {
      if (strlen($qtlang) == 2) {
        $qtlang = mb_strtoupper(OssAPI::getLanguage($qtlang));
      }
      $params['qt.lang'] = $qtlang;
    }
    $params['cmd'] = OssSearchTemplate::API_SEARCH_TEMPLATE_CREATE;
    $return = $this->queryServerXML(OssSearchTemplate::API_SEARCH_TEMPLATE, $params);
    return $return === FALSE ? FALSE : TRUE;
  }

  /**
   * Function to create spell check Query Template
   * @param string $qtname
   * @param string $qtquery
   * @param int $qtsuggestions
   * @param stringarray $qtfield
   * @param float $qtscore
   * @param string $qtlang
   * @param string $qtalgorithm LevensteinDistance, NGramDistance or JaroWinklerDistance
   */
  public function createSpellCheckTemplate($qtname, $qtquery = NULL, $qtsuggestions = NULL, $qtfield = NULL, $qtscore = NULL, $qtlang = NULL, $qtalgorithm = NULL) {
    $params = array("qt.name" => $qtname);
    $params['qt.type'] = 'SpellCheckRequest';
    if ($qtquery) {
      $params['qt.query'] = $qtquery;
    }
    if ($qtsuggestions) {
      $params['qt.suggestions'] = $qtsuggestions;
    }
    if ($qtfield) {
      $params['qt.field'] = $qtfield;
    }
    if ($qtscore) {
      $params['qt.score'] = $qtscore;
    }

    if ($qtlang) {
      $params['qt.lang'] = $qtlang;
    }
    if ($qtalgorithm) {
      $params['qt.algorithm'] = $qtalgorithm;
    }
    $params['cmd'] = OssSearchTemplate::API_SEARCH_TEMPLATE_CREATE;
    $return = $this->queryServerXML(OssSearchTemplate::API_SEARCH_TEMPLATE, $params);
    return $return === FALSE ? FALSE : TRUE;
  }

  public function setReturnField($qtname, $returnField) {
    $params = array("qt.name" => $qtname);
    $params['returnfield']=$returnField;
    $params['cmd'] = OssSearchTemplate::API_SEARCH_TEMPLATE_SETRETURNFIELD;
    $return = $this->queryServerXML(OssSearchTemplate::API_SEARCH_TEMPLATE, $params);
    return $return === FALSE ? FALSE : TRUE;
  }

  public function setSnippetField($qtname, $snippetField, $maxSnippetSize=NULL, $tag=NULL, $maxSnippetNo=NULL, $fragmenter=NULL) {
    $params = array("qt.name" => $qtname);
    if ($maxSnippetSize) {
      $params['qt.maxSnippetSize'] = $maxSnippetSize;
    }
    if ($tag) {
      $params['qt.tag']=$tag;
    }
    if ($maxSnippetNo) {
      $params['qt.maxSnippetNo'] = $maxSnippetNo;
    }
    if ($fragmenter) {
      $params['qt.fragmenter'] = $fragmenter;
    }
    $params['snippetfield'] = $snippetField;
    $params['cmd'] = OssSearchTemplate::API_SEARCH_TEMPLATE_SETSNIPPETFIELD;
    $return = $this->queryServerXML(OssSearchTemplate::API_SEARCH_TEMPLATE, $params);
    return $return === FALSE ? FALSE : TRUE;
  }

  public function createMoreLikeThisTemplate(
    $qtname, $qtquery = NULL, $qtLike = NULL, $qtAnalyzer = NULL, $qtLang = NULL, $qtMinwordlen = NULL,
    $qtMaxwordlen = NULL, $qtMindocfreq = NULL, $qtMintermfreq = NULL, $qtMaxqueryTerms = NULL,
    $qtMaxnumtokensparsed = NULL, $qtStopwords = NULL, $qtRows = NULL, $qtStart = NULL, $qtFields = NULL) {

    $params = array("qt.name" => $qtname);
    $params['qt.type'] = 'MoreLikeThisRequest';
    if ($qtquery) {
      $params['qt.query'] = $qtquery;
    }
    if ($qtLike) {
      $params['qt.like'] = $qtLike;
    }
    if ($qtAnalyzer) {
      $params['qt.analyzer'] = $qtAnalyzer;
    }
    if ($qtLang) {
      $params['qt.lang'] = $qtLang;
    }
    if ($qtMinwordlen) {
      $params['qt.minwordlen'] = $qtMinwordlen;
    }
    if ($qtMaxwordlen) {
      $params['qt.maxwordlen'] = $qtMaxwordlen;
    }
    if ($qtMindocfreq) {
      $params['qt.mindocfreq'] = $qtMindocfreq;
    }
    if ($qtMintermfreq) {
      $params['qt.mintermfreq'] = $qtMintermfreq;
    }
    if ($qtMaxqueryTerms) {
      $params['qt.maxqueryTerms'] = $qtMaxqueryTerms;
    }
    if ($qtMaxnumtokensparsed) {
      $params['qt.maxnumtokensparsed'] = $qtMaxnumtokensparsed;
    }
    if ($qtStopwords) {
      $params['qt.stopwords'] = $qtStopwords;
    }
    if ($qtRows) {
      $params['qt.rows'] = $qtRows;
    }
    if ($qtStart) {
      $params['qt.start'] = $qtStart;
    }
    if ($qtFields) {
      $params['qt.fields'] = $qtFields;
    }
    $params['cmd'] = OssSearchTemplate::API_SEARCH_TEMPLATE_CREATE;
    $return = $this->queryServerXML(OssSearchTemplate::API_SEARCH_TEMPLATE, $params);
    return $return === FALSE ? FALSE : TRUE;
  }
 

  public function get_search_template_list() {
    $path_parameters = array(
        "{index_name}" => $this->index
    );
    $path = strtr(OssApi::REST_API_SEARCH_TEMPLATE_LIST, $path_parameters);
    $return = $this->queryServerREST($path,NULL,NULL, OssApi::DEFAULT_CONNEXION_TIMEOUT, OssApi::DEFAULT_QUERY_TIMEOUT,'GET',2);
    return json_decode($return)->templates;
  }
}
?>