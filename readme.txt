=== Payvessel – Payment Gateway for WooCommerce ===
Contributors: payvessel
Tags: woocommerce, payment gateway, payvessel, credit card, bank transfer, ussd, nigeria
Requires at least: 5.6
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept credit card, bank transfer, and USSD payments on your WooCommerce store via Payvessel.

== Description ==

Payvessel WooCommerce Payment Gateway allows you to accept payments on your WooCommerce store using Payvessel - Nigeria's trusted payment solution.

**Features:**

* **Easy Setup** - Quick installation with minimal configuration
* **Multiple Payment Channels** - Accept Card, Bank Transfer, USSD payments
* **Popup & Redirect Checkout** - Choose between modal popup or redirect checkout
* **WooCommerce Block Checkout** - Full support for the new block-based checkout
* **Real-time Transaction Monitoring** - Track all payments from your WordPress admin
* **Automatic Payment Verification** - Webhook integration with signature verification
* **Order Auto-completion** - Optionally auto-complete orders on successful payment
* **WordPress Multisite Compatible** - Works on multisite installations
* **HPOS Compatible** - Supports WooCommerce High-Performance Order Storage
* **Test Mode** - Switch between sandbox and live environments easily

**Supported Payment Channels:**

* Credit/Debit Cards (Visa, Mastercard, Verve)
* Bank Transfer
* USSD

== Installation ==

**From WordPress Admin:**

1. Download the plugin zip file
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Choose the zip file and click Install Now
4. Activate the plugin

**Manual Installation:**

1. Download and extract the plugin
2. Upload the `payvessel-woocommerce` folder to `/wp-content/plugins/`
3. Activate the plugin through the Plugins menu in WordPress

**Configuration:**

1. Go to WooCommerce → Settings → Payments
2. Click on Payvessel to configure
3. Enable the payment gateway
4. Enter your API credentials from your Payvessel dashboard
5. Configure payment channels and checkout mode
6. Save changes

**Webhook Setup:**

1. Go to Payvessel → Transactions in your WordPress admin
2. Copy the Webhook URL displayed
3. Add this URL to your Payvessel Dashboard under Settings → Webhooks

== Frequently Asked Questions ==

= What do I need to use this plugin? =

1. A Payvessel merchant account
2. API credentials from your Payvessel dashboard
3. WooCommerce 5.0 or higher
4. WordPress 5.6 or higher
5. PHP 7.4 or higher

= How do I get my API keys? =

1. Log in to your Payvessel Dashboard
2. Navigate to Settings → API Keys
3. Copy your API Key and Secret Key

= Does this plugin support test mode? =

Yes! Enable Test Mode in the plugin settings to use the sandbox environment for testing.

= What payment methods are supported? =

The plugin supports:
* Credit/Debit Cards (Visa, Mastercard, Verve)
* Bank Transfer
* USSD

= Does it work with WooCommerce Blocks? =

Yes! The plugin fully supports the new WooCommerce Block Checkout.

= Is it compatible with WooCommerce HPOS? =

Yes, the plugin is fully compatible with WooCommerce High-Performance Order Storage.

= Why aren't orders being marked as paid? =

Ensure you've configured the webhook URL correctly:
1. Go to Payvessel → Transactions in WordPress admin
2. Copy the Webhook URL
3. Add it to your Payvessel Dashboard under Settings → Webhooks

= Can I use this on a multisite installation? =

Yes, the plugin is compatible with WordPress Multisite.

== Screenshots ==

1. Plugin settings page - Configure your API keys, payment channels, and checkout mode
2. Checkout with Payvessel payment option - Customers see Payvessel as a payment method
3. Popup checkout modal - Secure payment overlay without leaving your site
4. Transaction dashboard - Monitor all transactions with real-time statistics
5. Admin transactions list - Filter and manage all payment records
6. WooCommerce Block Checkout - Full support for modern block-based checkout

== Changelog ==

= 1.0.0 =
* Initial release
* Card, Bank Transfer, and USSD payment support
* Popup and Redirect checkout modes
* WooCommerce Block Checkout support
* Transaction dashboard
* Webhook integration with signature verification
* HPOS compatibility
* WordPress Multisite support

== Upgrade Notice ==

= 1.0.0 =
Initial release of Payvessel WooCommerce Payment Gateway.

== Support ==

For support, please contact support@payvessel.com or visit https://docs.payvessel.com
