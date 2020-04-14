<?php

/**
 * Fired during plugin deactivation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Itella_Shipping
 * @subpackage Itella_Shipping/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Itella_Shipping
 * @subpackage Itella_Shipping/includes
 * @author     Your Name <email@example.com>
 */
class Itella_Shipping_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {

    require_once plugin_dir_path( __FILE__ ) . 'class-itella-shipping-cron.php';
    Itella_Shipping_Cron::unschedule();
	}

}
