<?php

/**
 * @package    Itella_Shipping
 * @subpackage Itella_Shipping/includes
 */

/**
 *
 * @package    Itella_Shipping
 * @subpackage Itella_Shipping/public
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
  public function __construct($name, $version, $available_countries)
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
    wp_enqueue_style($this->name . 'MarkerCluster.css', "https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css", array(), $this->version, 'all');
    wp_enqueue_style($this->name . 'MarkerCluster.Default.css', "https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css", array(), $this->version, 'all');
    wp_enqueue_style($this->name . 'itella-mapping.css', plugin_dir_url(__FILE__) . 'css/itella-mapping.css', array(), $this->version, 'all');
  }

  /**
   * Register the stylesheets for the public-facing side of the site.
   *
   * @since    1.0.0
   */
  public function enqueue_scripts()
  {
    wp_enqueue_script($this->name . 'leaflet.js', plugin_dir_url(__FILE__) . 'js/leaflet.min.js', array(), $this->version, TRUE);
//    wp_enqueue_script($this->name . 'leaflet.markercluster.js', "https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js", array($this->name . 'leaflet.js'), $this->version, TRUE);
    wp_enqueue_script($this->name . 'itella-mapping.js', plugin_dir_url(__FILE__) . 'js/itella-mapping.js', array($this->name . 'leaflet.js'), $this->version, TRUE);
    wp_enqueue_script($this->name . 'itella-shipping-public.js', plugin_dir_url(__FILE__) . 'js/itella-shipping-public.js', array(), $this->version, TRUE);
    wp_localize_script($this->name . 'itella-shipping-public.js',
        'variables', array(
            'imagesUrl' => plugin_dir_url(__FILE__) . 'assets/images/',
            'locationsUrl' => plugin_dir_url(__FILE__) . '/../../locations/',
            'translations' => array(
                'nothing_found' => __('Nothing found', 'itella-shipping'),
                'modal_header' => __('Pickup points', 'itella-shipping'),
                'selector_header' => __('Pickup point', 'itella-shipping'),
                'workhours_header' => __('Workhours', 'itella-shipping'),
                'contacts_header' => __('Contacts', 'itella-shipping'),
                'search_placeholder' => __('Enter postcode/address', 'itella-shipping'),
                'select_pickup_point' => __('Select a pickup point', 'itella-shipping'),
                'no_pickup_points' => __('No points to select', 'itella-shipping'),
                'select_btn' => __('select', 'itella-shipping'),
                'back_to_list_btn' => __('reset search', 'itella-shipping'),
                'select_pickup_point_btn' => __('Select pickup point', 'itella-shipping'),
                'no_information' => __('No information', 'itella-shipping'),
                'error_leaflet' => __('Leaflet is required for Itella-Mapping', 'itella-shipping'),
                'error_missing_mount_el' => __('No mount supplied to itellaShipping', 'itella-shipping')
            )
        )
    );

    wp_enqueue_script($this->name . 'itella-init-map.js', plugin_dir_url(__FILE__) . 'js/itella-init-map.js?20200601', array('jquery'), $this->version, TRUE);

  }

  /**
   * Show chosen pickup point after placing order
   *
   * @param $order
   */
  public function show_pp_details($order)
  {
    $chosen_itella_method = get_post_meta($order->get_id(), '_itella_method', true);
    if ($chosen_itella_method === 'itella_pp') {
      echo "<p>" . __('Itella Pickup Point', 'itella-shipping') . ": " . $this->get_pickup_point_public_name($order) . "</p>";
    }
  }

  /**
   * Add pickup point id to order
   *
   * @param $order_id
   */
  public function add_pp_id_to_order($order_id)
  {
    if (isset($_POST['itella-chosen-point-id']) && $order_id) {
      update_post_meta($order_id, '_pp_id', $_POST['itella-chosen-point-id']);
    }

    // set itella method todo refactor
    if (isset($_POST['shipping_method'][0]) && ($_POST['shipping_method'][0] === "itella_pp" || $_POST['shipping_method'][0] === "itella_c")) {
      update_post_meta($order_id, '_itella_method', $_POST['shipping_method'][0]);
    }
  }

  /**
   * Get chosen pickup point's public name from file
   *
   * @param $order
   * @return string|void
   */
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

    return $pickup_point_public_name ? $pickup_point_public_name : __('Itella Pickup Point not found!', 'itella-shipping');
  }

  /**
   * Show itella shipping methods for allowed countries
   *
   * @param $methods
   * @return mixed
   */
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

  /**
   * Add hidden fields
  */
  public function itella_checkout_hidden_fields()
  {
    // fix for Paypal Checkout page
    $ship_country = WC()->customer->get_shipping_country();
    if ( function_exists('wc_gateway_ppec') && isset($_GET['token']) ) {
      $token = $_GET['token'];
      $client   = wc_gateway_ppec()->client;
      $response = $client->get_express_checkout_details( $token );
      if ( isset($response['SHIPTOCOUNTRYCODE']) ) {
        $ship_country = $response['SHIPTOCOUNTRYCODE'];
      }
    }
    echo '<input type="hidden" id="itella_shipping_country" value="' . $ship_country . '">';
  }

}
