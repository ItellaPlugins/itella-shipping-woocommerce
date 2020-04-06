<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * Dashboard. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * this starts the plugin.
 *
 * @link
 * @since             1.0.0
 * @package           Itella_Shipping
 *
 * @wordpress-plugin
 * Plugin Name:       Itella Shipping Plugin
 * Plugin URI:
 * Description:       A shipping plugin for Itella
 * Version:           1.0.0
 * Author:
 * Author URI:
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       itella-shipping
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-itella-shipping-activator.php';

/**
 * The code that runs during plugin deactivation.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-itella-shipping-deactivator.php';

/** This action is documented in includes/class-itella-shipping-activator.php */
register_activation_hook( __FILE__, array('Itella_Shipping_Activator', 'activate' ) );

/** This action is documented in includes/class-itella-shipping-deactivator.php */
register_activation_hook( __FILE__, array('Itella_Shipping_Deactivator', 'deactivate' ) );

/**
 * The core plugin class that is used to define internationalization,
 * dashboard-specific hooks, and public-facing site hooks.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-itella-shipping.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_itella_shipping() {

	$plugin = new Itella_Shipping();

}
run_itella_shipping();