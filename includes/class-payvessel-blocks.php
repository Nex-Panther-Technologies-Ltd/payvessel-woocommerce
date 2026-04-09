<?php
/**
 * Payvessel WooCommerce Blocks Support
 *
 * @package Payvessel_WooCommerce
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Payvessel Blocks Support Class
 */
final class WC_Payvessel_Blocks_Support extends AbstractPaymentMethodType {

    /**
     * Payment method name/id/slug
     *
     * @var string
     */
    protected $name = 'payvessel';

    /**
     * Gateway instance
     *
     * @var WC_Payvessel_Gateway
     */
    private $gateway;

    /**
     * Initialize
     */
    public function initialize() {
        $this->settings = get_option('woocommerce_payvessel_settings', array());
        $gateways = WC()->payment_gateways->payment_gateways();
        $this->gateway = isset($gateways[$this->name]) ? $gateways[$this->name] : null;
    }

    /**
     * Check if gateway is active
     *
     * @return bool
     */
    public function is_active() {
        return $this->gateway && $this->gateway->is_available();
    }

    /**
     * Get payment method script handles
     *
     * @return array
     */
    public function get_payment_method_script_handles() {
        $script_path = '/assets/js/blocks/checkout.js';
        $script_asset_path = PAYVESSEL_WC_PLUGIN_DIR . 'assets/js/blocks/checkout.asset.php';
        $script_asset = file_exists($script_asset_path)
            ? require($script_asset_path)
            : array(
                'dependencies' => array(),
                'version'      => PAYVESSEL_WC_VERSION
            );
        $script_url = PAYVESSEL_WC_PLUGIN_URL . $script_path;

        wp_register_script(
            'wc-payvessel-blocks',
            $script_url,
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations(
                'wc-payvessel-blocks',
                'payvessel-woocommerce',
                PAYVESSEL_WC_PLUGIN_DIR . 'languages'
            );
        }

        return array('wc-payvessel-blocks');
    }

    /**
     * Get payment method data for frontend
     *
     * @return array
     */
    public function get_payment_method_data() {
        return array(
            'title'             => $this->get_setting('title'),
            'description'       => $this->get_setting('description'),
            'supports'          => array_filter($this->gateway ? $this->gateway->supports : array(), array($this->gateway, 'supports')),
            'logo_url'          => PAYVESSEL_WC_PLUGIN_URL . 'assets/images/payvessel-logo.svg',
            'payment_method'    => $this->get_setting('payment_method'),
            'testmode'          => $this->get_setting('testmode') === 'yes',
        );
    }
}
