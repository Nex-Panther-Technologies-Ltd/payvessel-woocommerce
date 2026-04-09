# Payvessel WooCommerce Payment Gateway

<p align="center">
  <img src="assets/images/payvessel-dark-logo.svg" alt="Payvessel Logo" width="200">
</p>

[![WooCommerce](https://img.shields.io/badge/WooCommerce-5.0%2B-purple.svg)](https://woocommerce.com/)
[![WordPress](https://img.shields.io/badge/WordPress-5.6%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPLv2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

Accept payments on your WooCommerce store using Payvessel - Nigeria's trusted payment gateway. Support for multiple payment channels including cards, bank transfers, and USSD.

## 🚀 Features

- **Easy Setup** - Quick installation with minimal configuration
- **Multiple Payment Channels** - Accept Card, Bank Transfer, USSD payments
- **Popup & Redirect Checkout** - Choose between modal popup or redirect checkout
- **WooCommerce Block Checkout** - Full support for the new block-based checkout
- **Real-time Transaction Monitoring** - Track all payments from your WordPress admin
- **Automatic Payment Verification** - Webhook integration with signature verification
- **Order Auto-completion** - Optionally auto-complete orders on successful payment
- **WordPress Multisite Compatible** - Works on multisite installations
- **HPOS Compatible** - Supports WooCommerce High-Performance Order Storage
- **Test Mode** - Switch between sandbox and live environments easily

## 📋 Requirements

- WordPress 5.6 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher
- Payvessel merchant account

## 📦 Installation

### From WordPress Admin

1. Download the plugin zip file
2. Go to **WordPress Admin → Plugins → Add New → Upload Plugin**
3. Choose the zip file and click **Install Now**
4. Activate the plugin

### Manual Installation

1. Download and extract the plugin
2. Upload the `payvessel-woocommerce` folder to `/wp-content/plugins/`
3. Activate the plugin through the **Plugins** menu in WordPress

## ⚙️ Configuration

### 1. Get Your API Keys

1. Log in to your [Payvessel Dashboard](https://dashboard.payvessel.com)
2. Navigate to **Settings → API Keys**
3. Copy your **API Key** and **Secret Key**

### 2. Configure the Plugin

1. Go to **WooCommerce → Settings → Payments**
2. Click on **Payvessel** to configure
3. Enable the payment gateway
4. Enter your API credentials:
   - **Test Mode**: Enable for testing (uses sandbox environment)
   - **API Key**: Your Payvessel API key
   - **Secret Key**: Your Payvessel secret key
5. Configure payment options:
   - **Payment Channels**: Select which payment methods to enable
   - **Checkout Mode**: Choose Popup or Redirect
   - **Auto-complete Orders**: Enable to automatically complete orders on payment

### 3. Set Up Webhook (Important!)

1. Go to **Payvessel → Transactions** in your WordPress admin
2. Copy the **Webhook URL** displayed at the top
3. Add this URL to your Payvessel Dashboard under **Settings → Webhooks**
4. This ensures automatic payment verification

## 🎨 Checkout Modes

### Popup Mode (Recommended)
Opens Payvessel checkout in a modal overlay on your site. Customers stay on your site throughout the payment process.

<p align="center">
  <img src="https://github.com/Nex-Panther-Technologies-Ltd/payvessel-woocommerce/raw/main/screenshots/popup-checkout.png" alt="Popup Checkout" width="600">
</p>

### Redirect Mode
Redirects customers to Payvessel checkout page. After payment, they're returned to your thank you page.

## 📊 Transaction Dashboard

Access your transaction dashboard at **Payvessel → Transactions** to:

- View all transactions with status
- Filter by status (Success, Pending, Failed)
- See transaction statistics at a glance
- Access detailed transaction information
- Copy webhook URL for configuration

<p align="center">
  <img src="https://github.com/Nex-Panther-Technologies-Ltd/payvessel-woocommerce/raw/main/screenshots/transactions-dashboard.png" alt="Transactions Dashboard" width="800">
</p>

## 🔒 Security Features

### Webhook Signature Verification
All webhook notifications are verified using HMAC-SHA512 signatures to ensure authenticity.

### IP Whitelisting (Optional)
For additional security, whitelist Payvessel's webhook IPs in your firewall.

### SSL Required
Always use SSL (HTTPS) on your checkout pages for secure payments.

## 🔧 Advanced Configuration

### Customizing Payment Description

The payment description sent to Payvessel can be customized using a filter:

```php
add_filter('payvessel_payment_description', function($description, $order) {
    return 'Custom description for order #' . $order->get_id();
}, 10, 2);
```

### Modifying Checkout Parameters

Customize the checkout initialization:

```php
add_filter('payvessel_checkout_params', function($params, $order) {
    $params['metadata'] = [
        'custom_field' => 'custom_value',
    ];
    return $params;
}, 10, 2);
```

### Custom Webhook Actions

Perform additional actions after successful payment:

```php
add_action('payvessel_payment_successful', function($order, $transaction_data) {
    // Your custom code here
    // e.g., send custom notification, update inventory, etc.
}, 10, 2);
```

### Custom Failed Payment Actions

Handle failed payments:

```php
add_action('payvessel_payment_failed', function($order, $transaction_data) {
    // Your custom code here
}, 10, 2);
```

## 🧪 Testing

### Test Mode

1. Enable **Test Mode** in the plugin settings
2. Use test credentials from your Payvessel sandbox account
3. Use test card numbers provided by Payvessel

### Test Card Numbers

| Card Number | Description |
|-------------|-------------|
| 5061 2611 1111 1111 117 | Successful payment |
| 5061 2611 1111 1111 118 | Failed payment |
| 5399 8300 0000 0008 | 3D Secure card |

## 🔄 WooCommerce Blocks

The plugin fully supports WooCommerce Block Checkout. No additional configuration needed - it works automatically when you use the block-based checkout.

## ❓ Frequently Asked Questions

### Why is my webhook not working?

1. Ensure you've added the correct webhook URL to your Payvessel dashboard
2. Check that your site is accessible from the internet (not localhost)
3. Verify SSL is working properly on your site
4. Check your server error logs for any PHP errors

### Payments are successful but orders stay in "Pending"

This usually means webhooks aren't being received. Check:
1. Webhook URL is correctly configured
2. Your server isn't blocking Payvessel IPs
3. There are no PHP errors preventing webhook processing

### How do I switch to live mode?

1. Go to **WooCommerce → Settings → Payments → Payvessel**
2. Uncheck **Enable Test Mode**
3. Enter your live API credentials
4. Save changes

### Can I use this with WooCommerce Subscriptions?

Currently, this plugin supports one-time payments. Subscription support is planned for a future release.

## 📝 Changelog

### 1.0.0
- Initial release
- Card, Bank Transfer, and USSD payment support
- Popup and Redirect checkout modes
- WooCommerce Block Checkout support
- Transaction dashboard
- Webhook integration with signature verification
- HPOS compatibility
- WordPress Multisite support

## 🤝 Support

- **Documentation**: [docs.payvessel.com](https://docs.payvessel.com)
- **Email**: support@payvessel.com
- **Dashboard**: [dashboard.payvessel.com](https://dashboard.payvessel.com)

## 📄 License

This plugin is licensed under the GPLv2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## 🙏 Credits

Developed by the Payvessel Team.
