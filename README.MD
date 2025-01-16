# 9PSB Gateway

**Contributors**: 9psb  
**Tags**: fintech, payments, WooCommerce, Nigeria, Naira, payment gateway, bank transfer, mobile payments  
**Requires at least**: 3.1  
**Stable tag**: 1.4.1  
**License**: GPL-2.0+  
**Short Description**: A WooCommerce plugin for integrating 9PSB as a payment gateway with support for bank transfers, mobile payments, and USSD.

## Description

With the official 9PSB WooCommerce Plugin, you can accept payments from your customers via bank transfers, and mobile payments directly on your WooCommerce store.

## Plugin Features

- **Payment Methods Supported**:  
  - Bank Transfers  
  - Mobile Payments  
  - USSD  

## Requirements

1. A 9PSB Business account with API keys. You can get your keys [here](https://9psb.com.ng/dashboard/settings/apis).  
2. WooCommerce Plugin (download from [WooCommerce](https://woocommerce.com/)).  
3. Supported PHP version: 7.4.0 - 8.2.

## Installation

### Automatic Installation

1. Log in to your WordPress Dashboard.
2. Go to **Plugins > Add New**.
3. Search for `9PSB WooCommerce`.
4. Click **Install Now** next to the plugin name.
5. After installation, click **Activate**.
6. Go to **WooCommerce > Settings**, and select the **Checkout** tab.
7. Click on **9PSB** under the available payment methods and configure your settings.

### Manual Installation

1. Download the plugin ZIP file.
2. Go to **Plugins > Add New** in your WordPress Dashboard.
3. Click **Upload Plugin** and select the downloaded ZIP file.
4. Click **Install Now** and then **Activate** the plugin.
5. Follow the same configuration steps mentioned in the automatic installation section.

## Tested Up to
- Tested up to Wordpress 6.7.1

## Troubleshooting
- **Authentication Failure**: Ensure API keys are correct for the environment set (test/live).
- **Failed to Get Payment URL**: Check if the callback URL is correctly set in your 9PSB dashboard.

## License
This module is licensed under the AFL-3.0.

## Support
For support, contact [itsupport@9psb.com.ng](mailto:itsupport@9psb.com.ng) or refer to the official documentation for Wordpress plugin development.