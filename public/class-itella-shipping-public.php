<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Itella_Shipping
 * @subpackage Itella_Shipping/includes
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    Itella_Shipping
 * @subpackage Itella_Shipping/admin
 * @author     Your Name <email@example.com>
 */
class Itella_Shipping_Public
{

  /**
   * The ID of this plugin.
   *
   * @since    1.0.0
   * @access   private
   * @var      string $name The ID of this plugin.
   */
  private $name;

  /**
   * The version of this plugin.
   *
   * @since    1.0.0
   * @access   private
   * @var      string $version The current version of this plugin.
   */
  private $version;

  /**
   * Initialize the class and set its properties.
   *
   * @since    1.0.0
   * @var      string $name The name of the plugin.
   * @var      string $version The version of this plugin.
   */
  public function __construct($name, $version)
  {

    $this->name = $name;
    $this->version = $version;

  }

  /**
   * Register the stylesheets for the public-facing side of the site.
   *
   * @since    1.0.0
   */
  public function enqueue_styles()
  {
    wp_enqueue_style($this->name . 'itella-shipping-public.css', plugin_dir_url(__FILE__) . 'css/itella-shipping-public.css', array(), $this->version, 'all');
    wp_enqueue_style($this->name . 'leaflet.css', "https://unpkg.com/leaflet@1.5.1/dist/leaflet.css", array(), $this->version, 'all');
    wp_enqueue_style($this->name . 'itella-mapping.css', plugin_dir_url(__FILE__) . 'css/itella-mapping.css', array(), $this->version, 'all');
  }

  /**
   * Register the stylesheets for the public-facing side of the site.
   *
   * @since    1.0.0
   */
  public function enqueue_scripts()
  {
    wp_enqueue_script($this->name . 'leaflet.js', "https://unpkg.com/leaflet@1.5.1/dist/leaflet.js", array(), $this->version, TRUE);
    wp_enqueue_script($this->name . 'itella-mapping.js', plugin_dir_url(__FILE__) . 'js/itella-mapping.js', array(), $this->version, TRUE);
    wp_enqueue_script($this->name . 'itella-shipping-public.js', plugin_dir_url(__FILE__) . 'js/itella-shipping-public.js', array(), $this->version, TRUE);
    wp_localize_script($this->name . 'itella-shipping-public.js', 'mapScript', array('pluginsUrl' => plugin_dir_url(__FILE__)));
    wp_enqueue_script($this->name . 'itella-init-map.js', plugin_dir_url( __FILE__ ) . 'js/itella-init-map.js', array( 'jquery' ), $this->version, TRUE );

  }

  public function show_pp_details($order)
  {
    $chosen_itella_method = get_post_meta($order->get_id(), '_itella_method', true);
    if ($chosen_itella_method === 'itella_pp') {
      echo "<p>" . __('Itella Pickup Point', 'itella_shipping') . ": " . $this->get_pickup_point_public_name($order) . "</p>";
    }

  }

  public function add_pp_id_to_order($order_id)
  {
    if (isset($_POST['itella-chosen-point-id']) && $order_id) {
      update_post_meta($order_id, '_pp_id', $_POST['itella-chosen-point-id']);
    }
    if (isset($_POST['shipping_method'][0]) && ($_POST['shipping_method'][0] === "itella_pp" || $_POST['shipping_method'][0] === "itella_c")) {
      update_post_meta($order_id, '_itella_method', $_POST['shipping_method'][0]);
    }
  }

  public function get_pickup_point_public_name($order)
  {
    global $woocommerce;
    $chosen_pickup_point = null;
    $pickup_point_public_name = null;

    $shipping_country = $woocommerce->customer->get_shipping_country();
    $chosen_pickup_point_id = get_post_meta($order->get_id(), '_pp_id', true);
    $pickup_points = file_get_contents(plugin_dir_url(__FILE__) . '../locations/locations' . $shipping_country .'.json');
    $pickup_points = json_decode($pickup_points);

    foreach ($pickup_points as $pickup_point) {
      $chosen_pickup_point = $pickup_point->id === $chosen_pickup_point_id ? $pickup_point : null;
      if ($chosen_pickup_point) {
        $pickup_point_public_name = $chosen_pickup_point->publicName;

        break;
      }
    }

    return $pickup_point_public_name ?? __('Itella Pickup Point not found!', 'itella_shipping');
  }

}
