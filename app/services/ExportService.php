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
            fputcsv($output, $row, ';');
        }

        fclose($output);
        exit;
    }
}
