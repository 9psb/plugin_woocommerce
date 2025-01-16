<?php
/**
 * Plugin Name: 9PSB Gateway
 * Plugin URI: https://9psb.com.ng/
 * Description: The 9PSB WooCommerce Plugin enables you to easily integrate 9PSB as a payment gateway on your online store, allowing customers to make payments using various methods such as Credit/Debit cards, Bank Transfers, Mobile Payments, and more.
 * Version: 1.4.1
 * Author: 9psb Developers
 * Developer: 9psb developers
 * Copyright: @ 2024 9psb.com.ng
 * WC requires at least:   6.9.1
 * Requires at least:      5.6
 * Requires PHP:           7.4
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: woo-9psb
 */


 // Include initialization file
require_once plugin_dir_path(__FILE__) . '9payment-init.php';

// Prevent plugin from being accessed outside wordpress
defined('ABSPATH') or die('please ensure wordpress is installed');

//Ensure Woocemmerce is installed and active.
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}


// Enqueue block assets
add_action('enqueue_block_assets', 'wc_9payment_enqueue_block_assets');
function wc_9payment_enqueue_block_assets()
{
    // Ensure the path is correct and matches your directory structure
    $script_path = 'assets/js/wc-9payment-block.js';
    $script_url = plugins_url($script_path, __FILE__);
    $script_version = filemtime(plugin_dir_path(__FILE__) . $script_path);

    wp_register_script(
        'wc-9payment-block',
        $script_url,
        ['wp-element', 'wp-i18n', 'wp-blocks', 'wp-components', 'wp-editor', 'wc-settings'],
        $script_version,
        true // Load in footer
    );

    // Localize script with required data (public key and API URL)
   wp_localize_script('wc-9payment-block', 'wc9PaymentParams', [
    'privateKey' => get_option('woocommerce_9payment_private_key'),
    'publicKey' => get_option('woocommerce_9payment_public_key'),
    'apiUrl' => 'https://bank9jacollectapi.9psb.com.ng/gateway-api/v1/authenticate', // 'https://9psb-sonar-test.9psb.com.ng/gateway-api/v1/authenticate',
    'icon' => 'https://baastest.9psb.com.ng/gateway/assets/banktitle-D-nB80hl.svg', // Add this line
]);


    wp_enqueue_script('wc-9payment-block');
}



// Register your gateway for WooCommerce blocks
add_action('wc_blocks_payment_method_type_registration', 'register_9payment_method');
function register_9payment_method($payment_method_registry)
{
    if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        require_once __DIR__ . '/includes/class-wc-9payment-blocks.php';
        
        // Make sure the class WC_9payment_Blocks_Payment is correctly defined in the file
        $payment_method_registry->register(new WC_9payment_Blocks_Payment());
    }
}

add_filter('woocommerce_cart_payment_method_label', 'display_9payment_icon', 10, 2);
function display_9payment_icon($label, $method) {
    if ($method->id === '9payment') { // Adjust this to your payment method ID
        $icon = 'https://baastest.9psb.com.ng/gateway/assets/banktitle-D-nB80hl.svg';
        $label .= '<img src="' . esc_url($icon) . '" alt="9Payment Icon" style="width: 50px; height: auto; margin-left: 10px;" />';
    }
    return $label;
}


/*
* This action hook registers our PHP class as a WooCommerce payment gateway
*/
add_filter('woocommerce_payment_gateways', 'add_9payment_gateway_class');
function add_9payment_gateway_class($gateways)
{
    $gateways[] = 'WC_9payment_Gateway';
    return $gateways;
}


/**
 * Handle plugin activation.
 */
add_action('activated_plugin', 'detect_9payment_plugin_activated', 10, 2);
function detect_9payment_plugin_activated($plugin, $network_activation)
{
    if (strpos($plugin, 'woo-9psb') !== false) {
        customer_9payment_notification('activate');
    }
}

/**
 * Handle plugin deactivation.
 */
add_action('deactivated_plugin', 'detect_9payment_plugin_deactivated', 10, 2);
function detect_9payment_plugin_deactivated($plugin, $network_activation)
{
    if (strpos($plugin, 'woo-9psb') !== false) {
        customer_9payment_notification('deactivate');
    }
}

/**
 * Notify 9payment team of plugin status.
 */
function customer_9payment_notification($event)
{
    try {
        // Get logged in user.
        $current_user = wp_get_current_user();

        // Construct user agent string.
        $user_agent = "9payment/WooCommerce/3.8.0 WooCommerce/" . WC()->version . " WordPress/" . get_bloginfo('version') . " PHP/" . PHP_VERSION;

        // Log event to Sentry.
        Sentry\captureMessage('Event: ' . $event . ' | User: ' . $current_user->user_email . ' | Plugin: WooCommerce | User agent: ' . $user_agent);

        return true;
    } catch (Exception $e) {
        // Send exception to Sentry 
        Sentry\captureException($e);

        // If it fails, display error message.
        wc_add_notice(__('Plugin Events error:', '9payment') . $e->getMessage(), 'error');
        return false;
    }
}

/*
 * Initialize the plugin class for the gateway.
 */
add_action('plugins_loaded', 'wc_9payment_gateway_init', 11);
function wc_9payment_gateway_init()
{
    if (class_exists('WC_payment_Gateway')) {
        require_once dirname(__FILE__) . '/includes/class-wc-9payment-gateway.php';
    }
}

/**
 * Add the Settings link to the plugin
 *
 * @param  array $links Existing links on the plugin page.
 *
 * @return array Existing links with our settings link added
 */
function psb_plugin_action_links( array $links ): array {
    error_log('psb_plugin_action_links called'); // Check if the function is called

    $psb_settings_url = esc_url( get_admin_url( null, 'admin.php?page=wc-settings&tab=checkout&section=9payment' ) );
    
    array_unshift( $links, "<a title='9PSB Settings Page' href='$psb_settings_url'>Settings</a>" );

    return $links;
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'psb_plugin_action_links' );


