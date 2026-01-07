<?php

/**
 * All the functions which need to communicate with WooCommerce
 *
 * @link       https://itella.lt
 * @since      1.4.1
 *
 * @package    Itella_Shipping
 * @subpackage Itella_Shipping/includes
 * @author     UAB Mijora <support@mijora.lt>
 * @author     Marijus Kundelis
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

    protected function check_and_get_order( $order )
    {
        if ( ! is_object($order) && (int)$order > 0 ) {
            $order = $this->get_order($order);
        }

        if ( ! $order ) {
            return false;
        }

        return $order;
    }

    public function save_order( $order )
    {
        if ( ! is_object($order) ) {
            return false;
        }

        if ( ! $order->get_id() ) {
            return false;
        }

        $order->save();

        return true;
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

    public function get_order_meta( $order, $meta_key )
    {
        $order = $this->check_and_get_order($order);
        if ( ! $order || empty($meta_key) ) {
            return '';
        }
        
        return $order->get_meta($meta_key, true);
    }

    public function update_order_meta( $order, $meta_key, $value, $save = true )
    {
        $order = $this->check_and_get_order($order);
        if ( ! $order || empty($meta_key) ) {
            return false;
        }

        $order->update_meta_data($meta_key, $value);

        if ( $save && ! doing_action('woocommerce_before_order_object_save') ) {
            $this->save_order($order);
        }

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
            $item_data = array(
                'product_id' => (int) $item->get_product_id(),
                'quantity' => (int) $item->get_quantity(),
                'weight' => 0,
                'length' => 0,
                'width' => 0,
                'height' => 0,
            );
            $product = $item->get_product();
            if ( $product ) {
                if ( !empty($product->get_weight()) ) $item_data['weight'] = (float) $product->get_weight();
                if ( !empty($product->get_length()) ) $item_data['length'] = (float) $product->get_length();
                if ( !empty($product->get_width()) ) $item_data['width'] = (float) $product->get_width();
                if ( !empty($product->get_height()) ) $item_data['height'] = (float) $product->get_height();
            }
            $all_items[$item_id] = (object) $item_data;
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

    public function is_wc_block_checkout() {
        global $post;

        if ( ! function_exists('has_blocks') || ! function_exists('has_block') ) {
            return false;
        }
        if ( empty($post) || ! ($post instanceof WP_Post) ) {
            return false;
        }
        if ( ! isset($post->post_content) || $post->post_content === '' ) {
            return false;
        }

        return has_blocks($post->post_content) && has_block('woocommerce/checkout', $post);
    }
}
