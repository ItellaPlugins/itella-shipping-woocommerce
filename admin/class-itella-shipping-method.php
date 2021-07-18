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
   * @var string
   */
  public $id;

  /**
   * Initialize the class and set its properties.
   *
   * @since    1.0.0
   * @var      string $name The name of this plugin.
   * @var      string $version The version of this plugin.
   */
  // TODO find bug - wp throws fatal error, that 0 args are passed to constructor, but debugger shows, that args are getting through
  public function __construct($name = 'itella-shipping', $version = '1.0.0')
  {

    parent::__construct();

    $this->name = $name;
    $this->version = $version;
    $this->id = "itella-shipping";
    $this->method_title = __('Smartpost Shipping', 'itella-shipping');
    $this->method_description = __('Plugin to use with Smartpost Shipping methods', 'itella-shipping');
    $this->title = "Smartpost Shipping Method";

    $this->available_countries = array('lt', 'ee', 'lv', 'fi');

    $this->init();

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
    if ( ($hook == 'woocommerce_page_wc-settings' && isset($_GET['section']) && $_GET['section'] == 'itella-shipping')
      || ($hook == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'shop_order') ) {
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
    if ( $hook == 'woocommerce_page_wc-settings' && isset($_GET['section']) && $_GET['section'] == 'itella-shipping' ) {
      wp_enqueue_script($this->name . 'itella-shipping-admin.js', plugin_dir_url(__FILE__) . 'js/itella-shipping-admin.js', array('jquery'), $this->version, TRUE);
    }
    if ( $hook == 'post.php') {
      wp_enqueue_script($this->name . 'itella-shipping-edit-orders.js', plugin_dir_url(__FILE__) . 'js/itella-shipping-edit-orders.js', array('jquery'), $this->version, TRUE);
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

  /**
   * Update locations
   *
   * @param string[] $country_codes
   */
  public function update_locations()
  {
    $itella_pickup_points_obj = new PickupPoints('https://locationservice.posti.com/api/2/location');

    foreach ($this->available_countries as $country_code) {
      $filename = plugin_dir_path(dirname(__FILE__))
          . 'locations/locations' . wc_strtoupper($country_code) . '.json';
      if (file_exists($filename)) {
        if ((time() - filemtime($filename)) > 86400) {
          $locations = $itella_pickup_points_obj->getLocationsByCountry($country_code);
          $itella_pickup_points_obj
              ->saveLocationsToJSONFile(plugin_dir_path(dirname(__FILE__))
                  . 'locations/locations' . wc_strtoupper($country_code) . '.json',
                  json_encode($locations));
        }
      }
    }
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
    global $woocommerce;
    $current_country = $woocommerce->customer->get_shipping_country();
    $cart_amount = floatval($woocommerce->cart->cart_contents_total) + floatval($woocommerce->cart->tax_total);
    $items = (isset($package['contents'])) ? $package['contents'] : $woocommerce->cart->get_cart();

    // add Pickup Point Rate
    if ($this->settings['pickup_point_method'] === 'yes') {
      $pickup_params = $this->get_pickup_params(array(
        'country_code' => $current_country,
        'cart_amount' => $cart_amount,
      ));

      $price_pickup = $this->get_shipping_price($pickup_params, $woocommerce->cart->cart_contents_weight, $items);

      $rate = array(
          'id' => 'itella_pp',
          'label' => __('Smartpost Pickup Point', 'itella-shipping'),
          'cost' => $price_pickup
      );

      if ($pickup_params['show'] == true && $this->settings['enabled'] == 'yes')
        $this->add_rate($rate);
    }

    // add Courier rate
    if ($this->settings['courier_method'] === 'yes') {
      $courier_params = $this->get_courier_params(array(
        'country_code' => $current_country,
        'cart_amount' => $cart_amount,
      ));

      $price_courier = $this->get_shipping_price($courier_params, $woocommerce->cart->cart_contents_weight, $items);

      $rate = array(
          'id' => 'itella_c',
          'label' => __('Smartpost Courier', 'itella-shipping'),
          'cost' => $price_courier
      );

      if ($courier_params['show'] == true && $this->settings['enabled'] == 'yes')
        $this->add_rate($rate);
    }
  }

  /**
   * Get shipping price from params.
   *
   * @access private
   * @param array $shipping_params
   * @param float $cart_weight
   * @return integer
   */
  private function get_shipping_price($shipping_params, $cart_weight, $cart_items=array())
  {
    $cart_amount = $shipping_params['cart_amount'];
    $price_shipping = $shipping_params['amount'];
    $free_from = $shipping_params['free_from'];
    $amount_values = (is_array($shipping_params['amount'])) ? $shipping_params['amount'] : array();
    $price_shipping = (isset($amount_values['single'])) ? $amount_values['single'] : $price_shipping;
    if (isset($amount_values['cb']) && isset($amount_values['weight'])) { // Compatibility with old versions
      $prev_weight = -0.001;
      foreach ($amount_values['weight'] as $key => $weight) {
        if (empty($weight)) {
          $weight = 1000000;
        }
        if ($cart_weight > $prev_weight && $cart_weight <= $weight) {
          $price_shipping = (isset($amount_values['price'][$key])) ? $amount_values['price'][$key] : 0;
        }
        $prev_weight = $weight;
      }
    }
    if (isset($amount_values['radio'])) {
      if ($amount_values['radio'] == 'weight' && isset($amount_values['byWeight'])) {
        $price_shipping = $this->getShippingPriceValueFromTable($amount_values['byWeight'], $cart_weight);
      }
      if ($amount_values['radio'] == 'price' && isset($amount_values['byPrice'])) {
        $price_shipping = $this->getShippingPriceValueFromTable($amount_values['byPrice'], $cart_amount);
      }
    }
    if (!empty($shipping_params['classes_amount'])) {
      $classes_amount = $shipping_params['classes_amount'];
      $use_shipclass = true;
      $class_price = 0;
      foreach ($cart_items as $item => $values) {
        $shipping_class = $values['data']->get_shipping_class_id();
        $found = false;
        if (!empty($shipping_class)) {
          foreach ($classes_amount as $class) {
            if (isset($class->ship_class) && $class->ship_class == $shipping_class) {
              $found = true;
              if (!empty($class->price) && $class->price > $class_price) {
                $class_price = $class->price;
              }
            }
          }
        }
        if (!$found) {
          $use_shipclass = false;
        }
      }
      if (!empty($cart_items) && $use_shipclass) {
        $price_shipping = $class_price;
      }
    }
    if ($cart_amount > $free_from && $free_from > 0) {
      $price_shipping = 0.0;
    }
    return $price_shipping;
  }

  /**
   * Check if string is json code.
   *
   * @access private
   * @param string $string
   * @return boolean
   */
  private function if_this_json($string)
  {
    json_decode($string);
     return (json_last_error() == JSON_ERROR_NONE);
  }

  /**
   * Get pickup point output parameters
   * 
   * @param array $args
   * @return array
   */
  private function get_pickup_params($args = array())
  {
    $country_code = isset($args['country_code']) ? strtolower($args['country_code']) : 'lt';
    $cart_amount = isset($args['cart_amount']) ? $args['cart_amount'] : 0;

    if ( ! in_array($country_code, $this->available_countries) ) {
      $country_code = 'lt';
    }

    $amount = $this->settings['pickup_point_price_' . $country_code];
    $amount = ($this->if_this_json($amount)) ? json_decode($amount, true) : $amount;
    $free_from = floatval($this->settings['pickup_point_nocharge_amount_' . $country_code]);
    $classes_amount = '';
    if (isset($this->settings['pickup_point_classes_' . $country_code])) {
      $classes_amount = $this->settings['pickup_point_classes_' . $country_code];
      $classes_amount = ($this->if_this_json($classes_amount)) ? json_decode($classes_amount, true) : $classes_amount;
    }
    return array(
      'show' => $this->check_rate_show($amount),
      'amount' => $amount,
      'classes_amount' => $classes_amount,
      'cart_amount' => $cart_amount,
      'free_from' => $free_from,
    );
  }

  /**
   * Get courier output parameters
   * 
   * @param array $args
   * @return array
   */
  private function get_courier_params($args = array())
  {
    $country_code = isset($args['country_code']) ? strtolower($args['country_code']) : 'lt';
    $cart_amount = isset($args['cart_amount']) ? $args['cart_amount'] : 0;

    if ( ! in_array($country_code, $this->available_countries) ) {
      $country_code = 'lt';
    }

    $amount = $this->settings['courier_price_' . $country_code];
    $amount = ($this->if_this_json($amount)) ? json_decode($amount, true) : $amount;
    $free_from = floatval($this->settings['courier_nocharge_amount_' . $country_code]);
    $classes_amount = '';
    if (isset($this->settings['courier_classes_' . $country_code])) {
      $classes_amount = $this->settings['courier_classes_' . $country_code];
      $classes_amount = ($this->if_this_json($classes_amount)) ? json_decode($classes_amount, true) : $classes_amount;
    }
    return array(
      'show' => $this->check_rate_show($amount),
      'amount' => $amount,
      'classes_amount' => $classes_amount,
      'cart_amount' => $cart_amount,
      'free_from' => $free_from,
    );
  }

  /**
   * Get 'show' value for shipping rate.
   *
   * @access private
   * @param int|float|json $amount
   * @return boolean
   */
  private function check_rate_show($amount)
  {
    $show = true;
    if (is_array($amount)) {
      $show = false;
      if (isset($amount['single'])) { // Compatibility with old versions
        if ($amount['single'] === '') {
          if (isset($amount['cb'])) {
            $show = true;
          }
        } else {
          $show = true;
        }
      }
      if (isset($amount['radio'])) {
        if ($amount['radio'] == 'disabled') {
          $show = false;
        } else {
          $show = true;
        }
      } else {
        $show = false;
      }
    } else {
      if ($amount === '') {
        $show = false;
      }
    }

    return $show;
  }

  private function getShippingPriceValueFromTable($values, $cart_value) {
    $price_shipping = 0;
    $prev_value = -0.001;
    foreach ($values as $key => $value) {
      if (empty($value['value'])) {
        $value['value'] = 1000000;
      }
      if ($cart_value > $prev_value && $cart_value <= $value['value']) {
        $price_shipping = (isset($value['price'])) ? $value['price'] : 0;
      }
      $prev_value = $value['value'];
    }

    return $price_shipping;
  }

  /**
   * Initialise Itella shipping settings form
   */
  function init_form_fields()
  {
    $fields = array(
        'enabled' => array(
            'title' => __('Enable', 'itella-shipping'),
            'type' => 'checkbox',
            'description' => __('Enable this shipping.', 'itella-shipping'),
            'default' => 'yes'
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
        'company' => array(
            'title' => __('Company name', 'itella-shipping'),
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
            'options' => array(
                'EE'  => 'EE - ' . __('Estonia', 'itella-shipping'),
                'FI' => 'FI - ' . __('Finland', 'itella-shipping'),
                'LV' => 'LV - ' . __('Latvia', 'itella-shipping'),
                'LT' => 'LT - ' . __('Lithuania', 'itella-shipping'),
            ),
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
        'pickup_point_method' => array(
            'title' => __('Enable Pickup Point', 'itella-shipping'),
            'class' => 'pickup-point-method',
            'type' => 'checkbox',
            'description' => __('Show pickup point shipping method in checkout.', 'itella-shipping'),
            'default' => 'no'
        ),
        'courier_method' => array(
            'title' => __('Enable Courier', 'itella-shipping'),
            'type' => 'checkbox',
            'class' => 'courier-method',
            'description' => __('Show courier shipping method in checkout.', 'itella-shipping'),
            'default' => 'no'
        ),
        'checkout_show_style' => array(
            'title' => __('Pickup point selection style', 'itella-shipping'),
            'type'    => 'select',
            'class' => 'checkout-style pickup-point',
            'options' => array(
                'map'  => __('Map', 'itella-shipping'),
                'dropdown' => __('Dropdown', 'itella-shipping'),
            ),
            'default' => 'map',
            'description' => __('Choose what the pickup point selection in the checkout will look like.', 'itella-shipping'),
        ),
    );
    foreach ($this->available_countries as $country_code) {
      $fields['hr_' . $country_code] = array(
        'title' => strtoupper($country_code),
        'type' => 'hr'
      );
      /*$fields['section_pickup_' . $country_code] = array(
        'title' => __('Pickup Point', 'itella-shipping'),
        'type' => 'section_name',
        'class' => 'pickup-point'
      );*/
      $fields['pickup_point_price_' . $country_code] = array(
        'title' => strtoupper($country_code) . '. ' . __('Pickup Point price', 'itella-shipping'),
        'class' => 'pickup-point',
        'type' => 'price_by_weight',
        'default' => 2,
      );
      $fields['pickup_point_classes_' . $country_code] = array(
        'title' => strtoupper($country_code) . '. ' . __('Pickup Point price by class', 'itella-shipping'),
        'class' => 'pickup-point',
        'type' => 'shipping_class',
        'default' => '',
        'description' => __('Set custom price for specific shipping class. Only works when all items in the cart belong to this class', 'itella-shipping'),
      );
      $fields['pickup_point_nocharge_amount_' . $country_code] = array(
        'title' => strtoupper($country_code) . '. ' . __('Pickup Point free from', 'itella-shipping'),
        'class' => 'pickup-point',
        'type' => 'number',
        'custom_attributes' => array(
            'step' => 0.01,
            'min' => 0.01,
        ),
        'default' => 100,
        'description' => __('Disable pickup point fee if cart amount is greater or equal than this limit', 'itella-shipping'),
      );
      $fields['pickup_point_description_' . $country_code] = array(
        'title' => strtoupper($country_code) . '. ' . __('Pickup Point description', 'itella-shipping'),
        'class' => 'pickup-point',
        'type' => 'textarea',
        'default' => '',
        'description' => __('Show shipping method description on Checkout page'),
      );
      /*$fields['section_courier_' . $country_code] = array(
        'title' => __('Courier', 'itella-shipping'),
        'type' => 'section_name',
        'class' => 'courier'
      );*/
      $fields['courier_price_' . $country_code] = array(
        'title' => strtoupper($country_code) . '. ' . __('Courier price', 'itella-shipping'),
        'class' => 'courier',
        'type' => 'price_by_weight',
        'default' => 2,
      );
      $fields['courier_classes_' . $country_code] = array(
        'title' => strtoupper($country_code) . '. ' . __('Courier price by class', 'itella-shipping'),
        'class' => 'courier',
        'type' => 'shipping_class',
        'default' => '',
        'description' => __('Set custom price for specific shipping class. Only works when all items in the cart belong to this class', 'itella-shipping'),
      );
      $fields['courier_nocharge_amount_' . $country_code] = array(
        'title' => strtoupper($country_code) . '. ' . __('Courier free from', 'itella-shipping'),
        'class' => 'courier',
        'type' => 'number',
        'custom_attributes' => array(
            'step' => 0.01,
            'min' => 0.01,
        ),
        'default' => 100,
        'description' => __('Disable courier fee if cart amount is greater or equal than this limit', 'itella-shipping'),
      );
      $fields['courier_description_' . $country_code] = array(
        'title' => strtoupper($country_code) . '. ' . __('Courier description', 'itella-shipping'),
        'class' => 'courier',
        'type' => 'textarea',
        'default' => '',
        'description' => __('Show shipping method description on Checkout page'),
      );
    }
    $fields['hr_courier_mail'] = array(
      'type' => 'hr'
    );
    foreach ($this->available_countries as $country_code) {
      $fields['call_courier_mail_' . $country_code] = array(
        'title' => sprintf(__('Smartpost %s email', 'itella-shipping'), strtoupper($country_code)),
        'type' => 'text',
        'default' => sprintf('smartship.routing.%s@itella.com', $country_code),
      );
    }
    $fields['call_courier_mail_subject'] = array(
        'title' => __('Smartpost email subject', 'itella-shipping'),
        'type' => 'text',
        'default' => 'E-com order booking',
      );
    $this->form_fields = $fields;
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
      'single' => __('Simple', 'itella-shipping'),
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
    $weight_unit = get_option('woocommerce_weight_unit');

    ob_start();
    ?>
    <tr valign="top">
      <th scope="row" class="titledesc">
        <label><?php echo esc_html($value['title']); ?></label>
      </th>
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
    $shipping_classes = WC()->shipping->get_shipping_classes();

    if ( $this->get_option($key) !== '' ) {
      $values = $this->get_option($key);
      if ( is_string($values) ) {
        $values = json_decode($this->get_option($key), true);
      }
    } else {
      $values = array();
    }

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

  public function add_shipping_details_to_order($order)
  {
    $order_id = $order->get_id();

    //check if shipping was previously updated
    $is_shipping_updated = !empty(get_post_meta($order_id, 'itella_shipping_method', true));

    $itella_method = $is_shipping_updated ?
        get_post_meta($order_id, 'itella_shipping_method', true) :
        get_post_meta($order_id, '_itella_method', true);

    if ($itella_method) {

      // defaults
      $oversized = 'oversized';
      $call_before_delivery = 'call_before_delivery';
      $fragile = 'fragile';
      $default_packet_count = '1';
      $default_weight = '0.00';
      $extra_services = array();
      $default_is_cod = $order->get_payment_method() === 'itella_cod';
      $default_cod_amount = $order->get_total();

      $extra_services_options = array(
          $oversized => __('Oversized', 'itella-shipping'),
          $call_before_delivery => __('Call before delivery', 'itella-shipping'),
          $fragile => __('Fragile', 'itella-shipping')
      );

      // vars
      if ($is_shipping_updated) {
        $packet_count = get_post_meta($order_id, 'packet_count', true);
        $weight = get_post_meta($order_id, 'weight_total', true);
        $is_cod = get_post_meta($order_id, 'itella_cod_enabled', true) === 'yes';
        $cod_amount = get_post_meta($order_id, 'itella_cod_amount', true);
        $extra_services = get_post_meta($order_id, 'itella_extra_services', true);
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

      $chosen_pickup_point_id = get_post_meta($order_id, '_pp_id', true);
      $chosen_pickup_point = $this->get_chosen_pickup_point(Itella_Manifest::order_getCountry($order), $chosen_pickup_point_id);

      $weight_unit = get_option('woocommerce_weight_unit');

      // packet html select element options
      $packets = array();
      for ($i = 1; $i < 11; $i++) {
        $packets[$i] = strval($i);
      }

      ?>
        <br class="clear"/>
        <h4><?= __('Smartpost Shipping Options', 'itella-shipping') ?><a href="#" class="edit_address"
                                                                      id="itella-shipping-options">Edit</a></h4>
        <div class="address">
            <p>
                <strong><?= __('Packets(total):', 'itella-shipping') ?></strong> <?= $packet_count ?>
            </p>
            <p>
                <strong><?= sprintf(__('Weight (%s):', 'itella-shipping'), $weight_unit) ?></strong> <?= $weight ?>
            </p>
            <p><strong><?= __('COD:', 'itella-shipping') ?></strong>
              <?=
              $is_cod ? __('Yes', 'woocommerce') : __('No', 'woocommerce')
              ?>
            </p>
          <?php if ($is_cod): ?>
              <p>
                  <strong><?= sprintf(__('COD amount (%s):', 'itella-shipping'), $order->get_currency()) ?></strong> <?= $cod_amount ?>
              </p>
          <?php endif; ?>
            <p><strong><?= __('Shipping method:', 'itella-shipping') ?></strong>
              <?=
              $is_itella_pp ? __('Pickup Point', 'itella-shipping') :
                  ($is_itella_c ? __('Courier', 'itella-shipping') :
                      __('No Smartpost Shipping method selected', 'itella-shipping'))
              ?>
            </p>
          <?php if ($is_itella_pp): ?>
              <p><strong><?= __('Chosen Pickup Point', 'itella-shipping') ?></strong>
                <?=
                $chosen_pickup_point->address->municipality . ' - ' .
                $chosen_pickup_point->address->address . ', ' .
                $chosen_pickup_point->address->postalCode . ' (' .
                $chosen_pickup_point->publicName . ')'
                ?>
              </p>
          <?php endif; ?>
            <p><strong><?= __('Extra Services:', 'itella-shipping') ?></strong>
              <?php
              if (empty($extra_services)) {
                echo __('No extra services selected', 'itella-shipping');
              } else {
                if (in_array($oversized, $extra_services)) {
                  echo __('Oversized', 'itella-shipping');
                }
                if (in_array($call_before_delivery, $extra_services)) {
                  echo __('Call before delivery', 'itella-shipping');
                  echo '<br>';
                }
                if (in_array($fragile, $extra_services)) {
                  echo __('Fragile', 'itella-shipping');
                  echo '<br>';
                }
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
              'label' => sprintf(__('Weight (%s)', 'itella-shipping'), $weight_unit),
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
              'label' => sprintf(__('COD amount (%s):', 'itella-shipping'), $order->get_currency()),
              'value' => $cod_amount,
              'wrapper_class' => 'form-field-wide'
          ));

          woocommerce_wp_select(array(
              'id' => 'itella_shipping_method',
              'label' => __('Carrier:', 'itella-shipping'),
              'value' => $is_itella_pp ? 'itella_pp' : 'itella_c',
              'options' => array(
                  'itella_pp' => __('Pickup Point', 'itella-shipping'),
                  'itella_c' => __('Courier', 'itella-shipping')
              ),
              'wrapper_class' => 'form-field-wide'
          ));

          woocommerce_wp_select(array(
              'id' => '_pp_id',
              'label' => __('Select Pickup Point:', 'itella-shipping'),
              'value' => $chosen_pickup_point_id,
              'options' => $this->build_pickup_points_list(Itella_Manifest::order_getCountry($order)),
              'wrapper_class' => 'form-field-wide'
          ));

          $this->woocommerce_wp_multi_checkbox(array(
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
    <?php }
  }

  public static function getTrackingUrl($country_code = 'lt')
  {
    $all_tracking_urls = array(
      'lt' => 'https://itella.lt/verslui/siuntos-sekimas/?trackingCode=',
      'lv' => 'https://itella.lv/private-customer/sutijuma-meklesana/?trackingCode=',
      'ee' => 'https://itella.ee/eraklient/saadetise-jalgimine/?trackingCode=',
    );
    $country_code = strtolower($country_code);

    if (isset($all_tracking_urls[$country_code])) {
      return $all_tracking_urls[$country_code];
    }

    return $all_tracking_urls['lt'];
  }

  // Multi Checkbox field for woocommerce backend
  function woocommerce_wp_multi_checkbox($field)
  {
    global $thepostid, $post;

    $thepostid = empty($thepostid) ? $post->ID : $thepostid;
    $field['class'] = isset($field['class']) ? $field['class'] : 'select short';
    $field['style'] = isset($field['style']) ? $field['style'] : '';
    $field['wrapper_class'] = isset($field['wrapper_class']) ? $field['wrapper_class'] : '';
    $field['value'] = isset($field['value']) ? $field['value'] : array();
    $field['name'] = isset($field['name']) ? $field['name'] : $field['id'];
    $field['desc_tip'] = isset($field['desc_tip']) ? $field['desc_tip'] : false;

    echo '<fieldset class="form-field ' . esc_attr($field['id']) . '_field ' . esc_attr($field['wrapper_class']) . '">
    <legend>' . wp_kses_post($field['label']) . '</legend>';

    if (!empty($field['description']) && false !== $field['desc_tip']) {
      echo wc_help_tip($field['description']);
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
    $pickup_points = wp_remote_get(plugin_dir_url(__FILE__) . '../locations/locations' . $shipping_country_id . '.json');

    return json_decode($pickup_points['body']);
  }

  /**
   * Get chosen pickup point
   *
   * @param $shipping_country_id
   * @param $pickup_point_id
   * @return object|null
   */
  public function get_chosen_pickup_point($shipping_country_id, $pickup_point_id)
  {
    $pickup_points = $this->get_pickup_points($shipping_country_id);
    $chosen_pickup_point = null;

    foreach ($pickup_points as $pickup_point) {
      $chosen_pickup_point = $pickup_point->id === $pickup_point_id ? $pickup_point : null;
      if ($chosen_pickup_point) {

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
      $pickup_points_list[$pickup_point->id] =
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
    $isItellaPp = in_array('itella_pp',wc_clean($_POST['shipping_method']));
    if ($isItellaPp && empty($_POST['itella-chosen-point-id'])) {
      wc_add_notice( __( "You must choose Smartpost Pickup Point", 'itella-shipping' ), 'error');
    }
  }

  /**
   * Save in Woocommerce->Orders->Edit Order updated shipping options
   *
   * @param $order_id
   */
  public function save_shipping_settings($order_id)
  {
    update_post_meta($order_id, 'packet_count', wc_clean($_POST['packet_count']));
    if (intval(wc_clean($_POST['packet_count']) > 1)) {
      update_post_meta($order_id, 'itella_multi_parcel', 'true');
    }
    update_post_meta($order_id, 'weight_total', wc_clean($_POST['weight_total']));
    update_post_meta($order_id, 'itella_cod_enabled', wc_clean($_POST['itella_cod_enabled']));
    update_post_meta($order_id, 'itella_cod_amount', wc_clean($_POST['itella_cod_amount']));
    update_post_meta($order_id, 'itella_shipping_method', wc_clean($_POST['itella_shipping_method']));
    update_post_meta($order_id, '_pp_id', wc_clean($_POST['_pp_id']));
    update_post_meta($order_id, 'itella_extra_services', wc_clean($_POST['itella_extra_services']));
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

      // merge downloaded labels
      $this->merge_labels($temp_files);
    } catch (ItellaException $e) {
      // add error message
      $this->add_msg(__('An error occurred.', 'itella-shipping')
          . ' ' . $e->getMessage()
          , 'error');

      // log error
      file_put_contents(plugin_dir_path(dirname(__FILE__)) . 'var/log/errors.log',
          "\n" . date('Y-m-d H:i:s') . ": ItellaException:\n" . $e->getMessage() . "\n"
          . $e->getTraceAsString(), FILE_APPEND);

    } catch (\Exception $e) {
      $this->add_msg(__('An error occurred.', 'itella-shipping')
          . ' ' . $e->getMessage()
          , 'error');

      // log error
      file_put_contents(plugin_dir_path(dirname(__FILE__)) . 'var/log/errors.log',
          "\n" . date('Y-m-d H:i:s') . ": Exception:\n" . $e->getMessage() . "\n"
          . $e->getTraceAsString(), FILE_APPEND);
    }
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
            'meta_key' => '_itella_manifest_generation_date',
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
      $order_ids = wc_get_orders($args);
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
    $order_id = $order->get_id();
    $chosen_pickup_point_id = get_post_meta($order_id, '_pp_id', true);
    $tracking_number = $this->get_tracking_code($order_id);
    $tracking_url = $order->get_meta('_itella_tracking_url');
    
    if ( empty( $tracking_number ) && empty($chosen_pickup_point_id) ) {
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

    if ( ! empty($chosen_pickup_point_id) && $chosen_pickup_point_id != '-' ) {
      $pp_address = $this->build_pickup_address_for_display( $order, $chosen_pickup_point_id );

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

    $Itella_Shipping_Method = new Itella_Shipping_Method();
    if ( ! isset($Itella_Shipping_Method->settings) ) {
      return;
    }

    $customer = WC()->session->get('customer');
    if ( ! isset($customer['country']) ) {
      return;
    }

    $pt_description_name = 'pickup_point_description_' . strtolower($customer['country']);
    $c_description_name = 'courier_description_' . strtolower($customer['country']);

    if ( ! empty($Itella_Shipping_Method->settings[$pt_description_name]) && $method->id === 'itella_pp' ) {
      echo '<span class="itella-shipping-description">' . $Itella_Shipping_Method->settings[$pt_description_name] . '</span>';
    }
    if ( ! empty($Itella_Shipping_Method->settings[$c_description_name]) && $method->id === 'itella_c' ) {
      echo '<span class="itella-shipping-description">' . $Itella_Shipping_Method->settings[$c_description_name] . '</span>';
    }
  }

  /**
   * Shipping information in order quickview (Add fields)
   */
  public function add_custom_admin_order_preview_meta( $data, $order )
  {
    $order_id = $order->get_id();
    $tracking_number = $this->get_tracking_code($order_id);
    $tracking_url = $order->get_meta('_itella_tracking_url');
    $chosen_pickup_point_id = get_post_meta($order_id, '_pp_id', true);

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
          <strong>' . __('Pickup point', 'itella-shipping') . '</strong><br/>
          <span>{{data.pp_address}}</span>
        </div>
      <# } #>
    </div>';
  }

  /**
   * Build formated pickup address string
   */
  private function build_pickup_address_for_display( $order, $chosen_pickup_point_id )
  {
    $order_id = $order->get_id();
    $chosen_pickup_point = $this->get_chosen_pickup_point(Itella_Manifest::order_getCountry($order), $chosen_pickup_point_id);
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
      if ($this->get_tracking_code($order_id)) {
        update_post_meta($order_id,
            '_itella_manifest_generation_date',
            date('Y-m-d H:i:s', $timestamp)
        );
      }
    }
  }

  /**
   * Get order tracking code
   *
   * @param $order_id
   * @return array|mixed|string
   */
  private function get_tracking_code($order_id)
  {
    $order = wc_get_order($order_id);

    return $order->get_meta('_itella_tracking_code');
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

    foreach ($order_ids as $order_id) {
      $order = wc_get_order($order_id);
      $shipping_parameters = Itella_Manifest::get_shipping_parameters($order_id);
      $order_country = Itella_Manifest::order_getCountry($order);
      $shipping_method = $shipping_parameters['itella_shipping_method'];
      $shipment = null;

      // check if itella shipping method
      if ($shipping_method !== 'itella_pp' && $shipping_method !== 'itella_c') {
        if (in_array('msg',$exit_modes)) {
          $this->add_msg($order_id . ' - ' . __('Shipment is not registered.', 'itella-shipping')
            . "<br>"
            . __('Error: ', 'itella-shipping')
            . __('Not Smartpost Shipping Method', 'itella-shipping'), 'error');
        }
        if (in_array('redirect',$exit_modes)) {
          wp_safe_redirect(wp_get_referer());
        }
        if (in_array('return',$exit_modes)) {
          return array('status' => 'error', 'msg' => __('Not Smartpost Shipping Method', 'itella-shipping'));
        }
      }

      $contract_number = $shipping_method === 'itella_pp'
          ? $this->settings['api_contract_2711']
          : $this->settings['api_contract_2317'];

      // register shipment
      try {
        $sender = $this->create_sender($contract_number);
        $receiver = $this->create_receiver($order);

        $shipment = $shipping_method === 'itella_pp'
            ? $this->register_pickup_point_shipment($sender, $receiver, $shipping_parameters, $order_id)
            : $this->register_courier_shipment($sender, $receiver, $shipping_parameters, $order_id);

        $result = $shipment->registerShipment();

        // set tracking number
        update_post_meta($order_id, '_itella_tracking_code', $result->__toString());
        update_post_meta($order_id, '_itella_tracking_url', self::getTrackingUrl($order_country) . $result->__toString());

        // add order note
        $note = sprintf(
          __('Smartpost shipment registered successfully. Tracking number: %s', 'itella-shipping'),
          '<a href="' . self::getTrackingUrl($order_country) . $result . '" target="_blank">' . $result . '</a>'
        );
        $order->add_order_note( $note );

        // add notices
        if (in_array('msg',$exit_modes)) {
          $this->add_msg(
            'Order ' . $order_id . ' - '
            . __('Shipment registered successfully.', 'itella-shipping'), 'success'
          );
          $this->add_msg(
            'Order ' . $order_id . ' - '
            . __('Tracking number: ', 'itella-shipping') . $result, 'info'
          );
        }

        // log order id and tracking number
        file_put_contents(plugin_dir_path(dirname(__FILE__)) . 'var/log/registered_tracks.log',
            "\nOrder ID : " . $order->get_id() . "\n" . 'Tracking number: ' . $result, FILE_APPEND);

        if (in_array('return',$exit_modes)) {
          return array('status' => 'success', 'msg' => __('Shipment registered successfully.', 'itella-shipping'));
        }

      } catch (ItellaException $th) {

        // add error message
        if (in_array('msg',$exit_modes)) {
          $this->add_msg($order_id . ' - ' . __('Shipment is not registered.', 'itella-shipping')
            . "<br>"
            . __('An error occurred. ', 'itella-shipping')
            . $th->getMessage()
            , 'error');
        }

        // log error
        file_put_contents(plugin_dir_path(dirname(__FILE__)) . 'var/log/errors.log',
            "\n" . date('Y-m-d H:i:s') . ": Exception:\n" . $th->getMessage() . "\n"
            . $th->getTraceAsString(), FILE_APPEND);

        if (in_array('return',$exit_modes)) {
          return array('status' => 'error', 'msg' => __('An error occurred. ', 'itella-shipping') . $th->getMessage());
        }
      }

      catch (\Exception $e) {

          // add error message
          if (in_array('msg',$exit_modes)) {
            $this->add_msg($order_id . ' - ' . __('Shipment is not registered.', 'itella-shipping')
              . "<br>"
              . __('An error occurred. ', 'itella-shipping')
              . $e->getMessage()
              , 'error');
          }

          // log error
          file_put_contents(plugin_dir_path(dirname(__FILE__)) . 'var/log/errors.log',
              "\n" . date('Y-m-d H:i:s') . ": Exception:\n" . $e->getMessage() . "\n"
              . $e->getTraceAsString(), FILE_APPEND);

          if (in_array('return',$exit_modes)) {
            return array('status' => 'error', 'msg' => __('An error occurred. ', 'itella-shipping') . $th->getMessage());
          }
      }
    }

    // return to shipments
    if (in_array('redirect',$exit_modes)) {
      wp_safe_redirect(wp_get_referer());
    }
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
      $check_code = get_post_meta($id, '_itella_tracking_code');
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
      $failed = array();
      foreach($_REQUEST['ids'] as $id) {
        $check_code = get_post_meta($id, '_itella_tracking_code');
        if (empty($check_code)) {
          $_REQUEST['post'] = $id;
          $status = $this->itella_post_shipment_actions( array('return') );
          if ($status['status'] === 'error') {
            array_push($failed, array('id' => $id, 'msg' => $status['msg']));
          }
        }
      }
      if (empty($failed)) {
        echo json_encode(array(
          'status' => 'success',
          'msg' => __("Shipments successfully registered", 'itella-shipping')
        ));
      } else {
        echo json_encode(array(
          'status' => 'notice',
          'msg' => __("Some orders failed to register the shipment", 'itella-shipping'),
          'values' => $failed
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
    $is_test = true;

    // Create GoodsItem (parcel)
    $items = array();

    for ($i = 0; $i < intval($shipping_parameters['packet_count']); $i++) {
      $items[] = new GoodsItem();
    }

    // Create additional services
    $additional_services = array();

    // get services from order
    $extra_services = $shipping_parameters['extra_services'];
    $extra_services = is_array($extra_services) ? $extra_services : array($extra_services);

    // cod
    if ($shipping_parameters['is_cod'] === 'yes' || $shipping_parameters['is_cod'] === true) {
      $service_cod = new AdditionalService(3101, array(
          'amount' => $shipping_parameters['cod_amount'],
          'account' => $this->settings['bank_account'],
          'reference' => ItellaHelper::generateCODReference($order_id),
          'codbic' => $this->settings['cod_bic']
      ));
      $additional_services[] = $service_cod;
    }

    // other services
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

    // Create shipment object
    $shipment = new Shipment($p_user, $p_secret, $is_test);
    $shipment
        ->setProductCode(Shipment::PRODUCT_COURIER)
        ->setShipmentNumber($order_id)
        ->setShipmentDateTime(date('c'))
        ->setSenderParty($sender)
        ->setReceiverParty($receiver)
        ->addAdditionalServices($additional_services)
        ->addGoodsItems($items);

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
    $is_test = true;
    $shipping_country = Itella_Manifest::order_getCountry(wc_get_order($order_id));
    $chosen_pickup_point = $this->get_chosen_pickup_point($shipping_country, $shipping_parameters['pickup_point_id']);

    // Create GoodsItem (parcel)
    $item = new GoodsItem();
    $item->setGrossWeight(intval($shipping_parameters['weight'])); // kg

    // Create shipment object
    $shipment = new Shipment($p_user, $p_secret, $is_test);
    $shipment
        ->setProductCode(Shipment::PRODUCT_PICKUP)
        ->setShipmentNumber($order_id)
        ->setShipmentDateTime(date('c'))
        ->setSenderParty($sender)
        ->setReceiverParty($receiver)
        ->setPickupPoint($chosen_pickup_point->pupCode) // pupCode
        ->addGoodsItem($item);

    return $shipment;

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
    $receiver = new Party(Party::ROLE_RECEIVER);
    $receiver
        ->setName1($order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name())
        ->setStreet1($order->get_shipping_address_1())
        ->setPostCode($order->get_shipping_postcode())
        ->setCity($order->get_shipping_city())
        ->setCountryCode(Itella_Manifest::order_getCountry($order))
        ->setContactMobile($order->get_billing_phone())
        ->setContactEmail($order->get_billing_email());

    return $receiver;
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

  public function itella_shipping_shop_order_bulk_actions($actions)
  {
    // TODO
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
      $order = wc_get_order($order_id);
      $tracking_code = $order->get_meta('_itella_tracking_code');

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
    $order_ids = $_REQUEST['post']; // no post

    $translation = $this->get_manifest_translation();
    $items = $this->prepare_items_for_manifest($order_ids);

    $manifest = $this->create_manifest($translation, $items);
    $manifest_string = $manifest
        ->setToString(true)
        ->setBase64(true)
        ->printManifest('manifest.pdf');

    $shop_country_code = strtolower($this->settings['shop_countrycode']);
    if ( in_array($shop_country_code, $this->available_countries) && $this->settings['call_courier_mail_' . $shop_country_code] ) {
      $email = $this->settings['call_courier_mail_' . $shop_country_code];
    } else {
      $email = 'smartship.routing.lt@itella.com';
    }
    $email_subject = __('E-com order booking', 'itella-shipping');
    if (!empty($this->settings['call_courier_mail_subject'])) {
      $email_subject = $this->settings['call_courier_mail_subject'];
    }

    try {
      $caller = new CallCourier($email);
      $result = $caller
          ->setSenderEmail($this->settings['shop_email'])
          ->setSubject($email_subject)
          ->setPickUpAddress(array(
              'sender' => $this->settings['shop_name'],
              'address_1' => $this->settings['shop_address'],
              'postcode' => $this->settings['shop_postcode'],
              'city' => $this->settings['shop_city'],
              'country' => $this->settings['shop_countrycode'],
              'pickup_time' => '8:00 - 17:00',
              'contact_phone' => $this->settings['shop_phone'],
          ))
          ->setAttachment($manifest_string, true)
          ->setItems($items)
          ->callCourier();

      if ($result) {
        // add notices
        $this->add_msg(__('Email sent to courier', 'itella-shipping')
            . ' (' . $email . ')', 'success');
      }
    } catch (ItellaException $e) {

      // add error message
      $this->add_msg(__('Failed to send email.', 'itella-shipping')
          . "<br>"
          . __('Error:', 'itella-shipping')
          . ' '
          . $e->getMessage()
          , 'error');

      // log error
      file_put_contents(plugin_dir_path(dirname(__FILE__)) . 'var/log/errors.log',
          "\n" . date('Y-m-d H:i:s') . ": ItellaException:\n" . $e->getMessage() . "\n"
          . $e->getTraceAsString(), FILE_APPEND);
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
      $order = wc_get_order($order_id);
      $shipping_parameters = Itella_Manifest::get_shipping_parameters($order_id);
      $shipping_method = $shipping_parameters['itella_shipping_method'];
      $shipping_country = Itella_Manifest::order_getCountry(wc_get_order($order_id));
      $chosen_pickup_point = $this->get_chosen_pickup_point($shipping_country, $shipping_parameters['pickup_point_id']);

      $tracking_code = $this->get_tracking_code($order_id);

      // manifest is only for registered shipments (that have tracking code)
      if ($tracking_code) {
          if ($shipping_method === 'itella_c') {
              $prepared_tracking_items[] = array(
                  'track_num' => $tracking_code,
                  'weight' => !empty($shipping_parameters['weight']) ? $shipping_parameters['weight'] : 0,
                  'delivery_address' => $order->get_shipping_first_name() . ' '
                      . $order->get_shipping_last_name() . ', '
                      . $order->get_shipping_address_1() . ', '
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
