<?php

class ExportService
{
    public static function downloadCsv($filename, $headers, $rows)
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // UTF-8 BOM for Microsoft Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Use semicolon delimiter for Indonesian Excel regional format
        fputcsv($output, $headers, ';');

        foreach ($rows as $row) {
            $sanitizedRow = array_map(function($val) {
                if (is_string($val) && strlen($val) > 0 && in_array($val[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
                    return "'" . $val;
                }
                return $val;
            }, (array)$row);
            fputcsv($output, $sanitizedRow, ';');
        }

        fclose($output);
        exit;
    }
}
