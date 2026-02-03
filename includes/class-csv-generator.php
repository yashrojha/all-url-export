<?php
/**
 * CSV Generator Class
 *
 * @package All_URL_Export
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles CSV generation and download
 */
class AUE_CSV_Generator {
    
    /**
     * Export data
     */
    private $data;
    
    /**
     * Export options
     */
    private $options;
    
    /**
     * Constructor
     */
    public function __construct($data, $options) {
        $this->data = $data;
        $this->options = $options;
    }
    
    /**
     * Generate CSV content
     */
    public function generate() {
        if (empty($this->data)) {
            return '';
        }
        
        $output = fopen('php://temp', 'r+');
        
        // Add BOM for Excel UTF-8 compatibility
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        
        // Get headers from first row
        $headers = array_keys($this->data[0]);
        fputcsv($output, $headers);
        
        // Add data rows
        foreach ($this->data as $row) {
            $values = array();
            foreach ($headers as $header) {
                $values[] = isset($row[$header]) ? $row[$header] : '';
            }
            fputcsv($output, $values);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    /**
     * Download CSV file
     */
    public function download() {
        $csv = $this->generate();
        $filename = $this->generate_filename();
        
        // Set headers for download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($csv));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo $csv;
        exit;
    }
    
    /**
     * Generate filename
     */
    private function generate_filename() {
        $date = date('Y-m-d_H-i-s');
        
        return sprintf(
            'all-url-export-%s.csv',
            $date
        );
    }
    
    /**
     * Get CSV as array for preview
     */
    public function get_preview_data($limit = 50) {
        if (empty($this->data)) {
            return array(
                'headers' => array(),
                'rows' => array()
            );
        }
        
        $headers = array_keys($this->data[0]);
        $rows = array_slice($this->data, 0, $limit);
        
        // Truncate long content for preview
        foreach ($rows as &$row) {
            foreach ($row as $key => &$value) {
                if (strlen($value) > 200) {
                    $value = substr($value, 0, 200) . '...';
                }
            }
        }
        
        return array(
            'headers' => $headers,
            'rows' => $rows,
            'total' => count($this->data),
            'showing' => min($limit, count($this->data))
        );
    }
}