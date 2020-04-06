<?php

/**
 * Fired during plugin activation
 *
 * @link
 * @since      1.0.0
 *
 * @package    Itella_Shipping
 * @subpackage Itella_Shipping/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Itella_Shipping
 * @subpackage Itella_Shipping/includes
 * @author     Your Name <email@example.com>
 */
class Itella_Shipping_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
    set_transient( 'itella-shipping-activated', true, 3 );
	}

}
