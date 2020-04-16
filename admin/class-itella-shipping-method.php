<?php

use Mijora\Itella\Locations\PickupPoints;

/**
 * The dashboard-specific functionality of the plugin.
 *
 * @package    Itella_Woocommerce
 * @subpackage Itella_Woocommerce/admin
 * @author     Your Name <email@example.com>
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
    $this->method_title = __('Itella Shipping');
    $this->method_description = __('Plugin to use with Itella Shipping methods');

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

    /**
     * This function is provided for demonstration purposes only.
     *
     * An instance of this class should be passed to the run() function
     * defined in Itella_Shipping_Method_Loader as all of the hooks are defined
     * in that particular class.
     *
     * The Itella_Shipping_Method_Loader will then create the relationship
     * between the defined hooks and the functions defined in this
     * class.
     */

    wp_enqueue_style($this->name, plugin_dir_url(__FILE__) . 'css/itella-shipping-admin.css', array(), $this->version, 'all');

  }

  /**
   * Register the JavaScript for the dashboard.
   *
   * @since    1.0.0
   */
  public function enqueue_scripts()
  {

    /**
     * This function is provided for demonstration purposes only.
     *
     * An instance of this class should be passed to the run() function
     * defined in Itella_Shipping_Method_Loader as all of the hooks are defined
     * in that particular class.
     *
     * The Itella_Shipping_Method_Loader will then create the relationship
     * between the defined hooks and the functions defined in this
     * class.
     */

    wp_enqueue_script($this->name, plugin_dir_url(__FILE__) . 'js/itella-shipping-admin.js', array('jquery'), $this->version, TRUE);
    wp_enqueue_script($this->name, plugin_dir_url(__FILE__) . 'js/itella-shipping-edit-orders.js', array('jquery'), $this->version, TRUE);

  }

  /**
   * Init your settings
   *
   * @access public
   * @return void
   */
  public function init()
  {
    // Load the settings API
    $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
    $this->init_settings(); // This is part of the settings API. Loads settings you previously init.

  }

  /**
   * Update locations
   */
  public function update_locations()
  {

    $itella_pickup_points_obj = new PickupPoints('https://locationservice.posti.com/api/2/location');
    $itella_loc_lt = $itella_pickup_points_obj->getLocationsByCountry('lt');
    $itella_loc_lv = $itella_pickup_points_obj->getLocationsByCountry('lv');
    $itella_pickup_points_obj->saveLocationsToJSONFile(plugin_dir_path(dirname(__FILE__)) . 'locations/locationsLT.json', json_encode($itella_loc_lt));
    $itella_pickup_points_obj->saveLocationsToJSONFile(plugin_dir_path(dirname(__FILE__)) . 'locations/locationsLV.json', json_encode($itella_loc_lv));
  }

  /**
   * calculate_shipping function.
   *
   * @access public
   * @param mixed $package
   * @return void
   */
  public function calculate_shipping($package = array())
  {

    global $woocommerce;
    $current_country = $woocommerce->customer->get_shipping_country();
    $cart_amount = $woocommerce->cart->cart_contents_total + $woocommerce->cart->tax_total;
//    var_dump($this->settings);
//    die;

    // add Pickup Point Rate
    if ($this->settings['pickup_point_method'] === 'yes') {
      switch ($current_country) {
        case 'LV':
          $amount = $this->settings['pickup_point_price_lv'];
          if ($cart_amount > floatval($this->settings['pickup_point_nocharge_amount_lv']))
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
        default:
          $amountC = $this->settings['courier_price_lt'];
          if ($cart_amount > floatval($this->settings['courier_nocharge_amount_lt']))
            $amountC = 0.0;
          break;
      }

      $rate = array(
          'id' => 'itella_c',
          'label' => __('Itella courrier', 'itella-shipping'),
          'cost' => $amountC
      );
      $this->add_rate($rate);
    }

  }

  function init_form_fields()
  {
    $this->form_fields = array(
        'enabled' => array(
            'title' => __('Enable', 'itella_shipping'),
            'type' => 'checkbox',
            'description' => __('Enable this shipping.', 'itella_shipping'),
            'default' => 'yes'
        ),
        'api_user_2711' => array(
            'title' => __('API user (Product 2711)', 'itella_shipping'),
            'type' => 'text',
        ),
        'api_pass_2711' => array(
            'title' => __('Api password (Product 2711)', 'itella_shipping'),
            'type' => 'password',
        ),
        'api_user_2317' => array(
            'title' => __('API user (Product 2317)', 'itella_shipping'),
            'type' => 'text',
        ),
        'api_pass_2317' => array(
            'title' => __('Api password (Product 2317)', 'itella_shipping'),
            'type' => 'password',
        ),
        'company' => array(
            'title' => __('Company name', 'itella_shipping'),
            'type' => 'text',
        ),
        'bank_account' => array(
            'title' => __('Bank account', 'itella_shipping'),
            'type' => 'text',
        ),
        'shop_name' => array(
            'title' => __('Shop name', 'itella_shipping'),
            'type' => 'text',
        ),
        'shop_city' => array(
            'title' => __('Shop city', 'itella_shipping'),
            'type' => 'text',
        ),
        'shop_address' => array(
            'title' => __('Shop address', 'itella_shipping'),
            'type' => 'text',
        ),
        'shop_postcode' => array(
            'title' => __('Shop postcode', 'itella_shipping'),
            'type' => 'text',
        ),
        'shop_countrycode' => array(
            'title' => __('Shop country code', 'itella_shipping'),
            'type' => 'text',
        ),
        'shop_phone' => array(
            'title' => __('Shop phone number', 'itella_shipping'),
            'type' => 'text',
        ),
        'pickup_point_method' => array(
            'title' => __('Enable Pickup Point', 'itella_shipping'),
            'class' => 'pickup-point-method',
            'type' => 'checkbox',
            'description' => __('Show pickup point shipping method in checkout.', 'itella_shipping'),
            'default' => 'no'
        ),
        'courier_method' => array(
            'title' => __('Enable Courier', 'itella_shipping'),
            'type' => 'checkbox',
            'class' => 'courier-method',
            'description' => __('Show courier shipping method in checkout.', 'itella_shipping'),
            'default' => 'no'
        ),
        'pickup_point_price_lt' => array(
            'title' => 'LT ' . __('Pickup Point price', 'itella_shipping'),
            'class' => 'pickup-point',
            'type' => 'number',
            'custom_attributes' => array(
                'step' => 0.01,
            ),
            'default' => 2,
        ),
        'courier_price_lt' => array(
            'title' => 'LT ' . __('Courrier price', 'itella_shipping'),
            'class' => 'courier',
            'type' => 'number',
            'default' => 2,
            'custom_attributes' => array(
                'step' => 0.01,
            ),
        ),
        'pickup_point_nocharge_amount_lt' => array(
            'title' => 'LT ' . __('Disable pickup point fee if cart amount is greater or equal than this limit', 'itella_shipping'),
            'class' => 'pickup-point',
            'type' => 'number',
            'custom_attributes' => array(
                'step' => 0.01,
            ),
            'default' => 100
        ),
        'courier_nocharge_amount_lt' => array(
            'title' => 'LT ' . __('Disable courier fee if cart amount is greater or equal than this limit', 'itella_shipping'),
            'class' => 'courier',
            'type' => 'number',
            'custom_attributes' => array(
                'step' => 0.01,
            ),
            'default' => 100
        ),
        'pickup_point_price_lv' => array(
            'title' => 'LV ' . __('Pickup Point price', 'itella_shipping'),
            'class' => 'pickup-point',
            'type' => 'number',
            'custom_attributes' => array(
                'step' => 0.01,
            ),
            'default' => 2,
        ),
        'courier_price_lv' => array(
            'title' => 'LV ' . __('Courrier price', 'itella_shipping'),
            'class' => 'courier',
            'type' => 'number',
            'default' => 2,
            'custom_attributes' => array(
                'step' => 0.01,
            ),
        ),
        'pickup_point_nocharge_amount_lv' => array(
            'title' => 'LV ' . __('Disable pickup point fee if cart amount is greater or equal than this limit', 'itella_shipping'),
            'class' => 'pickup-point',
            'type' => 'number',
            'custom_attributes' => array(
                'step' => 0.01,
            ),
            'default' => 100
        ),
        'courier_nocharge_amount_lv' => array(
            'title' => 'LV ' . __('Disable courier fee if cart amount is greater or equal than this limit', 'itella_shipping'),
            'class' => 'courier',
            'type' => 'number',
            'custom_attributes' => array(
                'step' => 0.01,
            ),
            'default' => 100
        ),
    );
  }

  public function add_shipping_details_to_order($order)
  {
    $itella_method = get_post_meta($order->get_id(), '_itella_method', true);

    if ($itella_method) {
      $is_itella_pp = $itella_method === 'itella_pp';
      $is_itella_c = $itella_method === 'itella_c';

      $is_cod = true;
      $cod_amount = $order->get_total();

      $weight_unit = get_option('woocommerce_weight_unit');
      $currency = $order->get_currency();

      ?>
        <br class="clear"/>
        <h4>Itella Shipping Options <a href="#" class="edit_address">Edit</a></h4>
      <?php

      $packets = [];
      for ($i = 1; $i < 11; $i++) {
        $packets[$i] = strval($i);
      }

      // defaults
      $default_packets_count = '1';
      $default_weight = '1.00';

      // extra services
      $is_oversized = false;
      $call_before_delivery = false;
      $fragile = false;

      ?>
        <div class="address">
            <p><strong><?= __('Packets(total):', 'itella_shipping') ?></strong> <?= $default_packets_count ?></p>
            <p><strong><?= __('Weight(' . $weight_unit . ')', 'itella_shipping') ?></strong> <?= $default_weight ?></p>
            <p><strong><?= __('COD:', 'itella_shipping') ?></strong>
              <?=
              $is_cod ? __('Yes', 'woocommerce') : __('No', 'woocommerce')
              ?>
            </p>
          <?php if ($is_cod): ?>
              <p><strong><?= __('COD amount:', 'itella_shipping') ?></strong> <?= $cod_amount . '(' . $currency . ')' ?>
              </p>
          <?php endif; ?>
            <p><strong><?= __('Carrier:', 'itella_shipping') ?></strong>
              <?=
              $is_itella_pp ? __('Pickup Point', 'itella_shipping') :
                  ($is_itella_c ? __('Courier', 'itella_shipping') :
                      __('No Itella Shipping method selected', 'itella_shipping'))
              ?>
            </p>
            <p><strong><?= __('Extra Services', 'itella_shipping') ?></strong>
              <?php
              if (!$is_oversized && !$call_before_delivery && !$fragile) {
                echo __('No extra services selected', 'itella_shipping');
              } else {
                if ($is_oversized) {
                  echo __('Oversized', 'itella_shipping');
                }
                ?>
                  <br>
                <?php
                if ($call_before_delivery) {
                  echo __('Call before delivery', 'itella_shipping');
                }
                ?>
                  <br>
                <?php
                if ($fragile) {
                  echo __('Fragile', 'itella_shipping');
                }
              }
              ?>
            </p>
        </div>
        <div class="edit_address">
          <?php
          woocommerce_wp_select(array(
              'id' => 'packet_count',
              'label' => __('Packets(total):', 'itella_shipping'),
              'value' => $default_packets_count,
              'options' => $packets,
              'wrapper_class' => 'form-field-wide'
          ));

          woocommerce_wp_text_input(array(
              'id' => 'weight_total',
              'label' => __('Weight(' . $weight_unit . ')'),
              'value' => $default_weight,
              'wrapper_class' => 'form-field-wide'
          ));

          woocommerce_wp_select(array(
              'id' => 'itella_cod_enabled',
              'label' => __('COD:', 'itella_shipping'),
              'value' => $is_cod ? __('Yes', 'woocommerce') : __('No', 'woocommerce'),
              'options' => array(
                  'false' => __('No', 'woocommerce'),
                  'true' => __('Yes', 'woocommerce')
              ),
              'wrapper_class' => 'form-field-wide'
          ));

          if ($is_cod) {
            woocommerce_wp_text_input(array(
                'id' => 'itella_cod_amount',
                'label' => __('COD amount:', 'itella_shipping'),
                'value' => $cod_amount,
                'wrapper_class' => 'form-field-wide'
            ));
          }

          woocommerce_wp_select(array(
              'id' => 'itella_shipping_method',
              'label' => __('Carrier:', 'itella_shipping'),
              'value' => $is_itella_pp ? __('Pickup Point', 'itella_shipping') :
                  ($is_itella_c ? __('Courier', 'itella_shipping') :
                      __('No Itella Shipping method selected', 'itella_shipping')),
              'options' => array(
                  'itella_pp' => __('Pickup Point', 'itella_shipping'),
                  'itella_c' => __('Courier', 'itella_shipping')
              ),
              'wrapper_class' => 'form-field-wide'
          ));

          if ($is_itella_pp) {
            // TODO select pps
          }

          $this->woocommerce_wp_multi_checkbox( array(
              'id'    => 'itella_extra_services',
              'name'  => 'itella_extra_services[]',
              'style' => 'width: 1rem',
              'label' => __('Extra Services', 'itella_shipping'),
              'options' => array(
                  'oversized'   => __('Oversized', 'itella_shipping'),
                  'call_berore_delivery'   => __('Call before delivery', 'itella_shipping'),
                  'fragile'    => __('Fragile', 'itella_shipping')
              ),
              'wrapper_class' => 'form-field-wide'
          ) );

          ?></div>


    <?php }
  }

  public function build_itella_shipping_options_overview()
  {

  }

  // New Multi Checkbox field for woocommerce backend
  function woocommerce_wp_multi_checkbox( $field ) {
    global $thepostid, $post;

    $field['value'] = get_post_meta( $thepostid, $field['id'], true );

    $thepostid              = empty( $thepostid ) ? $post->ID : $thepostid;
    $field['class']         = isset( $field['class'] ) ? $field['class'] : 'select short';
    $field['style']         = isset( $field['style'] ) ? $field['style'] : '';
    $field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
    $field['value']         = isset( $field['value'] ) ? $field['value'] : array();
    $field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
    $field['desc_tip']      = isset( $field['desc_tip'] ) ? $field['desc_tip'] : false;

    echo '<fieldset class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '">
    <legend>' . wp_kses_post( $field['label'] ) . '</legend>';

    if ( ! empty( $field['description'] ) && false !== $field['desc_tip'] ) {
      echo wc_help_tip( $field['description'] );
    }

    echo '<ul class="wc-radios">';

    foreach ( $field['options'] as $key => $value ) {

      echo '<li><label><input
                name="' . esc_attr( $field['name'] ) . '"
                value="' . esc_attr( $key ) . '"
                type="checkbox"
                class="' . esc_attr( $field['class'] ) . '"
                style="' . esc_attr( $field['style'] ) . '"
                ' . ( in_array( $key, $field['value'] = array() ) ? 'checked="checked"' : '' ) . ' /> ' . esc_html( $value ) . '</label>
        </li>';
    }
    echo '</ul>';

    if ( ! empty( $field['description'] ) && false === $field['desc_tip'] ) {
      echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
    }

    echo '</fieldset>';
  }

}
