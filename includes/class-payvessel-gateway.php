<?php
/**
 * Payvessel Payment Gateway for WooCommerce
 *
 * @package Payvessel_WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Payvessel Payment Gateway Class
 */
class WC_Payvessel_Gateway extends WC_Payment_Gateway {

    /**
     * API Key
     *
     * @var string
     */
    public $api_key;

    /**
     * Secret Key (for webhook verification)
     *
     * @var string
     */
    public $secret_key;

    /**
     * Test mode
     *
     * @var bool
     */
    public $testmode;

    /**
     * Payment channels
     *
     * @var array
     */
    public $channels;

    /**
     * API Base URL
     *
     * @var string
     */
    private $api_base_url;

    /**
     * Checkout URL
     *
     * @var string
     */
    private $checkout_url = 'https://checkout.payvessel.com';

    /**
     * Constructor
     */
    public function __construct() {
        $this->id                 = 'payvessel';
        $this->icon               = PAYVESSEL_WC_PLUGIN_URL . 'assets/images/payvessel-logo.svg';
        $this->has_fields         = false;
        $this->method_title       = __('Payvessel', 'payvessel-woocommerce');
        $this->method_description = __('Accept payments via Bank Transfer and Card using Payvessel Payment Gateway.', 'payvessel-woocommerce');
        $this->supports           = array(
            'products',
            'refunds',
        );

        // Load the form fields
        $this->init_form_fields();

        // Load the settings
        $this->init_settings();

        // Define user set variables
        $this->title        = $this->get_option('title');
        $this->description  = $this->get_option('description');
        $this->enabled      = $this->get_option('enabled');
        $this->testmode     = 'yes' === $this->get_option('testmode');
        $this->api_key      = $this->testmode ? $this->get_option('test_api_key') : $this->get_option('live_api_key');
        $this->secret_key   = $this->testmode ? $this->get_option('test_secret_key') : $this->get_option('live_secret_key');
        $this->channels     = $this->get_option('channels', array('BANK_TRANSFER'));

        // Set API base URL based on mode
        $this->api_base_url = $this->testmode 
            ? 'https://sandbox.payvessel.com' 
            : 'https://api.payvessel.com';

        // Hooks
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_payvessel_callback', array($this, 'handle_callback'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));

        // Payment listener/API hook
        add_action('woocommerce_api_wc_payvessel_gateway', array($this, 'verify_transaction'));
    }

    /**
     * Initialize Gateway Settings Form Fields
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'       => __('Enable/Disable', 'payvessel-woocommerce'),
                'label'       => __('Enable Payvessel', 'payvessel-woocommerce'),
                'type'        => 'checkbox',
                'description' => __('Enable Payvessel as a payment option on the checkout page.', 'payvessel-woocommerce'),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'title' => array(
                'title'       => __('Title', 'payvessel-woocommerce'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'payvessel-woocommerce'),
                'default'     => __('Payvessel', 'payvessel-woocommerce'),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __('Description', 'payvessel-woocommerce'),
                'type'        => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'payvessel-woocommerce'),
                'default'     => __('Pay securely using Bank Transfer or Card via Payvessel.', 'payvessel-woocommerce'),
                'desc_tip'    => true,
            ),
            'testmode' => array(
                'title'       => __('Test Mode', 'payvessel-woocommerce'),
                'label'       => __('Enable Test Mode', 'payvessel-woocommerce'),
                'type'        => 'checkbox',
                'description' => __('Place the payment gateway in test mode using test API keys.', 'payvessel-woocommerce'),
                'default'     => 'yes',
                'desc_tip'    => true,
            ),
            'test_api_key' => array(
                'title'       => __('Test API Key', 'payvessel-woocommerce'),
                'type'        => 'text',
                'description' => __('Enter your Payvessel test API key (starts with PVTESTKEY-).', 'payvessel-woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'test_secret_key' => array(
                'title'       => __('Test Secret Key', 'payvessel-woocommerce'),
                'type'        => 'password',
                'description' => __('Enter your Payvessel test secret key for webhook verification.', 'payvessel-woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'live_api_key' => array(
                'title'       => __('Live API Key', 'payvessel-woocommerce'),
                'type'        => 'text',
                'description' => __('Enter your Payvessel live API key (starts with PVLIVEKEY-).', 'payvessel-woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'live_secret_key' => array(
                'title'       => __('Live Secret Key', 'payvessel-woocommerce'),
                'type'        => 'password',
                'description' => __('Enter your Payvessel live secret key for webhook verification.', 'payvessel-woocommerce'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'channels' => array(
                'title'       => __('Payment Channels', 'payvessel-woocommerce'),
                'type'        => 'multiselect',
                'class'       => 'wc-enhanced-select',
                'description' => __('Select the payment channels to enable.', 'payvessel-woocommerce'),
                'default'     => array('BANK_TRANSFER'),
                'desc_tip'    => true,
                'options'     => array(
                    'BANK_TRANSFER' => __('Bank Transfer', 'payvessel-woocommerce'),
                    'CARD'          => __('Card Payment', 'payvessel-woocommerce'),
                ),
            ),
            'payment_method' => array(
                'title'       => __('Payment Method', 'payvessel-woocommerce'),
                'type'        => 'select',
                'description' => __('Choose how the payment form is displayed.', 'payvessel-woocommerce'),
                'default'     => 'redirect',
                'desc_tip'    => true,
                'options'     => array(
                    'redirect' => __('Redirect - Redirect to Payvessel checkout page', 'payvessel-woocommerce'),
                    'popup'    => __('Popup - Display payment in a popup modal', 'payvessel-woocommerce'),
                ),
            ),
            'autocomplete_order' => array(
                'title'       => __('Autocomplete Order', 'payvessel-woocommerce'),
                'label'       => __('Autocomplete order after successful payment', 'payvessel-woocommerce'),
                'type'        => 'checkbox',
                'description' => __('If enabled, the order will automatically be set to "completed" after successful payment.', 'payvessel-woocommerce'),
                'default'     => 'no',
                'desc_tip'    => true,
            ),
            'webhook_url' => array(
                'title'       => __('Webhook URL', 'payvessel-woocommerce'),
                'type'        => 'title',
                'description' => sprintf(
                    __('Copy this URL and add it to your Payvessel dashboard webhook settings: %s', 'payvessel-woocommerce'),
                    '<br><code>' . home_url('/payvessel-webhook/') . '</code>'
                ),
            ),
        );
    }

    /**
     * Check if gateway is available
     */
    public function is_available() {
        if ('yes' !== $this->enabled) {
            return false;
        }

        if (!$this->api_key) {
            return false;
        }

        return true;
    }

