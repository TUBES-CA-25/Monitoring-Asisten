<?php
class CsvExporter {
 public static function download($filename,$header,$rows){
  while(ob_get_level()){ob_end_clean();}
  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="'.$filename.'"');
  $out=fopen('php://output','w');
  fwrite($out,"\xEF\xBB\xBF");
  fputcsv($out,$header,';');
  foreach($rows as $r){
   $sanitized = array_map(function($val) {
    if (is_string($val) && strlen($val) > 0 && in_array($val[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
     return "'" . $val;
    }
    return $val;
   }, (array)$r);
   fputcsv($out,$sanitized,';');
  }
  fclose($out); exit;
 }
}
