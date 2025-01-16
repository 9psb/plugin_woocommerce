<?php

defined('ABSPATH') or die('You should not be here');
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class WC_9Payment_Gateway extends WC_Payment_Gateway
{
    private $private_key = "";
    private $public_key = "";
    private $client;

    public function __construct()
    {
        $this->id = '9payment';
        $this->icon = 'https://baastest.9psb.com.ng/gateway/assets/banktitle-D-nB80hl.svg';
        $this->has_fields = false;
        $this->method_title = '9PSB';
        $this->method_description = 'The 9PSB WooCommerce Plugin enables you to easily integrate 9PSB as a payment gateway on your online store, allowing customers to make payments using various methods such as Credit/Debit cards, Bank Transfers, Mobile Payments, and more.';
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');

        $this->private_key = $this->get_option('private_key', $this->private_key);
        $this->public_key = $this->get_option('public_key', $this->public_key);

        $this->client = new Client([
            'base_uri' => 'https://bank9jacollectapi.9psb.com.ng/gateway-api/v1/authenticate', // 'https://9psb-sonar-test.9psb.com.ng/gateway-api/v1/authenticate',
            'verify' => __DIR__ . '/curl-ca-bundle.crt',
        ]);
        
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        $this->init_form_fields();
        $this->init_settings();
    }

    public function init_form_fields()
    {
        $this->form_fields = apply_filters('wc_9payment_form_fields', array(
            'enabled' => array(
                'title'       => 'Enable/Disable',
                'label'       => 'Enable 9PSB',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'title' => array(
                'title'       => 'Title',
                'type'        => 'text',
                'description' => 'This controls the title which your customer sees during checkout.',
                'default'     => 'Pay with 9PSB',
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => 'Description',
                'type'        => 'textarea',
                'css'         => 'height:100px; width:400px;',
                'description' => 'Payment method description that the customer will see on your checkout page.',
                'default'     => 'Secure your transactions with 9PSB.',
                'desc_tip'    => true
            ),
            'callbackUrl' => array(
                'title'       => __('Your Redirect URL: '),
                'type'        => 'text',
                'default'     => get_home_url().'/checkout/order-received/',
                'desc_tip' => true,
            ),
            'private_key' => array(
                'title'       => '9PSB Private Key',
                'type'        => 'password',
                'description' => 'Enter your 9PSB Private Key for the configured environment.',
                'desc_tip'    => true
            ),
            'public_key' => array(
                'title'       => '9PSB Public Key',
                'type'        => 'password',
                'description' => 'Enter your 9PSB Public Key for the configured environment.',
                'desc_tip'    => true
            )
        ));
    }

    public function process_admin_options()
    {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        parent::process_admin_options();

        $this->save_custom_settings();
    }

    public function save_custom_settings()
    {
        $updated_settings = $this->get_post_data();

        foreach ($this->get_form_fields() as $key => $field) {
            if (isset($updated_settings[$key])) {
                $this->settings[$key] = $updated_settings[$key];
            }
        }

        update_option('woocommerce_9payment_private_key', $this->settings['private_key']);
        update_option('woocommerce_9payment_public_key', $this->settings['public_key']);
    }
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        // Get access token from session
        $access_token = WC()->session->get('9payment_access_token');

        // If the access token is not present, authenticate to retrieve it
        if (!$access_token) {
            try {
                $auth_response = $this->client->post('authenticate', [
                    'headers' => ['Accept' => 'application/json'],
                    'json' => [
                        'privateKey' => $this->private_key,
                        'publicKey' => $this->public_key
                    ]
                ]);

                $auth_body = json_decode($auth_response->getBody(), true);
                $access_token = $auth_body['data']['accessToken'] ?? null;

                if (!$access_token) {
                    wc_add_notice('Failed to retrieve access token.', 'error');
                    return;
                }

                // Store access token in session for future use
                WC()->session->set('9payment_access_token', $access_token);
            } catch (RequestException $e) {
                wc_add_notice('Authentication error: ' . $e->getMessage(), 'error');
                return;
            }
        }

        // Prepare order details
        $order_total = number_format($order->get_total(), 2, '.', '');
        $email = $order->get_billing_email();
        $merchant_reference = substr(hash('sha256', $order_total . $email . time()), 0, 25);
        $callback_url = $this->get_option('callbackUrl');

        // Initiate payment
        try {
            $initiate_response = $this->client->post('initiate-payment', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                    'Accept' => 'application/json',
                ],
                'json' => [
                    'amount' => $order_total,
                    'callbackUrl' => $callback_url,
                    'customer' => [
                        'name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                        'email' => $order->get_billing_email(),
                        'phoneNo' => $order->get_billing_phone(),
                    ],
                    'merchantReference' => $merchant_reference,
                    'narration' => 'Payment for Order #' . $order->get_id(),
                    'amountType' => 'EXACT',
                    'metaData' => [
                        ['key' => 'order_id', 'value' => (string)$order->get_id()],
                        ['key' => 'customer_id', 'value' => (string)$order->get_customer_id()]
                    ],
                    'businessCode' => ''
                ]
            ]);

            $initiate_body = json_decode($initiate_response->getBody(), true);

            // Handle the response from payment initiation
            if (isset($initiate_body['data']['link'])) {
                return array(
                    'result' => 'success',
                    'redirect' => $initiate_body['data']['link']
                );
            } else {
                wc_add_notice('Payment link not found in the payment gateway response.', 'error');
                return;
            }
        } catch (RequestException $e) {
            wc_add_notice('Payment initiation error: ' . $e->getMessage(), 'error');
            return;
        }
    }

    public function handle_callback()
    {
        if (!isset($_GET['reference'])) {
            wp_die('Invalid request');
        }

        $merchant_reference = sanitize_text_field($_GET['reference']);
        $order_id = wc_get_order_id_by_order_key($merchant_reference);

        if (!$order_id) {
            wp_die('Order not found');
        }

        $order = wc_get_order($order_id);

        // Retrieve the access token from the session
        $access_token = WC()->session->get('9payment_access_token');

        // Verify the payment using the access token
        try {
            $verify_response = $this->client->get('verify-payment', [
                'query' => ['reference' => $merchant_reference],
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                    'Accept' => 'application/json',
                ],
            ]);

            $verify_body = json_decode($verify_response->getBody(), true);

            if ($verify_body['data']['status'] === 'SUCCESS') {
                $order->payment_complete();
                // Reduce stock levels
                wc_reduce_stock_levels($order_id);
                // Empty the cart
                WC()->cart->empty_cart();
                $order->add_order_note('Payment was successfully verified.');
            } else {
                $order->update_status('failed', 'Payment verification failed.');
                wp_die('Payment verification failed.');
            }
        } catch (RequestException $e) {
            $order->update_status('failed', 'Payment verification error: ' . $e->getMessage());
            wp_die('Payment verification error.');
        }

        wp_redirect($this->get_return_url($order));
        exit;
    }
               
}