    /**
     * Admin options
     */
    public function admin_options() {
        ?>
        <h2><?php esc_html_e('Payvessel Payment Gateway', 'payvessel-woocommerce'); ?></h2>
        <p>
            <?php esc_html_e('Accept payments via Bank Transfer and Card using Payvessel.', 'payvessel-woocommerce'); ?>
            <a href="https://dashboard.payvessel.com" target="_blank"><?php esc_html_e('Get your API keys', 'payvessel-woocommerce'); ?></a>
        </p>
        
        <?php if ($this->testmode) : ?>
            <div class="notice notice-warning inline">
                <p><?php esc_html_e('TEST MODE ENABLED. No real transactions will be processed.', 'payvessel-woocommerce'); ?></p>
            </div>
        <?php endif; ?>

        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table>
        <?php
    }

    /**
     * Process the payment
     *
     * @param int $order_id Order ID
     * @return array
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        // Initialize checkout via API
        $response = $this->initialize_checkout($order);

        if (is_wp_error($response)) {
            wc_add_notice($response->get_error_message(), 'error');
            return array(
                'result'   => 'fail',
                'redirect' => '',
            );
        }

        // Store access code in order meta
        $order->update_meta_data('_payvessel_access_code', $response['access_code']);
        $order->update_meta_data('_payvessel_reference', $response['reference']);
        $order->save();

        // Redirect to checkout page
        return array(
            'result'   => 'success',
            'redirect' => $this->checkout_url . '/' . $response['access_code'],
        );
    }

    /**
     * Initialize checkout via Payvessel API
     *
     * @param WC_Order $order Order object
     * @return array|WP_Error
     */
    private function initialize_checkout($order) {
        $body = array(
            'amount'                => $order->get_total(),
            'currency'              => $order->get_currency(),
            'customer_email'        => $order->get_billing_email(),
            'customer_name'         => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'customer_phone_number' => $order->get_billing_phone(),
            'reference'             => 'WC-' . $order->get_id() . '-' . time(),
            'channels'              => $this->channels,
            'redirect_url'          => $this->get_return_url($order),
            'metadata'              => array(
                'order_id'     => $order->get_id(),
                'order_key'    => $order->get_order_key(),
                'source'       => 'woocommerce',
                'plugin_version' => PAYVESSEL_WC_VERSION,
            ),
        );

        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'api-key'      => $this->api_key,
            ),
            'body'    => wp_json_encode($body),
            'timeout' => 60,
        );

        $response = wp_remote_post($this->api_base_url . '/pms/checkout/initialize/', $args);

        if (is_wp_error($response)) {
            return new WP_Error('payvessel_error', __('Unable to connect to Payvessel. Please try again.', 'payvessel-woocommerce'));
        }

        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        $response_code = wp_remote_retrieve_response_code($response);

        if ($response_code !== 200 && $response_code !== 201) {
            $error_message = isset($response_body['message']) 
                ? $response_body['message'] 
                : __('Unable to initialize payment. Please try again.', 'payvessel-woocommerce');
            return new WP_Error('payvessel_error', $error_message);
        }

        $data = isset($response_body['data']) ? $response_body['data'] : $response_body;

        if (empty($data['access_code'])) {
            return new WP_Error('payvessel_error', __('Invalid response from Payvessel.', 'payvessel-woocommerce'));
        }

        return array(
            'access_code' => $data['access_code'],
            'reference'   => isset($data['reference']) ? $data['reference'] : $body['reference'],
        );
    }

    /**
     * Verify transaction after redirect
     */
    public function verify_transaction() {
        if (!isset($_GET['reference'])) {
            wp_die(__('Invalid request', 'payvessel-woocommerce'));
        }

        $reference = sanitize_text_field($_GET['reference']);

        // Find order by reference
        $orders = wc_get_orders(array(
            'meta_key'   => '_payvessel_reference',
            'meta_value' => $reference,
            'limit'      => 1,
        ));

        if (empty($orders)) {
            wp_die(__('Order not found', 'payvessel-woocommerce'));
        }

        $order = $orders[0];

        // Verify with Payvessel API
        $verified = $this->verify_payment($reference, $order);

        if ($verified) {
            wp_redirect($this->get_return_url($order));
        } else {
            wp_redirect($order->get_checkout_payment_url());
        }
        exit;
    }

    /**
     * Verify payment with Payvessel API
     *
     * @param string   $reference Transaction reference
     * @param WC_Order $order     Order object
     * @return bool
     */
    public function verify_payment($reference, $order) {
        $args = array(
            'headers' => array(
                'api-key' => $this->api_key,
            ),
            'timeout' => 60,
        );

        $response = wp_remote_get($this->api_base_url . '/pms/transaction/verify/' . $reference . '/', $args);

        if (is_wp_error($response)) {
            $order->add_order_note(__('Payvessel: Unable to verify transaction.', 'payvessel-woocommerce'));
            return false;
        }

        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($response_body) || !isset($response_body['data'])) {
            $order->add_order_note(__('Payvessel: Invalid verification response.', 'payvessel-woocommerce'));
            return false;
        }

        $data = $response_body['data'];
        $status = isset($data['status']) ? strtolower($data['status']) : '';

        if ($status === 'successful' || $status === 'success') {
            // Verify amount
            $amount_paid = isset($data['amount']) ? floatval($data['amount']) : 0;
            $order_amount = floatval($order->get_total());

            if ($amount_paid >= $order_amount) {
                // Payment successful
                $order->payment_complete($reference);
                $order->add_order_note(
                    sprintf(
                        __('Payvessel payment successful. Transaction Reference: %s', 'payvessel-woocommerce'),
                        $reference
                    )
                );

                // Store transaction details
                $order->update_meta_data('_payvessel_transaction_id', isset($data['id']) ? $data['id'] : '');
                $order->update_meta_data('_payvessel_payment_method', isset($data['channel']) ? $data['channel'] : '');
                $order->save();

                // Empty cart
                WC()->cart->empty_cart();

                return true;
            } else {
                $order->update_status('on-hold', sprintf(
                    __('Payvessel: Amount mismatch. Paid: %s, Expected: %s', 'payvessel-woocommerce'),
                    $amount_paid,
                    $order_amount
                ));
                return false;
            }
        }

        $order->add_order_note(sprintf(
            __('Payvessel payment failed. Status: %s', 'payvessel-woocommerce'),
            $status
        ));

        return false;
    }

    /**
     * Process refund
     *
     * @param int    $order_id Order ID
     * @param float  $amount   Refund amount
     * @param string $reason   Refund reason
     * @return bool|WP_Error
     */
    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);

        if (!$order) {
            return new WP_Error('payvessel_refund_error', __('Order not found.', 'payvessel-woocommerce'));
        }

        $reference = $order->get_meta('_payvessel_reference');

        if (!$reference) {
            return new WP_Error('payvessel_refund_error', __('No Payvessel reference found for this order.', 'payvessel-woocommerce'));
        }

        // Note: Implement actual refund API call when Payvessel supports it
        $order->add_order_note(sprintf(
            __('Refund request for %s. Please process manually in Payvessel dashboard. Reason: %s', 'payvessel-woocommerce'),
            wc_price($amount),
            $reason
        ));

        return true;
    }

    /**
     * Receipt page
     *
     * @param int $order_id Order ID
     */
    public function receipt_page($order_id) {
        $order = wc_get_order($order_id);
        $access_code = $order->get_meta('_payvessel_access_code');

        if ($access_code) {
            echo '<p>' . __('Redirecting to payment page...', 'payvessel-woocommerce') . '</p>';
            echo '<script>window.location.href = "' . esc_url($this->checkout_url . '/' . $access_code) . '";</script>';
        }
    }
}
