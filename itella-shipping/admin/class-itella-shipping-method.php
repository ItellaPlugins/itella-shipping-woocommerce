<?php

use Mijora\Itella\CallCourier;
use Mijora\Itella\Helper as ItellaHelper;
use Mijora\Itella\ItellaException;
use Mijora\Itella\Locations\PickupPoints;
use Mijora\Itella\Pdf\Manifest;
use Mijora\Itella\Pdf\PDFMerge;
use Mijora\Itella\Shipment\AdditionalService;
use Mijora\Itella\Shipment\GoodsItem;
use Mijora\Itella\Shipment\Party;
use Mijora\Itella\Shipment\Shipment;

/**
 * The dashboard-specific functionality of the plugin.
 *
 * @package    Itella_Shipping
 * @subpackage Itella_Shipping/admin
 */
class Itella_Shipping_Method extends WC_Shipping_Method
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
   * Class to get and controll Woocommerce data
   * 
   * @since    1.4.1
   * @access   private
   * @var      class $wc Itella_Shipping_Wc class
   */
  private $wc;

  /**
   * Helper class of this class with custom functions
   * 
   * @since    1.4.0
   * @access   private
   * @var      class $helper Itella_Shipping_Method_Helper class
   */
  private $helper;

  /**
   * Class that generates all the HTML elements
   * 
   * @since    1.4.1
   * @access   private
   * @var      class $html Itella_Shipping_Admin_Display class
   */
  private $html;

  /**
   * @var string
   */
  public $id;

  /**
   * @var array
   */
  private $itella_methods;

  /**
   * @var array
   */
  private $plugin_url;

  /**
   * Available sender countries
   * 
   * @since 1.4.0
   * @access private
   * @var array
   */
  private $sender_countries;

  /**
   * Available receiver countries
   * 
   * @since 1.4.0
   * @access private
   * @var array
   */
  private $available_countries;

  /**
   * Available receiver countries for each method
   * 
   * @since 1.4.0
   * @access private
   * @var array
   */
  private $grouped_countries;

  private static $instance = null;

  /**
   * Initialize the class and set its properties.
   *
   * @since    1.0.0
   */
  public function __construct()
  {
    $plugin = Itella_Shipping::get_instance()->get_plugin_data();

    parent::__construct();

    $this->name = $plugin->name ?? 'itella-shipping';
    $this->version = $plugin->version ?? '1.0.0';
    $this->id = "itella-shipping";
    $this->method_title = __('Smartposti Shipping', 'itella-shipping');
    $this->method_description = __('Plugin to use with Smartposti Shipping methods', 'itella-shipping');
    $this->title = "Smartposti Shipping Method";
    $this->itella_methods = $plugin->methods ?? array();
    $this->plugin_url = $plugin->url ?? home_url() . '/';

    $this->available_countries = $plugin->countries ?? array();
    $this->grouped_countries = $plugin->countries_grouped ?? array();
    $this->sender_countries = $plugin->sender_countries ?? array();
    $this->wc = new Itella_Shipping_Wc_Itella();
    $this->helper = new Itella_Shipping_Method_Helper();
    $this->html = new Itella_Shipping_Admin_Display($this->id);

    $this->init();
  }

  public static function getInstance() {
    if ( self::$instance === null ) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public static function getSettings() {
    return self::getInstance()->settings;
  }

  public function plugin_links($links)
  {
    array_unshift($links, '<a href="' .
      admin_url( 'admin.php?page=wc-settings&tab=shipping&section=' . $this->id ) .
      '">' . __('Settings', 'itella-shipping') . '</a>');
    return $links;
  }

  /**
   * Register the stylesheets for the Dashboard.
   *
   * @since    1.0.0
   */
  public function enqueue_styles($hook)
  {
    wp_enqueue_style($this->name, plugin_dir_url(__FILE__) . 'js/itella-shipping-popup/itella-shipping-popup.css', array(), $this->version, 'all');

    if ( ($hook == 'woocommerce_page_wc-settings' && isset($_GET['section']) && $_GET['section'] == $this->id)
      || ($hook == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'shop_order')
      || ($hook == 'woocommerce_page_wc-orders' && isset($_GET['action']) && $_GET['action'] == 'edit') ) {
      wp_enqueue_style($this->name, plugin_dir_url(__FILE__) . 'css/itella-shipping-admin.css', array(), $this->version, 'all');
    }
  }

  /**
   * Register the JavaScript for the dashboard.
   *
   * @since    1.0.0
   */
  public function enqueue_scripts($hook)
  {
    wp_enqueue_script($this->name . 'itella-shipping-popup.js', plugin_dir_url(__FILE__) . 'js/itella-shipping-popup/itella-shipping-popup.js', array('jquery'), $this->version, TRUE);

    if ( $hook == 'woocommerce_page_wc-settings' && isset($_GET['section']) && $_GET['section'] == $this->id ) {
      wp_enqueue_script($this->name . 'itella-shipping-admin.js', plugin_dir_url(__FILE__) . 'assets/js/itella-shipping-admin.js', array('jquery'), $this->version, TRUE);
    }
    if ( $hook == 'post.php'
      || ($hook == 'woocommerce_page_wc-orders' && isset($_GET['action']) && $_GET['action'] == 'edit') ) {
      wp_enqueue_script($this->name . 'itella-shipping-edit-orders.js', plugin_dir_url(__FILE__) . 'assets/js/itella-shipping-edit-orders.js', array('jquery'), $this->version, TRUE);
    } else if ( ($hook == 'woocommerce_page_wc-orders' && isset($_GET['page']) && $_GET['page'] == 'wc-orders')
      || ($hook == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'shop_order') ) {
      wp_enqueue_script($this->name . 'itella-shipping-orders-list.js', plugin_dir_url(__FILE__) . 'assets/js/itella-shipping-orders-list.js', array('jquery'), $this->version, TRUE);
      wp_localize_script($this->name . 'itella-shipping-orders-list.js', 'itellaParams', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce_register' => wp_create_nonce('itella_shipments'),
        //'nonce_check' => wp_create_nonce('itella_cron_check'), //TODO: Use only if separated
      ));
      wp_localize_script($this->name . 'itella-shipping-orders-list.js', 'itellaTranslations', array(
        'registering_shipments' => __('Shipments registration is in progress. Please wait...', 'itella-shipping'),
        'left_actions' => __('Still %d requests in queue...', 'itella-shipping'),
        'register_completed' => __('Shipments registration is complete', 'itella-shipping'),
        'reloading_page' => __('Reloading page...', 'itella-shipping'),
        'check_fail' => sprintf(__('Unable to check if shipments registration is still in progress. You can see the queue on the "%s" page.', 'itella-shipping'), '<a href="' . Itella_Shipping_Cron::get_queue_page_url('itella_cronjob_register_shipment') . '" target="_blank">' . __('Scheduled Actions', 'woocommerce') . '</a>'),
        'error' => __('Error', 'itella-shipping'),
        'error_unknown' => __('Unknown error', 'itella-shipping')
      ));
    }
  }

  /**
   * Init settings
   *
   * @access public
   * @return void
   */
  public function init()
  {
    $this->init_form_fields();
    $this->init_settings();

    // Update settings page values after save
    add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
  }

  /**
   * Load settings form
   */
  function admin_options()
  {
    ?>
    <h2><?php echo $this->method_title; ?></h2>
    <p><?php echo $this->method_description; ?></p>
    <table class="form-table itella-settings">
    <?php $this->generate_settings_html(); ?>
    </table>
    <?php
  }

  public function get_translated_weekday($weekday)
  {
    switch ($weekday) {
      case 'Monday':
        return __('Monday', 'default'); // Use WP translations domain
      case 'Tuesday':
        return __('Tuesday', 'default');
      case 'Wednesday':
        return __('Wednesday', 'default');
      case 'Thursday':
        return __('Thursday', 'default');
      case 'Friday':
        return __('Friday', 'default');
      case 'Saturday':
        return __('Saturday', 'default');
      case 'Sunday':
        return __('Sunday', 'default');
      default:
        return $weekday;
    }
  }

    /**
     * Get available itella shipping methods list
     */
    private function get_itella_shipping_methods()
    {
        return $this->itella_methods;
    }

  /**
   * Update locations
   *
   * @param string[] $country_codes
   */
  public function update_locations()
  {
    $itella_pickup_points_obj = new PickupPoints('https://delivery.plugins.itella.com/api/locations');

    foreach ( $this->available_countries as $country_code ) {
      $filename = plugin_dir_path(dirname(__FILE__))
          . 'locations/locations' . $this->wc->string_to_upper($country_code) . '.json';
      $update_file = false;

      if ( ! file_exists($filename) ) {
        file_put_contents($filename, '');
        $update_file = true;
      }

      if ( (time() - filemtime($filename)) > 86400 ) {
        $update_file = true;
      }

      if ( $update_file ) {
        $locations = $itella_pickup_points_obj->getLocationsByCountry($country_code);

        if ( ! empty($locations) ) {
          $itella_pickup_points_obj->saveLocationsToJSONFile($filename, json_encode($locations));
        } else {
          file_put_contents($filename, '');
        }
      }
    }
  }

  public function all_additional_services_names()
  {
    return array(
      'oversized' => __('Oversized', 'itella-shipping'),
      'fragile' => __('Fragile', 'itella-shipping'),
      'call_before_delivery' => __('Call before delivery', 'itella-shipping'),
    );
  }

  public function get_additional_service_name( $service_key )
  {
    $all_names = $this->all_additional_services_names();

    return $all_names[$service_key] ?? $service_key;
  }

  /**
   * Calculate_shipping function.
   *
   * @access public
   * @param mixed $package
   * @return void
   */
  public function calculate_shipping($package = array())
  {
    $current_country = strtoupper($this->wc->get_customer_data()->get_shipping_country());
    $cart_amount = floatval($this->wc->get_cart()->cart_contents_total) + floatval($this->wc->get_cart()->tax_total);
    $cart_weight = floatval($this->wc->get_cart()->cart_contents_weight);
    $items = $package['contents'] ?? $this->wc->get_cart_items();

    $all_methods = $this->get_itella_shipping_methods();
    $country_methods = $all_methods[strtoupper($current_country)] ?? array();
    $all_prices_settings = (!empty($this->settings['methods'])) ? json_decode($this->settings['methods'],true) : $this->methods_backward_compatibility();
    $country_prices = $all_prices_settings[strtolower($current_country)] ?? array();

    foreach ( $country_prices as $method => $prices ) {
      if ( $this->settings[$method . '_method'] === 'yes' ) {
        if ( ! isset($prices['price_type']) || $prices['price_type'] == 'disabled' ) {
          continue;
        }

        if ( ! $this->check_size_restrictions($method, $cart_weight, $items) ) {
          continue;
        }

        $shipping_price = $this->get_shipping_price($prices, array(
          'amount' => $cart_amount,
          'weight' => $cart_weight,
          'country' => $current_country,
          'items' => $items,
        ));

        if ( $shipping_price === false ) {
          continue;
        }

        $rate = array(
          'id' => 'itella_' . Itella_Shipping::get_instance()->get_method_short_key($method),
          'label' => (! empty($prices['name'])) ? $prices['name'] : 'Smartposti ' . $country_methods[$method],
          'cost' => $shipping_price
        );

        $this->add_rate($rate);
      }
    }
  }

  /**
   * Get shipping price for rate
   *
   * @access public
   * @param array $method_prices
   * @param array $cart_values
   * @return mixed
   */
  private function get_shipping_price( $method_prices, $cart_values )
  {
    $cart = array(
      'amount' => $cart_values['amount'] ?? 0,
      'weight' => $cart_values['weight'] ?? 0,
      'country' => ( ! in_array($cart_values['country'], $this->available_countries) ) ? $cart_values['country'] : 'LT',
      'items' => $cart_values['items'] ?? array(),
    );

    if ( empty($method_prices) ) {
      $method_prices = array();
    }

    $prices = array(
      'single' => $method_prices['price']['single'] ?? 0,
      'by_weight' => $method_prices['price']['by_weight'] ?? array(),
      'by_amount' => $method_prices['price']['by_amount'] ?? array(),
      'by_ship_class' => $method_prices['price']['by_ship_class'] ?? array(),
      'free_from' => $method_prices['price']['free_from'] ?? 0,
    );
    $price_type = $method_prices['price_type'] ?? 'single';

    switch ( $price_type ) {
      case 'weight':
        $ship_price = $this->get_rate_amount_from_table($prices['by_weight'], $cart['weight']);
        break;
      case 'amount':
        $ship_price = $this->get_rate_amount_from_table($prices['by_amount'], $cart['amount']);
        break;
      default:
        $ship_price = $prices['single'];
    }

    if ( $ship_price === false ) {
      return $ship_price;
    }

    $price_by_class = $this->get_rate_amount_by_class($prices['by_ship_class'], $cart['items']);
    if ( $price_by_class !== false ) {
      $ship_price = $price_by_class;
    }

    $free_from = floatval($prices['free_from']);
    if ( $free_from > 0 && $free_from <= $cart['amount'] ) {
      $ship_price = 0;
    }

    return $ship_price;
  }

  /**
   * Get shipping price from rates table
   *
   * @access public
   * @param array $prices_values
   * @param array $cart_values
   * @return mixed
   */
  private function get_rate_amount_from_table( $prices_values, $cart_value )
  {
    $rate_price = false;
    $prev_value = -0.001;
    foreach ($prices_values as $key => $value) {
      if (empty($value['value']) && $value['value'] !== 0 && $value['value'] !== '0') {
        $value['value'] = 1000000;
      }

      if ($cart_value > $prev_value && $cart_value <= $value['value']) {
        $rate_price = (isset($value['price'])) ? $value['price'] : 0;
      }

      $prev_value = $value['value'];
    }

    return $rate_price;
  }

  /**
   * Get shipping price from "Price by class" table
   *
   * @access public
   * @param array $prices_values
   * @param array $cart_items
   * @return mixed
   */
  private function get_rate_amount_by_class( $prices_values, $cart_items )
  {
    $rate_price = false;

    if ( ! is_array($cart_items) || empty($prices_values) ) {
      return $rate_price;
    }

    foreach ( $prices_values as $class_price ) {
      if ( empty($class_price['value']) ) {
        continue;
      }
      $all_have_class = true;
      foreach ( $cart_items as $item_id => $item ) {
        $shipping_class = $item['data']->get_shipping_class_id();
        if ( empty($shipping_class) || $class_price['value'] != $shipping_class ) {
          $all_have_class = false;
        }
      }
      if ( $all_have_class ) {
        $rate_price = $class_price['price'];
      }
    }

    return $rate_price;
  }

  private function check_size_restrictions( $method, $cart_weight, $cart_items )
  {
    $max_size = (! empty($this->settings[$method . '_max_size'])) ? json_decode($this->settings[$method . '_max_size'], true) : array();
    if ( empty($max_size) ) {
      return true;
    }

    if ( ! empty($max_size['weight']) ) {
      if ( (float)$cart_weight > 0 && (float)$cart_weight > (float)$max_size['weight'] ) {
        return false;
      }
    }

    if ( ! empty($max_size['length']) && ! empty($max_size['width']) && ! empty($max_size['height']) ) {
      try {
        $products = array();
        
        foreach ( $cart_items as $item ) {
          if ( empty($item['product_id']) ) {
            return true;
          }
          $product = $this->wc->get_product($item['product_id']);
          for ( $i = 1; $i <= $item['quantity']; $i++ ) {
            $products[] = array(
              'id' => $product->get_id(),
              'length' => (!empty($product->get_length())) ? $product->get_length() : 0,
              'width' => (!empty($product->get_width())) ? $product->get_width() : 0,
              'height' => (!empty($product->get_height())) ? $product->get_height() : 0,
            );
          }
        }

        $cart_dimmension = $this->helper->predict_cart_dimmension($products, $max_size);
        if ( ! $cart_dimmension ) {
          return false;
        }
      } catch(Exception $e) {
        //echo $e->getMessage();
        return true;
      }
    }

    return true;
  }

  /**
   * Initialise Itella shipping settings form
   */
  function init_form_fields()
  {
    $allowed_comment_variables_pp = array(
      'order_id' => __('Order ID', 'itella-shipping'),
    );
    $allowed_comment_variables_c = array(
      'order_id' => __('Order ID', 'itella-shipping'),
    );

    $shop_countries = array('EE', 'FI', 'LV', 'LT');
    $shop_countries_options = array();
    foreach ( $shop_countries as $country ) {
      $shop_countries_options[$country] = $country . ' - ' . $this->wc->get_country_name($country);
    }

    $fields = array(
        'enabled' => array(
            'title' => __('Enable', 'itella-shipping'),
            'type' => 'checkbox',
            'description' => __('Enable this shipping.', 'itella-shipping'),
            'default' => 'yes'
        ),
        'hr_api' => array(
            'type' => 'hr'
        ),
        'api_user_2711' => array(
            'title' => __('API user (Product 2711)', 'itella-shipping'),
            'type' => 'text',
        ),
        'api_pass_2711' => array(
            'title' => __('Api password (Product 2711)', 'itella-shipping'),
            'type' => 'text',
        ),
        'api_contract_2711' => array(
            'title' => __('Api contract number (Product 2711)', 'itella-shipping'),
            'type' => 'text',
        ),
        'api_user_2317' => array(
            'title' => __('API user (Product 2317)', 'itella-shipping'),
            'type' => 'text',
        ),
        'api_pass_2317' => array(
            'title' => __('Api password (Product 2317)', 'itella-shipping'),
            'type' => 'text',
        ),
        'api_contract_2317' => array(
            'title' => __('Api contract number (Product 2317)', 'itella-shipping'),
            'type' => 'text',
        ),
        'hr_sender' => array(
            'type' => 'hr'
        ),
        'company' => array(
            'title' => __('Company name', 'itella-shipping'),
            'type' => 'text',
        ),
        'company_code' => array(
            'title' => __('Company code', 'itella-shipping'),
            'type' => 'text',
        ),
        'bank_account' => array(
            'title' => __('Bank account', 'itella-shipping'),
            'type' => 'text',
        ),
        'cod_bic' => array(
            'title' => __('BIC', 'itella-shipping'),
            'type' => 'text',
        ),
        'shop_name' => array(
            'title' => __('Shop name', 'itella-shipping'),
            'type' => 'text',
        ),
        'shop_city' => array(
            'title' => __('Shop city', 'itella-shipping'),
            'type' => 'text',
        ),
        'shop_address' => array(
            'title' => __('Shop address', 'itella-shipping'),
            'type' => 'text',
        ),
        'shop_postcode' => array(
            'title' => __('Shop postcode', 'itella-shipping'),
            'type' => 'text',
        ),
        'shop_countrycode' => array(
            'title' => __('Shop country code', 'itella-shipping'),
            'type'    => 'select',
            'class' => 'checkout-style pickup-point',
            'options' => $shop_countries_options,
            'default' => 'LT',
        ),
        'shop_phone' => array(
            'title' => __('Shop phone number', 'itella-shipping'),
            'type' => 'text',
        ),
        'shop_email' => array(
            'title' => __('Shop email', 'itella-shipping'),
            'type' => 'email',
        ),
        'hr_methods' => array(
            'type' => 'hr'
        ),
        'pickup_point_method' => array(
            'title' => __('Enable Parcel locker', 'itella-shipping'),
            'class' => 'method-cb-pickup_point',
            'type' => 'checkbox',
            'description' => __('Show parcel locker shipping method in checkout.', 'itella-shipping'),
            'default' => 'no'
        ),
        'courier_method' => array(
            'title' => __('Enable Courier', 'itella-shipping'),
            'type' => 'checkbox',
            'class' => 'method-cb-courier',
            'description' => __('Show courier shipping method in checkout.', 'itella-shipping'),
            'default' => 'no'
        ),
    );

    $fields['methods'] = array(
        'title' => __('Methods', 'itella-shipping'),
        'type' => 'methods',
        'countries_methods' => $this->get_itella_shipping_methods(),
    );

    $fields['hr_front'] = array(
        'type' => 'hr'
    );

    $fields['checkout_show_style'] = array(
        'title' => __('Parcel locker selection style', 'itella-shipping'),
        'type'    => 'select',
        'class' => 'checkout-style field-toggle-pickup_point',
        'options' => array(
            'map'  => __('Map', 'itella-shipping'),
            'dropdown' => __('Dropdown', 'itella-shipping'),
        ),
        'default' => 'map',
        'description' => __('Choose what the parcel locker selection in the checkout will look like.', 'itella-shipping'),
    );

    $fields['disable_outdoors_pickup_points'] = array(
        'title' => __('Exclude outdoors parcel lockers', 'itella-shipping'),
        'type' => 'checkbox',
        'class' => 'field-toggle-pickup_point',
        'description' => __('In the Checkout page dont show parcel lockers that have "Outdoors" parameter', 'itella-shipping'),
    );

    $fields['show_checkout_logo'] = array(
        'title' => __('Show logo in Checkout', 'itella-shipping'),
        'type' => 'checkbox',
        'class' => 'field-toggle-pickup_point',
        'description' => __('On the checkout page, display the logo next to the shipping method', 'itella-shipping'),
    );

    $fields['courier_max_size'] = array(
      'title' => __('Courier max size', 'itella-shipping'),
      'type' => 'size',
      'class' => 'field-toggle-courier',
      'description' => __('The maximum size of the cart above which the delivery method will not be shown.', 'itella-shipping')
        . ' ' . __('Leave all dimension fields or weight field empty to disable checking by that parameter.', 'itella-shipping')
        . ' ' . __('Preliminary cart size is calculated by trying to fit all products by taking their dimensions (boxes) indicated in their settings.', 'itella-shipping')
    );

    $fields['pickup_point_max_size'] = array(
      'title' => __('Parcel locker max size', 'itella-shipping'),
      'type' => 'size',
      'class' => 'field-toggle-pickup_point',
      'description' => __('The maximum size of the cart above which the delivery method will not be shown.', 'itella-shipping')
        . ' ' . __('Leave all dimension fields or weight field empty to disable checking by that parameter.', 'itella-shipping')
        . ' ' . __('Preliminary cart size is calculated by trying to fit all products by taking their dimensions (boxes) indicated in their settings.', 'itella-shipping')
    );

    $fields['hr_labels'] = array(
        'type' => 'hr'
    );

    $comment_c_desc = __('Add custom comment to label', 'itella-shipping') . '.';
    foreach ($allowed_comment_variables_c as $key => $desc) {
      $comment_c_desc .= '<br/><code>{' . $key . '}</code> - ' . $desc;
    }
    $fields['comment_c'] = array(
      'title' => __('Courier label comment', 'itella-shipping'),
      'type' => 'text',
      'class' => 'field-toggle-courier',
      'description' => $comment_c_desc,
    );

    $comment_pp_desc = __('Add custom comment to label', 'itella-shipping') . '.';
    foreach ($allowed_comment_variables_pp as $key => $desc) {
      $comment_pp_desc .= '<br/><code>{' . $key . '}</code> - ' . $desc;
    }
    $fields['comment_pp'] = array(
      'title' => __('Parcel locker label comment', 'itella-shipping'),
      'type' => 'text',
      'class' => 'field-toggle-pickup_point',
      'description' => $comment_pp_desc,
    );

    $fields['hr_courier_call'] = array(
        'type' => 'hr'
    );

    $fields['call_courier_message'] = array(
        'title' => __('Pickup note', 'itella-shipping'),
        'type' => 'text',
        'description' => __('A message related to the pickup of shipments is sent to the courier when the courier is called', 'itella-shipping'),
    );
    foreach ($this->sender_countries as $country_code) {
      $fields['call_courier_mail_' . $country_code] = array(
        'title' => sprintf(__('Smartposti %s email', 'itella-shipping'), strtoupper($country_code)),
        'type' => 'text',
        'default' => sprintf('smartship.routing.%s@itella.com', strtolower($country_code)),
      );
    }
    $fields['call_courier_mail_subject'] = array(
        'title' => __('Smartposti email subject', 'itella-shipping'),
        'type' => 'text',
        'default' => 'E-com order booking',
    );
    $this->form_fields = $fields;
  }

    public function generate_methods_html( $key, $value )
    {
        $field_key = $this->get_field_key($key);
        $fields_values = $this->get_option($key);
        if ( is_string($fields_values) ) {
            $fields_values = json_decode($fields_values, true);
        }
        $weight_unit = get_option('woocommerce_weight_unit');

        if ( empty($fields_values) ) {
            $old_plugin_values = $this->methods_backward_compatibility();
            $fields_values = (!empty($old_plugin_values)) ? $old_plugin_values : array();
        }
        $title_html = (isset($value['title'])) ? $this->html->settings_row_title($value['title']) : '';
        $countries_methods = $value['countries_methods'] ?? array();

        $shipping_classes_options = array();
        foreach ( $this->wc->get_shipping_classes() as $ship_class ) {
            $shipping_classes_options[$ship_class->term_id] = $ship_class->name;
        }

        ob_start();
        ?>
        <tr valign="top">
            <?php echo $title_html; ?>
            <td class="forminp itella-methods" <?php if (empty($title_html)) echo 'colspan="2"'; ?>>
                <?php foreach ( $countries_methods as $country => $methods ) : ?>
                    <?php $country = strtolower($country); ?>
                    <?php $field_key_country = $field_key . '[' . $country . ']'; ?>
                    <?php $field_id_country = $field_key . '_' . $country; ?>
                    <div class="itella-country itella-country-<?php echo $country; ?>">
                        <div class="title">
                            <img src="<?php echo $this->plugin_url . 'admin/assets/flags/' . $country . '.png'; ?>" alt="[<?php echo strtoupper($country); ?>]"/>
                            <span><?php echo $this->wc->get_country_name(strtoupper($country)); ?></span>
                        </div>
                        <div class="content">
                            <?php foreach ( $methods as $method_key => $method_title ) : ?>
                                <?php $field_key_method = $field_key_country . '[' . $method_key . ']'; ?>
                                <?php $field_id_method = $field_id_country . '_' . $method_key; ?>
                                <?php $method_values = $fields_values[$country][$method_key] ?? array(); ?>
                                <div class="itella-method itella-method-<?php echo $method_key; ?>">
                                    <p class="method_title"><?php echo $method_title; ?></p>
                                    <div class="method_params">
                                        <?php
                                        echo $this->helper->methods_select_field_html(array(
                                            'label' => __('Price type', 'itella-shipping'),
                                            'id' => $field_id_method . '_price_type',
                                            'name' => $field_key_method . '[price_type]',
                                            'value' => $method_values['price_type'] ?? '',
                                            'options' => array(
                                                'disabled' => __('Dont use this method', 'itella-shipping'),
                                                'single' => __('Fixed price', 'itella-shipping'),
                                                'weight' => __('Price by cart weight', 'itella-shipping'),
                                                'amount' => __('Price by cart amount', 'itella-shipping'),
                                            ),
                                            'class' => 'row-price_type',
                                        ));
                                        echo $this->helper->methods_number_field_html(array(
                                            'label' => __('Price', 'itella-shipping'),
                                            'id' => $field_id_method . '_price_single',
                                            'name' => $field_key_method . '[price][single]',
                                            'value' => $method_values['price']['single'] ?? null,
                                            'default' => 3,
                                            'step' => 0.01,
                                            'min' => 0,
                                            'class' => 'row-price-single',
                                        ));
                                        echo $this->helper->methods_multirows_field_html(array(
                                            'type' => 'weight',
                                            'label' => __('Prices', 'itella-shipping'),
                                            'id' => $field_id_method . '_price_weight',
                                            'name' => $field_key_method . '[price][by_weight]',
                                            'value' => $method_values['price']['by_weight'] ?? null,
                                            'step' => 0.001,
                                            'min' => 0,
                                            'title_col_1' => sprintf(__('Weight (%s)', 'itella-shipping'), $weight_unit),
                                            'title_col_2' => __('Price', 'itella-shipping'),
                                            'class' => 'row-price-weight',
                                        ));
                                        echo $this->helper->methods_multirows_field_html(array(
                                            'type' => 'amount',
                                            'label' => __('Prices', 'itella-shipping'),
                                            'id' => $field_id_method . '_price_amount',
                                            'name' => $field_key_method . '[price][by_amount]',
                                            'value' => $method_values['price']['by_amount'] ?? null,
                                            'step' => 0.01,
                                            'min' => 0,
                                            'title_col_1' => __('Amount range', 'itella-shipping'),
                                            'title_col_2' => __('Price', 'itella-shipping'),
                                            'class' => 'row-price-amount',
                                        ));
                                        echo $this->helper->methods_multirows_field_html(array(
                                            'type' => 'shipclass',
                                            'field_type' => 'select',
                                            'label' => __('Prices by class', 'itella-shipping'),
                                            'id' => $field_id_method . '_price_shipclass',
                                            'name' => $field_key_method . '[price][by_ship_class]',
                                            'value' => $method_values['price']['by_ship_class'] ?? null,
                                            'options' => $shipping_classes_options,
                                            'description' => __('Set custom price for specific shipping class. Only works when all items in the cart belong to this class', 'itella-shipping')
                                        ));
                                        echo $this->helper->methods_number_field_html(array(
                                            'label' => __('Free from', 'itella-shipping'),
                                            'id' => $field_id_method . '_price_free_from',
                                            'name' => $field_key_method . '[price][free_from]',
                                            'value' => $method_values['price']['free_from'] ?? null,
                                            'default' => '',
                                            'step' => 0.01,
                                            'min' => 0,
                                            'description' => __('Disable shipping method fee if cart amount is greater or equal than this limit', 'itella-shipping'),
                                        ));
                                        echo $this->helper->methods_text_field_html(array(
                                            'label' => __('Custom name', 'itella-shipping'),
                                            'id' => $field_id_method . '_name',
                                            'name' => $field_key_method . '[name]',
                                            'value' => $method_values['name'] ?? null,
                                            'default' => '',
                                            'description' => __('A custom shipping method name that will display on the cart/checkout page. Many translation plugins do not translate this field value.', 'itella-shipping'),
                                        ));
                                        echo $this->helper->methods_textarea_field_html(array(
                                            'label' => __('Description', 'itella-shipping'),
                                            'id' => $field_id_method . '_description',
                                            'name' => $field_key_method . '[description]',
                                            'value' => $method_values['description'] ?? null,
                                            'default' => '',
                                            'rows' => 2,
                                            'description' => __('A description next to shipping method that will display on the cart/checkout page. Many translation plugins do not translate this field value.', 'itella-shipping'),
                                        ));
                                        ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </td>
        </tr>
        <?php
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    private function methods_backward_compatibility()
    {
        $old_countries = array('lt', 'ee', 'lv', 'fi');
        $old_keys = array(
            'pickup_point_price',
            'pickup_point_classes',
            'pickup_point_nocharge_amount',
            'pickup_point_description',
            'courier_price',
            'courier_classes',
            'courier_nocharge_amount',
            'courier_description'
        );

        $old_values = array();
        foreach ( $old_keys as $key ) {
            foreach ( $old_countries as $country ) {
                $value = $this->get_option($key . '_' . $country);
                $old_values[$country][$key] = ($this->helper->is_json($value)) ? json_decode($value, true) : $value;
            }
        }

        $new_values = array();
        foreach ( $old_countries as $country ) {
            $price_type = $old_values[$country]['pickup_point_price']['radio'] ?? '';
            $price_type = ($price_type == 'price') ? 'amount' : $price_type;
            $pickup_point_values = array(
                'price_type' => $price_type,
                'price' => array(
                    'single' => $old_values[$country]['pickup_point_price']['single'] ?? '',
                    'by_weight' => $old_values[$country]['pickup_point_price']['byWeight'] ?? '',
                    'by_amount' => $old_values[$country]['pickup_point_price']['byPrice'] ?? '',
                    'by_ship_class' => array(
                        array(
                            'value' => $old_values[$country]['pickup_point_classes'][0]['ship_class'] ?? '',
                            'price' => $old_values[$country]['pickup_point_classes'][0]['price'] ?? '',
                        ),
                    ),
                    'free_from' => $old_values[$country]['pickup_point_nocharge_amount'] ?? '',
                ),
                'description' => $old_values[$country]['pickup_point_description'] ?? '',
            );
            $new_values[$country]['pickup_point'] = $pickup_point_values;

            $price_type = $old_values[$country]['courier_price']['radio'] ?? '';
            $price_type = ($price_type == 'price') ? 'amount' : $price_type;
            $courier_values = array(
                'price_type' => $price_type,
                'price' => array(
                    'single' => $old_values[$country]['courier_price']['single'] ?? '',
                    'by_weight' => $old_values[$country]['courier_price']['byWeight'] ?? '',
                    'by_amount' => $old_values[$country]['courier_price']['byPrice'] ?? '',
                    'by_ship_class' => array(
                        array(
                            'value' => $old_values[$country]['courier_classes'][0]['ship_class'] ?? '',
                            'price' => $old_values[$country]['courier_classes'][0]['price'] ?? '',
                        ),
                    ),
                    'free_from' => $old_values[$country]['courier_nocharge_amount'] ?? '',
                ),
                'description' => $old_values[$country]['courier_description'] ?? '',
            );
            $new_values[$country]['courier'] = $courier_values;
        }

        return $new_values;
    }

    public function validate_methods_field( $key, $value )
    {
        $values = wp_json_encode($value);
        return $values;
    }

  public function generate_price_by_weight_html( $key, $value )
  {
    $field_key = $this->get_field_key($key);

    if ( $this->get_option($key) !== '' ) {
      $values = $this->get_option($key);
      if ( is_string($values) ) {
        $values = json_decode($this->get_option($key), true);
      }
    } else {
      $values = array();
    }

    $table_weight_values = array();
    if (isset($values['byWeight'])) {
      foreach ($values['byWeight'] as $k => $vals) {
        $value_value = (isset($vals['value'])) ? $vals['value'] : 0;
        $value_price = (isset($vals['price'])) ? $vals['price'] : $value['default'];
        array_push($table_weight_values, array($value_value,$value_price));
      }
    } else {
      if (isset($values['weight'])) { // Compatibility with old versions
        foreach ($values['weight'] as $k => $val) {
          $price_value = (isset($values['price'][$k])) ? $values['price'][$k] : $value['default'];
          array_push($table_weight_values, array($val,$price_value));
        }
      }
    }

    $table_prices_values = array();
    if (isset($values['byPrice'])) {
      foreach ($values['byPrice'] as $k => $vals) {
        $value_value = (isset($vals['value'])) ? $vals['value'] : 0;
        $value_price = (isset($vals['price'])) ? $vals['price'] : $value['default'];
        array_push($table_prices_values, array($value_value,$value_price));
      }
    }

    if (is_array($values)) {
      $single_value = (isset($values['single'])) ? esc_html($values['single']) : esc_html($value['default']);
    } else {
      $single_value = (!empty($values)) ? esc_html($values) : esc_html($value['default']);
    }

    $radio_list = array(
      'disabled' => __('Disabled', 'itella-shipping'),
      'single' => _x('Simple', 'Price type', 'itella-shipping'),
      'weight' => __('By weight', 'itella-shipping'),
      'price' => __('By amount', 'itella-shipping'),
    );
    $radio_checked = 'single';

    $show_fieldset = 'single';
    if (isset($values['radio'])) {
      foreach ($radio_list as $radio_key => $radio_label) {
        $radio_checked = ($values['radio'] == $radio_key) ? $radio_key : $radio_checked;
      }
      $show_fieldset = ($radio_checked == 'disabled') ? '' : $show_fieldset;
      $show_fieldset = ($radio_checked == 'single') ? 'single' : $show_fieldset;
      $show_fieldset = ($radio_checked == 'weight') ? 'table-weight' : $show_fieldset;
      $show_fieldset = ($radio_checked == 'price') ? 'table-price' : $show_fieldset;
    } else { // Compatibility with old versions
      if (empty($single_value)) {
        $radio_checked = 'disabled';
        $show_fieldset = '';
      }
      if (isset($values['cb'])) {
        $radio_checked = 'weight';
        $show_fieldset = 'table-weight';
      }
    }
    $weight_unit = $this->wc->get_units()->weight;


    ob_start();
    ?>
    <tr valign="top">
        <?php echo $this->html->settings_row_title($value['title']); ?>
      <td class="forminp itella-price_by_weight">
        <fieldset class="field-radio">
          <?php foreach ($radio_list as $radio_key => $radio_label) : ?>
            <?php
            $radio_id = $field_key . '_radio_' . $radio_key;
            ?>
            <div class="radio-option">
              <input type="radio" name="<?php echo esc_html($field_key); ?>[radio]" id="<?php echo esc_html($radio_id); ?>" value="<?php echo esc_html($radio_key); ?>" <?php echo ($radio_checked == $radio_key) ? 'checked' : ''; ?>>
              <label for="<?php echo esc_html($radio_id); ?>"><?php echo $radio_label; ?></label>
            </div>
          <?php endforeach; ?>
        </fieldset>
        <fieldset class="field-number" <?php echo ($show_fieldset !== 'single') ? 'style="display:none;"' : ''; ?>>
          <legend class="screen-reader-text"><span><?php echo esc_html($value['title']); ?></span></legend>
          <input class="input-text regular-input <?php echo esc_html($value['class']); ?>" type="number"
            name="<?php echo esc_html($field_key); ?>[single]" id="<?php echo esc_html($field_key); ?>"
            value="<?php echo $single_value; ?>" step="0.01" min="0">
          <?php if (!empty($value['description'])) : ?>
            <p class="description"><?php echo esc_html($value['description']); ?></p>
          <?php endif; ?>
        </fieldset>
        <fieldset class="field-table table-weight" <?php echo ($show_fieldset !== 'table-weight') ? 'style="display:none;"' : ''; ?>>
          <?php
          $table_params = array(
            'key' => $field_key,
            'type' => 'byWeight',
            'class' => $value['class'],
            'column-1-title' => sprintf(__('Weight (%s)', 'itella-shipping'), $weight_unit),
            'values' => $table_weight_values,
            'step' => 0.001,
          );
          echo $this->build_prices_table_html($table_params);
          ?>
        </fieldset>
        <fieldset class="field-table table-price" <?php echo ($show_fieldset !== 'table-price') ? 'style="display:none;"' : ''; ?>>
          <?php
          $table_params = array(
            'key' => $field_key,
            'type' => 'byPrice',
            'class' => $value['class'],
            'column-1-title' => __('Amount range', 'itella-shipping'),
            'values' => $table_prices_values,
            'step' => 0.01,
          );
          echo $this->build_prices_table_html($table_params);
          ?>
        </fieldset>
      </td>
    </tr>
    <?php
    $html = ob_get_contents();
    ob_end_clean();
    return $html;
  }

  private function build_prices_table_html( $params=array() )
  {
    $params['key'] = (isset($params['key'])) ? $params['key'] : 'itella';
    $params['type'] = (isset($params['type'])) ? $params['type'] : '';
    $params['class'] = (isset($params['class'])) ? $params['class'] : '';
    $params['column-1-title'] = (isset($params['column-1-title'])) ? $params['column-1-title'] : '';
    $params['column-2-title'] = (isset($params['column-2-title'])) ? $params['column-2-title'] : __('Price', 'itella-shipping');
    $params['column-3-title'] = (isset($params['column-3-title'])) ? $params['column-3-title'] : '';
    $params['values'] = (isset($params['values']) && is_array($params['values'])) ? $params['values'] : array();
    $params['step'] = (isset($params['step'])) ? $params['step'] : 0.01;
    $decimal = strlen(substr(strrchr((float)$params['step'], "."), 1));
    
    ob_start();
    ?>
    <table class="<?php echo esc_html($params['class']); ?>">
      <tr>
        <th class="column-values"><?php echo esc_html($params['column-1-title']); ?></th>
        <th class="column-price"><?php echo esc_html($params['column-2-title']); ?></th>
        <th class="column-actions"><?php echo esc_html($params['column-3-title']); ?></th>
      </tr>
      <?php $prev_value = 0; ?>
      <?php for ($i=0;$i<count($params['values']);$i++) : ?>
        <?php
        $next_value = (isset($params['values'][$i+1]) && $params['values'][$i+1][0] != '') ? $params['values'][$i+1][0]-$params['step'] : '';
        $min_value = ($prev_value > 0) ? $prev_value + $params['step'] : $prev_value;
        ?>
        <tr valign="middle" class="row-values">
          <td class="column-values">
            <span class="from_value"><?php echo ($i == 0) ? number_format((float)$prev_value, $decimal, '.', '') : number_format((float)$prev_value+$params['step'], $decimal, '.', ''); ?> -</span>
            <input type="number" value="<?php echo $params['values'][$i][0]; ?>"
              id="<?php echo esc_html($params['key'] . '_' . $params['type'] . '_' . ($i+1)); ?>"
              name="<?php echo esc_html($params['key'] . '[' . $params['type'] . '][' . $i . '][value]'); ?>"
              min="<?php echo $min_value; ?>" max="<?php echo $next_value; ?>" step="<?php echo $params['step'];?>"
              <?php if (!isset($params['values'][$i+1])) echo 'readonly'; ?>>
          </td>
          <td class="column-price">
            <input type="number" id="<?php echo esc_html($params['key'] . '_' . $params['type'] . '_price_' . ($i+1)); ?>"
              name="<?php echo esc_html($params['key'] . '[' . $params['type'] . '][' . $i . '][price]'); ?>" value="<?php echo $params['values'][$i][1]; ?>" min="0" step="0.01">
          </td>
          <td class="column-actions">
            <button class="remove-row">X</button>
          </td>
        </tr>
        <?php $prev_value = $params['values'][$i][0]; ?>
      <?php endfor; ?>
      <tr>
        <td colspan="3" class="column-footer">
          <button class="insert-row" data-id="<?php echo esc_html($params['key']); ?>"
            data-type="<?php echo esc_html($params['type']); ?>"
            data-step="<?php echo esc_html($params['step']); ?>"
          ><?php echo __('Add row', 'itella-shipping'); ?></button>
        </td>
      </tr>
    </table>
    <?php
    $html = ob_get_contents();
    ob_end_clean();
    return $html;
  }

  public function validate_price_by_weight_field( $key, $value )
  {
    $values = wp_json_encode($value);
    return $values;
  }

  public function generate_hr_html( $key, $value )
  {
    $class = (isset($value['class'])) ? $value['class'] : '';
    ob_start();
    ?>
    <tr valign="top">
      <td colspan="2" class="itella-hr">
        <hr class="' . $class . '">
        <?php if (isset($value['title'])) : ?>
          <small><?php echo esc_html($value['title']); ?></small>
        <?php endif; ?>
      </td>
    </tr>
    <?php
    $html = ob_get_contents();
    ob_end_clean();
    return $html;
  }

  public function generate_section_name_html( $key, $value )
  {
    $class = (isset($value['class'])) ? $value['class'] : '';
    ob_start();
    ?>
    <tr valign="top">
      <td colspan="2" class="itella-section_name">
        <small class="<?php echo $class;?>"><?php echo esc_html($value['title']); ?></small>
      </td>
    </tr>
    <?php
    $html = ob_get_contents();
    ob_end_clean();
    return $html;
  }

  public function generate_shipping_class_html( $key, $value )
  {
    $field_key = $this->get_field_key($key);
    $shipping_classes = $this->wc->get_shipping_classes();
    $values = $this->get_field_array_values($key);

    ob_start();
    ?>
    <tr valign="top">
      <th scope="row" class="titledesc">
        <label><?php echo esc_html($value['title']); ?></label>
      </th>
      <td class="forminp itella-shipping_class">
        <fieldset class="field-select">
          <?php for ($i=0;$i<1;$i++) : ?>
            <?php
            $this_name = esc_html($field_key) . '[' . $i . ']';
            $this_key = esc_html($field_key) . '_' . ($i+1);
            ?>
            <select name="<?php echo $this_name; ?>[ship_class]" id="<?php echo $this_key; ?>_class">
              <?php $selected = (!isset($values[$i]['ship_class']) || empty($values[$i]['ship_class'])) ? 'selected' : ''; ?>
              <option value="" <?php echo $selected; ?>>-</option>
              <?php foreach ($shipping_classes as $ship_class) : ?>
                <?php $selected = (isset($values[$i]['ship_class']) && $values[$i]['ship_class'] == $ship_class->term_id) ? 'selected' : ''; ?>
                <option value="<?php echo $ship_class->term_id; ?>" <?php echo $selected; ?>><?php echo $ship_class->name; ?></option>
              <?php endforeach; ?>
            </select>
            <?php $price = (isset($values[$i]['price'])) ? $values[$i]['price'] : ''; ?>
            <label for="<?php echo $this_key; ?>_price"><?php echo __('Price', 'itella-shipping'); ?>:</label>
            <input class="input-text regular-input <?php echo esc_html($value['class']); ?>" type="number"
              name="<?php echo $this_name; ?>[price]" id="<?php echo $this_key; ?>_price"
              value="<?php echo $price; ?>" step="0.01" min="0">
          <?php endfor; ?>
          <?php if (!empty($value['description'])) : ?>
            <p class="description"><?php echo esc_html($value['description']); ?></p>
          <?php endif; ?>
        </fieldset>
      </td>
    </tr>
    <?php
    $html = ob_get_contents();
    ob_end_clean();
    return $html;
  }

  public function validate_shipping_class_field( $key, $value )
  {
    $values = wp_json_encode($value);
    return $values;
  }

  private function get_field_array_values( $field_key )
  {
    $values = array();

    if ( $this->get_option($field_key) !== '' ) {
      $values = $this->get_option($field_key);
      if ( is_string($values) ) {
        $values = json_decode($this->get_option($field_key), true);
      }
    }

    return $values;
  }

  public function generate_size_html( $key, $value )
  {
    return $this->html->settings_cart_size_row(array(
      'title' => $value['title'],
      'id_prefix' => $key,
      'values' => $this->get_field_array_values($key),
      'class' => $value['class'] ?? '',
      'description' => $value['description'] ?? '',
    ));
  }

  public function validate_size_field( $key, $value )
  {
    $values = wp_json_encode($value);
    return $values;
  }

  public function add_shipping_details_to_order($order)
  {
    if ( ! $this->wc->get_order($order) ) {
      return;
    }

    if ( ! $this->check_itella_method($order) ) {
      return;
    }

    $order_items = $this->wc->get_order_items($order);
    $order_data = $this->wc->get_order_data($order);
    if ( empty($order_data) ) {
      return;
    }
    $itella_data = $this->wc->get_itella_data($order);

    $services_names = $this->all_additional_services_names();

    //check if shipping was previously updated
    $is_shipping_updated = (!empty($itella_data->shipping_method));

    $itella_method = $is_shipping_updated ? $itella_data->shipping_method : $itella_data->itella_method;

    if ($itella_method) {

      // defaults
      $oversized = 'oversized';
      $call_before_delivery = 'call_before_delivery';
      $fragile = 'fragile';
      $default_packet_count = '1';
      $default_weight = 0;
      $extra_services = array();
      $default_is_cod = Itella_Manifest::is_cod_payment($order_data->payment_method);
      $default_cod_amount = $order_data->total;

      $extra_services_options = array(
          $oversized => $services_names['oversized'],
          $call_before_delivery => $services_names['call_before_delivery'],
          $fragile => $services_names['fragile'],
      );

      foreach ( $order_items as $item ) {
        $default_weight += floatval($item->weight * $item->quantity);
      }

      // vars
      if ($is_shipping_updated) {
        $packet_count = $itella_data->packet_count;
        $weight = $this->wc->get_order_meta($order, 'weight_total');
        $is_cod = $itella_data->cod->enabled === 'yes';
        $cod_amount = $itella_data->cod->amount;
        $extra_services = $itella_data->extra_services;
        if (!is_array($extra_services)) {
          $extra_services = array($extra_services);
        }
      }

      $packet_count = !empty($packet_count) ? $packet_count : $default_packet_count;
      $weight = !empty($weight) ? $weight : $default_weight;
      $is_cod = !empty($is_cod) && $is_cod ? $is_cod : $default_is_cod;
      $cod_amount = !empty($cod_amount) ? $cod_amount : $default_cod_amount;

      $is_itella_pp = $itella_method === 'itella_pp';
      $is_itella_c = $itella_method === 'itella_c';

      $chosen_pickup_point_id = $itella_data->pickup->id;
      $chosen_pickup_point_pupcode = $itella_data->pickup->pupcode;
      $chosen_pickup_point = $this->get_chosen_pickup_point(Itella_Manifest::order_getCountry($order), $chosen_pickup_point_id, $chosen_pickup_point_pupcode);
      $chosen_pickup_point_pupcode = $this->fix_pupcode($chosen_pickup_point_pupcode, $chosen_pickup_point);

      $weight_unit = $this->wc->get_units()->weight;

      // packet html select element options
      $packets = array();
      for ($i = 1; $i < 11; $i++) {
        $packets[$i] = strval($i);
      }

      ?>
        <br class="clear"/>
        <h4><?= __('Smartposti Shipping Options', 'itella-shipping') ?><a href="#" class="edit_address"
                                                                      id="itella-shipping-options">Edit</a></h4>
        <div class="address">
            <p>
                <strong><?= __('Packets (total):', 'itella-shipping') ?></strong> <?= $packet_count ?>
            </p>
            <p>
                <strong><?= sprintf(__('Total weight (%s):', 'itella-shipping'), $weight_unit) ?></strong> <?= number_format((float)$weight,3) ?>
            </p>
            <p><strong><?= __('COD:', 'itella-shipping') ?></strong>
              <?=
              $is_cod ? __('Yes', 'woocommerce') : __('No', 'woocommerce')
              ?>
            </p>
          <?php if ($is_cod): ?>
              <p>
                  <strong><?= sprintf(__('COD amount (%s):', 'itella-shipping'), $order_data->currency) ?></strong> <?= $cod_amount ?>
              </p>
          <?php endif; ?>
            <p><strong><?= __('Shipping method:', 'itella-shipping') ?></strong>
              <?=
              $is_itella_pp ? __('Parcel locker', 'itella-shipping') :
                  ($is_itella_c ? __('Courier', 'itella-shipping') :
                      __('No Smartposti Shipping method selected', 'itella-shipping'))
              ?>
            </p>
          <?php if ($is_itella_pp): ?>
              <p><strong><?= __('Chosen Parcel locker', 'itella-shipping') ?></strong>
                <?php if ( ! empty($chosen_pickup_point) ) : ?>
                  <?=
                  $chosen_pickup_point->address->municipality . ' - ' .
                  $chosen_pickup_point->address->address . ', ' .
                  $chosen_pickup_point->address->postalCode . ' (' .
                  $chosen_pickup_point->publicName . ')'
                  ?>
                <?php else : ?>
                  —
                <?php endif; ?>
              </p>
          <?php endif; ?>
            <p><strong><?= __('Extra Services:', 'itella-shipping') ?></strong>
              <?php
              if (empty($extra_services)) {
                echo __('No extra services selected', 'itella-shipping');
              } else {
                $services_html = '';
                foreach ( $services_names as $service_key => $service_name ) {
                  if ( in_array($service_key, $extra_services) ) {
                    if ( ! empty($services_html) ) {
                      $services_html .= '<br>';
                    }
                    $services_html .= $service_name;
                  }
                }
                echo $services_html;
              }
              ?>
            </p>
        </div>
        <div class="edit_address">
          <?php

          woocommerce_wp_select(array(
              'id' => 'packet_count',
              'label' => __('Packets (total):', 'itella-shipping'),
              'value' => $packet_count,
              'options' => $packets,
              'wrapper_class' => 'form-field-wide'
          ));

          woocommerce_wp_checkbox(array(
              'id' => 'itella_multi_parcel',
              'label' => __('Multi Parcel', 'itella-shipping'),
              'style' => 'width: 1rem',
              'description' => __('If more than one packet is selected, then a multi parcel service is mandatory', 'itella-shipping'),
              'value' => 'no',
              'cbvalue' => 'no',
              'wrapper_class' => 'form-field-wide'
          ));

          woocommerce_wp_text_input(array(
              'id' => 'weight_total',
              'label' => sprintf(__('Total weight (%s)', 'itella-shipping'), $weight_unit),
              'value' => $weight,
              'wrapper_class' => 'form-field-wide'
          ));

          woocommerce_wp_select(array(
              'id' => 'itella_cod_enabled',
              'label' => __('COD:', 'itella-shipping'),
              'value' => $is_cod ? 'yes' : 'no',
              'options' => array(
                  'no' => __('No', 'woocommerce'),
                  'yes' => __('Yes', 'woocommerce')
              ),
              'wrapper_class' => 'form-field-wide'
          ));

          woocommerce_wp_text_input(array(
              'id' => 'itella_cod_amount',
              'label' => sprintf(__('COD amount (%s):', 'itella-shipping'), $order_data->currency),
              'value' => $cod_amount,
              'wrapper_class' => 'form-field-wide'
          ));

          woocommerce_wp_select(array(
              'id' => 'itella_shipping_method',
              'label' => __('Carrier:', 'itella-shipping'),
              'value' => $is_itella_pp ? 'itella_pp' : 'itella_c',
              'options' => array(
                  'itella_pp' => __('Parcel locker', 'itella-shipping'),
                  'itella_c' => __('Courier', 'itella-shipping')
              ),
              'wrapper_class' => 'form-field-wide'
          ));

          woocommerce_wp_select(array(
              'id' => 'itella_pupCode',
              'label' => __('Select Parcel locker:', 'itella-shipping'),
              'value' => $chosen_pickup_point_pupcode,
              'options' => $this->build_pickup_points_list(Itella_Manifest::order_getCountry($order)),
              'wrapper_class' => 'form-field-wide'
          ));

          $this->woocommerce_wp_multi_checkbox($order_data->id, array(
              'id' => 'itella_extra_services',
              'name' => 'itella_extra_services[]',
              'style' => 'width: 1rem',
              'value' => $extra_services,
              'class' => 'itella_extra_services_cb',
              'label' => __('Extra Services', 'itella-shipping'),
              'options' => $extra_services_options,
              'wrapper_class' => 'form-field-wide'
          ));
          ?></div>
    <?php } else {
      $field_id = 'itella_add_manually';
      ?>
      <div class="edit_address">
        <p class="form-field-wide">
          <label for="<?php echo $field_id; ?>"><?php _e('Smartposti Shipping method', 'itella-shipping'); ?>:</label>
          <select id="<?php echo $field_id; ?>" class="select short" name="<?php echo $field_id; ?>">
            <option><?php _e('Not Smartposti', 'itella-shipping'); ?></option>
            <option value="pp"><?php _e('Pickup point', 'itella-shipping'); ?></option>
            <option value="c"><?php _e('Courier', 'itella-shipping'); ?></option>
          </select>
        </p>
      </div>
    <?php }
  }

  private function fix_pupcode($pupcode, $pickup_point_data)
  {
    if ( $pupcode == 'undefined') {
      $pupcode = '';
    }
    if ( empty($pupcode) && ! empty($pickup_point_data->pupCode) ) {
      return $pickup_point_data->pupCode;
    }

    return $pupcode;
  }

  private function check_itella_method($order)
  {
    $order_methods = $this->wc->get_order_shipping_methods($order);

    foreach ( $order_methods as $method ) {
      if ( $method->method_id == 'itella-shipping' ) {
        return true;
      }
    }

    return false;
  }

  public static function getTrackingUrl($country_code = 'lt')
  {
    $all_tracking_urls = array(
      'lt' => 'https://itella.lt/verslui/siuntos-sekimas/?trackingCode=',
      'lv' => 'https://itella.lv/private-customer/sutijuma-meklesana/?trackingCode=',
      'ee' => 'https://itella.ee/eraklient/saadetise-jalgimine/?trackingCode=',
      'en' => 'https://itella.lt/en/business-customer/track-shipment/?trackingCode=',
    );
    $country_code = strtolower($country_code);

    if (isset($all_tracking_urls[$country_code])) {
      return $all_tracking_urls[$country_code];
    }

    return $all_tracking_urls['lt'];
  }

  // Multi Checkbox field for woocommerce backend
  public function woocommerce_wp_multi_checkbox( $order_id, $field )
  {
    $field['class'] = isset($field['class']) ? $field['class'] : 'select short';
    $field['style'] = isset($field['style']) ? $field['style'] : '';
    $field['wrapper_class'] = isset($field['wrapper_class']) ? $field['wrapper_class'] : '';
    $field['value'] = isset($field['value']) ? $field['value'] : array();
    $field['name'] = isset($field['name']) ? $field['name'] : $field['id'];
    $field['desc_tip'] = isset($field['desc_tip']) ? $field['desc_tip'] : false;

    echo '<fieldset class="form-field ' . esc_attr($field['id']) . '_field ' . esc_attr($field['wrapper_class']) . '">
    <legend>' . wp_kses_post($field['label']) . '</legend>';

    if (!empty($field['description']) && $field['desc_tip']) {
      echo $this->wc->get_help_tip($field['description']);
    }

    echo '<ul class="wc-radios">';

    foreach ($field['options'] as $key => $value) {

      echo '<li><label><input
                name="' . esc_attr($field['name']) . '"
                value="' . esc_attr($key) . '"
                ' . (in_array($key, $field['value']) ? 'checked' : '') . '
                type="checkbox"
                class="' . esc_attr($field['class']) . '"
                style="' . esc_attr($field['style']) . '"
                 /> ' . esc_html($value) . '</label>
        </li>';
    }
    echo '</ul>';

    if (!empty($field['description']) && false === $field['desc_tip']) {
      echo '<span class="description">' . wp_kses_post($field['description']) . '</span>';
    }

    echo '</fieldset>';
  }

  /**
   * Get locations from file
   *
   * @param $shipping_country_id
   * @return array|null
   */
  public function get_pickup_points($shipping_country_id)
  {
    if ( ! in_array($shipping_country_id, $this->grouped_countries['pickup_point']) ) {
      return array();
    }

    $pickup_points = file_get_contents(plugin_dir_path(__FILE__) . '../locations/locations' . $shipping_country_id . '.json');

    return json_decode($pickup_points);
  }

  /**
   * Get chosen pickup point
   *
   * @param $shipping_country_id
   * @param $pickup_point_id
   * @return object|null
   */
  public function get_chosen_pickup_point($shipping_country_id, $pickup_point_id, $pickup_point_pupcode = '')
  {
    $pickup_points = $this->get_pickup_points($shipping_country_id);
    $chosen_pickup_point = null;

    foreach ( $pickup_points as $pickup_point ) {
      $chosen_pickup_point = $pickup_point->id == $pickup_point_id ? $pickup_point : null;
      if ( $chosen_pickup_point ) {
        break;
      }
      if ( ! empty($pickup_point_pupcode) && $pickup_point->pupCode == $pickup_point_pupcode ) {
        $chosen_pickup_point = $pickup_point;
        break;
      }
    }

    return $chosen_pickup_point;
  }

  /**
   * Build alphabetically sorted pickup point array to use with dropdown
   *
   * @param $shipping_country_id
   * @return mixed
   */
  public function build_pickup_points_list($shipping_country_id)
  {
    $pickup_points_list['-'] = '-';
    $pickup_points = $this->get_pickup_points($shipping_country_id);

    foreach ($pickup_points as $pickup_point) {
      $pickup_points_list[$pickup_point->pupCode] =
          $pickup_point->address->municipality . ' - ' .
          $pickup_point->address->address . ', ' .
          $pickup_point->address->postalCode . ' (' .
          $pickup_point->publicName . ')';
    }

    //sort by municipality name(city)
    asort($pickup_points_list);

    return $pickup_points_list;
  }

  /**
   * Validate if pp is chosen
   */
  public function validate_pickup_point()
  {
		$shipping_method = (isset($_POST['shipping_method']) && is_array($_POST['shipping_method'])) ? $this->wc->clean($_POST['shipping_method']) : [];
    $isItellaPp = in_array('itella_pp',$shipping_method);
    if ($isItellaPp && empty($_POST['itella-chosen-point-id'])) {
      $this->wc->add_notice( __( "You must choose Smartposti Parcel locker", 'itella-shipping' ), 'error');
    }
  }

  /**
   * Save in Woocommerce->Orders->Edit Order updated shipping options
   *
   * @param $order_id
   */
  public function save_shipping_settings($order_id)
  {
    $post_fields = array('itella_add_manually', 'packet_count', 'weight_total', 'itella_cod_enabled', 'itella_cod_amount', 'itella_shipping_method', 'itella_pupCode', 'itella_extra_services');
    
    foreach ( $post_fields as $field)  {
      if ( ! isset($_POST[$field]) ) continue;

      if ( $field == 'packet_count' && intval(wc_clean($_POST[$field]) > 1) ) {
        $this->wc->save_itella_multi_parcel($order_id, 'true');
      }

      if ( $field == 'itella_add_manually' && isset($_POST[$field]) ) {
        $method = 'itella_' . wc_clean($_POST[$field]);
        $this->wc->save_itella_method($order_id, $method);
        continue;
      }

      if ( $field == 'itella_pupCode' ) {
        $order = $this->wc->get_order($order_id);
        $choosen_pickup_point = $this->get_chosen_pickup_point(Itella_Manifest::order_getCountry($order), wc_clean($_POST[$field]));
        $this->wc->update_order_meta($order_id, 'itella_pp_id', wc_clean($choosen_pickup_point->pupCode));
      }

      $this->wc->update_order_meta($order_id, $field, wc_clean($_POST[$field]));
    }
  }

  /**
   * Print Label
   *
   * executes after Print Labels or Print Label is pressed
   */
  public function itella_post_label_actions()
  {
    $order_ids = $_REQUEST['post'];

    $tracking_codes = $this->get_tracking_codes($order_ids);
    $this->sort_tracking_codes_by_product_code($tracking_codes);

    try {
      // download labels
      $temp_name = 'itella_label_' . time();
      $temp_files = array();
      foreach ($tracking_codes as $product_key => $tr_codes) {
        $shipment = new Shipment(
            htmlspecialchars_decode($this->settings['api_user_' . $product_key]),
            htmlspecialchars_decode($this->settings['api_pass_' . $product_key])
        );

        $result = base64_decode($shipment->downloadLabels($tr_codes));

        if ($result) { // check if its not empty and save temporary for merging
          $pdf_path = plugin_dir_path(dirname(__FILE__)) . 'var/downloaded-labels/' . $temp_name . '-' . $product_key . '.pdf';
          $is_saved = file_put_contents($pdf_path, $result);
          if (!$is_saved) { // make sure it was saved
            throw new ItellaException(__("Failed to save label pdf to: ", 'itella-shipping') . $pdf_path);
          }
          $temp_files[] = $pdf_path;
        }
      }

      if (empty($temp_files)) {
        throw new ItellaException(__("Couldn't get a label for any order", 'itella-shipping'));
      }

      // merge downloaded labels
      $this->merge_labels($temp_files);
      return;
    } catch (ItellaException $e) {
      // add error message
      $this->add_msg(__('An error occurred', 'itella-shipping')
          . ': ' . $e->getMessage()
          , 'error');

      // log error
      file_put_contents(plugin_dir_path(dirname(__FILE__)) . 'var/log/errors.log',
          "\n" . date('Y-m-d H:i:s') . ": ItellaException:\n" . $e->getMessage() . "\n"
          . $e->getTraceAsString(), FILE_APPEND);

    } catch (\Exception $e) {
      $this->add_msg(__('An error occurred', 'itella-shipping')
          . ': ' . $e->getMessage()
          , 'error');

      // log error
      file_put_contents(plugin_dir_path(dirname(__FILE__)) . 'var/log/errors.log',
          "\n" . date('Y-m-d H:i:s') . ": Exception:\n" . $e->getMessage() . "\n"
          . $e->getTraceAsString(), FILE_APPEND);
    }
    wp_safe_redirect(wp_get_referer());
  }

  /**
   * Generate manifest
   *
   * executes after Generate manifests or Generate manifest is pressed
   */
  public function itella_post_manifest_actions()
  {
    $for_all = (isset($_REQUEST['for_all'])) ? $_REQUEST['for_all'] : false;
    if ($for_all) {
      switch ($for_all) {
        case 'new_orders':
          $args = array(
            'itella_manifest' => false,
          );
          break;
        case 'completed_orders':
          $args = array(
            'itella_manifest' => true,
            'meta_key' => 'itella_manifest_generation_date',
            'orderby' => 'meta_value',
            'order' => 'DESC'
          );
          break;
        case 'all_orders':
        default:
          $args = array();
          break;
      }
      $args = array_merge(
        $args,
        array(
          'limit' => -1,
          'return' => 'ids'
        )
      );
      set_time_limit(0);
      $order_ids = $this->wc->get_orders($args);
    } else {
      $order_ids = $_REQUEST['post'];
    }

    $translation = $this->get_manifest_translation();

    $items = $this->prepare_items_for_manifest($order_ids);

    $manifest = $this->create_manifest($translation, $items);

    $this->update_manifest_generation_date($order_ids, $manifest->timestamp);


    $manifest->printManifest('itella_manifest.pdf');
  }

  /**
   * Shipping information in emails
   */
  public function itella_shipping_info_css_in_email( $css, $email = '' )
  {
    $css .= '
      .itella-ship-info { border:1px solid #e5e5e5; margin-bottom:30px; }
      .itella-ship-info table { margin:0; }
      .itella-ship-info th { width:40%; }
    ';
    return $css;
  }
  public function add_itella_shipping_info_to_email( $order, $sent_to_admin = '', $plain_text = '', $email = '' )
  {
    $itella_data = $this->wc->get_itella_data($order);
    $tracking_number = $itella_data->tracking->code;
    $chosen_pickup_point_id = $itella_data->pickup->id;
    $chosen_pickup_point_pupcode = $itella_data->pickup->pupcode;
    $tracking_url = $itella_data->tracking->url;
    
    if ( empty( $tracking_number ) && empty($chosen_pickup_point_id) && empty($chosen_pickup_point_pupcode) ) {
      return;
    }

    $tracking_provider = $order->get_shipping_method();

    if ( ! $plain_text ) {
      echo '<h2 class="itella-ship-info-title">' . __('Shipping information', 'itella-shipping') . '</h2>';
      echo '<div class="itella-ship-info"><table>';
      echo '<tr><th>' . __('Shipping method', 'itella-shipping') . '</th><td>' . $tracking_provider . '</td></tr>';
    } else {
      printf( __('Your order has been shipped with %s.', 'itella-shipping') . "\n", $tracking_provider );
    }

    if ( (! empty($chosen_pickup_point_id) || ! empty($chosen_pickup_point_pupcode)) && $chosen_pickup_point_id != '-' ) {
      $pp_address = $this->build_pickup_address_for_display($order, $chosen_pickup_point_id, $chosen_pickup_point_pupcode);

      if ( $plain_text ) {
        printf( __('The order will be delivered to %s.', 'itella-shipping') . "\n", $pp_address );
      } else {
        echo '<tr><th>' . __('Deliver to', 'itella-shipping') . '</th><td>' . $pp_address . '</td></tr>';
      }
    }

    if ( ! empty( $tracking_number ) && ! empty( $tracking_url ) ) {
      if ( $plain_text ) {
        if ( ! empty( $tracking_url ) ) {
          printf(
            __('The tracking number is %s and you can track it at %s.', 'itella-shipping') . "\n",
            esc_html( $tracking_number ), esc_url( $tracking_url, array('http', 'https') )
          );
        } else {
          printf(
            __('The tracking number is %s.', 'itella-shipping') . "\n",
            esc_html( $tracking_number )
          );
        }
      } else {
        echo '<tr><th>' . __('Tracking number', 'itella-shipping') . '</th><td>';
        if ( ! empty($tracking_url) ) {
          echo '<a href="' . esc_url( $tracking_url, array('http', 'https') ) . '" target="_blank">' . esc_html( $tracking_number ) . '</a>';
        } else {
          echo esc_html( $tracking_number );
        }
        echo '</td></tr>';
      }
    }

    if ( ! $plain_text ) {
      echo '</table></div>';
    }
  }

  public function itella_shipping_method_description( $method, $index )
  {
    if( is_cart() ) return; // Exit on cart page

    if ( ! isset($this->settings) ) {
      return;
    }

    $session = $this->wc->get_global_wc_property('session');
    if ( ! $session ) {
      return;
    }
    $customer = $session->get('customer');
    if ( ! isset($customer['country']) ) {
      return;
    }

    $country = strtolower($customer['country']);
    $methods_settings = (!empty($this->settings['methods'])) ? json_decode($this->settings['methods'], true) : $this->methods_backward_compatibility();
    
    foreach ( $methods_settings[$country] as $method_key => $method_data ) {
      if ( ! empty($method_data['description']) && $method->id === 'itella_' . Itella_Shipping::get_instance()->get_method_short_key($method_key) ) {
        echo '<span class="itella-shipping-description">' . $method_data['description'] . '</span>';
      } 
    }
  }

  /**
   * Shipping information in order quickview (Add fields)
   */
  public function add_custom_admin_order_preview_meta( $data, $order )
  {
    $itella_data = $this->wc->get_itella_data($order);
    $tracking_number = $itella_data->tracking->code;
    $tracking_url = $itella_data->tracking->url;
    $chosen_pickup_point_id = $itella_data->pickup->id;

    if( ! empty($tracking_number) ) {
      $data['tracking_code'] = $tracking_number;
    }
    if( ! empty($tracking_url) ) {
      $data['tracking_url'] = $tracking_url;
    }
    if ( ! empty($chosen_pickup_point_id) && $chosen_pickup_point_id != '-' ) {
      $pp_address = $this->build_pickup_address_for_display( $order, $chosen_pickup_point_id );
      $data['pp_address'] = $pp_address;
    }

    return $data;
  }

  /**
   * Shipping information in order quickview (Display fields)
   */
  public function display_custom_data_in_admin_order_preview()
  {
    echo '<div class="itella-order-quickview">
      <# if ( data.tracking_code ) { #>
        <div class="quickview-row">
          <strong>' . __('Tracking number', 'itella-shipping') . '</strong><br/>
          <# if ( data.tracking_url ) { #>
            <a href="{{data.tracking_url}}" target="_blank">{{data.tracking_code}}</a>
          <# } else { #>
            <span>{{data.tracking_code}}</span>
            <# } #>
        </div>
      <# } #>
      <# if ( data.pp_address ) { #>
        <div class="quickview-row">
          <strong>' . __('Parcel locker', 'itella-shipping') . '</strong><br/>
          <span>{{data.pp_address}}</span>
        </div>
      <# } #>
    </div>';
  }

  /**
   * Build formated pickup address string
   */
  private function build_pickup_address_for_display( $order, $chosen_pickup_point_id, $chosen_pickup_point_pupcode = '' )
  {
    $chosen_pickup_point = $this->get_chosen_pickup_point(Itella_Manifest::order_getCountry($order), $chosen_pickup_point_id, $chosen_pickup_point_pupcode);
    return $chosen_pickup_point->address->municipality . ' - ' .
           $chosen_pickup_point->address->address . ', ' .
           $chosen_pickup_point->address->postalCode . ' (' .
           $chosen_pickup_point->publicName . ')';
  }
  /**
   * Update manifest generation date for orders
   *
   * @param $order_ids
   * @param $timestamp
   */
  private function update_manifest_generation_date($order_ids, $timestamp)
  {
    $order_ids = is_array($order_ids) ? $order_ids : array($order_ids);;
    foreach ($order_ids as $order_id) {
      if ( $this->wc->get_itella_tracking_code($order_id) ) {
        $this->wc->save_itella_manifest_generation_date($order_id, date('Y-m-d H:i:s', $timestamp));
      }
    }
  }

  /**
   * Register shipment
   *
   * executes after Register shipment is pressed
   */
  public function itella_post_shipment_actions( $exit_modes = '' )
  {
    if (!is_array($exit_modes)) {
      $exit_modes = array('msg','redirect');
    }
    $order_ids = $_REQUEST['post'];
    $order_ids = is_array($order_ids) ? $order_ids : array($order_ids);

    $result = $this->register_shipments($order_ids);
    $success_messages = array();
    $failed_messages = array();
    foreach ( $result as $order_id => $result_data ) {
      if ( $result_data['status'] == 'success' ) {
        $success_messages[] = $result_data['msg'];
        if ( in_array('msg',$exit_modes) ) {
          $this->add_msg(
            'Order ' . $result_data['number'] . ' - '
            . __('Shipment registered successfully.', 'itella-shipping'), 'success'
          );
          $this->add_msg(
            'Order ' . $result_data['number'] . ' - '
            . __('Tracking number: ', 'itella-shipping') . $result_data['tracking_code'], 'info'
          );
          file_put_contents(plugin_dir_path(dirname(__FILE__)) . 'var/log/registered_tracks.log',
            "\nOrder ID : " . $order_id . "\n" . 'Tracking number: ' . $result_data['tracking_code'], FILE_APPEND);
        }
      }

      if ( $result_data['status'] == 'error' ) {
        $failed_messages[] = $result_data['msg'];
        if ( in_array('msg',$exit_modes) ) {
          $this->add_msg($order_id . ' - ' . __('Shipment is not registered.', 'itella-shipping')
            . "<br>"
            . __('Error: ', 'itella-shipping')
            . $result_data['msg'], 'error');
        }
      }
    }

    $return_message = '';
    if ( ! empty($success_messages) && ! empty($failed_messages) ) {
      $return_message = implode("\n", $success_messages) . "\n\n" . implode("\n", $failed_messages);
    } else if ( ! empty($success_messages) ) {
      $return_message = __('Shipments registered successfully', 'itella-shipping');
    } else if ( ! empty($failed_messages) ) {
      $return_message = __('Could not register any shipment', 'itella-shipping') . ":\n" . implode("\n", $failed_messages);
    }

    if ( in_array('return',$exit_modes) ) {
      if ( empty($failed_messages) ) {
        return array('status' => 'success', 'msg' => $return_message);
      } else {
        return array('status' => 'error', 'msg' => $return_message);
      }
    }

    if (in_array('redirect',$exit_modes)) {
      wp_safe_redirect(wp_get_referer());
    }
  }

  public function register_shipments( $order_ids )
  {
    if ( ! is_array($order_ids) || empty($order_ids) ) {
      return array('status' => 'error', 'msg' => __('No order ids received', 'itella-shipping'));
    }

    $itella_shipping_methods = array(
      'itella_pp', 'itella_c'
    );

    $orders_result = array();
    foreach ( $order_ids as $order_id ) {
      $order = $this->wc->get_order($order_id);
      $shipping_parameters = Itella_Manifest::get_shipping_parameters($order_id);
      $order_country = Itella_Manifest::order_getCountry($order);
      $shipping_method = $shipping_parameters['itella_shipping_method'];
      $shipment = null;

      $result_data = array(
        'status' => 'error',
        'id' => $order_id,
        'number' => $order->get_order_number(),
        'tracking_code' => '',
        'msg' => ''
      );

      if ( ! in_array($shipping_method, $itella_shipping_methods) ) {
        $result_data['msg'] = __('Not Smartposti Shipping Method', 'itella-shipping');
        $orders_result[$order_id] = $result_data;
        continue;
      }

      if ( $shipping_parameters['total_weight'] > 150 ) {
        $result_data['msg'] = sprintf(__('The total weight of shipment exceeds %s kg', 'itella-shipping'), 150);
        $orders_result[$order_id] = $result_data;
        continue;
      }
      if ( $shipping_parameters['packet_weight'] > 35 ) {
        $result_data['msg'] = sprintf(__('The weight of package exceeds %s kg', 'itella-shipping'), 35);
        $orders_result[$order_id] = $result_data;
        continue;
      }

      $contract_number = ($shipping_method === 'itella_pp')
        ? $this->settings['api_contract_2711']
        : $this->settings['api_contract_2317'];

      // register shipment
      try {
        $sender = $this->create_sender($contract_number);
        $receiver = $this->create_receiver($order);

        $shipment = ($shipping_method === 'itella_pp')
          ? $this->register_pickup_point_shipment($sender, $receiver, $shipping_parameters, $order_id)
          : $this->register_courier_shipment($sender, $receiver, $shipping_parameters, $order_id);

        $result = $shipment->registerShipment();

        // save result
        $this->wc->save_itella_tracking_code($order, $result->__toString());
        $this->wc->save_itella_tracking_url($order, self::getTrackingUrl($order_country) . $result->__toString());
        $this->wc->save_itella_tracking_code_error($order, '');

        // add order note
        $note = sprintf(
          __('Smartposti shipment registered successfully. Tracking number: %s', 'itella-shipping'),
          '<a href="' . self::getTrackingUrl($order_country) . $result->__toString() . '" target="_blank">' . $result->__toString() . '</a>'
        );
        $order->add_order_note($note);

        $result_data['status'] = 'success';
        $result_data['msg'] = __('Successfully registered', 'itella-shipping');
        $orders_result[$order_id] = $result_data;
      } catch (ItellaException $e) {
        file_put_contents(plugin_dir_path(dirname(__FILE__)) . 'var/log/errors.log',
          '[' . date('Y-m-d H:i:s') . "] Exception:\n" . $e->getMessage() . PHP_EOL
          . $e->getTraceAsString() . PHP_EOL, FILE_APPEND
        );
        $result_data['msg'] = $e->getMessage();
        $this->wc->save_itella_tracking_code_error($order, $e->getMessage());
        $orders_result[$order_id] = $result_data;
        continue;
      }
    }

    return $orders_result;
  }

  /**
   * AJAX function for single shipment registration
   */
  public function itella_ajax_single_register_shipment()
  {
    if ( !isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'itella_shipments') ) {
      echo json_encode(array(
        'status' => 'error',
        'msg' => __("Failed to validate nonce", 'itella-shipping')
      ));
      die();
    }
    if (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
      $id = $_REQUEST['id'];
      $check_code = $this->wc->get_itella_data($id)->tracking->code;
      if (empty($check_code)) {
        $_REQUEST['post'] = $id;
        $status = $this->itella_post_shipment_actions( array('return') );
        if ($status['status'] === 'error') {
          echo json_encode(array(
            'status' => 'error',
            'msg' => (!empty($status['msg'])) ? $status['msg'] : __("Failed to register shipment", 'itella-shipping')
          ));
        } else {
          echo json_encode(array(
            'status' => 'success',
            'msg' => __("Shipment successfully registered", 'itella-shipping')
          ));
        }
      }
    } else {
      echo json_encode(array(
        'status' => 'error',
        'msg' => __("Couldn't get order", 'itella-shipping')
      ));
    }
    die();
  }

  /**
   * AJAX function for massive shipments registration
   */
  public function itella_ajax_bulk_register_shipments()
  {
    if ( !isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'itella_shipments') ) {
      echo json_encode(array(
        'status' => 'error',
        'msg' => __("Failed to validate nonce", 'itella-shipping')
      ));
      die();
    }
    if (isset($_REQUEST['ids']) && is_array($_REQUEST['ids'])) {
      $counter = 0;
      foreach($_REQUEST['ids'] as $id) {
        $itella_data = $this->wc->get_itella_data($id);
        if ( ! self::is_itella_method($itella_data) ) {
          continue;
        }
        if ( ! empty($itella_data->tracking->code) ) {
          continue;
        }

        $counter++;
        $execute_after_sec = $counter * 3;
        $args = array(
          'order' => $id,
        );
        as_schedule_single_action(time() + $execute_after_sec, 'itella_cronjob_register_shipment', array($args));
      }
      if ( ! $counter ) {
        echo json_encode(array(
          'status' => 'error',
          'msg' => __('No shipment can be registered for any order', 'itella-shipping')
        ));
      } else {
        $url = add_query_arg(array(
          'page' => 'action-scheduler',
          'status' => 'pending',
          's' => 'itella_cronjob_register_shipment',
        ), admin_url('tools.php'));
        echo json_encode(array(
          'status' => 'notice',
          'msg' => sprintf(__('Registration of shipments has begun. You can see the queue for shipments registration on the %s page.', 'itella-shipping'), '"' . __('Scheduled Actions', 'woocommerce') . '"') . "\n" . __('Do you want to open this page now?', 'itella-shipping'),
          'confirm_url' => $url,
          'order_ids' => $_REQUEST['ids']
        ));
      }
    } else {
      echo json_encode(array(
        'status' => 'error',
        'msg' => __("Couldn't get list of selected orders", 'itella-shipping')
      ));
    }
    die();
  }

  public function itella_ajax_check_ongoing_registrations()
  {
    if ( !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'itella_shipments') ) {
        wp_send_json(array('completed' => false, 'error' => 'Invalid nonce'));
    }

    $pending_actions = as_get_scheduled_actions([
        'hook' => 'itella_cronjob_register_shipment',
        'status' => ActionScheduler_Store::STATUS_PENDING,
        'per_page' => 100,
    ]);

    wp_send_json(array(
        'completed' => empty($pending_actions),
        'actions_left' => count($pending_actions)
    ));
  }

  /**
   * Register courier shipment
   *
   * @param $sender
   * @param $receiver
   * @param $shipping_parameters
   * @param $order_id
   * @return Shipment
   * @throws ItellaException
   */
  private function register_courier_shipment($sender, $receiver, $shipping_parameters, $order_id)
  {
    $p_user = htmlspecialchars_decode($this->settings['api_user_2317']);
    $p_secret = htmlspecialchars_decode($this->settings['api_pass_2317']);
    $is_test = false;

    // Create GoodsItem (parcel)
    $items = array();

    for ($i = 0; $i < intval($shipping_parameters['packet_count']); $i++) {
      $item = new GoodsItem();
      $item->setGrossWeight(floatval($shipping_parameters['packet_weight'])); // kg
      $items[] = $item;
    }

    // Create additional services
    $additional_services = array();

    // Get services from order
    $extra_services = $shipping_parameters['extra_services'];
    $extra_services = is_array($extra_services) ? $extra_services : array($extra_services);

    // COD
    $service_cod = $this->get_service_cod_info($order_id, $shipping_parameters);
    if ($service_cod) {
      $additional_services[] = $service_cod;
    }

    // Other services
    $oversized = in_array('oversized', $extra_services) ? new AdditionalService(3174) : false;
    if ($oversized) {
      $additional_services[] = $oversized;
    }
    $fragile = in_array('fragile', $extra_services) ? new AdditionalService(3104) : false;
    if ($fragile) {
      $additional_services[] = $fragile;
    }
    $call_before_delivery = in_array('call_before_delivery', $extra_services) ? new AdditionalService(3166) : false;
    if ($call_before_delivery) {
      $additional_services[] = $call_before_delivery;
    }

    // Prepare label comment
    $comment_replaces = array(
      'order_id' => $order_id,
    );
    $comment = $this->prepare_comment(htmlspecialchars_decode($this->settings['comment_c']), $comment_replaces);

    // Create shipment object
    $shipment = new Shipment($p_user, $p_secret, $is_test);
    $shipment
        ->setProductCode(Shipment::PRODUCT_COURIER)
        ->setShipmentNumber($order_id)
        ->setShipmentDateTime(date('c'))
        ->setSenderParty($sender)
        ->setReceiverParty($receiver)
        ->addAdditionalServices($additional_services)
        ->addGoodsItems($items)
        ->setComment($comment);

    return $shipment;
  }

  /**
   * Register Pickup Point shipment
   *
   * @param $sender
   * @param $receiver
   * @param $shipping_parameters
   * @param $order_id
   * @return Shipment
   * @throws ItellaException
   */
  private function register_pickup_point_shipment($sender, $receiver, $shipping_parameters, $order_id)
  {
    $p_user = htmlspecialchars_decode($this->settings['api_user_2317']);
    $p_secret = htmlspecialchars_decode($this->settings['api_pass_2317']);
    $is_test = false;
    $shipping_country = Itella_Manifest::order_getCountry($this->wc->get_order($order_id));
    $chosen_pickup_point = $this->get_chosen_pickup_point($shipping_country, $shipping_parameters['pickup_point_id'], $shipping_parameters['pickup_point_pupcode']);

    // Create GoodsItem (parcel)
    $item = new GoodsItem();
    $item->setGrossWeight(floatval($shipping_parameters['total_weight'])); // kg

    // Create additional services
    $additional_services = array();

    // COD
    $service_cod = $this->get_service_cod_info($order_id, $shipping_parameters);
    if ($service_cod) {
      $additional_services[] = $service_cod;
    }

    // Prepare label comment
    $comment_replaces = array(
      'order_id' => $order_id,
    );
    $comment = $this->prepare_comment(htmlspecialchars_decode($this->settings['comment_pp']), $comment_replaces);

    // Create shipment object
    $shipment = new Shipment($p_user, $p_secret, $is_test);
    $shipment
        ->setProductCode(Shipment::PRODUCT_PICKUP)
        ->setShipmentNumber($order_id)
        ->setShipmentDateTime(date('c'))
        ->setSenderParty($sender)
        ->setReceiverParty($receiver)
        ->addAdditionalServices($additional_services)
        ->setPickupPoint($chosen_pickup_point->pupCode) // pupCode
        ->addGoodsItem($item)
        ->setComment($comment);

    return $shipment;

  }

  private function prepare_comment($comment, $variables)
  {
    foreach ($variables as $key => $value) {
      $comment = str_replace('{' . $key . '}', $value, $comment);
    }

    return $comment;
  }

  private function get_service_cod_info($order_id, $shipping_parameters)
  {
    if ($shipping_parameters['is_cod'] === 'yes' || $shipping_parameters['is_cod'] === true) {
      $service_cod = new AdditionalService(3101, array(
        'amount' => $shipping_parameters['cod_amount'],
        'account' => $this->settings['bank_account'],
        'reference' => ItellaHelper::generateCODReference($order_id),
        'codbic' => $this->settings['cod_bic']
      ));
      return $service_cod;
    }

    return false;
  }

  /**
   * Create sender
   *
   * @param $contract_number
   * @return Party
   * @throws ItellaException
   */
  private function create_sender($contract_number)
  {
    $sender = new Party(Party::ROLE_SENDER);
    $sender
        ->setContract($contract_number) // important comes from supplied tracking code interval
        ->setName1($this->settings['shop_name'])
        ->setStreet1($this->settings['shop_address'])
        ->setPostCode($this->settings['shop_postcode'])
        ->setCity($this->settings['shop_city'])
        ->setCountryCode($this->settings['shop_countrycode'])
        ->setContactMobile($this->settings['shop_phone'])
        ->setContactEmail($this->settings['shop_email']);

    return $sender;
  }

  /**
   * Create receiver
   *
   * @param $order
   * @return Party
   */
  private function create_receiver($order)
  {
    $first_name = $order->get_shipping_first_name();
    $last_name = $order->get_shipping_last_name();
    if ( empty($first_name) && empty($last_name) ) {
      $first_name = $order->get_billing_first_name();
      $last_name = $order->get_billing_last_name();
    }
    $address = $this->build_address($order->get_shipping_address_1(), $order->get_shipping_address_2());
    $postcode = $order->get_shipping_postcode();
    $city = $order->get_shipping_city();
    if ( empty($address) && empty($postcode) && empty($city) ) {
      $address = $this->build_address($order->get_billing_address_1(), $order->get_billing_address_1());
      $postcode = $order->get_billing_postcode();
      $city = $order->get_billing_city();
    }
    $phone = $order->get_shipping_phone();
    if ( empty($phone) ) {
      $phone = $order->get_billing_phone();
    }

    $receiver = new Party(Party::ROLE_RECEIVER);
    $receiver
        ->setName1($first_name . ' ' . $last_name)
        ->setStreet1($address)
        ->setPostCode($postcode)
        ->setCity($city)
        ->setCountryCode(Itella_Manifest::order_getCountry($order))
        ->setContactMobile($phone)
        ->setContactEmail($order->get_billing_email());

    return $receiver;
  }

  private function build_address($address_1, $address_2 = '', $separator = ' - ')
  {
    $full_address = $address_1;
    if ( ! empty($address_2) ) {
      if ( ! empty($full_address) ) {
        $full_address .= $separator;
      }
      $full_address .= $address_2;
    }

    return $full_address;
  }

  /**
   * Add message to show as admin notices by type(error, success, info, update).
   *
   * @param $msg
   * @param $type
   */
  private function add_msg($msg, $type)
  {
    if (!session_id()) {
      session_start();
    }
    if (!isset($_SESSION['itella_shipping_notices']))
      $_SESSION['itella_shipping_notices'] = array();
    $_SESSION['itella_shipping_notices'][] = array('msg' => $msg, 'type' => 'notice notice-' . $type);
  }

  /**
   * Show messages as admin notices. Messages stored in php session
   */
  public function itella_shipping_notices()
  {
    if (!session_id()) {
      session_start();
    }
    if (array_key_exists('itella_shipping_notices', $_SESSION)) {
      foreach ($_SESSION['itella_shipping_notices'] as $notice):
        ?>
      <div class="<?php echo $notice['type']; ?>">
          <p><?php echo $notice['msg']; ?></p>
          </div><?php
      endforeach;
      unset($_SESSION['itella_shipping_notices']);
    }
  }

  public function itella_register_orders_bulk_actions($bulk_actions)
  {
    global $wp_version;

    $title = 'Smartposti';
    $grouped = (version_compare($wp_version, '5.6.0', '>=')) ? true : false;
    $actions = array(
      'register_shipments' => __('Register shipments', 'itella-shipping'),
      'print_labels' => __('Print shipping labels', 'itella-shipping'),
    );

    foreach ( $actions as $action_key => $action_title ) {
        if ( $grouped ) {
            $bulk_actions[$title]['itella_' . $action_key] = $action_title;
        } else {
            $bulk_actions['itella_' . $action_key] = $title . ': ' . $action_title;
        }
    }
    
    return $bulk_actions;
  }

  public function woo_orders_custom_bulk_control()
  {
    if ( get_current_screen()->id !== 'edit-shop_order' ) {
      return;
    }
  }

  public static function is_itella_method($itella_data)
  {
    $is_shipping_updated = !empty($itella_data->shipping_method);
    $itella_method = $is_shipping_updated ? $itella_data->shipping_method : $itella_data->itella_method;

    return (bool) $itella_method;
  }

  public function itella_handle_orders_bulk_actions($redirect_to, $action, $ids)
  {
    if ( $action == 'itella_register_shipments' ) {
      $counter = 0;
      foreach ( $ids as $id ) {
        $itella_data = $this->wc->get_itella_data($id);
        if ( ! self::is_itella_method($itella_data) ) {
          continue;
        }
        if ( ! empty($itella_data->tracking->code) ) {
          continue;
        }

        $counter++;
        $execute_after_sec = $counter * 3;
        $args = array(
          'order' => $id,
        );
        as_schedule_single_action(time() + $execute_after_sec, 'itella_cronjob_register_shipment', array($args));
      }

      if ( ! $counter ) {
        $this->add_msg(__('No shipment can be registered for any order', 'itella-shipping'), 'error');
      } else {
        $url = add_query_arg(array(
          'page' => 'action-scheduler',
          'status' => 'pending',
          's' => 'itella_cronjob_register_shipment',
        ), admin_url('tools.php'));
        $this->add_msg(sprintf(__('Registration of shipments has begun. You can see the queue for shipments registration on the %s page.', 'itella-shipping'), '<a href="' . $url . '">' . __('Scheduled Actions', 'woocommerce') . '</a>'), 'warning');
      }
    }

    if ( $action == 'itella_print_labels' ) {
      $_REQUEST['post'] = $ids;
      $this->itella_post_label_actions();
      die();
    }

    return $redirect_to;
  }

  /**
   * Get tracking codes by order ids
   *
   * @param $order_ids
   * @return array
   */
  private function get_tracking_codes($order_ids)
  {
    $order_ids = is_array($order_ids) ? $order_ids : array($order_ids);
    $tracking_codes = array();

    foreach ($order_ids as $order_id) {
      $order = $this->wc->get_order($order_id);
      $tracking_code = $this->wc->get_itella_data($order_id)->tracking->code;

      if (!$tracking_code) {
        continue;
      }
      $tracking_codes[] = $tracking_code;
    }

    return $tracking_codes;
  }

  /**
   * Sort tracking codes array by product code.
   *
   * note - array passed by reference
   *
   * @param $tracking_codes
   */
  private function sort_tracking_codes_by_product_code(&$tracking_codes)
  {
    foreach ($tracking_codes as $key => $tracking_code) {
      $product_code = ItellaHelper::getProductIdFromTrackNum($tracking_code);
      if (!ItellaHelper::keyExists($product_code, $tracking_codes)) {
        $tracking_codes[$product_code] = array();
      }
      $tracking_codes[$product_code][] = $tracking_code;
      unset($tracking_codes[$key]);
    }
  }

  /**
   * Merge labels
   *
   * @param $files
   * @return string
   */
  private function merge_labels($files)
  {
    $merger = new PDFMerge();
    $merger->setFiles($files); // pass array of paths to pdf files
    $merger->merge();

    // remove downloaded labels ()
    foreach ($files as $file) {
      if (is_file($file)) {
        unlink($file);
      }
    }

    /**
     * Second param:
     * I: send the file inline to the browser (default).
     * D: send to the browser and force a file download with the name given by name.
     * F: save to a local server file with the name given by name.
     * S: return the document as a string (name is ignored).
     * FI: equivalent to F + I option
     * FD: equivalent to F + D option
     * E: return the document as base64 mime multi-part email attachment (RFC 2045)
     */
    return base64_encode($merger->Output(plugin_dir_path(dirname(__FILE__))
        . 'var/downloaded-labels/itella_labels_'
        . date('Y-m-d H:i:s')
        . '.pdf',
        'FD'));
  }

  /**
   * Call courier. Sends email.
   *
   * executes after Call Itella courier is pressed.
   */
  public function itella_post_call_courier_actions()
  {
    if ( ! isset($_REQUEST['itella-call-courier_nonce']) || ! wp_verify_nonce($_REQUEST['itella-call-courier_nonce'], 'itella-call-courier') ) {
      $this->add_msg(__('Incorrect request nonce', 'itella-shipping'), 'error');
      wp_safe_redirect(wp_get_referer());
      return;
    }

    $order_ids = $_REQUEST['post']; // no post
    $call_date = (isset($_REQUEST['itella_call_courier_date'])) ? $_REQUEST['itella_call_courier_date'] : date('Y-m-d', strtotime('+1 day'));
    $call_time_from = (isset($_REQUEST['itella_call_courier_time_from'])) ? $_REQUEST['itella_call_courier_time_from'] : '08:00';
    $call_time_to = (isset($_REQUEST['itella_call_courier_time_to'])) ? $_REQUEST['itella_call_courier_time_to'] : '17:00';
    $call_info = (isset($_REQUEST['itella_call_courier_info'])) ? $_REQUEST['itella_call_courier_info'] : '';

    $username = (!empty($this->settings['api_user_2317'])) ? $this->settings['api_user_2317'] : $this->settings['api_user_2711'];
    $password = (!empty($this->settings['api_pass_2317'])) ? $this->settings['api_pass_2317'] : $this->settings['api_pass_2711'];

    $translation = $this->get_manifest_translation();
    $items = $this->prepare_items_for_manifest($order_ids);

    $manifest = $this->create_manifest($translation, $items);
    $manifest_string = $manifest
        ->setToString(true)
        ->setBase64(true)
        ->printManifest('manifest.pdf');

    $shop_country_code = strtoupper($this->settings['shop_countrycode']);
    if ( in_array($shop_country_code, $this->available_countries) && $this->settings['call_courier_mail_' . $shop_country_code] ) {
      $email = $this->settings['call_courier_mail_' . $shop_country_code];
    } else {
      $email = 'smartship.routing.lt@itella.com';
    }

    $email_subject = 'E-com order booking';
    if (!empty($this->settings['call_courier_mail_subject'])) {
      $email_subject = $this->settings['call_courier_mail_subject'];
    }

    try {
      $caller = new CallCourier($email);
      $caller
        ->setUsername($username)
        ->setPassword($password)
        ->setSenderEmail($this->settings['shop_email'])
        ->setSubject($email_subject)
        ->setPickUpAddress(array(
            'sender' => $this->settings['shop_name'],
            'address_1' => $this->settings['shop_address'],
            'postcode' => $this->settings['shop_postcode'],
            'city' => $this->settings['shop_city'],
            'country' => $this->settings['shop_countrycode'],
            'contact_phone' => $this->settings['shop_phone'],
        ))
        ->setPickUpParams(array(
          'date' => $call_date,
          'time_from' => $call_time_from,
          'time_to' => $call_time_to,
          'info_general' => $call_info,
          'id_sender' => 'LT100011522813',
        ))
        ->setAttachment($manifest_string, true)
        ->setItems($items)
        ->showMessagesPrefix(true);

      $result = $caller->callCourier();
      if (!is_array($result) || (!isset($result['errors']) && !isset($result['success']))) {
        $this->add_msg(__('An unknown result was received from the call', 'itella-shipping').': '.print_r($result, true), 'error');
      } else {
        if (!empty($result['errors'])) {
          $this->add_msg(implode('<br>', $result['errors']), 'error');
        }
        if (!empty($result['success'])) {
          $result['success'][] = '<br/>' . sprintf(__('The courier should arrive %s', 'itella-shipping'), $call_date . ' ' . $call_time_from . ' - ' . $call_time_to);
          $this->add_msg(implode('<br>', $result['success']), 'success');
        }
      }
    } catch (ItellaException $e) {
      $this->add_msg(__('Failed to call Smartposti courier', 'itella-shipping'), 'error');
    }

    // return to shipments
    wp_safe_redirect(wp_get_referer());
  }

  /**
   * Manifest translation.
   *
   * @return array
   */
  private function get_manifest_translation()
  {
    return array(
        'sender_address' => __('Sender address:', 'itella-shipping'),
        'nr' => __('No.:', 'itella-shipping'),
        'track_num' => __('Tracking number:', 'itella-shipping'),
        'date' => __('Date:', 'itella-shipping'),
        'amount' => __('Amount:', 'itella-shipping'),
        'weight' => __('Weight:', 'itella-shipping'),
        'delivery_address' => __('Delivery address:', 'itella-shipping'),
        'courier' => __('Courier', 'itella-shipping'),
        'sender' => __('Sender', 'itella-shipping'),
        'name_lastname_signature' => __('name, lastname, signature', 'itella-shipping'),
    );
  }

  /**
   * Create manifest
   *
   * @param $translation
   * @param $items
   * @return Manifest
   */
  private function create_manifest($translation, $items)
  {
    $manifest = new Manifest();
    $manifest
        ->setStrings($translation)
        ->setSenderName($this->settings['shop_name'])
        ->setSenderAddress($this->settings['shop_address'])
        ->setSenderPostCode($this->settings['shop_postcode'])
        ->setSenderCity($this->settings['shop_city'])
        ->setSenderCountry($this->settings['shop_countrycode'])
        ->addItem($items);

    return $manifest;
  }

  /**
   * Build array of itella api applicable items for manifest
   *
   * @param $order_ids
   * @return array
   */
  private function prepare_items_for_manifest($order_ids)
  {
    $order_ids = is_array($order_ids) ? $order_ids : array($order_ids);
    $prepared_tracking_items = array();


    foreach ($order_ids as $order_id) {
      $shipping_parameters = Itella_Manifest::get_shipping_parameters($order_id);
      if ( empty($shipping_parameters) ) {
        continue;
      }

      $order = $this->wc->get_order($order_id);
      $shipping_method = $shipping_parameters['itella_shipping_method'];
      $shipping_country = Itella_Manifest::order_getCountry($order);
      $chosen_pickup_point = $this->get_chosen_pickup_point($shipping_country, $shipping_parameters['pickup_point_id'], $shipping_parameters['pickup_point_pupcode']);

      $tracking_code = $this->wc->get_itella_tracking_code($order);

      // manifest is only for registered shipments (that have tracking code)
      if ($tracking_code) {
          if ($shipping_method === 'itella_c') {
              $prepared_tracking_items[] = array(
                  'track_num' => $tracking_code,
                  'weight' => !empty($shipping_parameters['weight']) ? $shipping_parameters['weight'] : 0,
                  'delivery_address' => $order->get_shipping_first_name() . ' '
                      . $order->get_shipping_last_name() . ', '
                      . $this->build_address($order->get_shipping_address_1(), $order->get_shipping_address_2()) . ', '
                      . $order->get_shipping_postcode() . ' '
                      . $order->get_shipping_city() . ', '
                      . Itella_Manifest::order_getCountry($order)
              );
          }

          if ($shipping_method === 'itella_pp') {
              $prepared_tracking_items[] = array(
                  'track_num' => $tracking_code,
                  'weight' => !empty($shipping_parameters['weight']) ? $shipping_parameters['weight'] : 0,
                  'delivery_address' => $order->get_shipping_first_name() . ' '
                      . $order->get_shipping_last_name() . '. '
                      . $chosen_pickup_point->publicName . ', '
                      . $chosen_pickup_point->address->address . ', '
                      . $chosen_pickup_point->address->postalCode . ', '
                      . $chosen_pickup_point->address->municipality . ', '
                      . $chosen_pickup_point->countryCode
              );
          }
      }
    }

    return $prepared_tracking_items;
  }
}
