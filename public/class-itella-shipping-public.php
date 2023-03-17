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
   * This plugin information.
   *
   * @since    1.3.7
   * @access   private
   * @var      object $plugin
   */
  private $plugin;

  /**
   * URL's for every assets group.
   *
   * @since    1.3.7
   * @access   private
   * @var      object $assets
   */
  private $assets;

  /**
   * Itella shipping available country list
   *
   * @since    1.1.0
   * @access   private
   * @var array $available_countries
   */
  private $available_countries;

  /**
   * Initialize the class and set its properties.
   *
   * @param object $plugin
   * @param array $available_countries
   * @since 1.0.0
   *
   */
  public function __construct($plugin)
  {
    $this->plugin = $plugin;
    $this->available_countries = $plugin->countries;

    $this->assets = (object) array(
      'css' => $plugin->url . 'public/assets/css/',
      'js' => $plugin->url . 'public/assets/js/',
      'img' => $plugin->url . 'public/assets/images/',
    );

    $this->itella_shipping = new Itella_Shipping_Method();
  }

  /**
   * Register the stylesheets for the public-facing side of the site.
   *
   * @since 1.0.0
   */
  public function enqueue_styles()
  {
    $css_files = array(
      'itella-shipping-public' => $this->assets->css . 'itella-shipping-public.css',
      'leaflet' => "https://unpkg.com/leaflet@1.5.1/dist/leaflet.css",
      'MarkerCluster' => "https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css",
      'MarkerCluster-Default' => "https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css",
      'itella-mapping' => $this->assets->css . 'itella-mapping.css',
    );

    foreach ( $css_files as $id => $url ) {
      wp_enqueue_style($this->plugin->name . '-' . $id, $url, array(), $this->plugin->version, 'all');
    }
  }

  /**
   * Register the stylesheets for the public-facing side of the site.
   *
   * @since 1.0.0
   */
  public function enqueue_scripts()
  {
    wp_enqueue_script($this->plugin->name . 'leaflet.js', $this->assets->js . 'leaflet.min.js', array(), $this->plugin->version, TRUE);
//    wp_enqueue_script($this->plugin->name . 'leaflet.markercluster.js', "https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js", array($this->plugin->name . 'leaflet.js'), $this->plugin->version, TRUE);
    wp_enqueue_script($this->plugin->name . 'itella-mapping.js', $this->assets->js . 'itella-mapping.js', array($this->plugin->name . 'leaflet.js'), $this->plugin->version, TRUE);
    wp_enqueue_script($this->plugin->name . 'itella-shipping-public.js', $this->assets->js . 'itella-shipping-public.js', array(), $this->plugin->version, TRUE);
    wp_localize_script($this->plugin->name . 'itella-shipping-public.js',
        'variables', array(
            'show_style' => $this->itella_shipping->settings['checkout_show_style'],
            'imagesUrl' => $this->assets->img,
            'locationsUrl' => $this->plugin->url . 'locations/',
            'translations' => array(
                'nothing_found' => __('Nothing found', 'itella-shipping'),
                'modal_header' => __('Parcel lockers', 'itella-shipping'),
                'selector_header' => __('Parcel locker', 'itella-shipping'),
                'workhours_header' => __('Workhours', 'itella-shipping'),
                'contacts_header' => __('Contacts', 'itella-shipping'),
                'search_placeholder' => __('Enter postcode/address', 'itella-shipping'),
                'select_pickup_point' => __('Select a parcel locker', 'itella-shipping'),
                'no_pickup_points' => __('No locker to select', 'itella-shipping'),
                'select_btn' => __('select', 'itella-shipping'),
                'back_to_list_btn' => __('reset search', 'itella-shipping'),
                'select_pickup_point_btn' => __('Select parcel locker', 'itella-shipping'),
                'no_information' => __('No information', 'itella-shipping'),
                'error_leaflet' => __('Leaflet is required for Itella-Mapping', 'itella-shipping'),
                'error_missing_mount_el' => __('No mount supplied to itellaShipping', 'itella-shipping')
            )
        )
    );

    wp_enqueue_script($this->plugin->name . 'itella-init-map.js', $this->assets->js . 'itella-init-map.js', array('jquery'), $this->plugin->version, TRUE);

  }

  /**
   * Show chosen pickup point after placing order
   *
   * @param $order
   */
  public function show_pp_details($order)
  {
    $chosen_itella_method = $order->get_meta('_itella_method');
    $tracking_code = $order->get_meta('_itella_tracking_code');
    $tracking_url = $order->get_meta('_itella_tracking_url');
    $shipping_method = $order->get_shipping_method();
    $pickup_point_name = $this->get_pickup_point_public_name($order);

    if ( empty($pickup_point_name) && empty($tracking_code) ) {
      return;
    }

    echo '<div class="itella-ship-info">';
    echo '<h2>' . __('Shipping information', 'itella-shipping') . '</h2>';
    echo '<table>';
    echo '<tr><th>' . __('Shipping method', 'itella-shipping') . '</th><td>' . $shipping_method . '</td></tr>';

    if ( ! empty($pickup_point_name) ) {
      echo '<tr><th>' . __('Deliver to', 'itella-shipping') . '</th><td>' . $pickup_point_name . '</td></tr>';
    }
    
    if ( ! empty($tracking_code) ) {
      echo '<tr><th>' . __('Tracking code', 'itella-shipping') . '</th><td>';
      if ( ! empty($tracking_url) ) {
        echo '<a href="' . $tracking_url . '" target="_blank">' . $tracking_code . '</a>';
      } else {
        echo '<span>' . $tracking_code . '</span>';
      }
      echo '</td></tr>';
    } else {
      echo '<tr><th>' . __('Status', 'itella-shipping') . '</th><td>' . __('Not shipped', 'itella-shipping') . '</td></tr>';
    }

    echo '</table></div>';
  }

  /**
   * Add pickup point id to order
   *
   * @param $order_id
   */
  public function add_pp_id_to_order($order_id)
  {
    $this->save_pp_id_to_order($order_id);
    $this->save_method_to_order($order_id);
  }

  public function check_pp_id_in_order($order)
  {
    try {
      $pp_in_order = get_post_meta($order->get_id(), '_pp_id', true);
      $method_in_order = get_post_meta($order->get_id(), '_itella_method', true);

      if ( empty($pp_in_order) && isset($_POST['itella-chosen-point-id']) ) {
        $this->save_pp_id_to_order($order->get_id());
      }
      if ( empty($method_in_order) && $_POST['shipping_method'] ) {
        $this->save_method_to_order($order->get_id());
      }
    } catch(\Exception $e) {
      //Nothing
    }
  }

  private function save_pp_id_to_order($order_id)
  {
    if ( isset($_POST['itella-chosen-point-id']) && $order_id ) {
      update_post_meta($order_id, '_pp_id', $_POST['itella-chosen-point-id']);
      $country = (!empty($_POST['shipping_country'])) ? $_POST['shipping_country'] : $_POST['billing_country'];
      $pickup_point = $this->itella_shipping->get_chosen_pickup_point($country, $_POST['itella-chosen-point-id']);
      update_post_meta($order_id, 'itella_pupCode', $pickup_point->pupCode);
    }
  }

  private function save_method_to_order($order_id)
  {
    if ( isset($_POST['shipping_method']) && is_array($_POST['shipping_method']) ) {
      foreach ( $_POST['shipping_method'] as $shipping_method ) {
        if ( $shipping_method == 'itella_pp' || $shipping_method == 'itella_c' ) {
          update_post_meta($order_id, '_itella_method', $shipping_method);
        }
      }
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
    $pickup_points = file_get_contents($this->plugin->path . 'locations/locations' . $shipping_country . '.json');
    $pickup_points = json_decode($pickup_points);

    if ( empty($pickup_points) ) {
        return '';
    }

    foreach ($pickup_points as $pickup_point) {
      $chosen_pickup_point = $pickup_point->id === $chosen_pickup_point_id ? $pickup_point : null;
      if ($chosen_pickup_point) {
        $pickup_point_public_name = $chosen_pickup_point->address->municipality . ' - ' .
                    $chosen_pickup_point->address->address . ', ' .
                    $chosen_pickup_point->address->postalCode . ' (' .
                    $chosen_pickup_point->publicName . ')';
        break;
      }
    }

    return $pickup_point_public_name ? $pickup_point_public_name : '';
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
