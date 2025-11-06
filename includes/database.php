<?php
/**
 * Database functions for Számlázz.hu invoice table
 * 
 * @package SzamlazzHuFluentCart
 */

namespace SzamlazzHuFluentCart;

// Exit if accessed directly
if (!\defined('ABSPATH')) {
    exit;
}

/**
 * Create database table on plugin activation
 */
function create_invoices_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'szamlazz_invoices';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        order_id bigint(20) NOT NULL,
        invoice_number varchar(255) NOT NULL,
        invoice_id varchar(255) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY order_id (order_id),
        KEY invoice_number (invoice_number)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Save invoice data to database
 * 
 * @param int $order_id The order ID
 * @param object $result The invoice generation result
 * @return int|false The number of rows inserted, or false on error
 */
function save_invoice($order_id, $result) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'szamlazz_invoices';
    
    return $wpdb->insert(
        $table_name,
        [
            'order_id' => $order_id,
            'invoice_number' => $result->getDocumentNumber(),
            'invoice_id' => $result->getDataObj()->invoiceId ?? null
        ],
        ['%d', '%s', '%s']
    );
}

/**
 * Get invoice record by order ID
 * 
 * @param int $order_id The order ID
 * @return object|null The invoice record or null if not found
 */
function get_invoice_by_order_id($order_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'szamlazz_invoices';
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE order_id = %d",
        $order_id
    ));
}
