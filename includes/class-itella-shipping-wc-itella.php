<?php

/**
 * Functions for working with Itella data for the extension of Woocommerce functions
 *
 * @link       https://itella.lt
 * @since      1.4.3
 *
 * @package    Itella_Shipping
 * @subpackage Itella_Shipping/includes
 * @author     UAB Mijora <support@mijora.lt>
 * @author     Marijus Kundelis
 */

class Itella_Shipping_Wc_Itella extends Itella_Shipping_Wc {
    public function get_itella_data( $order )
    {
        $order = $this->check_and_get_order($order);
        if ( ! $order ) {
            return '';
        }

        return (object) array(
            'shipping_method' => $order->get_meta('itella_shipping_method', true),
            'itella_method' => $this->get_itella_method($order),
            'packet_count' => $order->get_meta('packet_count', true),
            'multi_parcel' => $order->get_meta('itella_multi_parcel', true),
            'extra_services' => $order->get_meta('itella_extra_services', true),
            'cod' => (object) array(
                'enabled' => $order->get_meta('itella_cod_enabled', true),
                'amount' => $order->get_meta('itella_cod_amount', true),
            ),
            'pickup' => (object) array(
                'id' => $this->get_itella_pp_id($order),
                'pupcode' => $this->get_itella_pp_code($order),
            ),
            'tracking' => (object) array(
                'code' => $this->get_itella_tracking_code($order),
                'url' => $this->get_itella_tracking_url($order),
                'error' => $order->get_meta('itella_tracking_code_error', true),
            ),
            'manifest' => (object) array(
                'date' => $this->get_itella_manifest_generation_date($order),
            ),
        );
    }

    public function save_itella_shipping_method( $order, $value )
    {
        // Not using
    }

    public function get_itella_shipping_method( $order )
    {
        return $this->get_order_meta($order, 'itella_shipping_method');
    }

    public function save_itella_method( $order, $value )
    {
        return $this->update_order_meta($order, 'itella_method', $value);
    }

    public function get_itella_method( $order )
    {
        $value = $this->get_order_meta($order, 'itella_method');
        if ( empty($value) ) { // Compatible with old
            $value = $this->get_order_meta($order, '_itella_method');
        }
        
        return $value;
    }

    public function save_itella_packet_count( $order, $value )
    {
        // Not using
    }

    public function get_itella_packet_count( $order )
    {
        return $this->get_order_meta($order, 'packet_count');
    }

    public function save_itella_multi_parcel( $order, $value )
    {
        return $this->update_order_meta($order, 'itella_multi_parcel', $value);
    }

    public function get_itella_multi_parcel( $order )
    {
        return $this->get_order_meta($order, 'itella_multi_parcel');
    }

    public function save_itella_extra_services( $order, $value )
    {
        // Not using
    }

    public function get_itella_extra_services( $order )
    {
        return $this->get_order_meta($order, 'itella_extra_services');
    }

    public function save_itella_cod_enabled( $order, $value )
    {
        // Not using
    }

    public function get_itella_cod_enabled( $order )
    {
        return $this->get_order_meta($order, 'itella_cod_enabled');
    }

    public function save_itella_cod_amount( $order, $value )
    {
        // Not using
    }

    public function get_itella_cod_amount( $order )
    {
        return $this->get_order_meta($order, 'itella_cod_amount');
    }

    public function save_itella_pp_id( $order, $value )
    {
        return $this->update_order_meta($order, 'itella_pp_id', $value);
    }

    public function get_itella_pp_id( $order )
    {
        $value = $this->get_order_meta($order, 'itella_pp_id');
        if ( empty($value) ) { // Compatible with old
            $value = $this->get_order_meta($order, '_pp_id');
        }
        
        return $value;
    }

    public function save_itella_pp_code( $order, $value )
    {
        return $this->update_order_meta($order, 'itella_pp_code', $value);
    }

    public function get_itella_pp_code( $order )
    {
        $value = $this->get_order_meta($order, 'itella_pp_code');
        if ( empty($value) ) { // Compatible with old
            $value = $this->get_order_meta($order, 'itella_pupCode');
        }
        
        return $value;
    }

    public function save_itella_tracking_code( $order, $value )
    {
        return $this->update_order_meta($order, 'itella_tracking_code', $value);
    }

    public function get_itella_tracking_code( $order )
    {
        $value = $this->get_order_meta($order, 'itella_tracking_code');
        if ( empty($value) ) { // Compatible with old
            $value = $this->get_order_meta($order, '_itella_tracking_code');
        }
        
        return $value;
    }

    public function save_itella_tracking_url( $order, $value )
    {
        return $this->update_order_meta($order, 'itella_tracking_url', $value);
    }

    public function get_itella_tracking_url( $order )
    {
        $value = $this->get_order_meta($order, 'itella_tracking_url');
        if ( empty($value) ) { // Compatible with old
            $value = $this->get_order_meta($order, '_itella_tracking_url');
        }
        
        return $value;
    }

    public function save_itella_tracking_code_error( $order, $value )
    {
        // Not using
    }

    public function get_itella_tracking_code_error( $order )
    {
        return $this->get_order_meta($order, 'itella_tracking_code_error');
    }

    public function save_itella_manifest_generation_date( $order, $value )
    {
        return $this->update_order_meta($order, 'itella_manifest_generation_date', $value);
    }

    public function get_itella_manifest_generation_date( $order )
    {
        $value = $this->get_order_meta($order, 'itella_manifest_generation_date');
        if ( empty($value) ) { // Compatible with old
            $value = $this->get_order_meta($order, '_itella_manifest_generation_date');
        }
        
        return $value;
    }
}
