<?php

/**
 * Register all actions and filters for the plugin
 *
 * @link       http://example.com
 * @since      1.5.0
 *
 * @package    Itella_Shipping
 * @subpackage Itella_Shipping/includes
 */

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 *
 * @package    Itella_Shipping
 * @subpackage Itella_Shipping/includes
 * @author     UAB Mijora <support@mijora.lt>
 */

class Itella_Shipping_Wc {
    public function get_order( $order_id )
    {
        if ( empty($order_id) ) {
            return false;
        }

        $order = wc_get_order($order_id);
        if ( ! $order ) {
            return false;
        }

        return $order;
    }

    public function get_orders( $args )
    {
        return wc_get_orders($args);
    }

    private function check_and_get_order( $order )
    {
        if ( ! is_object($order) && (int)$order > 0 ) {
            $order = $this->get_order($order);
        }

        if ( ! $order ) {
            return false;
        }

        return $order;
    }

    public function get_order_data( $order, $get_data = '' )
    {
        $order = $this->check_and_get_order($order);
        if ( ! $order ) {
            return '';
        }

        $order_data = array(
            'id' => $order->get_id(),
            'number' => $order->get_order_number(),
            'status' => $order->get_status(),
            'created' => $order->get_date_created()->format('Y-m-d H:i:s'),
            'payment_method' => $order->get_payment_method(),
            'total' => $order->get_total(),
            'currency' => $order->get_currency(),
        );

        return ($get_data !== '' && isset($order_data[$get_data])) ? $order_data[$get_data] : (object) $order_data;
    }

    public function get_order_itella_data( $order )
    {
        $order = $this->check_and_get_order($order);
        if ( ! $order ) {
            return '';
        }

        return (object) array(
            'shipping_method' => $order->get_meta('itella_shipping_method', true),
            'itella_method' => $order->get_meta('_itella_method', true),
            'packet_count' => $order->get_meta('packet_count', true),
            'multi_parcel' => $order->get_meta('itella_multi_parcel', true),
            'extra_services' => $order->get_meta('itella_extra_services', true),
            'cod' => (object) array(
                'enabled' => $order->get_meta('itella_cod_enabled', true),
                'amount' => $order->get_meta('itella_cod_amount', true),
            ),
            'pickup' => (object) array(
                'id' => $order->get_meta('_pp_id', true),
                'pupcode' => $order->get_meta('itella_pupCode', true),
            ),
            'tracking' => (object) array(
                'code' => $order->get_meta('_itella_tracking_code', true),
                'url' => $order->get_meta('_itella_tracking_url', true),
                'error' => $order->get_meta('_itella_tracking_code_error', true),
            ),
            'manifest' => (object) array(
                'date' => $order->get_meta('_itella_manifest_generation_date', true),
            ),
        );
    }

    public function get_order_meta( $order, $meta_key )
    {
        $order = $this->check_and_get_order($order);
        if ( ! $order || empty($meta_key) ) {
            return '';
        }
        
        return $order->get_meta($meta_key, true);
    }

    public function update_order_meta( $order, $meta_key, $value )
    {
        $order = $this->check_and_get_order($order);
        if ( ! $order || empty($meta_key) ) {
            return false;
        }

        $order->update_meta_data($meta_key, $value);
        $order->save();

        return true;
    }

    public function delete_order_meta( $order, $meta_key, $delete_value = '' )
    {
        $order = $this->check_and_get_order($order);
        if ( ! $order || empty($meta_key) ) {
            return false;
        }

        $order->delete_meta_data($meta_key, $delete_value);
        $order->save();

        return true;
    }

    public function get_order_items( $order )
    {
        $all_items = array();

        $order = $this->check_and_get_order($order);
        if ( ! $order ) {
            return $all_items;
        }

        foreach ( $order->get_items() as $item_id => $item ) {
            $product = $item->get_product();
            $all_items[$item_id] = (object)array(
                'product_id' => (int)$item->get_product_id(),
                'quantity' => (int)$item->get_quantity(),
                'weight' => (!empty($product->get_weight())) ? (float)$product->get_weight() : 0,
                'length' => (!empty($product->get_length())) ? (float)$product->get_length() : 0,
                'width' => (!empty($product->get_width())) ? (float)$product->get_width() : 0,
                'height' => (!empty($product->get_height())) ? (float)$product->get_height() : 0,
            );
        }

        return $all_items;
    }

    public function get_order_shipping_methods( $order )
    {
        $all_methods = array();

        $order = $this->check_and_get_order($order);
        if ( ! $order ) {
            return $all_methods;
        }

        foreach ( $order->get_items('shipping') as $item_id => $item ) {
            $all_methods[$item_id] = (object)array(
                'method_id' => $item->get_method_id(),
            );
        }

        return $all_methods;
    }

    public function get_units( $get_as_object = true )
    {
        $units = array(
            'weight' => get_option('woocommerce_weight_unit'),
            'dimension' => get_option('woocommerce_dimension_unit'),
            'currency' => get_option('woocommerce_currency'),
            'currency_symbol' => get_woocommerce_currency_symbol(),
        );

        return ($get_as_object) ? (object) $units : $units;
    }

    public function get_customer_data( $user_id = false )
    {
        if ( $user_id ) {
            $customer = new WC_Customer($user_id);
        } else {
            $customer = $this->get_global_wc_property('customer');
        }

        if ( empty($customer) ) {
            return false;
        }

        return $customer;
    }

    public function get_cart()
    {
        $cart = $this->get_global_wc_property('cart');
        
        if ( empty($cart) ) {
            return false;
        }

        return $cart;
    }

    public function get_cart_items()
    {
        $cart = $this->get_cart();

        if ( ! $cart || ! method_exists($cart, 'get_cart') ) {
            return array();
        }

        return $cart->get_cart();
    }

    public function get_global_wc()
    {
        if ( function_exists('WC') ) {
            return WC();
        }

        global $woocommerce;
        return $woocommerce;
    }

    public function get_global_wc_property( $property_name )
    {
        $wc_class = $this->get_global_wc();

        if ( ! property_exists($wc_class, $property_name) && ! method_exists($wc_class, $property_name) ) {
            return false;
        }

        return $wc_class->{$property_name};
    }

    public function get_product( $product_id )
    {
        if ( empty($product_id) ) {
            return false;
        }

        $product = wc_get_product($product_id);
        if ( empty($product) ) {
            return false;
        }

        return $product;
    }

    public function get_country_name( $country_code )
    {
        $countries = $this->get_global_wc_property('countries');
        
        if ( empty($countries) || ! isset($countries->countries[$country_code]) ) {
            return $country_code;
        }

        return $countries->countries[$country_code];
    }

    public function get_shipping_classes()
    {
        $shipping = $this->get_global_wc_property('shipping');

        if ( empty($shipping) ) {
            return array();
        }

        return $shipping->get_shipping_classes();
    }

    public function get_all_order_statuses()
    {
        return wc_get_order_statuses();
    }

    public function get_order_status_name( $status_key )
    {
        return wc_get_order_status_name($status_key);
    }

    public function clean( $value )
    {
        return wc_clean($value);
    }

    public function string_to_upper( $string )
    {
        return wc_strtoupper($string);
    }

    public function add_notice( $text, $type = 'notice' )
    {
        wc_add_notice($text, $type);
    }

    public function get_help_tip( $text, $allow_html = false )
    {
        return wc_help_tip($text, $allow_html);
    }

    public function convert_weight( $weight, $to_unit, $from_unit = '' )
    {
        return wc_get_weight($weight, $to_unit, $from_unit);
    }
}
