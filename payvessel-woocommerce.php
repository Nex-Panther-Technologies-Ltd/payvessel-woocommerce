<?php
/**
 * Plugin Name: Payvessel – Payment Gateway for WooCommerce
 * Plugin URI: https://github.com/Nex-Panther-Technologies-Ltd/payvessel-woocommerce
 * Description: Accept payments via Bank Transfer and Card using Payvessel Payment Gateway. Supports WooCommerce Block Checkout, popup modal, and webhook notifications.
 * Version: 1.0.0
 * Author: Payvessel
 * Author URI: https://payvessel.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: payvessel-payment-gateway-for-woocommerce
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 9.0
 *
 * @package Payvessel_WooCommerce
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('PAYVESSEL_WC_VERSION', '1.0.0');
define('PAYVESSEL_WC_PLUGIN_FILE', __FILE__);
define('PAYVESSEL_WC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PAYVESSEL_WC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PAYVESSEL_WC_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Payvessel WooCommerce Class
 */
final class Payvessel_WooCommerce {

    /**
     * Single instance
     *
     * @var Payvessel_WooCommerce
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return Payvessel_WooCommerce
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Check WooCommerce on plugins_loaded
        add_action('plugins_loaded', array($this, 'init'), 11);

        // Activation/Deactivation hooks
        register_activation_hook(PAYVESSEL_WC_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(PAYVESSEL_WC_PLUGIN_FILE, array($this, 'deactivate'));

        // HPOS compatibility
        add_action('before_woocommerce_init', array($this, 'declare_hpos_compatibility'));

        // Block checkout compatibility
        add_action('woocommerce_blocks_loaded', array($this, 'register_block_support'));

        // Admin hooks
        add_filter('plugin_action_links_' . PAYVESSEL_WC_PLUGIN_BASENAME, array($this, 'plugin_action_links'));
        add_filter('plugin_row_meta', array($this, 'plugin_row_meta'), 10, 2);

        // Admin menu for transactions
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

        // AJAX handlers
        add_action('wp_ajax_payvessel_verify_payment', array($this, 'ajax_verify_payment'));
        add_action('wp_ajax_nopriv_payvessel_verify_payment', array($this, 'ajax_verify_payment'));
        add_action('wp_ajax_payvessel_get_transactions', array($this, 'ajax_get_transactions'));

        // Webhook endpoint
        add_action('init', array($this, 'register_webhook_endpoint'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_webhook'));

        // REST API
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    /**
     * Initialize plugin
     */
    public function init() {
        if (!$this->check_woocommerce()) {
            return;
        }

        // Load text domain
        load_plugin_textdomain('payvessel-woocommerce', false, dirname(PAYVESSEL_WC_PLUGIN_BASENAME) . '/languages');

        // Include required files
        $this->includes();

        // Add gateway to WooCommerce
        add_filter('woocommerce_payment_gateways', array($this, 'add_gateway'));
    }

