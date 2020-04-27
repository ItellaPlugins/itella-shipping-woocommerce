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
   * Itella shipping available country list
   *
   * @var array $available_countries
   */
  private $available_countries;

  /**
   * Initialize the class and set its properties.
   *
   * @param $name
   * @param $version
   * @param string[] $available_countries
   * @since    1.0.0
   *
   */
  public function __construct($name, $version, $available_countries = array('LT', 'LV', 'EE'))
  {

    $this->name = $name;
    $this->version = $version;
    $this->available_countries = $available_countries;

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
    wp_localize_script($this->name . 'itella-shipping-public.js',
        'variables', array(
            'imagesUrl' => plugin_dir_url(__FILE__) . 'assets/images/',
            'locationsUrl' => plugin_dir_url(__FILE__) . '/../../locations/',
            'translations' => array(
                'nothing_found' => __('Nothing found', 'itella_shipping'),
                'modal_header' => __('Pickup points', 'itella_shipping'),
                'selector_header' => __('Pickup point', 'itella_shipping'),
                'workhours_header' => __('Workhours', 'itella_shipping'),
                'contacts_header' => __('Contacts', 'itella_shipping'),
                'search_placeholder' => __('Enter postcode/address', 'itella_shipping'),
                'select_pickup_point' => __('Select a pickup point', 'itella_shipping'),
                'no_pickup_points' => __('No points to select', 'itella_shipping'),
                'select_btn' => __('select', 'itella_shipping'),
                'back_to_list_btn' => __('reset search', 'itella_shipping'),
                'select_pickup_point_btn' => __('Select pickup point', 'itella_shipping'),
                'no_information' => __('No information', 'itella_shipping'),
                'error_leaflet' => __('Leaflet is required for Itella-Mapping', 'itella_shipping'),
                'error_missing_mount_el' => __('No mount supplied to itellaShipping', 'itella_shipping')
            )
        )
    );

    wp_enqueue_script($this->name . 'itella-init-map.js', plugin_dir_url(__FILE__) . 'js/itella-init-map.js', array('jquery'), $this->version, TRUE);

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
    $pickup_points = file_get_contents(plugin_dir_url(__FILE__) . '../locations/locations' . $shipping_country . '.json');
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

  public function show_itella_shipping_methods($methods)
  {

    global $woocommerce;
    $current_country = $woocommerce->customer->get_shipping_country();

    if (!in_array($current_country, $this->available_countries)) {
      unset($methods['itella_pp']);
      unset($methods['itella_c']);
    }

    return $methods;
  }

}
