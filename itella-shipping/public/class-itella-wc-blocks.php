<?php

class Itella_Wc_blocks
{
    /**
     * This plugin information.
     *
     * @since    1.x.x
     * @access   private
     * @var      object $plugin
     */
    private $plugin;

    /**
     * Initialize the class and set its properties.
     *
     * @param object $plugin
     * @param array $available_countries
     * @since 1.x.x
     *
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;

        //$this->wc = new Itella_Shipping_Wc_Itella();
    }

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
        //add_action('woocommerce_store_api_checkout_update_order_from_request', array($this, 'update_block_order_meta'), 10, 2);
        
        add_filter('__experimental_woocommerce_blocks_add_data_attributes_to_namespace', function ( $allowed_namespaces ) {
            $allowed_namespaces[] = $this->plugin->name;
            return $allowed_namespaces;
        }, 10, 1);
    }

    public function itella_data_callback()
    {
        return array();
    }

    public function itella_schema_callback()
    {
        return array();
    }
}
