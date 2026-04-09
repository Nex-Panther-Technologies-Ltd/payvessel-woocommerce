<?php
/**
 * Uninstall Payvessel WooCommerce
 *
 * Removes all plugin data on uninstall
 *
 * @package Payvessel_WooCommerce
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('woocommerce_payvessel_settings');

// Optionally clear transients
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_payvessel_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_payvessel_%'");
