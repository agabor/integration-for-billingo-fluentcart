<?php
/**
 * API functions for communicating with Számlázz.hu
 */

namespace SzamlazzHuFluentCart;

// Exit if accessed directly
if (!\defined('ABSPATH')) {
    exit;
}

/**
 * Fetch invoice PDF from Számlázz.hu API using WordPress HTTP API
 * 
 * @param string $api_key Számlázz.hu API key
 * @param string $invoice_number Invoice number
 * @return array|\WP_Error Array with 'success' boolean and 'pdf_data' on success, or WP_Error on failure
 */
function fetch_invoice_pdf($api_key, $invoice_number) {
    // Build XML request for invoice PDF
    $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><xmlszamlapdf></xmlszamlapdf>');
    
    // Add authentication
    $beallitasok = $xml->addChild('beallitasok');
    $beallitasok->addChild('szamlaagentkulcs', $api_key);
    $beallitasok->addChild('szamlaLetoltes', 'true');
    
    // Add invoice number
    $fejlec = $xml->addChild('fejlec');
    $fejlec->addChild('szamlaszam', $invoice_number);
    
    $xml_string = $xml->asXML();
    
    // Send request to Számlázz.hu API using WordPress HTTP API
    $response = \wp_remote_post('https://www.szamlazz.hu/szamla/', array(
        'timeout' => 30,
        'headers' => array(
            'Content-Type' => 'text/xml; charset=UTF-8',
        ),
        'body' => $xml_string,
    ));
    
    // Check for HTTP errors
    if (\is_wp_error($response)) {
        return $response;
    }
    
    $response_code = \wp_remote_retrieve_response_code($response);
    $response_body = \wp_remote_retrieve_body($response);
    $response_headers = \wp_remote_retrieve_headers($response);
    
    // Check response code
    if ($response_code !== 200) {
        return new \WP_Error('api_error', 'API returned error code: ' . $response_code);
    }
    
    // Check if response is PDF or error message
    if (isset($response_headers['content-type']) && strpos($response_headers['content-type'], 'application/pdf') !== false) {
        // Success - got PDF
        return array(
            'success' => true,
            'pdf_data' => $response_body,
            'filename' => 'invoice_' . $invoice_number . '.pdf'
        );
    } else {
        // Error response (usually XML)
        return new \WP_Error('api_error', 'Failed to retrieve PDF: ' . substr($response_body, 0, 200));
    }
}
