<?php
/**
 * Admin Transactions Page Template
 *
 * @package Payvessel_WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get gateway settings
$settings = get_option('woocommerce_payvessel_settings', array());
$testmode = isset($settings['testmode']) && $settings['testmode'] === 'yes';

// Get statistics
$total_orders = count(wc_get_orders(array(
    'payment_method' => 'payvessel',
    'limit'          => -1,
    'return'         => 'ids',
)));

$completed_orders = count(wc_get_orders(array(
    'payment_method' => 'payvessel',
    'status'         => array('completed', 'processing'),
    'limit'          => -1,
    'return'         => 'ids',
)));

$pending_orders = count(wc_get_orders(array(
    'payment_method' => 'payvessel',
    'status'         => array('pending', 'on-hold'),
    'limit'          => -1,
    'return'         => 'ids',
)));

$failed_orders = count(wc_get_orders(array(
    'payment_method' => 'payvessel',
    'status'         => array('failed', 'cancelled'),
    'limit'          => -1,
    'return'         => 'ids',
)));
?>

<div class="wrap payvessel-admin-wrap">
    <div class="payvessel-admin-header">
        <h1>
            <img src="<?php echo esc_url(PAYVESSEL_WC_PLUGIN_URL . 'assets/images/payvessel-logo.svg'); ?>" alt="Payvessel" style="height: 30px;">
            <?php esc_html_e('Payvessel Transactions', 'payvessel-woocommerce'); ?>
        </h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=payvessel')); ?>" class="button">
            <?php esc_html_e('Settings', 'payvessel-woocommerce'); ?>
        </a>
    </div>

    <?php if ($testmode) : ?>
        <div class="payvessel-test-mode-notice">
            <strong><?php esc_html_e('Test Mode Enabled', 'payvessel-woocommerce'); ?></strong> - 
            <?php esc_html_e('Transactions are using test credentials. No real payments are processed.', 'payvessel-woocommerce'); ?>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="payvessel-stats">
        <div class="payvessel-stat-card">
            <h3><?php esc_html_e('Total Transactions', 'payvessel-woocommerce'); ?></h3>
            <div class="value"><?php echo esc_html(number_format($total_orders)); ?></div>
        </div>
        <div class="payvessel-stat-card success">
            <h3><?php esc_html_e('Successful', 'payvessel-woocommerce'); ?></h3>
            <div class="value"><?php echo esc_html(number_format($completed_orders)); ?></div>
        </div>
        <div class="payvessel-stat-card pending">
            <h3><?php esc_html_e('Pending', 'payvessel-woocommerce'); ?></h3>
            <div class="value"><?php echo esc_html(number_format($pending_orders)); ?></div>
        </div>
        <div class="payvessel-stat-card failed">
            <h3><?php esc_html_e('Failed', 'payvessel-woocommerce'); ?></h3>
            <div class="value"><?php echo esc_html(number_format($failed_orders)); ?></div>
        </div>
    </div>

    <!-- Filters -->
    <div class="payvessel-filters">
        <select id="payvessel-status-filter">
            <option value=""><?php esc_html_e('All Statuses', 'payvessel-woocommerce'); ?></option>
            <option value="completed"><?php esc_html_e('Completed', 'payvessel-woocommerce'); ?></option>
            <option value="processing"><?php esc_html_e('Processing', 'payvessel-woocommerce'); ?></option>
            <option value="pending"><?php esc_html_e('Pending', 'payvessel-woocommerce'); ?></option>
            <option value="on-hold"><?php esc_html_e('On Hold', 'payvessel-woocommerce'); ?></option>
            <option value="failed"><?php esc_html_e('Failed', 'payvessel-woocommerce'); ?></option>
            <option value="cancelled"><?php esc_html_e('Cancelled', 'payvessel-woocommerce'); ?></option>
            <option value="refunded"><?php esc_html_e('Refunded', 'payvessel-woocommerce'); ?></option>
        </select>
        <button type="button" class="button payvessel-refresh-transactions">
            <span class="dashicons dashicons-update" style="margin-top: 3px;"></span>
            <?php esc_html_e('Refresh', 'payvessel-woocommerce'); ?>
        </button>
    </div>

    <!-- Transactions Table -->
    <table id="payvessel-transactions-table" class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Order', 'payvessel-woocommerce'); ?></th>
                <th><?php esc_html_e('Reference', 'payvessel-woocommerce'); ?></th>
                <th><?php esc_html_e('Amount', 'payvessel-woocommerce'); ?></th>
                <th><?php esc_html_e('Status', 'payvessel-woocommerce'); ?></th>
                <th><?php esc_html_e('Channel', 'payvessel-woocommerce'); ?></th>
                <th><?php esc_html_e('Customer', 'payvessel-woocommerce'); ?></th>
                <th><?php esc_html_e('Date', 'payvessel-woocommerce'); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="7" style="text-align: center;">
                    <?php esc_html_e('Loading transactions...', 'payvessel-woocommerce'); ?>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="payvessel-pagination"></div>

    <!-- Webhook Info -->
    <div class="card" style="margin-top: 30px; max-width: 600px;">
        <h3><?php esc_html_e('Webhook Configuration', 'payvessel-woocommerce'); ?></h3>
        <p><?php esc_html_e('Add this URL to your Payvessel dashboard to receive payment notifications:', 'payvessel-woocommerce'); ?></p>
        <div class="payvessel-webhook-url">
            <?php echo esc_url(home_url('/payvessel-webhook/')); ?>
            <button type="button" class="button button-small payvessel-copy-btn" onclick="navigator.clipboard.writeText('<?php echo esc_url(home_url('/payvessel-webhook/')); ?>')">
                <?php esc_html_e('Copy', 'payvessel-woocommerce'); ?>
            </button>
        </div>
        <p class="description">
            <?php esc_html_e('Alternative REST API endpoint:', 'payvessel-woocommerce'); ?>
            <code><?php echo esc_url(rest_url('payvessel/v1/webhook')); ?></code>
        </p>
    </div>
</div>