    /**
     * Check if WooCommerce is active
     *
     * @return bool
     */
    private function check_woocommerce() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return false;
        }
        return true;
    }

    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="error">
            <p>
                <strong><?php esc_html_e('Payvessel Payment Gateway', 'payvessel-woocommerce'); ?></strong>
                <?php esc_html_e('requires WooCommerce to be installed and active.', 'payvessel-woocommerce'); ?>
                <a href="<?php echo esc_url(admin_url('plugin-install.php?s=woocommerce&tab=search&type=term')); ?>">
                    <?php esc_html_e('Install WooCommerce', 'payvessel-woocommerce'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Include required files
     */
    private function includes() {
        require_once PAYVESSEL_WC_PLUGIN_DIR . 'includes/class-payvessel-gateway.php';
        require_once PAYVESSEL_WC_PLUGIN_DIR . 'includes/class-payvessel-webhook.php';
        
        if (file_exists(PAYVESSEL_WC_PLUGIN_DIR . 'includes/class-payvessel-admin.php')) {
            require_once PAYVESSEL_WC_PLUGIN_DIR . 'includes/class-payvessel-admin.php';
        }
    }

    /**
     * Add gateway to WooCommerce
     *
     * @param array $gateways Existing gateways
     * @return array
     */
    public function add_gateway($gateways) {
        $gateways[] = 'WC_Payvessel_Gateway';
        return $gateways;
    }

    /**
     * Declare HPOS compatibility
     */
    public function declare_hpos_compatibility() {
        if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', PAYVESSEL_WC_PLUGIN_FILE, true);
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', PAYVESSEL_WC_PLUGIN_FILE, true);
        }
    }

    /**
     * Register WooCommerce Blocks support
     */
    public function register_block_support() {
        if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            return;
        }

        require_once PAYVESSEL_WC_PLUGIN_DIR . 'includes/class-payvessel-blocks.php';
        
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function($payment_method_registry) {
                $payment_method_registry->register(new WC_Payvessel_Blocks_Support());
            }
        );
    }

    /**
     * Plugin action links
     *
     * @param array $links Existing links
     * @return array
     */
    public function plugin_action_links($links) {
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=payvessel') . '">' . __('Settings', 'payvessel-woocommerce') . '</a>',
            '<a href="' . admin_url('admin.php?page=payvessel-transactions') . '">' . __('Transactions', 'payvessel-woocommerce') . '</a>',
        );
        return array_merge($plugin_links, $links);
    }

    /**
     * Plugin row meta
     *
     * @param array  $links Plugin row meta
     * @param string $file  Plugin base file
     * @return array
     */
    public function plugin_row_meta($links, $file) {
        if (PAYVESSEL_WC_PLUGIN_BASENAME === $file) {
            $row_meta = array(
                'docs'    => '<a href="https://docs.payvessel.com" target="_blank">' . __('Documentation', 'payvessel-woocommerce') . '</a>',
                'support' => '<a href="https://payvessel.com/support" target="_blank">' . __('Support', 'payvessel-woocommerce') . '</a>',
            );
            return array_merge($links, $row_meta);
        }
        return $links;
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Payvessel Transactions', 'payvessel-woocommerce'),
            __('Payvessel', 'payvessel-woocommerce'),
            'manage_woocommerce',
            'payvessel-transactions',
            array($this, 'render_transactions_page')
        );
    }

    /**
     * Render transactions page
     */
    public function render_transactions_page() {
        include PAYVESSEL_WC_PLUGIN_DIR . 'templates/admin-transactions.php';
    }

    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {
        if (!is_checkout() && !is_checkout_pay_page()) {
            return;
        }

        $settings = get_option('woocommerce_payvessel_settings', array());
        $payment_method = isset($settings['payment_method']) ? $settings['payment_method'] : 'redirect';

        if ($payment_method === 'popup') {
            wp_enqueue_script(
                'payvessel-checkout-sdk',
                'https://cdn.jsdelivr.net/npm/payvessel-checkout@latest/dist/index.umd.js',
                array(),
                PAYVESSEL_WC_VERSION,
                true
            );
        }

        wp_enqueue_script(
            'payvessel-checkout',
            PAYVESSEL_WC_PLUGIN_URL . 'assets/js/checkout.js',
            array('jquery'),
            PAYVESSEL_WC_VERSION,
            true
        );

        wp_enqueue_style(
            'payvessel-checkout',
            PAYVESSEL_WC_PLUGIN_URL . 'assets/css/checkout.css',
            array(),
            PAYVESSEL_WC_VERSION
        );

        wp_localize_script('payvessel-checkout', 'payvessel_params', array(
            'ajax_url'       => admin_url('admin-ajax.php'),
            'verify_nonce'   => wp_create_nonce('payvessel_verify_payment'),
            'payment_method' => $payment_method,
        ));
    }

    /**
     * Enqueue admin scripts
     *
     * @param string $hook Current admin page
     */
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'payvessel') === false && strpos($hook, 'wc-settings') === false) {
            return;
        }

        wp_enqueue_style(
            'payvessel-admin',
            PAYVESSEL_WC_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            PAYVESSEL_WC_VERSION
        );

        wp_enqueue_script(
            'payvessel-admin',
            PAYVESSEL_WC_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            PAYVESSEL_WC_VERSION,
            true
        );

        wp_localize_script('payvessel-admin', 'payvessel_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('payvessel_admin'),
        ));
    }

    /**
     * AJAX verify payment
     */
    public function ajax_verify_payment() {
        check_ajax_referer('payvessel_verify_payment', 'nonce');

        $reference = isset($_POST['reference']) ? sanitize_text_field($_POST['reference']) : '';
        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;

        if (empty($reference) || empty($order_id)) {
            wp_send_json_error(array('message' => __('Invalid request', 'payvessel-woocommerce')));
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(array('message' => __('Order not found', 'payvessel-woocommerce')));
        }

        // Get gateway instance
        $gateways = WC()->payment_gateways()->payment_gateways();
        if (!isset($gateways['payvessel'])) {
            wp_send_json_error(array('message' => __('Gateway not found', 'payvessel-woocommerce')));
        }

        $gateway = $gateways['payvessel'];
        $verified = $gateway->verify_payment($reference, $order);

        if ($verified) {
            wp_send_json_success(array(
                'message'      => __('Payment verified successfully', 'payvessel-woocommerce'),
                'redirect_url' => $gateway->get_return_url($order),
            ));
        } else {
            wp_send_json_error(array('message' => __('Payment verification failed', 'payvessel-woocommerce')));
        }
    }

    /**
     * AJAX get transactions
     */
    public function ajax_get_transactions() {
        check_ajax_referer('payvessel_admin', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 20;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

        $args = array(
            'payment_method' => 'payvessel',
            'limit'          => $per_page,
            'offset'         => ($page - 1) * $per_page,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        if (!empty($status)) {
            $args['status'] = $status;
        }

        $orders = wc_get_orders($args);

        $transactions = array();
        foreach ($orders as $order) {
            $transactions[] = array(
                'id'          => $order->get_id(),
                'reference'   => $order->get_meta('_payvessel_reference'),
                'amount'      => $order->get_total(),
                'currency'    => $order->get_currency(),
                'status'      => $order->get_status(),
                'customer'    => $order->get_billing_email(),
                'channel'     => $order->get_meta('_payvessel_payment_channel'),
                'date'        => $order->get_date_created() ? $order->get_date_created()->format('Y-m-d H:i:s') : '',
            );
        }

        // Get total count
        $total_args = array(
            'payment_method' => 'payvessel',
            'limit'          => -1,
            'return'         => 'ids',
        );
        if (!empty($status)) {
            $total_args['status'] = $status;
        }
        $total = count(wc_get_orders($total_args));

        wp_send_json_success(array(
            'transactions' => $transactions,
            'total'        => $total,
            'pages'        => ceil($total / $per_page),
        ));
    }

    /**
     * Register webhook endpoint
     */
    public function register_webhook_endpoint() {
        add_rewrite_rule(
            '^payvessel-webhook/?$',
            'index.php?payvessel_webhook=1',
            'top'
        );
    }

    /**
     * Add query vars
     *
     * @param array $vars Query vars
     * @return array
     */
    public function add_query_vars($vars) {
        $vars[] = 'payvessel_webhook';
        return $vars;
    }

    /**
     * Handle webhook
     */
    public function handle_webhook() {
        if (get_query_var('payvessel_webhook')) {
            $webhook = new Payvessel_Webhook();
            $webhook->handle();
            exit;
        }
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('payvessel/v1', '/webhook', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'rest_webhook_handler'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route('payvessel/v1', '/transactions', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'rest_get_transactions'),
            'permission_callback' => array($this, 'rest_permission_check'),
        ));
    }

    /**
     * REST webhook handler
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function rest_webhook_handler($request) {
        $webhook = new Payvessel_Webhook();
        $webhook->handle();
        return new WP_REST_Response(array('status' => 'ok'), 200);
    }

    /**
     * REST get transactions
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function rest_get_transactions($request) {
        $page = $request->get_param('page') ?: 1;
        $per_page = $request->get_param('per_page') ?: 20;

        $orders = wc_get_orders(array(
            'payment_method' => 'payvessel',
            'limit'          => $per_page,
            'offset'         => ($page - 1) * $per_page,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ));

        $transactions = array();
        foreach ($orders as $order) {
            $transactions[] = array(
                'id'          => $order->get_id(),
                'reference'   => $order->get_meta('_payvessel_reference'),
                'amount'      => $order->get_total(),
                'currency'    => $order->get_currency(),
                'status'      => $order->get_status(),
                'customer'    => $order->get_billing_email(),
                'date'        => $order->get_date_created() ? $order->get_date_created()->format('Y-m-d H:i:s') : '',
            );
        }

        return new WP_REST_Response($transactions, 200);
    }

    /**
     * REST permission check
     *
     * @return bool
     */
    public function rest_permission_check() {
        return current_user_can('manage_woocommerce');
    }

    /**
     * Activation
     */
    public function activate() {
        $this->register_webhook_endpoint();
        flush_rewrite_rules();

        // Set default options
        update_option('payvessel_wc_version', PAYVESSEL_WC_VERSION);
    }

    /**
     * Deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
}

/**
 * Initialize the plugin
 *
 * @return Payvessel_WooCommerce
 */
function payvessel_wc() {
    return Payvessel_WooCommerce::instance();
}

// Initialize
payvessel_wc();
