<?php
ini_set('default_charset', 'UTF-8');
ini_set('display_errors', '6');
header('Content-type: text/plain; charset=UTF-8');
$query = stripcslashes($_REQUEST['q']);
if (!isset($query) || strlen($query) == 0) return;
$escapechars = array('\\', '^', '~', ':', '(', ')', '{', '}', '[', ']' , '&&', '||', '!', '*', '?');
foreach ($escapechars as $escchar) $query = str_replace($escchar, ' ', $query);
$query = trim($query);
if (!isset($query) || strlen($query) == 0) return;
$search = opensearchserver_getautocomplete_instance();
$result = $search->autocomplete($query,10);
$result_array = explode("\n", $result);
$count = count($result_array)-1;
for($i=0;$i<$count;$i++) {
  echo $result_array[$i];
  echo "\r\n";
}

?>