<?php

class Itella_Wc_blocks
{
    /**
     * This plugin information.
     *
     * @since    1.5.0
     * @access   private
     * @var      object $plugin
     */
    private $plugin;

    /**
     * This plugin Itella_Shipping_Wc_Itella class.
     *
     * @since    1.5.0
     * @access   private
     * @var      object $wc
     */
    private $wc;

    /**
     * Initialize the class and set its properties.
     *
     * @since 1.5.0
     * @param object $plugin
     * @param array $available_countries
     *
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;

        $this->wc = new Itella_Shipping_Wc_Itella();
    }

    /**
     * Initializes the integration of the plugin with WooCommerce Blocks
     * 
     * @since 1.5.0
     */
    public function init()
    {
        add_action('woocommerce_blocks_checkout_block_registration', function( $integration_registry ) {
            $integration_registry->register( new Itella_Wc_blocks_Integration($this->plugin) );
        });
        add_action('woocommerce_blocks_cart_block_registration', function( $integration_registry ) {
            $integration_registry->register( new Itella_Wc_blocks_Integration($this->plugin) );
        });

        if ( function_exists('woocommerce_store_api_register_endpoint_data') ) {
            woocommerce_store_api_register_endpoint_data(array(
                'endpoint' => \Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema::IDENTIFIER,
                'namespace' => $this->plugin->name,
                'data_callback' => array($this, 'itella_data_callback'),
                'schema_callback' => array($this, 'itella_schema_callback'),
                'schema_type' => ARRAY_A,
            ));
        }
        add_action('woocommerce_store_api_checkout_update_order_from_request', array($this, 'save_block_order_meta'), 10, 2);
        
        add_filter('__experimental_woocommerce_blocks_add_data_attributes_to_namespace', function ( $allowed_namespaces ) {
            $allowed_namespaces[] = $this->plugin->name;
            return $allowed_namespaces;
        }, 10, 1);
    }

    /**
     * Registered the data elements, which will be transmitted with requests
     * 
     * @since 1.5.0
     */
    public function itella_data_callback()
    {
        return array(
            'selected_pickup_id' => '',
            'selected_rate_id' => '',
        );
    }

    /**
     * Description of registered data elements
     * 
     * @since 1.5.0
     */
    public function itella_schema_callback()
    {
        return array(
            'selected_pickup_id' => array(
                'description' => __('Selected pickup point', 'itella-shipping'),
                'type'        => array('string', 'null'),
                'readonly'    => true,
            ),
            'selected_rate_id' => array(
                'description' => __('Selected method', 'itella-shipping'),
                'type'        => array('string', 'null'),
                'readonly'    => true,
            ),
        );
    }

    /**
     * When creating an order, get Itella meta data and save it to the Order
     * 
     * @since 1.5.0
     * @param WC_Order $order - Created Order
     * @param array $request - Data from Checkout page
     */
    public function save_block_order_meta($order, $request)
    {
        $data = $request['extensions']['itella-shipping'] ?? array();

        $selected_pickup_point_id = wc_clean($data['selected_pickup_id'] ?? '');
        $selected_rate_id = wc_clean($data['selected_rate_id'] ?? '');

        if ( ! empty($selected_rate_id) ) {
            $this->wc->update_order_meta($order->get_id(), 'itella_method', esc_attr($selected_rate_id));
        }
        
        if ( empty($selected_pickup_point_id) ) {
            return;
        }


        $country = $order->get_shipping_country();
        $selected_pickup_point = Itella_Shipping_Method::getInstance()->get_chosen_pickup_point($country, (int)$selected_pickup_point_id);
        if ( ! $selected_pickup_point ) {
            return;
        }
        $this->wc->update_order_meta($order->get_id(), 'itella_pp_id', esc_attr($selected_pickup_point->id));
        $this->wc->update_order_meta($order->get_id(), 'itella_pupCode', esc_attr($selected_pickup_point->pupCode));
    }
}
