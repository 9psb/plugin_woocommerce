<?php

defined('ABSPATH') || exit;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_9payment_Blocks_Payment extends AbstractPaymentMethodType
{
    private $gateway;
    protected $name = '9payment'; 

    public function initialize()
    {
        $this->settings = get_option('woocommerce_9payment_settings', []);
        $this->gateway = new WC_9Payment_Gateway(); 
    }

    public function is_active()
    {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles()
    {
        wp_register_script(
            'wc-9payment-blocks-integration',
            plugin_dir_url(__FILE__) . 'block/checkout.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            null,
            true
        );

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('wc-9payment-blocks-integration', 'wc-9payment', plugin_dir_path(__FILE__) . 'languages/');
        }

        return ['wc-9payment-blocks-integration'];
    }

    public function get_payment_method_data()
    {
        return [
            'title'       => $this->gateway->title,
            'description' => $this->gateway->description,
        ];
    }
}
