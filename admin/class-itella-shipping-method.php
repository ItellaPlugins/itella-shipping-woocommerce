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
    const ITELLA_TRACKING_URL = 'https://itella.lt/verslui/siuntos-sekimas/?trackingCode=';

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
    $this->method_title = __('Itella Shipping', 'itella-shipping');
    $this->method_description = __('Plugin to use with Itella Shipping methods', 'itella-shipping');
    $this->title = "Itella Shipping Method";

    $this->init();

  }

  /**
   * Register the stylesheets for the Dashboard.
   *
   * @since    1.0.0
   */
  public function enqueue_styles()
  {

    wp_enqueue_style($this->name, plugin_dir_url(__FILE__) . 'css/itella-shipping-admin.css', array(), $this->version, 'all');

  }

  /**
   * Register the JavaScript for the dashboard.
   *
   * @since    1.0.0
   */
  public function enqueue_scripts()
  {

    wp_enqueue_script($this->name . 'itella-shipping-admin.js', plugin_dir_url(__FILE__) . 'js/itella-shipping-admin.js', array('jquery'), $this->version, TRUE);
    wp_enqueue_script($this->name . 'itella-shipping-edit-orders.js', plugin_dir_url(__FILE__) . 'js/itella-shipping-edit-orders.js', array('jquery'), $this->version, TRUE);

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
   * Update locations
   *
   * @param string[] $country_codes
   */
  public function update_locations()
  {
    $country_codes = array('lt', 'lv', 'ee', 'fi');

    $itella_pickup_points_obj = new PickupPoints('https://locationservice.posti.com/api/2/location');

    foreach ($country_codes as $country_code) {
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
    $cart_amount = floatval($woocommerce->cart->get_cart_contents_total()) + floatval($woocommerce->cart->get_tax_totals());

    // add Pickup Point Rate
    if ($this->settings['pickup_point_method'] === 'yes') {
      switch ($current_country) {
        case 'LV':
          $amount = $this->settings['pickup_point_price_lv'];
          if ($cart_amount > floatval($this->settings['pickup_point_nocharge_amount_lv']))
            $amount = 0.0;
          break;
        case 'EE':
          $amount = $this->settings['pickup_point_price_ee'];
          if ($cart_amount > floatval($this->settings['pickup_point_nocharge_amount_ee']))
            $amount = 0.0;
          break;
        case 'FI':
          $amount = $this->settings['pickup_point_price_fi'];
          if ($cart_amount > floatval($this->settings['pickup_point_nocharge_amount_fi']))
            $amount = 0.0;
          break;
        default:
          $amount = $this->settings['pickup_point_price_lt'];
          if ($cart_amount > floatval($this->settings['pickup_point_nocharge_amount_lt']))
            $amount = 0.0;
          break;
      }

      $rate = array(
          'id' => 'itella_pp',
          'label' => __('Itella Pickup Point', 'itella-shipping'),
          'cost' => $amount
      );

      $this->add_rate($rate);
    }

    // add Courier rate
    if ($this->settings['courier_method'] === 'yes') {
      switch ($current_country) {
        case 'LV':
          $amountC = $this->settings['courier_price_lv'];
          if ($cart_amount > floatval($this->settings['courier_nocharge_amount_lv']))
            $amountC = 0.0;
          break;
        case 'EE':
          $amountC = $this->settings['courier_price_ee'];
          if ($cart_amount > floatval($this->settings['courier_nocharge_amount_ee']))
            $amountC = 0.0;
          break;
        case 'FI':
          $amountC = $this->settings['courier_price_fi'];
          if ($cart_amount > floatval($this->settings['courier_nocharge_amount_fi']))
            $amountC = 0.0;
          break;
        default:
          $amountC = $this->settings['courier_price_lt'];
          if ($cart_amount > floatval($this->settings['courier_nocharge_amount_lt']))
            $amountC = 0.0;
          break;
      }

      $rate = array(
          'id' => 'itella_c',
          'label' => __('Itella Courier', 'itella-shipping'),
          'cost' => $amountC
      );
      $this->add_rate($rate);
    }
  }


  /**
   * Initialise Itella shipping settings form
   */
  function init_form_fields()
  {
    $this->form_fields = array(
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
            'type' => 'text',
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
        'pickup_point_price_lt' => array(
            'title' => 'LT. ' . __('Pickup Point price', 'itella-shipping'),
            'class' => 'pickup-point',
            'type' => 'number',
            'custom_attributes' => array(
                'step' => 0.01,
            ),
            'default' => 2,
        ),
        'courier_price_lt' => array(
            'title' => 'LT. ' . __('Courier price', 'itella-shipping'),
            'class' => 'courier',
            'type' => 'number',
            'default' => 2,
            'custom_attributes' => array(
                'step' => 0.01,
            ),
        ),
        'pickup_point_nocharge_amount_lt' => array(
            'title' => 'LT. ' . __('Disable pickup point fee if cart amount is greater or equal than this limit', 'itella-shipping'),
            'class' => 'pickup-point',
            'type' => 'number',
            'custom_attributes' => array(
                'step' => 0.01,
            ),
            'default' => 100
        ),
        'courier_nocharge_amount_lt' => array(
            'title' => 'LT. ' . __('Disable courier fee if cart amount is greater or equal than this limit', 'itella-shipping'),
            'class' => 'courier',
            'type' => 'number',
            'custom_attributes' => array(
                'step' => 0.01,
            ),
            'default' => 100
        ),
        'pickup_point_price_lv' => array(
            'title' => 'LV. ' . __('Pickup Point price', 'itella-shipping'),
            'class' => 'pickup-point',
            'type' => 'number',
            'custom_attributes' => array(
                'step' => 0.01,
            ),
            'default' => 2,
        ),
        'courier_price_lv' => array(
            'title' => 'LV. ' . __('Courier price', 'itella-shipping'),
            'class' => 'courier',
            'type' => 'number',
            'default' => 2,
            'custom_attributes' => array(
                'step' => 0.01,
            ),
        ),
        'pickup_point_nocharge_amount_lv' => array(
            'title' => 'LV. ' . __('Disable pickup point fee if cart amount is greater or equal than this limit', 'itella-shipping'),
            'class' => 'pickup-point',
            'type' => 'number',
            'custom_attributes' => array(
                'step' => 0.01,
            ),
            'default' => 100
        ),
        'courier_nocharge_amount_lv' => array(
            'title' => 'LV. ' . __('Disable courier fee if cart amount is greater or equal than this limit', 'itella-shipping'),
            'class' => 'courier',
            'type' => 'number',
            'custom_attributes' => array(
                'step' => 0.01,
            ),
            'default' => 100
        ),
        'pickup_point_price_ee' => array(
            'title' => 'EE. ' . __('Pickup Point price', 'itella-shipping'),
            'class' => 'pickup-point',
            'type' => 'number',
            'custom_attributes' => array(
                'step' => 0.01,
            ),
            'default' => 2,
        ),
        'courier_price_ee' => array(
            'title' => 'EE. ' . __('Courier price', 'itella-shipping'),
            'class' => 'courier',
            'type' => 'number',
            'default' => 2,
            'custom_attributes' => array(
                'step' => 0.01,
            ),
        ),
        'pickup_point_nocharge_amount_ee' => array(
            'title' => 'EE. ' . __('Disable pickup point fee if cart amount is greater or equal than this limit', 'itella-shipping'),
            'class' => 'pickup-point',
            'type' => 'number',
            'custom_attributes' => array(
                'step' => 0.01,
            ),
            'default' => 100
        ),
        'courier_nocharge_amount_ee' => array(
            'title' => 'EE. ' . __('Disable courier fee if cart amount is greater or equal than this limit', 'itella-shipping'),
            'class' => 'courier',
            'type' => 'number',
            'custom_attributes' => array(
                'step' => 0.01,
            ),
            'default' => 100
        ),
        'pickup_point_price_fi' => array(
            'title' => 'FI. ' . __('Pickup Point price', 'itella-shipping'),
            'class' => 'pickup-point',
            'type' => 'number',
            'custom_attributes' => array(
                'step' => 0.01,
            ),
            'default' => 2,
        ),
        'courier_price_fi' => array(
            'title' => 'FI. ' . __('Courier price', 'itella-shipping'),
            'class' => 'courier',
            'type' => 'number',
            'default' => 2,
            'custom_attributes' => array(
                'step' => 0.01,
            ),
        ),
        'pickup_point_nocharge_amount_fi' => array(
            'title' => 'FI. ' . __('Disable pickup point fee if cart amount is greater or equal than this limit', 'itella-shipping'),
            'class' => 'pickup-point',
            'type' => 'number',
            'custom_attributes' => array(
                'step' => 0.01,
            ),
            'default' => 100
        ),
        'courier_nocharge_amount_fi' => array(
            'title' => 'FI. ' . __('Disable courier fee if cart amount is greater or equal than this limit', 'itella-shipping'),
            'class' => 'courier',
            'type' => 'number',
            'custom_attributes' => array(
                'step' => 0.01,
            ),
            'default' => 100
        )
    );
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
      $chosen_pickup_point = $this->get_chosen_pickup_point($order->get_shipping_country(), $chosen_pickup_point_id);

      $weight_unit = get_option('woocommerce_weight_unit');

      // packet html select element options
      $packets = array();
      for ($i = 1; $i < 11; $i++) {
        $packets[$i] = strval($i);
      }

      ?>
        <br class="clear"/>
        <h4><?= __('Itella Shipping Options', 'itella-shipping') ?><a href="#" class="edit_address"
                                                                      id="itella-shipping-options">Edit</a></h4>
        <div class="address">
            <p>
                <strong><?= __('Packets(total):', 'itella-shipping') ?></strong> <?= $packet_count ?>
            </p>
            <p>
                <strong><?= __('Weight(' . $weight_unit . '):', 'itella-shipping') ?></strong> <?= $weight ?>
            </p>
            <p><strong><?= __('COD:', 'itella-shipping') ?></strong>
              <?=
              $is_cod ? __('Yes', 'woocommerce') : __('No', 'woocommerce')
              ?>
            </p>
          <?php if ($is_cod): ?>
              <p>
                  <strong><?= __('COD amount(' . $order->get_currency() . '):', 'itella-shipping') ?></strong> <?= $cod_amount ?>
              </p>
          <?php endif; ?>
            <p><strong><?= __('Carrier:', 'itella-shipping') ?></strong>
              <?=
              $is_itella_pp ? __('Pickup Point', 'itella-shipping') :
                  ($is_itella_c ? __('Courier', 'itella-shipping') :
                      __('No Itella Shipping method selected', 'itella-shipping'))
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
              'label' => __('Packets(total):', 'itella-shipping'),
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
              'label' => __('Weight(' . $weight_unit . ')'),
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
              'label' => __('COD amount(' . $order->get_currency() . '):', 'itella-shipping'),
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
              'options' => $this->build_pickup_points_list($order->get_shipping_country()),
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
    $pickup_points = file_get_contents(plugin_dir_url(__FILE__) . '../locations/locations' . $shipping_country_id . '.json');

    return json_decode($pickup_points);
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
      wc_add_notice( __( "You must choose Itella Pickup Point", 'itella-shipping' ), 'error');
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
            throw new ItellaException(__("Failed to save label pdf to: ", 'itella_shipping') . $pdf_path);
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
    $order_ids = $_REQUEST['post'];

    $translation = $this->get_manifest_translation();

    $items = $this->prepare_items_for_manifest($order_ids);

    $manifest = $this->create_manifest($translation, $items);

    $this->update_manifest_generation_date($order_ids, $manifest->timestamp);


    $manifest->printManifest('itella_manifest.pdf');
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
  public function itella_post_shipment_actions()
  {
    $order_ids = $_REQUEST['post'];
    $order_ids = is_array($order_ids) ? $order_ids : array($order_ids);

    foreach ($order_ids as $order_id) {
      $order = wc_get_order($order_id);
      $shipping_parameters = Itella_Manifest::get_shipping_parameters($order_id);
      $shipping_method = $shipping_parameters['itella_shipping_method'];
      $shipment = null;

      // check if itella shipping method
      if ($shipping_method !== 'itella_pp' && $shipping_method !== 'itella_c') {
        $this->add_msg($order_id . ' - ' . __('Shipment is not registered.', 'itella-shipping')
            . "<br>"
            . __('Error: ', 'itella-shipping')
            . __('Not Itella Shipping Method', 'itella-shipping'), 'error');

        wp_safe_redirect(wp_get_referer());
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
        update_post_meta($order_id, '_itella_tracking_url', self::ITELLA_TRACKING_URL . $result->__toString());

        // add notices
        $this->add_msg(
            'Order ' . $order_id . ' - '
            . __('Shipment registered successfully.', 'itella-shipping'), 'success'
        );
        $this->add_msg(
            'Order ' . $order_id . ' - '
            . __('Tracking number: ', 'itella-shipping') . $result, 'info'
        );

        // log order id and tracking number
        file_put_contents(plugin_dir_path(dirname(__FILE__)) . 'var/log/registered_tracks.log',
            "\nOrder ID : " . $order->get_id() . "\n" . 'Tracking number: ' . $result, FILE_APPEND);

      } catch (ItellaException $th) {

        // add error message
        $this->add_msg($order_id . ' - ' . __('Shipment is not registered.', 'itella-shipping')
            . "<br>"
            . __('An error occurred. ', 'itella-shipping')
            . $th->getMessage()
            , 'error');

        // log error
        file_put_contents(plugin_dir_path(dirname(__FILE__)) . 'var/log/errors.log',
            "\n" . date('Y-m-d H:i:s') . ": Exception:\n" . $th->getMessage() . "\n"
            . $th->getTraceAsString(), FILE_APPEND);
      }

      catch (\Exception $e) {

          // add error message
          $this->add_msg($order_id . ' - ' . __('Shipment is not registered.', 'itella-shipping')
              . "<br>"
              . __('An error occurred. ', 'itella-shipping')
              . $e->getMessage()
              , 'error');

          // log error
          file_put_contents(plugin_dir_path(dirname(__FILE__)) . 'var/log/errors.log',
              "\n" . date('Y-m-d H:i:s') . ": Exception:\n" . $e->getMessage() . "\n"
              . $e->getTraceAsString(), FILE_APPEND);
      }
    }

    // return to shipments
    wp_safe_redirect(wp_get_referer());
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
    $shipping_country = wc_get_order($order_id)->get_shipping_country();
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
        ->setCountryCode($order->get_shipping_country())
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

    switch ($this->settings['shop_countrycode']) {
      case 'LV':
        $email = 'smartship.routing.lv@itella.com';
        break;
      case 'EE':
        $email = 'smartship.routing.ee@itella.com';
        break;
      case 'FI':
        $email = 'smartship.routing.fi@itella.com';
        break;
      default:
        $email = 'smartship.routing.lt@itella.com';
    }

    try {
      $caller = new CallCourier($email);
      $result = $caller
          ->setSenderEmail($this->settings['shop_email'])
          ->setSubject(__('E-com order booking', 'itella-shipping'))
          ->setPickUpAddress(array(
              'sender' => $this->settings['shop_email'],
              'address' => $this->settings['shop_address'] . ', '
                  . $this->settings['shop_postcode'] . ',  '
                  . $this->settings['shop_city'] . ', '
                  . $this->settings['shop_countrycode'],
              'pickup_time' => '8:00 - 17:00',
              'contact_phone' => $this->settings['shop_phone'],
          ))
          ->setAttachment($manifest_string, true)
          ->callCourier();

      if ($result) {
        // add notices
        $this->add_msg(__('Email sent to courier', 'itella-shipping')
            . '(' . $email . ')', 'success');
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

      $tracking_code = $this->get_tracking_code($order_id);

      // manifest is only for registered shipments (that have tracking code)
      if ($tracking_code) {
        $prepared_tracking_items[] = array(
            'track_num' => $tracking_code,
            'weight' => !empty($shipping_parameters['weight']) ? $shipping_parameters['weight'] : 0,
            'delivery_address' => $order->get_shipping_first_name() . ' '
                . $order->get_shipping_last_name() . ', '
                . $order->get_shipping_address_1() . ', '
                . $order->get_shipping_postcode() . ' '
                . $order->get_shipping_city() . ', '
                . $order->get_shipping_country()
        );
      }
    }

    return $prepared_tracking_items;
  }

}
