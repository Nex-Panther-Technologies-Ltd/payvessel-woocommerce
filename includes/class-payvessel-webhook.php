<?php
/**
 * Payvessel Webhook Handler
 *
 * Handles webhook notifications from Payvessel
 *
 * @package Payvessel_WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Payvessel Webhook Class
 */
class Payvessel_Webhook {

    /**
     * Secret key for verification
     *
     * @var string
     */
    private $secret_key;

    /**
     * Gateway settings
     *
     * @var array
     */
    private $settings;

    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = get_option('woocommerce_payvessel_settings', array());
        $testmode = isset($this->settings['testmode']) && $this->settings['testmode'] === 'yes';
        
        $this->secret_key = $testmode 
            ? (isset($this->settings['test_secret_key']) ? $this->settings['test_secret_key'] : '')
            : (isset($this->settings['live_secret_key']) ? $this->settings['live_secret_key'] : '');
    }

    /**
     * Handle incoming webhook
     */
    public function handle() {
        // Log webhook receipt
        $this->log('Webhook received');

        // Get the request body
        $body = file_get_contents('php://input');

        if (empty($body)) {
            $this->log('Empty webhook body');
            $this->send_response(400, 'Empty request body');
            return;
        }

        // Verify webhook signature
        if (!$this->verify_signature($body)) {
            $this->log('Invalid webhook signature');
            $this->send_response(401, 'Invalid signature');
            return;
        }

        // Parse the payload
        $payload = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log('Invalid JSON payload');
            $this->send_response(400, 'Invalid JSON');
            return;
        }

        $this->log('Webhook payload: ' . print_r($payload, true));

        // Get event type
        $event = isset($payload['event']) ? $payload['event'] : '';
        $data = isset($payload['data']) ? $payload['data'] : $payload;

        // Handle different event types
        switch ($event) {
            case 'charge.success':
            case 'payment.success':
            case 'transaction.successful':
                $this->handle_successful_payment($data);
                break;

            case 'charge.failed':
            case 'payment.failed':
            case 'transaction.failed':
                $this->handle_failed_payment($data);
                break;

            case 'refund.processed':
            case 'refund.success':
                $this->handle_refund($data);
                break;

            default:
                // Try to determine status from data
                if (isset($data['status'])) {
                    $status = strtolower($data['status']);
                    if (in_array($status, array('successful', 'success', 'completed'))) {
                        $this->handle_successful_payment($data);
                    } elseif (in_array($status, array('failed', 'declined', 'cancelled'))) {
                        $this->handle_failed_payment($data);
                    } else {
                        $this->log('Unknown event/status: ' . $event . ' / ' . $status);
                    }
                } else {
                    $this->log('Unknown event type: ' . $event);
                }
        }

        $this->send_response(200, 'Webhook processed');
    }

    /**
     * Verify webhook signature
     *
     * @param string $body Raw request body
     * @return bool
     */
    private function verify_signature($body) {
        // Get signature from headers
        $signature = $this->get_signature_header();

        if (empty($signature)) {
            // If no signature header and no secret key set, allow (for testing)
            if (empty($this->secret_key)) {
                $this->log('Warning: No webhook signature verification (secret key not set)');
                return true;
            }
            return false;
        }

        if (empty($this->secret_key)) {
            $this->log('Warning: Secret key not configured');
            return true; // Allow if secret key not set
        }

        // Calculate expected signature
        $expected_signature = hash_hmac('sha512', $body, $this->secret_key);

        // Compare signatures
        $is_valid = hash_equals($expected_signature, $signature);

        if (!$is_valid) {
            $this->log('Signature mismatch. Expected: ' . substr($expected_signature, 0, 20) . '... Got: ' . substr($signature, 0, 20) . '...');
        }

        return $is_valid;
    }

    /**
     * Get signature from request headers
     *
     * @return string
     */
    private function get_signature_header() {
        // Check various header formats
        $headers = array(
            'HTTP_X_PAYVESSEL_SIGNATURE',
            'HTTP_PAYVESSEL_SIGNATURE',
            'HTTP_X_SIGNATURE',
            'X-Payvessel-Signature',
            'Payvessel-Signature',
        );

        foreach ($headers as $header) {
            if (isset($_SERVER[$header])) {
                return sanitize_text_field($_SERVER[$header]);
            }
        }

        // Try getallheaders() if available
        if (function_exists('getallheaders')) {
            $all_headers = getallheaders();
            $header_keys = array(
                'X-Payvessel-Signature',
                'Payvessel-Signature',
                'X-Signature',
            );

            foreach ($header_keys as $key) {
                if (isset($all_headers[$key])) {
                    return $all_headers[$key];
                }
            }
        }

        return '';
    }

    /**
     * Handle successful payment
     *
     * @param array $data Payment data
     */
    private function handle_successful_payment($data) {
        $order = $this->get_order_from_data($data);

        if (!$order) {
            $this->log('Order not found for successful payment');
            return;
        }

        // Check if order is already completed
        if ($order->is_paid()) {
            $this->log('Order ' . $order->get_id() . ' already paid');
            return;
        }

        // Get reference
        $reference = $this->get_reference_from_data($data);
        $amount = isset($data['amount']) ? floatval($data['amount']) : 0;
        $order_amount = floatval($order->get_total());

        // Verify amount
        if ($amount > 0 && $amount < $order_amount) {
            $order->update_status('on-hold', sprintf(
                __('Payvessel Webhook: Partial payment received. Paid: %s, Expected: %s. Reference: %s', 'payvessel-woocommerce'),
                wc_price($amount),
                wc_price($order_amount),
                $reference
            ));
            $this->log('Partial payment for order ' . $order->get_id());
            return;
        }

        // Mark payment complete
        $order->payment_complete($reference);
        $order->add_order_note(sprintf(
            __('Payvessel Webhook: Payment successful. Reference: %s', 'payvessel-woocommerce'),
            $reference
        ));

        // Store additional transaction data
        if (isset($data['id'])) {
            $order->update_meta_data('_payvessel_transaction_id', sanitize_text_field($data['id']));
        }
        if (isset($data['channel'])) {
            $order->update_meta_data('_payvessel_payment_channel', sanitize_text_field($data['channel']));
        }
        if (isset($data['paid_at'])) {
            $order->update_meta_data('_payvessel_paid_at', sanitize_text_field($data['paid_at']));
        }
        
        $order->save();

        $this->log('Payment successful for order ' . $order->get_id());
    }

    /**
     * Handle failed payment
     *
     * @param array $data Payment data
     */
    private function handle_failed_payment($data) {
        $order = $this->get_order_from_data($data);

        if (!$order) {
            $this->log('Order not found for failed payment');
            return;
        }

        // Don't update if already processing or completed
        if ($order->is_paid() || $order->get_status() === 'processing') {
            $this->log('Order ' . $order->get_id() . ' already processed, skipping failed status');
            return;
        }

        $reference = $this->get_reference_from_data($data);
        $reason = isset($data['gateway_response']) ? $data['gateway_response'] : '';
        $reason = empty($reason) && isset($data['message']) ? $data['message'] : $reason;

        $order->update_status('failed', sprintf(
            __('Payvessel Webhook: Payment failed. Reference: %s. Reason: %s', 'payvessel-woocommerce'),
            $reference,
            $reason ? $reason : __('Unknown', 'payvessel-woocommerce')
        ));

        $this->log('Payment failed for order ' . $order->get_id());
    }

    /**
     * Handle refund webhook
     *
     * @param array $data Refund data
     */
    private function handle_refund($data) {
        $order = $this->get_order_from_data($data);

        if (!$order) {
            $this->log('Order not found for refund');
            return;
        }

        $amount = isset($data['amount']) ? floatval($data['amount']) : 0;
        $reference = $this->get_reference_from_data($data);

        $order->add_order_note(sprintf(
            __('Payvessel Webhook: Refund processed. Amount: %s. Reference: %s', 'payvessel-woocommerce'),
            wc_price($amount),
            $reference
        ));

        $this->log('Refund processed for order ' . $order->get_id());
    }

    /**
     * Get order from webhook data
     *
     * @param array $data Webhook data
     * @return WC_Order|false
     */
    private function get_order_from_data($data) {
        // Try to get order from metadata
        if (isset($data['metadata']['order_id'])) {
            $order = wc_get_order(intval($data['metadata']['order_id']));
            if ($order) {
                return $order;
            }
        }

        // Try to get from reference
        $reference = $this->get_reference_from_data($data);
        if ($reference) {
            // Try WC- prefix format
            if (strpos($reference, 'WC-') === 0) {
                $parts = explode('-', $reference);
                if (isset($parts[1])) {
                    $order = wc_get_order(intval($parts[1]));
                    if ($order) {
                        return $order;
                    }
                }
            }

            // Search by meta
            $orders = wc_get_orders(array(
                'meta_key'   => '_payvessel_reference',
                'meta_value' => $reference,
                'limit'      => 1,
            ));

            if (!empty($orders)) {
                return $orders[0];
            }

            // Search by access code
            $orders = wc_get_orders(array(
                'meta_key'   => '_payvessel_access_code',
                'meta_value' => $reference,
                'limit'      => 1,
            ));

            if (!empty($orders)) {
                return $orders[0];
            }
        }

        // Try order_key from metadata
        if (isset($data['metadata']['order_key'])) {
            $order_id = wc_get_order_id_by_order_key($data['metadata']['order_key']);
            if ($order_id) {
                return wc_get_order($order_id);
            }
        }

        return false;
    }

    /**
     * Get reference from webhook data
     *
     * @param array $data Webhook data
     * @return string
     */
    private function get_reference_from_data($data) {
        $reference_keys = array('reference', 'transaction_reference', 'txn_ref', 'access_code', 'id');

        foreach ($reference_keys as $key) {
            if (isset($data[$key]) && !empty($data[$key])) {
                return sanitize_text_field($data[$key]);
            }
        }

        return '';
    }

    /**
     * Send HTTP response
     *
     * @param int    $code    HTTP status code
     * @param string $message Response message
     */
    private function send_response($code, $message) {
        status_header($code);
        header('Content-Type: application/json');
        echo wp_json_encode(array(
            'status'  => $code === 200 ? 'success' : 'error',
            'message' => $message,
        ));
    }

    /**
     * Log message
     *
     * @param string $message Log message
     */
    private function log($message) {
        if (class_exists('WC_Logger')) {
            $logger = wc_get_logger();
            $logger->info($message, array('source' => 'payvessel-webhook'));
        }
    }
}
