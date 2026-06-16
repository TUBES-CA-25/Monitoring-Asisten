<?php
class CsvExporter {
 public static function download($filename,$header,$rows){
  while(ob_get_level()){ob_end_clean();}
  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="'.$filename.'"');
  $out=fopen('php://output','w');
  fwrite($out,"\xEF\xBB\xBF");
  fputcsv($out,$header,';');
  foreach($rows as $r){fputcsv($out,$r,';');}
  fclose($out); exit;
 }
}
